<li class="nav-item">
    <a class="nav-link <?= (in_array(Arshwell\Monolith\Session::panel('box.tab.info'), [NULL, 'route']) ? 'active' : '') ?>" data-toggle="tab" href="#info-route">
        About this route
    </a>
</li>
<li class="nav-item">
    <a class="nav-link <?= (Arshwell\Monolith\Session::panel('box.tab.info') == 'site' ? 'active' : '') ?>" data-toggle="tab" href="#info-site">
        About website
    </a>
</li>
