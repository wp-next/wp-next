<?php

namespace WpNext\PostType;

class PostType
{
    public $name;

    protected $config = [
        'labels' => [
            'name' => '',
            'singular_name' => '',
        ],
        'public' => false,
        'has_archive' => false,
        'show_ui' => true,
        'show_in_menu' => true,
        'show_in_rest' => false,
        'supports' => ['title'],
        'sortable' => false,
    ];

    protected $settings;

    public function __construct($name, $settings)
    {
        $this->name = $name;

        $this->settings = $settings;
    }

    public function create()
    {
        $this->config = array_merge($this->config, $this->settings);

        $this->translateLabels();

        if ($this->hasArchive()) {
            $this->setArchivePage();
        }

        register_post_type($this->name, $this->config);
    }

    public function translateLabels()
    {
        $this->config['labels']['name'] = __($this->config['labels']['name'], TEXT_DOMAIN ?? null);
        $this->config['labels']['singular_name'] = __($this->config['labels']['singular_name'], TEXT_DOMAIN ?? null);
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
        $this->config['public'] = true;

        $this->config['rewrite'] = [
            'slug' => $archiveRoute,
            'with_front' => false,
        ];
    }
}
