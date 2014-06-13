<?php
/**
 * Template Tags
 */

if (!function_exists('dlspb_get_template_part')) {
    function dlspb_get_template_part($slug, $name=null, $load=true)
    {
        global $dlspb;

        // Execute code for this part
        do_action('get_template_part_'.$slug, $slug, $name);

        // Setup possible parts
        $templates = array();
        if ( isset( $name ) )
            $templates[] = $slug . '-' . $name . '.php';
        $templates[] = $slug . '.php';

        // Allow template parst to be filtered
        $templates = apply_filters( 'edd_get_template_part', $templates, $slug, $name );

        // Return the part that is found
        return $dlspb->locate_template($templates, $load, false);
    }
}

/**
 * Paginate a wp_query
 *
 * @param null $new_wp_query
 */
if (!function_exists('dlspb_pagination')) {
    function dlspb_pagination($new_wp_query=null)
    {
        global $wp_query;
        if (!empty($new_wp_query)) {
            $old_wp_query = $wp_query;
            $wp_query = $new_wp_query;
        }

        $output = null;
        if ($wp_query->max_num_pages > 1) {
            $big = 999999999; // need an unlikely integer
            $output .= paginate_links(array(
                'base' => str_replace($big, '%#%', esc_url(get_pagenum_link($big))),
                'format' => '?paged=%#%',
                'current' => max(1, get_query_var('paged')),
                'total' => $wp_query->max_num_pages
            ));

            /*
            $output .= '
            <nav id="'.esc_attr($html_id).'">
                <h3 class="assistive-text">'.__('Navigation', $this->data->get_prefix(false)).'</h3>
                <div class="nav-previous">'.get_next_posts_link(__('<span class="meta-nav">&larr;</span> Prev')).'</div>
                <div class="nav-next">'.get_previous_posts_link(__('Next <span class="meta-nav">&rarr;</span>')).'</div>
            </nav><!-- #'.esc_attr($html_id).' -->
            ';
            */
        }

        if (!empty($new_wp_query)) $wp_query = $old_wp_query;
        echo '<p class="dlspb-pages">'.$output.'</p>';
    }
}

/**
 * Get portfolio screenshots
 *
 * @param int $post_id
 * @return array screenshots
 */
if (!function_exists('dlspb_get_screenshots')) {
    function dlspb_get_screenshots($post_id=0)
    {
        global $post;
        if (!empty($post_id)) $post = get_post($post_id);
        if (empty($post)) return null;

        return get_post_meta($post->ID, 'dlspb_screenshots', true);
    }
}

/**
 * Get related projects
 *
 * @param int $post_id
 * @return array projects
 */
if (!function_exists('dlspb_get_related')) {
    function dlspb_get_related($post_id=0)
    {
        global $post, $dlspb;
        if (!empty($post_id)) $post = get_post($post_id);
        if (empty($post)) return false;

        $relatables = $dlspb->data->get_relatable_taxonomies();
        if (empty($relatables)) return array();

        $original_post_id = $post->ID;
        $posts = array();
        foreach ($relatables as $relatable) {
            $terms = wp_get_post_terms($post->ID, $relatable['slug'], array('fields' => 'names'));
            if (empty($terms)) return array();

            // Get posts for taxonomy term
            $posts_query = new WP_Query(array(
                'post_type' => $dlspb->data->post_type,
                $relatable['slug'] => implode(', ', $terms),
                'order' => 'ASC',
            ));
            if ($posts_query->have_posts()) {
                while ($posts_query->have_posts()) {
                    $posts_query->the_post();
                    if (!empty($posts[$post->ID]) || $original_post_id == $post->ID) continue;
                    $posts[$post->ID] = $post;
                }
            }
            wp_reset_postdata();
        }
        ksort($posts);
        return $posts;
    }
}

/**
 * Get filterable taxonomies
 *
 * @return array taxonomies
 */
if (!function_exists('dlspb_get_filters')) {
    function dlspb_get_filters()
    {
        global $dlspb;
        $filters = array();
        $filterables = $dlspb->data->get_filterable_taxonomies();
        foreach ($filterables as $filterable) {
            $terms = get_terms($filterable['slug'], array('fields' => 'all'));
            $options = array();
            foreach ($terms as $term) $options[$term->slug] = $term->name;
            $filters[] = array(
                'slug' => $filterable['slug'],
                'label' => $filterable['singular'],
                'type' => 'dropdown',
                'options' => $options,
            );
        }
        return $filters;
    }
}

/**
 * Display the filter form
 */
