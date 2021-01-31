<?php

namespace WpNext\View;

use Illuminate\View\Component as ViewComponent;

class Component extends ViewComponent
{
    public function render()
    {
        $view = app('view');

        $props = $this->extractPublicProperties();

        return $view->make($this->path, $props);
    }
}
