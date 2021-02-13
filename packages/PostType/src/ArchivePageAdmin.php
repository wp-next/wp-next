<?php

namespace WpNext\PostType;

class ArchivePageAdmin
{
    protected $postTypes;

    public function __construct($postTypes = [])
    {
        $this->postTypes = collect($postTypes)
            ->filter(function ($postType) {
                return $postType->hasArchive();
            })
            ->map(function ($postType) {
                return new ArchivePage(get_post_type_object($postType->name));
            });

        new ACFLocation($this->postTypes);

        add_action('admin_init', [$this, 'initSettingsFields']);

        add_action('theme_page_templates', [$this, 'hidePageTemplates'], 50, 3);
        add_filter('display_post_states', [$this, 'addDisplayPostStates'], 10, 2);
    }

    public function initSettingsFields()
    {
        register_setting(
            'reading',
            'custom_archive_pages'
        );

        if ($this->postTypes->count() > 0) {
            add_settings_field(
                'archive-pages',
                __('Archive Pages', 'wp_lib'),
                [$this, 'renderFields'],
                'reading'
            );
        }
    }

    public function renderFields()
    {
        echo '<fieldset>';

        foreach ($this->postTypes as $archivePage) {
            $this->renderField($archivePage);
        }

        echo '</fieldset>';
    }

    public function renderField(ArchivePage $archivePage)
    {
        echo '<label for="'.$archivePage->getFieldName().'">';

        printf(
            $archivePage->postType->label.': %s',
            wp_dropdown_pages([
                    'name'              => $archivePage->getFieldName(),
                    'echo'              => 0,
                    'show_option_none'  => __('&mdash; Select &mdash;'),
                    'option_none_value' => '0',
                    'selected'          => $archivePage->pageId,
            ])
        );

        echo '</label><br>';
    }

    public function addDisplayPostStates($postStates, $post)
    {
        $postType = ArchivePage::getPostType($post->ID);

        if (! $postType) {
            return $postStates;
        }

        $stateText = __('%s Archive Page', 'wp_lib');
        $stateText = sprintf($stateText, $postType->label);

        $postStates['custom_archive_page'] = $stateText;

        return $postStates;
    }

    public function hidePageTemplates($templates, $theme, $post)
    {
        if (! $post) {
            return $templates;
        }

        if (ArchivePage::getPostType($post->ID)) {
            return;
        }

        return $templates;
    }
}
