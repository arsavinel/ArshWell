<?php

namespace Arshwell\Monolith\Module\HTML;

use Arshwell\Monolith\Table\TableSegment;
use Arshwell\Monolith\Table\TableColumn;
use Arshwell\Monolith\Table\TableField;
use Arshwell\Monolith\Text;
use Arshwell\Monolith\Func;
use Arshwell\Monolith\File;
use Arshwell\Monolith\URL;
use Arshwell\Monolith\Web;

final class Piece {

    static function actions (array $breadcrumbs, array $actions = array()): string {
        ob_start(); ?>

            <div class="card border-left-0 border-top-0 border-right-0 rounded-0 w-100 mb-3">
                <div class="card-body py-2">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <ul class="breadcrumb bg-transparent align-items-center p-0 py-1 m-0">
                                <?php
                                foreach ($breadcrumbs as $breadcrumb) { ?>
                                    <li class="breadcrumb-item align-items-center"><?= $breadcrumb ?></li>
                                <?php  } ?>
                            </ul>
                        </div>
                        <div class="col-auto ml-auto">
                            <?php
                            foreach ($actions as $key => $action) { ?>
                                <span class="ml-1">
                                    <?= self::action($key, $action) ?>
                                </span>
                            <?php } ?>
                        </div>
                    </div>
                </div>
            </div>

        <?php
        return ob_get_clean();
    }

    /**
     * (closure|array) $action
    */
    static function action (string $key, $action): string {
        if (!is_string($action) && is_callable($action)) {
            $action = $action($key);
        }

        $action['HTML'] = array_replace_recursive(
            array(
                'icon'      => NULL,
                'text'      => '',
                'href'      => URL::get(),
                'type'      => 'link',
                'class'     => '',
                'disabled'  => false,
                'hidden'    => false,
                'values'    => array()
            ),
            $action['HTML']
        );

        array_walk_recursive($action, function (&$value) use ($key) {
            if (!is_string($value) && is_callable($value)) {
                $value = $value($key);
            }
        });

        $action['HTML']['href'] = URL::get(true, false, $action['HTML']['href']) .'?ctn='. $key;

        return array(
            'Arshwell\Monolith\Module\HTML\Action',
            $action['HTML']['type']
        )($key, $action);
    }

    static function search (array $query, array $fields): string {
        ob_start(); ?>

            <div class="arshmodule-addon-search card h-100">
                <div class="card-body py-3">
                    <?php
                    if (isset($query['search'])) {
                        $query['search'] = array_map(function ($array) {
                            return array_unique($array);
                        }, $query['search']);

                        foreach ($query['search'] as $key => $values) {
                            foreach ($values as $value) { ?>
                                <input type="hidden" name="search[<?= $key ?>][]" value="<?= $value ?>" />
                            <?php }
                        }
                    } ?>

                    <div class="row">
                        <div class="col-sm mb-1 mb-sm-0">
                            <input type="text" class="form-control h-100" placeholder="Search for...">
                        </div>
                        <div class="col">
                            <select class="custom-select h-100">
                                <?php
                                foreach ($fields as $key => $field) {
                                    switch ($field['HTML']['type']) {
                                        case 'text':
                                        case 'textarea':
                                        case 'number': { ?>
                                            <option value="<?= $key ?>">
                                                in <?= $field['HTML']['label'] ?>
                                            </option>
                                            <?php
                                            break;
                                        }
                                    }
                                } ?>
                            </select>
                        </div>
                        <div class="col-auto text-right">
                            <button class="btn btn-danger" title="Search">
                                <i class="fa fa-fw fa-search"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <?php
                if (isset($query['search']) || isset($query['filter'])) { ?>
                    <div class="card-footer">
                        <?php
                        if (!isset($query['search'])) {
                            echo "<i>No search added.</i>";
                        }
                        else {
                            foreach ($query['search'] as $field => $values) {
                                foreach ($values as $value) { ?>
                                    <span class="nowrap mr-3" data-field="<?= $field ?>" data-value="<?= $value ?>">
                                        <b><?= $fields[$field]['HTML']['label'] ?>:</b>
                                        <?= ($value ?: '<span class="badge badge-danger">Gol</span>') ?>
                                        <i type="button" class="fa fa-fw fa-times-circle text-danger"></i>
                                    </span>
                                <?php }
                            }
                        } ?>
                    </div>
                <?php } ?>
            </div>

        <?php
        return ob_get_clean();
    }

