<?php

namespace WpNext\PostType;

use WpNext\Support\Facades\Action;
use WpNext\Support\Facades\Ajax;

class PostTypeBuilder
{
    protected $postTypes = [];

    public function register($name, $closure)
    {
        $postType = new PostType($name, $closure);

        $this->postTypes[$name] = $postType;
    }

    public function init()
    {
        foreach ($this->postTypes as $postType) {
            $postType->create();

            if ($postType->isSortAble()) {
                $this->createSortingPage($postType->name);
            }
        }

        new ArchivePageAdmin($this->postTypes);

        Ajax::listen('updatePostsOrder', fn () => $this->updateSortOrder(request('posts')));
    }

    public function createSortingPage($postType)
    {
        Action::add('admin_menu', function () use ($postType) {
            $parentSlug = $postType === 'post' ? 'edit.php' : "edit.php?post_type={$postType}";

            $label = 'Sort '.ucfirst($postType);

            add_submenu_page(
                $parentSlug,
                $label,
                $label,
                'edit_posts',
                "{$postType}-sort",
                function () use ($postType, $label) {
                    $this->initPostsOrderPage($postType, $label);
                }
            );
        });
    }

    protected function updateSortOrder($posts)
    {
        foreach ($posts as $order => $post) {
            wp_update_post([
                'ID'         => $post,
                'menu_order' => $order,
            ]);
        }

        return 'updated';
    }

    public function initPostsOrderPage(string $postType, string $label) : void
    {
        $posts = get_posts([
            'post_type' => $postType,
            'posts_per_page' => -1,
            'orderby' => 'menu_order',
            'order' => 'asc',
            'suppress_filters' => false,
        ]);

        view('postType::sorting', compact('posts', 'label'));
    }

    public function removePostType(string $postType)
    {
        global $wp_post_types;
        if (isset($wp_post_types[$postType])) {
            $wp_post_types[$postType]->public = false;
            $wp_post_types[$postType]->show_in_menu = false;
            $wp_post_types[$postType]->show_ui = false;
            $wp_post_types[$postType]->show_in_admin_bar = false;
            $wp_post_types[$postType]->publicly_queryable = false;
            $wp_post_types[$postType]->show_in_rest = false;
            $wp_post_types[$postType]->show_in_nav_menus = false;

            return true;
        }

        return false;
    }

    public function removeEditor(string $postType)
    {
        remove_post_type_support($postType, 'editor');
    }
}
