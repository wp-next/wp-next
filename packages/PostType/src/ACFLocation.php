<?php

namespace WpNext\PostType;

class ACFLocation
{
    protected $postTypes;

    public function __construct($postTypes)
    {
        $this->postTypes = $postTypes;

        add_filter('acf/location/rule_values/page_type', [$this, 'addPageType']);
        add_filter('acf/location/rule_match/page_type', [$this, 'matchPageType'], 10, 3);
    }

    public function addPageType($choices)
    {
        $archivePageText = __('Archive Page', 'wp_lib');

        $choices['archive'] = $archivePageText;

        foreach ($this->postTypes as $archivePage) {
            $choices['archive_'.$archivePage->postType->name] = $archivePageText.': '.$archivePage->postType->label;
        }

        return $choices;
    }

    public function matchPageType($match, $rule, $options)
    {
        if (! isset($options['post_id'])) {
            return $match;
        }

        $archiveType = ArchivePage::getPostType($options['post_id']);

        if ($rule['value'] == 'archive') {
            if ($rule['operator'] == '==') {
                $match = ($archiveType);
            } elseif ($rule['operator'] == '!=') {
                $match = ! ($archiveType);
            }
        } elseif ($archiveType && strpos($rule['value'], 'archive_') !== false) {
            $postType = str_replace('archive_', '', $rule['value']);

            if ($rule['operator'] == '==') {
                $match = ($archiveType->name == $postType);
            } elseif ($rule['operator'] == '!=') {
                $match = ($archiveType->name != $postType);
            }
        }

        return $match;
    }
}
