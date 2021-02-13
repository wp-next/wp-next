<?php

namespace WpNext\PostType;

class PostType
{
    public $name;

    protected $config;

    protected $closure;

    public function __construct($name, $closure)
    {
        $this->name = $name;

        $this->closure = $closure;
    }

    public function create()
    {
        $this->config = ($this->closure)();

        if ($this->hasArchive()) {
            $this->setArchivePage();
        }

        register_post_type($this->name, $this->config);
    }

    public function hasArchive()
    {
        return ! empty($this->config['has_archive']);
    }

    public function isSortAble()
    {
        return ! empty($this->config['sortable']);
    }

    public function setArchivePage()
    {
        $archiveRoute = ArchivePage::getRoute($this->name);

        if (empty($archiveRoute)) {
            return;
        }

        $archiveRoute = trim(str_replace(home_url(), '', $archiveRoute), '/');

        if (! $archiveRoute) {
            return;
        }

        $this->config['has_archive'] = true;

        $this->config['rewrite'] = [
            'slug' => $archiveRoute,
            'with_front' => false,
        ];
    }
}
