<?php

namespace WpNext\PostType;

class ArchivePage
{
    public $postType;
    public $pageId;

    public function __construct($postType)
    {
        $this->postType = $postType;

        $this->pageId = self::getPageId($postType->name);
    }

    public static function getPostType($pageId)
    {
        if (! self::isPublic($pageId)) {
            return;
        }

        $postType = array_search($pageId, self::config());

        if (! $postType) {
            return;
        }

        return get_post_type_object($postType);
    }

    public static function config()
    {
        $config = get_option('custom_archive_pages');

        if (is_array($config)) {
            return $config;
        }

        return [];
    }

    public static function getRoute($slug)
    {
        $pageId = self::getPageId($slug);

        if (empty($pageId)) {
            return;
        }

        if (! self::isPublic($pageId)) {
            return;
        }

        return get_permalink($pageId);
    }

    public static function isPublic($pageId)
    {
        return in_array(get_post_status($pageId), ['publish']);
    }

    public static function getPageId($slug = null)
    {
        if (! $slug) {
            if (is_post_type_archive()) {
                $slug = get_query_var('post_type');
            } elseif (is_singular()) {
                $slug = get_post_type();
            } elseif (is_tax()) {
                $taxonomy = get_taxonomy(get_query_var('taxonomy'));
                $slug = (count($taxonomy->object_type) === 1) ? $taxonomy->object_type[0] : null;
                $slug = apply_filters('post_type_archive_pages/taxonomy_post_type', $slug, $taxonomy->name);
            } else {
                return;
            }
        }

        $config = self::config();

        return $config[$slug] ?? null;
    }

    public function getFieldName()
    {
        return "custom_archive_pages[{$this->postType->name}]";
    }
}