    static function filter (array $query, array $fields, array $options = array()): string {
        ob_start(); ?>

        <div class="arshmodule-addon-filter card h-100">
            <div class="card-body py-3">
                <?php
                if (array_intersect(array('select', 'radio'), array_column(array_column($fields, 'HTML'), 'type'))) { ?>
                    <?php
                    if (isset($query['filter'])) {
                        $query['filter'] = array_map(function ($array) {
                            return array_unique($array);
                        }, $query['filter']);

                        foreach ($query['filter'] as $field => $values) {
                            foreach ($values as $value) { ?>
                                <input type="hidden" name="filter[<?= $field ?>][]" value="<?= $value ?>" />
                            <?php }
                        }
                    } ?>

                    <div class="row">
                        <div class="col-sm mb-1 mb-sm-0">
                            <select class="custom-select h-100" title="Filter by">
                                <option selected hidden>Filter by</option>
                                <?php
                                foreach ($fields as $key => $field) {
                                    if (in_array($field['HTML']['type'], array('select', 'radio'))) { ?>
                                        <option value="<?= $key ?>">
                                            <?= $field['HTML']['label'] ?>
                                        </option>
                                    <?php }
                                } ?>
                            </select>
                        </div>
                        <div class="col">
                            <select class="custom-select h-100" disabled></select>
                            <?php
                            foreach ($fields as $key => $field) {
                                if (in_array($field['HTML']['type'], array('select', 'radio'))) { ?>
                                    <select class="custom-select h-100 d-none" data-key="<?= $key ?>">
                                        <?php
                                        $select = ($options[$key] ?? $field['HTML']['values'] ?? array());

                                        if ($select) {
                                            // optgroups with options
                                            if (Func::hasSubarrays($select)) {
                                                foreach ($select as $optgroup_name => $values) { ?>
                                                    <optgroup label="<?= $optgroup_name ?>">
                                                        <?php
                                                        foreach ($values as $index => $value) { ?>
                                                            <option value="<?= $index ?>"><?= $value ?></option>
                                                        <?php } ?>
                                                    </optgroup>
                                                <?php }
                                            }

                                            // simple options
                                            else {
                                                foreach (($options[$key] ?? $field['HTML']['values'] ?? array()) as $index => $value) { ?>
                                                    <option value="<?= $index ?>"><?= $value ?></option>
                                                <?php }
                                            }
                                        } ?>
                                    </select>
                                <?php }
                            } ?>
                        </div>
                        <div class="col-auto text-right">
                            <button type="submit" class="btn btn-danger" title="Filter">
                                <i class="fa fa-fw fa-search"></i>
                            </button>
                        </div>
                    </div>
                <?php } ?>
            </div>
            <?php
            if (isset($query['search']) || isset($query['filter'])) { ?>
                <div class="card-footer">
                    <?php
                    if (!isset($query['filter'])) {
                        echo "<i>No filter added.</i>";
                    }
                    else {
                        foreach ($query['filter'] as $key => $values) {
                            if (!empty($options[$key]) && Func::hasSubarrays($options[$key])) {
                                $options[$key] = Func::arrayFlatten($options[$key], true);
                            }

                            foreach ($values as $value) { ?>
                                <span class="nowrap mr-3" data-field="<?= $key ?>" data-value="<?= $value ?>">
                                    <b><?= $fields[$key]['HTML']['label'] ?>:</b>
                                    <?= (($options[$key][$value] ?? $fields[$key]['HTML']['values'][$value]) ?: '<span class="badge badge-danger">Gol</span>') ?>
                                    <i type="button" class="fa fa-fw fa-times-circle text-danger"></i>
                                </span>
                            <?php }
                        }
                    } ?>
                </div>
            <?php } ?>
        </div>

        <?php
        return ob_get_clean();
    }

