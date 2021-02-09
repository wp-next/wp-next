<?php

namespace WpNext\PostType;

use WpNext\Support\Facades\Action;
use WpNext\Support\Facades\Ajax;

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

            if (! empty($config['sortable'])) {
                $this->createSortingPage($name);
            }

            register_post_type($name, $config);
        }

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
}
