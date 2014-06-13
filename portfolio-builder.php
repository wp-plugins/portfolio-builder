<?php
/*
Plugin Name: Portfolio Builder
Plugin URI: http://www.dlssoftwarestudios.com/downloads/portfolio-builder-wordpress-plugin
Description: Flexible portfolio management
Version: 1.0.0
Author: DLS Software Studios
Author URI: http://www.dlssoftwarestudios.com/
*/

if (!class_exists('DLSPB_Data')) require_once dirname(__FILE__).'/data.php';
if (!class_exists('DLSPB_Admin')) require_once dirname(__FILE__).'/admin.php';
require_once dirname(__FILE__).'/template-tags.php';

if (!class_exists('DLSPB')):

class DLSPB
{

    public $data;
    private $admin;

    public $prefix;
    private $plugin_path;

    public function __construct()
    {
        $this->data = new DLSPB_Data();
        $this->admin = new DLSPB_Admin();
        $this->prefix = $this->data->prefix;

        $plugin = plugin_basename(__FILE__);
        $this->plugin_path = dirname(__FILE__).'/';

        register_activation_hook(__FILE__, array(&$this, 'activate'));
        register_deactivation_hook( __FILE__, array(&$this, 'deactivate'));

        $this->set_default_options();

        // Add banner image size
        add_image_size(
            $this->prefix.'-banner',
            get_option($this->prefix.'_banner_height'),
            get_option($this->prefix.'_banner_width'),
            get_option($this->prefix.'_banner_crop')
        );

        add_action('admin_menu', array(&$this->admin, 'menu'));
        add_action('wp_enqueue_scripts', array(&$this, 'add_css_and_js_to_frontend'));
        add_action('init', array(&$this, 'add_post_types'), 0);
        add_action('init', array(&$this, 'add_custom_taxonomy'), 0);
        add_action('init', array(&$this, 'flush_if_needed'), 0);
        add_action('single_template', array(&$this, 'single_template'));
        add_action('archive_template', array(&$this, 'archive_template'));
        add_action('pre_get_posts', array(&$this, 'archive_query_override'));
        add_action('admin_init', array(&$this, 'dup_plugin_check'));

        add_filter('plugin_action_links_'.$plugin, array(&$this->admin, 'settings_link'));
    }

    private function set_default_options()
    {
        if (get_option($this->prefix.'_list_layout') === false) {
            add_option($this->prefix.'_list_layout', '3col', null, 'yes');
        }
        if (get_option($this->prefix.'_banner_height') === false) {
            add_option($this->prefix.'_banner_height', '0', null, 'yes');
        }
        if (get_option($this->prefix.'_banner_width') === false) {
            add_option($this->prefix.'_banner_width', '200', null, 'yes');
        }
        if (get_option($this->prefix.'_banner_crop') === false) {
            add_option($this->prefix.'_banner_crop', 'false', null, 'yes');
        }
    }

    /**
     * Enqueue plugin css and js files
     *
     * @todo remove if necessary
     */
    public function add_css_and_js_to_frontend()
    {
        wp_enqueue_script('jquery-ui-tabs');
        wp_enqueue_script('jquery-ui-autocomplete');
        wp_enqueue_script(
            $this->prefix.'-main',
            plugins_url('/js/main.js', __FILE__),
            array('jquery-ui-tabs', $this->prefix.'-fancybox')
        );
        wp_enqueue_style(
            $this->prefix.'-style',
            plugins_url('/css/style.css', __FILE__)
        );

        // FancyBox
        wp_enqueue_script(
            $this->prefix.'-fancybox',
            'http://yandex.st/jquery/fancybox/1.3.4/jquery.fancybox.min.js',
            array('jquery')
        );
        wp_enqueue_style(
            $this->prefix.'-fancybox',
           'http://yandex.st/jquery/fancybox/1.3.4/jquery.fancybox.css'
        );
    }