    static function columns (array $fields, array $visible = array()): string {
        ob_start(); ?>

            <div class="arshmodule-addon-columns">
                <div class="dropdown btn pl-0 pr-0 pb-0 ml-0 mr-0 mb-0">
                    <button class="btn btn-sm <?= (count($fields) == count($visible) ? 'btn-dark' : 'btn-secondary') ?> dropdown-toggle"
                    type="button" title="Visible fields" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" data-offset="0,5">
                        Fields
                    </button>
                    <div class="dropdown-menu dropdown-menu-right">
                        <?php
                        foreach ($fields as $key => $field) { ?>
                            <div class="dropdown-item">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" name="columns[]" <?= (empty($visible) || in_array($key, $visible) ? 'checked' : '') ?> value="<?= $key ?>" id="arshmodule-addon-column-<?= $key ?>">
                                    <label class="form-check-label w-100" for="arshmodule-addon-column-<?= $key ?>">
                                        <?= $field['HTML']['label'] ?>
                                    </label>
                                </div>
                            </div>
                        <?php } ?>
                        <div class="dropdown-divider"></div>
                        <div class="dropdown-item">
                            <button type="submit" class="btn btn-sm btn-primary">
                                Display
                            </button>
                        </div>
                    </div>
                </div>
            </div>

        <?php
        return ob_get_clean();
    }

    static function languages (array $languages, string $lg, bool $submit = true): string {
        ob_start(); ?>

            <div class="arshmodule-addon-language <?= ($submit ? 'submit' : '') ?>">
                <?php
                if ($submit) { ?>
                    <input type="hidden" name="lg" value="<?= $lg ?>" />
                <?php } ?>

                <div class="dropdown btn px-0 pb-0 mx-0 mb-0">
                    <button class="btn btn-sm dropdown-toggle text-light" type="button" data-lg="<?= $lg ?>" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" data-offset="0,5">
                        <?= strtoupper($lg) ?>
                    </button>
                    <div class="dropdown-menu dropdown-menu-right py-0">
                        <?php
                        foreach ($languages as $i => $language) { ?>
                            <button class="dropdown-item btn-sm text-light" type="button" data-lg="<?= $language ?>">
                                <?= strtoupper($language) ?>
                            </button>
                        <?php } ?>
                    </div>
                </div>
            </div>

        <?php
        return ob_get_clean();
    }

    static function thead (array $query, array $HTMLs, bool $show_id_table = false): string {
        ob_start(); ?>

            <div class="arshmodule-html arshmodule-html-piece arshmodule-html-piece-thead">
                <div class="table-responsive">
                    <?php
                    if (!empty($query['sort'])) {
                        foreach ($query['sort'] as $key => $value) { ?>
                            <input type="hidden" name="sort[<?= $key ?>]" value="<?= $value ?>" />
                        <?php }
                    } ?>
                    <table class="table table-striped mb-1">
                        <thead>
                            <tr>
                                <?php
                                if ($show_id_table) { ?>
                                    <th class="th-id-table">ID</th>
                                <?php }

                                foreach ($query['columns'] as $key) {
                                    // check if it doesn't exist anymore
                                    if (!empty($HTMLs[$key]['type'])) { ?>
                                        <td>
                                            <?php
                                            switch ($HTMLs[$key]['type']) {
                                                case 'image':
                                                case 'images':
                                                case 'doc':
                                                case 'docs':
                                                case 'icon': {
                                                    echo $HTMLs[$key]['label'];
                                                    break;
                                                }
                                                default: { ?>
                                                    <span type="button" data-key="<?= $key ?>" title="Sort"
                                                    data-sort="<?= (!isset($query['sort'][$key]) || $query['sort'][$key] == 'd' ? 'a' : 'd') ?>">
                                                        <?= $HTMLs[$key]['label'] ?>
                                                        <i class="fa fa-fw fa-sort"></i>
                                                    </span>

                                                    <?php
                                                    if (isset($query['sort'][$key])) { ?>
                                                        <i type="button" class="fa fa-fw fa-times-circle text-danger" data-key="<?= $key ?>"></i>

                                                        <?php
                                                        if ($query['sort'][$key] == 'a') { ?>
                                                            <i class="fa fa-fw fa-sort-up text-danger"></i>
                                                        <?php }
                                                        else if ($query['sort'][$key] == 'd') { ?>
                                                            <i class="fa fa-fw fa-sort-down text-danger"></i>
                                                        <?php }
                                                    }
                                                    break;
                                                }
                                            } ?>
                                        </td>
                                    <?php }
                                } ?>
                                <td class="text-right border-top-0">
                                    Actions
                                </td>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>

        <?php
        return ob_get_clean();
    }

