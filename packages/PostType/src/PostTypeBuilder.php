<?php

namespace WpNext\PostType;

class PostTypeBuilder
{
    protected $postTypes = [];

    public function register($name, $closure)
    {
        $this->postTypes[$name] = $closure;
    }

    public function init()
    {
        foreach ($this->postTypes as $name => $closure) {
            $config = $closure();

            register_post_type($name, $config);
        }
    }
}