    public function add_post_types()
    {
        // Portfolio
        $slug = 'portfolio';
        $labels = array(
            'name' => _x('Portfolio', $this->data->post_type),
            'singular_name' => _x('Portfolio Item', $this->data->post_type),
            'add_new' => _x('Add New', $this->data->post_type),
            'add_new_item' => _x('Add New Portfolio Item', $this->data->post_type),
            'edit_item' => _x('Edit Portfolio Item', $this->data->post_type),
            'new_item' => _x('New Portfolio Item', $this->data->post_type),
            'view_item' => _x('View Portfolio Item', $this->data->post_type),
            'search_items' => _x('Search Portfolio', $this->data->post_type),
            'not_found' => _x('No portfolio items found', $this->data->post_type),
            'not_found_in_trash' => _x('No portfolio items found in Trash', $this->data->post_type),
            'parent_item_colon' => _x('Parent Portfolio Item:', $this->data->post_type),
            'menu_name' => _x('Portfolio', $this->data->post_type),
        );
        $args = array(
            'labels' => $labels,
            'hierarchical' => false,
            'supports' => array('title', 'editor', 'revisions'),
            'public' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'show_in_nav_menus' => true,
            'publicly_queryable' => true,
            'exclude_from_search' => false,
            'has_archive' => true,
            'query_var' => true,
            'can_export' => true,
            'rewrite' => array('slug' => $slug),
            'capability_type' => 'post',
            'menu_icon' => plugins_url('/images/icon-menu.png', __FILE__),
        );
        register_post_type($this->data->post_type, $args);

        // General
        $meta_box = array(
            'id'        => $this->data->post_type.'-general-meta',
            'title'     => 'Listing Page',
            'post_type' => $this->data->post_type,
            'context'   => 'normal',
            'priority'  => 'high',
            'fields'    => array(
                array(
                    'label' => 'Image',
                    'key'   => $this->prefix.'_banner_image',
                    'type'  => 'image',
                ),
                array(
                    'label'  => 'Subtitle',
                    'key'    => $this->prefix.'_subtitle',
                    'type'   => 'text',
                    'style'  => 'width: 98%;',
                ),
                array(
                    'label'  => 'Rank',
                    'key'    => $this->prefix.'_rank',
                    'type'   => 'text',
                    'append' => '<span class="howto">'.__('Overrides the ordering of portfolio items on your list page on the frontend.  If blank, defaults to zero. Enter a negative number to have an item appear prior to items with a rank of zero.', $this->prefix).'</span>',
                ),
            ),
        );
        new DLS_Meta_Boxes($meta_box);

        // Banner
        $meta_box = array(
            'id'        => $this->data->post_type.'-banner-meta',
            'title'     => 'Detail Page',
            'post_type' => $this->data->post_type,
            'context'   => 'normal',
            'priority'  => 'high',
            'fields'    => array(
                array(
                    'label'  => 'Main Image',
                    'key'    => $this->prefix.'_detail_image',
                    'type'  => 'image',
                ),
            ),
        );
        new DLS_Meta_Boxes($meta_box);

        // Screenshots
        $meta_box = array(
            'id'        => $this->data->post_type.'-screenshots-meta',
            'title'     => 'Screenshots',
            'post_type' => $this->data->post_type,
            'context'   => 'normal',
            'priority'  => 'high',
            'fields'    => array(
                array(
                    'label'  => null,
                    'key'    => $this->prefix.'_screenshots',
                    'type'   => 'repeater',
                    'fields' => array(
                        array(
                            'label' => null,
                            'key'   => 'id',
                            'type'  => 'image',
                            'style' => 'display: inline-block;',
                        ),
                    ),
                ),
            ),
        );
        new DLS_Meta_Boxes($meta_box);
    }

    /**
     * Add Custom Taxonomy
     */
    public function add_custom_taxonomy()
    {
        $taxonomies = (array) maybe_unserialize(get_option($this->prefix.'_taxonomies'));
        $default = array(
            'slug' => null,
            'singular' => null,
            'plural' => null,
            'hierarchical' => null,
            'filter' => null,
            'relate' => null,
        );
        foreach ($taxonomies as $k=>$v) $taxonomies[$k] = array_intersect_key((array)$v + $default, $default);
        reset($taxonomies);

        foreach ($taxonomies as $field) {
            register_taxonomy($field['slug'], $this->data->post_type, array(
                'hierarchical' => ($field['hierarchical'] === 'true') ? true : false,
                'labels' => array(
                    'name' => _x($field['plural'], 'taxonomy general name'),
                    'singular_name' => _x($field['singular'], 'taxonomy singular name'),
                    'search_items' =>  __('Search '.$field['plural']),
                    'all_items' => __('All '.$field['plural']),
                    'parent_item' => __('Parent '.$field['singular']),
                    'parent_item_colon' => __('Parent '.$field['singular'].':'),
                    'edit_item' => __('Edit '.$field['singular']),
                    'update_item' => __('Update '.$field['singular']),
                    'add_new_item' => __('Add New '.$field['singular']),
                    'new_item_name' => __('New '.$field['singular'].' Name'),
                    'menu_name' => __($field['plural']),
                ),
                'rewrite' => array(
                    'slug' => str_replace('_', '-', $field['slug']), // controls the base slug that will display before each term
                    'with_front' => false, // don't display category base before "/locations/"
                    'hierarchical' => ($field['hierarchical'] === 'true') ? true : false, // URL's like "/locations/boston/cambridge/"
                ),
            ));
        }
    }