    static function tbody (array $query, array $data, array $HTMLs, array $features, bool $show_id_table = false): string {
        ob_start(); ?>
            <div class="arshmodule-html arshmodule-html-piece arshmodule-html-piece-tbody">
                <div class="table-responsive">
                    <table class="table table-striped mb-1">
                        <tbody>
                            <?php
                            foreach ($data as $id_table => $row) { ?>
                                <tr>
                                    <?php
                                    if ($show_id_table) { ?>
                                        <th class="th-id-table"><?= $id_table ?></th>
                                    <?php }

                                    foreach ($query['columns'] as $key) {
                                        if (isset($HTMLs[$key])) {
                                            $HTML = $HTMLs[$key];

                                            $lg = NULL;
                                            $value = $row[$key];
                                            $suptitle = NULL;

                                            if (is_object($value)) {
                                                $lg = ($row[$key])->isTranslated() ? ($query['lg'] ?? NULL) : NULL;

                                                switch (get_class($value)) {
                                                    case TableField::class:
                                                    case TableColumn::class: {
                                                        if (property_exists($value, 'suptitle')) {
                                                            $suptitle = $value->suptitle;
                                                        }
                                                        $value = $value->value($lg);
                                                    }
                                                }
                                            }

                                            array_walk_recursive($HTML, function (&$h) use ($value) {
                                                if (!is_string($h) && is_callable($h)) {
                                                    $h = $h($value);
                                                }
                                            }); ?>

                                            <td>
                                                <?php
                                                if ($suptitle) { ?>
                                                    <div class="lh-12"><small class="lh-12"><small class="text-muted lh-12">
                                                        <?= $suptitle ?>
                                                    </small></small></div>
                                                <?php } ?>

                                                <?php
                                                switch ($HTML['type']) {
                                                    case 'image': {
                                                        if ($value && $value->urls()) {
                                                            $smallest = $value->smallest($lg);
                                                            $biggest  = $value->biggest($lg); ?>

                                                            <a href="<?= $biggest ?>"
                                                            data-caption="<?= basename($biggest) ?>" data-fancybox="<?= $id_table ?>-<?= $key ?>"
                                                            data-thumb="<?= $smallest ?>"
                                                            data-protect="true">
                                                                <div class="arshmodule-table-image">
                                                                    <img src="<?= $smallest ?>" />
                                                                </div>
                                                            </a>
                                                        <?php }
                                                        break;
                                                    }
                                                    case 'images': {
                                                        if ($value && $value->urls()) {
                                                            $smallest = $value->smallest($lg);
                                                            $biggest  = $value->biggest($lg); ?>

                                                            <a href="<?= $biggest[0] ?>"
                                                            data-caption="<?= basename($biggest[0]) ?>" data-fancybox="<?= $id_table ?>-<?= $key ?>"
                                                            data-thumb="<?= $smallest[0] ?>"
                                                            data-protect="true">
                                                                <div class="arshmodule-table-image">
                                                                    <img src="<?= $smallest[0] ?>" />
                                                                </div>
                                                            </a>

                                                            <?php
                                                            for ($i=1; $i<count($smallest); $i++) { ?>
                                                                <a href="<?= $biggest[$i] ?>"
                                                                data-caption="<?= basename($biggest[$i]) ?>" data-fancybox="<?= $id_table ?>-<?= $key ?>"
                                                                data-thumb="<?= $smallest[$i] ?>"
                                                                data-protect="true"
                                                                class="d-none">
                                                                    <img src="<?= $smallest[$i] ?>" />
                                                                </a>
                                                            <?php }
                                                        }
                                                        break;
                                                    }
                                                    case 'doc': {
                                                        if ($value->url()) { ?>
                                                            <a href="<?= $value->url() ?>" target="_blank"
                                                            class="btn badge btn-outline-info px-2" title="<?= basename($value->url()) ?>">
                                                                <?= strtoupper(File::extension($value->url())) ?>
                                                            </a>
                                                        <?php }
                                                        break;
                                                    }
                                                    case 'video': {
                                                        if ($value->url()) { ?>
                                                            <a href="<?= $value->url() ?>" target="_blank">
                                                                <video preload="metadata">
                                                                    <source src="<?= $value->url() ?>" />
                                                                    Your browser does not support HTML5 video.
                                                                </video>
                                                            </a>
                                                        <?php }
                                                        break;
                                                    }
                                                    case 'icon': { ?>
                                                        <i class="<?= $value ?> fa-fw d-block"></i>
                                                        <?php
                                                        break;
                                                    }
                                                    case 'date': {
                                                        echo ($value ? date('d-m-Y', $value) : '');
                                                        break;
                                                    }
                                                    case 'checkbox': {
                                                        echo ($value ? 'Da' : 'Nu');
                                                        break;
                                                    }
                                                    case 'select': {
                                                            if ($value) {
                                                                if (!empty($HTML['multiple'])) {
                                                                    echo implode(', ', $value);
                                                                } else {
                                                                    echo ($HTML['values'][$value] ?? $value);
                                                                }
                                                        }
                                                        break;
                                                    }
                                                    case 'textarea': { ?>
                                                        <span class="text">
                                                            <small>
                                                                <?php
                                                                if (isset($HTML['preview'])) {
                                                                    echo $HTML['preview'];
                                                                }
                                                                else {
                                                                    echo ($value ? Text::chars(Text::removeAllTags($value), 150) : '');
                                                                } ?>
                                                            </small>
                                                        </span>
                                                        <?php
                                                        break;
                                                    }
                                                    default: { ?>
                                                        <span class="text">
                                                            <?php
                                                            if (isset($HTML['preview'])) {
                                                                echo $HTML['preview'];
                                                            }
                                                            else {
                                                                echo ($value ? Text::chars(Text::removeAllTags($value), 150) : '');
                                                            } ?>
                                                        </span>
                                                        <?php
                                                        break;
                                                    }
                                                } ?>
                                            </td>
                                        <?php }
                                    } ?>
                                    <td class="arshmodule-html-features align-middle text-right nowrap">
                                        <?= self::features($features, $id_table) ?: '-' ?>
                                    </td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                    <?php
                    if (empty($data)) { ?>
                        <small class="text-muted">No record</small>
                    <?php } ?>
                </div>
            </div>

        <?php
        return ob_get_clean();
    }

    static function features (array $features, int $id_table): string {
        ob_start();

            foreach ($features as $key => $feature) {
                echo self::feature($key, $feature, $id_table);
            }

        return ob_get_clean();
    }

    /**
     * (closure|array) $feature
    */
    static function feature (string $key, $feature, int $id_table): string {
        if (!is_string($feature) && is_callable($feature)) {
            $feature = $feature($key, $id_table);
        }

        $feature['HTML'] = array_replace_recursive(
            array(
                'icon'      => NULL,
                'text'      => '',
                'href'      => URL::get(true, false), // not getting query for avoiding conflicts
                'type'      => 'link',
                'class'     => '',
                'disabled'  => false,
                'hidden'    => false,
                'values'    => array()
            ),
            $feature['HTML']
        );

        array_walk_recursive($feature, function (&$value) use ($key, $id_table) {
            if (!is_string($value) && is_callable($value)) {
                $value = $value($key, $id_table);
            }
        });

        foreach ($feature as $category => $attributes) {
            foreach ($attributes as $attr => $value) {
                $feature[$category][$attr] = ("Arshwell\Monolith\Module\Syntax\Frontend\Feature\\{$category}")::{$attr}($value);
            }
        }

        $query = parse_url($feature['HTML']['href'], PHP_URL_QUERY);
        $feature['HTML']['href'] = URL::get(true, false, $feature['HTML']['href']) .'?'.($query ? $query.'&' : '').'ftr='. $key .'&id=' . $id_table;

        return array(
            'Arshwell\Monolith\Module\HTML\Feature',
            $feature['HTML']['type']
        )($key, $feature, $id_table);
    }

    static function fields (string $table, array $fields, array $data = NULL, array $translated = array()): string {
        ob_start(); ?>

            <div class="row">
                <?php
                foreach ($fields as $key => $field) {
                    echo self::field(
                        $key,
                        $field,
                        $data[$key] ?? NULL, // TableFile | TableField | TableColumn | NULL
                        (in_array($key, $translated) ? (($table)::TRANSLATOR)::LANGUAGES : NULL)
                    );
                } ?>
            </div>

        <?php
        return ob_get_clean();
    }

    static function field (string $key, array $field, TableSegment $segment = NULL, array $languages = NULL): string
    {
        // run $field if it is a closure
        if (!is_string($field) && is_callable($field)) {
            $field = $field(
                $segment ? $segment->key() : NULL,
                $segment ? $segment->id() : NULL,
                $segment ? $segment->class() : NULL
            );
        }

        $field['LAYOUT'] = array_replace_recursive(
            array(
                'rowColumns' => array(
                    'xs' => 12,
                ),
                'isSavingOption' => false,
            ),
            $field['LAYOUT'] ?? []
        );

        $field['HTML'] = array_replace_recursive(
            array(
                'icon'          => NULL,
                'label'         => NULL,
                'type'          => 'text',
                'notes'         => array(),
                'class'         => '',
                'disabled'      => false,
                'readonly'      => false,
                'hidden'        => false,
                'checked'       => false,
                'placeholder'   => '',
                'multiple'      => false,
                'value'         => NULL,
                'values'        => array(),
                'overwrite'     => true
            ),
            $field['HTML']
        );

        // run subkeys which are closures
        array_walk_recursive($field, function (&$value) use ($segment) {
            if (!is_string($value) && is_callable($value)) {
                $value = $value(
                    $segment ? $segment->key() : NULL,
                    $segment ? $segment->id() : NULL,
                    $segment ? $segment->class() : NULL
                );
            }
        });

        if (!$languages) {
            $languages = array(NULL);
        }

        $bt_row_columns = implode(' ', array_map(function (string $resolution, int $cols) {
            if ($resolution == 'xs') {
                return "col-{$cols}";
            }

            return "col-{$resolution}-{$cols}";
        }, array_keys($field['LAYOUT']['rowColumns']), $field['LAYOUT']['rowColumns']));

        ob_start();
            foreach ($languages as $i => $lg) {
                if (empty($field['HTML']['id'])) {
                    $field['HTML']['id'] = ("data-".Text::slug($key));
                } ?>

                <div class="<?= $bt_row_columns ?> margin-1st-0"
                data-key="<?= $field['HTML']['id'] ?>"
                <?= (count($languages) > 1 ? 'data-lg="'.$lg .'"' : '') ?>
                <?= ($field['HTML']['hidden'] || $i > 0 ? 'style="display: none;"' : '') ?>>

                    <?php
                    if ($field['HTML']['icon'] || $field['HTML']['label']) { ?>
                        <!-- label -->
                        <label class="text-muted" <?= ($field['HTML']['label'] ? 'title="'.$field['HTML']['label'].'"' : '') ?>
                        for="<?= $field['HTML']['id'] ?><?= $lg ? "-$lg" : '' ?>">
                            <?php
                            if ($field['HTML']['icon']) {
                                switch ($field['HTML']['icon']['style'] ?? NULL) {
                                    case NULL:
                                    case 'solid': {
                                        $fa_class = 'fas';
                                        break;
                                    }
                                    case 'regular': {
                                        $fa_class = 'far';
                                        break;
                                    }
                                    case 'brand': {
                                        $fa_class = 'fab';
                                        break;
                                    }
                                } ?>
                                <i class="<?= $fa_class ?> fa-fw fa-<?= $field['HTML']['icon']['name'] ?? $field['HTML']['icon'] ?>"></i>
                            <?php }
                            if ($field['HTML']['label']) {
                                echo $field['HTML']['label'];
                            }
                            if (!empty($field['HTML']['required'])) { ?>
                                <span class="text-danger">*</span>
                            <?php } ?>
                        </label>
                    <?php } ?>

                    <!-- field -->
                    <div class="pb-sm-2">
                        <?php
                        if ($segment && (!isset($field['HTML']['value']) || $field['HTML']['overwrite'])) {
                            switch ($field['HTML']['type']) {
                                case 'doc':
                                case 'docs':
                                case 'video':
                                case 'image':
                                case 'images': {
                                    $field['HTML']['value'] = $segment;
                                    break;
                                }
                                case 'select': {
                                    if (empty($field['HTML']['multiple'])) {
                                        $field['HTML']['value'] = (array)$segment->value($lg);
                                    }
                                    else {
                                        $field['HTML']['value'] = array_keys($segment->value($lg));
                                    }
                                    break;
                                }
                                case 'checkbox': {
                                    $field['HTML']['checked'] = ($field['HTML']['value'] == $segment->value($lg));
                                    break;
                                }
                                default: {
                                    $field['HTML']['value'] = $segment->value($lg);
                                    break;
                                }
                            }
                        }

                        if (empty($field['HTML']['name'])) {
                            $field['HTML']['name'] = "data[$key]";
                        }

                        echo array(
                            'Arshwell\Monolith\Module\HTML\Field',
                            $field['HTML']['type']
                        )($field, $lg); ?>
                    </div>
                </div>
            <?php }

        return ob_get_clean();
    }

    static function saver (array $afters, array $savingOptions, bool $preservation = false): string {
        ob_start(); ?>

            <div class="arshmodule-html asrhmodule-html-piece arshmodule-html-piece-saver">
                <div class="card mb-3">
                    <h6 class="card-header">Save</h6>
                    <div class="card-body pt-3">
                        <?php
                        if ($afters) { ?>
                            <small>After saving:</small>
                        <?php } ?>
                        <div class="row align-items-center">
                            <?php
                            if ($afters) { ?>
                                <div class="col-sm-auto col-lg-12">
                                    <select class="custom-select" name="after" title="After saving...">
                                        <?php
                                        foreach (array_unique($afters) as $key) {
                                            if (!is_int($key)) {
                                                switch ($key) {
                                                    case 'select': { ?>
                                                        <option value="<?= $key ?>">Go to table view</option>
                                                        <?php
                                                        break;
                                                    }
                                                    case 'update': { ?>
                                                        <option value="<?= $key ?>" selected>After adding, edit this record</option>
                                                        <?php
                                                        break;
                                                    }
                                                    case 'insert': { ?>
                                                        <option value="<?= $key ?>">Add a new record</option>
                                                        <?php
                                                        break;
                                                    }
                                                }
                                            }
                                        } ?>
                                    </select>
                                </div>
                            <?php } ?>
                            <div class="col-12">
                                <?php
                                if ($savingOptions)  {
                                    echo Piece::fields(
                                        '',
                                        $savingOptions
                                    );
                                } ?>
                            </div>
                            <?php
                            if ($preservation) { ?>
                                <div class="col-sm-auto my-1 col-lg-12 mr-auto">
                                    <div class="custom-control custom-checkbox">
                                        <input
                                        type="checkbox"
                                        class="custom-control-input"
                                        id="arshmodule-form-preservation"
                                        name="preservation"
                                        value="1"
                                        form-valid-update="false"
                                        />
                                        <label class="custom-control-label d-flex" style="padding-top: 2px;" for="arshmodule-form-preservation">
                                            Keep fields after saving
                                        </label>
                                    </div>
                                </div>
                            <?php } ?>
                            <div class="col-sm-auto my-1 col-lg-12 ml-auto text-right">
                                <input type="submit" class="btn btn-sm" value="Save now" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        <?php
        return ob_get_clean();
    }

    static function dialog (): string {
        ob_start(); ?>

            <div class="modal fade arshmodule-html arshmodule-html-piece arshmodule-html-piece-dialog" tabindex="-1">
                <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
                    <div class="modal-content border-0 bg-dark text-light">
                        <div class="modal-header border-secondary">
                            <h6 class="modal-title"></h6>
                            <button type="button" class="close text-light" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="arshmodule-modal-info"></div>
                            <div class="arshmodule-modal-errors">
                                <small>Solve the mentions:</small>
                                <ul class="list-group list-group-flush mt-1 text-break">
                                    <li class="list-group-item list-group-item-warning d-none"></li>
                                </ul>
                            </div>
                            <div class="arshmodule-modal-bug">
                                Some unexpected errors occurred.<br>Try the following steps in order:
                                <ul class="list-group mt-1">
                                    <li class="media d-flex list-group-item list-group-item-danger">
                                        1.
                                        <div class="media-body ml-1">
                                            <b>Reload the page</b> and fill in again. It worked?
                                        </div>
                                    </li>
                                    <li class="media d-flex list-group-item list-group-item-danger">
                                        2.
                                        <div class="media-body ml-1">
                                            Are there chances that someone else is editing the same content right now?
                                        </div>
                                    </li>
                                    <li class="media d-flex list-group-item list-group-item-danger">
                                        3.
                                        <div class="media-body ml-1">
                                            <u>Still not working?</u> Notify the website developer.
                                        </div>
                                    </li>
                                </ul>
                            </div>
                        </div>
                        <div class="modal-footer border-secondary text-light">
                            <small><small>Warnings in these languages:</small> <span class="arshmodule-modal-languages"></span></small>
                        </div>
                    </div>
                </div>
            </div>

        <?php
        return ob_get_clean();
    }

    static function pagination (array $config): string {
        $links = call_user_func(function () use ($config): array {
            $config['icons'] = array(
                'first' => '<i class="fa fa-'. $config['icons']['first'] . '"></i>',
                'left'  => '<i class="fa fa-'. $config['icons']['left'] . '"></i>',
                'right' => '<i class="fa fa-'. $config['icons']['right'] . '"></i>',
                'last'  => '<i class="fa fa-'. $config['icons']['last'] . '"></i>'
            );

            $page = Web::page();
            $nr_of_pages = ceil($config['count'] / $config['limit']); // round up

            $links = array();

            if ($config['count'] > $config['limit']) {
                $range = function (int $nr_of_links) use ($page, $nr_of_pages) {
                    $nr_of_links = min($nr_of_links, $nr_of_pages);

                    $a = 1;
                    $z = $nr_of_links;
                    $ceil   = ceil($nr_of_links / 2);  // round up
                    $floor  = floor($nr_of_links / 2); // round down

                    if ($page > $ceil) {
                        $a = $page - $floor;
                        $z = $page + $floor - 1;
                    }
                    if ($page > ($nr_of_pages - $ceil)) {
                        $a = max(1, $nr_of_pages - $nr_of_links);
                        $z = $nr_of_pages;
                    }

                    return array($a, $z);
                };

                $ranges = array();
                foreach ($config['buttons'] as $resolution => $max) {
                    $r = $range($max);
                    foreach (range($r[0], $r[1]) as $v) {
                        $ranges[$v][] = $resolution;
                    }
                }
                ksort($ranges);

                if ($page > 6) {
                    $links[] = array(
                        'url'       => Web::url(Web::key(), Web::params(), Web::language(), 1, $_GET),
                        'title'     => $config['icons']['first'],
                        'active'    => false
                    );
                }
                if ($page > 1) {
                    $links[] = array(
                        'url'       => Web::url(Web::key(), Web::params(), Web::language(), $page - 1, $_GET),
                        'title'     => $config['icons']['left'],
                        'active'    => false
                    );
                }

                foreach ($ranges as $p => $resolutions) {
                    $links[] = array(
                        'url'       => Web::url(Web::key(), Web::params(), Web::language(), $p, $_GET),
                        'title'     => $p,
                        'active'    => ($page == $p),
                        'class'     => str_replace('-xs', '', 'd-none d-'. implode('-block d-', $resolutions) .'-block')
                    );
                }

                if ($page < $nr_of_pages) {
                    $links[] = array(
                        'url'       => Web::url(Web::key(), Web::params(), Web::language(), $page + 1, $_GET),
                        'title'     => $config['icons']['right'],
                        'active'    => false
                    );
                }
                if ($page < ($nr_of_pages - 6)) {
                    $links[] = array(
                        'url'       => Web::url(Web::key(), Web::params(), Web::language(), $nr_of_pages, $_GET),
                        'title'     => $config['icons']['last'],
                        'active'    => false
                    );
                }
            }

            return $links;
        });

        $config_hash = password_hash(serialize($config), PASSWORD_DEFAULT);

        ob_start(); ?>

            <div class="arshmodule-piece-pagination">
                <div class="row align-items-center">

                    <!-- total number of records -->
                    <div class="col-auto">
                        <small"><?= $config['count'] ?> <?= $config['text'] ?></small>
                    </div>

                    <!-- records per page -->
                    <div class="col-auto">
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <select name="limit" class="custom-select w-auto" id="limit-<?= $config_hash ?>">
                                    <?php
                                    $options = array_unique(array(10, 20, 30, 40, 50, 60, $config['limit']));
                                    sort($options);

                                    foreach ($options as $nr) { ?>
                                        <option value="<?= $nr ?>" <?= $nr == $config['limit'] ? 'selected' : '' ?>>
                                            <?= $nr ?>
                                        </option>
                                    <?php } ?>
                                </select>
                            </div>
                            <div class="input-group-append">
                                <label for="limit-<?= $config_hash ?>" class="input-group-text m-0">
                                    per page
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- pagination - every page -->
                    <div class="col-auto margin-2nd-2nd">
                        <ul class="pagination justify-content-center">
                            <?php
                            foreach ($links as $link) { ?>
                                <li class="page-item <?= ($link['active'] ? 'active' : '') ?> <?= ($link['class'] ?? '') ?>">
                                    <?php
                                    if ($link['active']) { ?>
                                        <span class="page-link">
                                            <?= $link['title'] ?>
                                            <span class="sr-only">(current)</span>
                                        </span>
                                    <?php }
                                    else { ?>
                                        <a href="<?= $link['url'] ?>" class="page-link">
                                            <?= $link['title'] ?>
                                        </a>
                                    <?php } ?>
                                </li>
                            <?php } ?>
                        </ul>
                    </div>
                </div>
            </div>

        <?php
        return ob_get_clean();
    }
}
