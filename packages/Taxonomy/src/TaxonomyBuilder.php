<?php

namespace WpNext\Taxonomy;

class TaxonomyBuilder
{
    public function remove(string $taxonomy)
    {
        global $wp_taxonomies;

        if (isset($wp_taxonomies[$taxonomy])) {
            unset($wp_taxonomies[$taxonomy]);
        }
    }
}