    public function archive_query_override($query)
    {
        if (is_admin()) return $query;
        if (!$query->is_post_type_archive($this->data->post_type)) return $query;

        $page_count = (get_option($this->prefix.'_list_layout') === '2col') ? 8 : 9;
        $query->set('posts_per_page', $page_count);
        $filterables = $this->data->get_filterable_taxonomies();
        foreach ($filterables as $filterable) {
            $qs_name = 'filter_'.$filterable['slug'];
            if (empty($_GET[$qs_name])) continue;
            $query->set($filterable['slug'], implode(', ', (array) $_GET[$qs_name]));
        }
        $query->set('post_status', 'publish');
        $query->set('orderby', 'meta_value_num date');
        $query->set('meta_key', $this->prefix.'_rank');
        $query->set('order', 'DESC');

        return $query;
    }

    /**
     * Override archive template
     *
     * @param string $template
     * @return string
     */
    public function archive_template($template)
    {
        global $post;

        if (is_post_type_archive($this->data->post_type)) {
            return $this->locate_template('archive-'.$this->data->post_type.'.php');
        }

        $filters = dlspb_get_filters();
        if (!empty($filters)) {
            foreach ($filters as $filter) {
                if (is_tax($filter['slug'])) return $this->locate_template('archive-'.$this->data->post_type.'.php');
            }
        }

        return $template;
    }

    /**
     * Override archive template
     *
     * @param string $template
     * @return string
     */
    public function single_template($template)
    {
        global $post;

        if (get_post_type() == $this->data->post_type) {
            return $this->locate_template('single-'.$this->data->post_type.'.php');
        }

        return $template;
    }

    /**
     * Retrieve the name of the highest priority template file that exists.
     *
     * Searches in the STYLESHEETPATH before TEMPLATEPATH so that themes which
     * inherit from a parent theme can just overload one file.
     *
     * Modified from core WP code to check plugin directory last
     *
     * @param string|array $template_names Template file(s) to search for, in order.
     * @param bool $load If true the template file will be loaded if it is found.
     * @param bool $require_once Whether to require_once or require. Default true. Has no effect if $load is false.
     * @return string The template filename if one is located.
     */
    function locate_template($template_names, $load=false, $require_once=true)
    {
        $located = false;

        foreach ((array) $template_names as $template_name) {

            if (empty($template_name)) continue;

            if (file_exists(STYLESHEETPATH . '/' . $template_name)) {
                $located = STYLESHEETPATH . '/' . $template_name;
                break;
            } elseif (file_exists(TEMPLATEPATH . '/' . $template_name)) {
                $located = TEMPLATEPATH . '/' . $template_name;
                break;
            } elseif (file_exists($this->plugin_path . 'theme-files/' . $template_name)) {
                $located = $this->plugin_path . 'theme-files/' . $template_name;
                break;
            }
        }

        if ((true == $load) && !empty($located))
            load_template($located, $require_once);

        return $located;
    }

    /**
     * Check if we need to flush rewrites (like if slug was changed in Settings)
     */
    public function flush_if_needed()
    {
        if (get_transient($this->prefix.'_flush_rewrite_rules')) {
            flush_rewrite_rules();
            delete_transient($this->prefix.'_flush_rewrite_rules');
        }
    }

    /**
     * Duplicate plugin check
     */
    public function dup_plugin_check()
    {
        if (is_plugin_active('portfolio-builder-pro/portfolio-builder.php')) {
            deactivate_plugins(plugin_basename(__FILE__));
            add_action('admin_notices', array($this, 'dup_plugin_admin_notice'));
        }
    }

    /**
     * Duplicate plugin admin notice
     */
    function dup_plugin_admin_notice()
    {
        echo '
            <div id="'.$this->prefix.'-dup-plugin" class="error">
                <p>More than one Portfolio Builder plugin was found.  Please only activate one at a time.</p>
            </div>
        ';
    }

    /**
     * Activate the plugin
     */
    public function activate()
    {
        if (is_plugin_active(plugin_basename('portfolio-builder-pro/portfolio-builder.php'))) die('Activate Free');//debug
        $this->add_post_types();
        $this->add_custom_taxonomy();
        flush_rewrite_rules();
    }

    /**
     * Deactivate the plugin
     */
    public function deactivate()
    {
        flush_rewrite_rules();
    }

}

$dlspb = new DLSPB();

endif;