if (!function_exists('dlspb_the_filter_form')) {
    function dlspb_the_filter_form()
    {
        global $dlspb;
        $filtered_by = null;
        $filters = dlspb_get_filters();
        $filter_out = null;
        if (!empty($filters)) {
            $filter_out .= '<form class="dlspb-filter" action="" method="post" class="js-only">';
            $filter_out .= '<h3>'.__('Filter by...', 'dlspb').'</h3>';
            $i = 0;
            foreach ($filters as $filter) {
                if (empty($filter['options'])) continue;
                $i++;
                if ($i > 1) $filter_out .= '<div class="dlspb-filter-or">'.__('- or -', 'dlspb').'</div>';
                $field_name = str_replace('_', '-', $filter['slug']);
                switch ($filter['type']) {
                    case 'dropdown':
                        $filter_out .= '
                            <div class="dlspb-filter-field dlspb-filter-field-'.$field_name.'">
                                <label>'.__($filter['label'], 'dlspb').':</label>
                                <ul class="dlspb-faux-select dlspb-filter-type-'.$filter['type'].' dlspb-filter-field-'.$filter['slug'].'">
                                    <li><a href="'.get_post_type_archive_link($dlspb->data->post_type).'">All</a></li>
                                    ';
                                    foreach ($filter['options'] as $key=>$value) {
                                        $selected = null;
                                        if (
                                            $filter['slug'] == get_query_var('taxonomy')
                                            && $key == get_query_var('term')
                                        ) {
                                            $selected = ' class="dlspb-selected"';
                                            $filtered_by = '<h2 class="dlspb-filtered-by">'.__($value, 'dlspb').' <span class="dlspb-view-all"><a href="'.get_post_type_archive_link($dlspb->data->post_type).'">'.__('(clear filter)', 'dlspb').'</a></span></h2>';
                                        }
                                        $filter_out .= '<li><a href="'.get_term_link($key, $filter['slug']).'"'.$selected.'>'.__($value, 'dlspb').'</a></li>';
                                    }
                                    $filter_out .= '
                                </ul><!-- .dlspb-faux-select -->
                            </div><!-- .dlspb-filter-field -->
                        ';
                        break;
                }
            }
            $filter_out .= '</form><!-- dlspb-filter -->';

            $filter_position = get_option('dlspb_filter_position');
            if ($filter_position == 'above-subhead') echo $filter_out;
            echo $filtered_by;
            if ($filter_position == 'below-subhead' || empty($filter_position)) echo $filter_out;
        }
    }
}

/**
 * Return CSS classes to define row numbers and item count
 *
 * @return string
 */
if (!function_exists('dlspb_row_classes')) {
    function dlspb_row_classes()
    {
        global $wp_query;

        $layout = get_option('dlspb_list_layout');
        $row = 0;

        switch ($layout) {
            case '1col':
                $row = 1;
                break;
            case '2col':
                for ($i=0; $i<=$wp_query->current_post; $i++) {
                    $row++;
                    if ($row > 2) $row = 1;
                }
                break;
            case '3col':
                for ($i=0; $i<=$wp_query->current_post; $i++) {
                    $row++;
                    if ($row > 3) $row = 1;
                }
                break;
        }

        return 'dlspb-current-post-'.$wp_query->current_post.' dlspb-row-'.$row;
    }
}

/**
 * Output the banner url for the current post
 *
 * @param int $post_id
 * @return bool
 */
if (!function_exists('dlspb_the_banner_url')) {
    function dlspb_the_banner_url($post_id=0)
    {
        global $post;
        if (!empty($post_id)) $post = get_post($post_id);
        if (empty($post)) return false;

        $banner_image = get_post_meta($post->ID, 'dlspb_banner_image', true);
        if (empty($banner_image)) return false;

        $banner = wp_get_attachment_image_src($banner_image, 'dlspb-banner');
        echo $banner[0];
        return true;
    }
}

/**
 * Display social media markup
 *
 * @return null|void
 */
if (!function_exists('dlspb_the_social_media')) {
    function dlspb_the_social_media()
    {
        global $dlspb;
        $social_media = $dlspb->data->get_option('social_media', false);
        if (empty($social_media)) return null;
        echo '
            <div class="dlspb-social-media js-only">
                <h3>'.__('Share this project', 'dlspb').'</h3>
                '.$social_media.'
            </div><!-- .dlspb-social-media -->
        ';
    }
}

/**
 * Get detail image
 *
 * @param string $size
 * @return string
 */
if (!function_exists('dlspb_get_detail_image')) {
    function dlspb_get_detail_image($size='medium')
    {
        global $post;
        $img = get_post_meta($post->ID, 'dlspb_detail_image', true);
        return wp_get_attachment_image($img, $size);
    }
}