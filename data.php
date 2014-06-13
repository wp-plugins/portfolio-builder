<?php
/**
 * Database queries and actions
 */

class DLSPB_Data
{

    public $wpdb;
    public $err;
    public $prefix = 'dlspb';
    public $post_type;
    public $footer = null;

    public function __construct()
    {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->post_type = $this->prefix.'-portfolio';

        $this->footer = "\n\n--\nThis message was automatically sent from ".site_url()." by the DLS Error Manager plugin.";

    }

    /**
     * Get all taxonomies marked as relatable
     *
     * @return array
     */
    public function get_relatable_taxonomies()
    {
        $relatable = array();
        $taxonomies = get_option('dlspb_taxonomies');
        if (empty($taxonomies)) return array();

        foreach ($taxonomies as $taxonomy) {
            if (!empty($taxonomy['relate']) && $taxonomy['relate'] === 'true') $relatable[] = $taxonomy;
        }

        return $relatable;
    }

    /**
     * Get all taxonomies marked as filterable
     *
     * @return array
     */
    public function get_filterable_taxonomies()
    {
        $filterable = array();
        $taxonomies = get_option('dlspb_taxonomies');
        if (empty($taxonomies)) return array();

        foreach ($taxonomies as $taxonomy) {
            if (!empty($taxonomy['filter']) && $taxonomy['filter'] === 'true') $filterable[] = $taxonomy;
        }

        return $filterable;
    }

    /**
     * Get options for this plugin and process custom modification if necessary
     *
     * @param string $key
     * @param mixed $default value
     * @return string|array
     */
    public function get_option($key, $default=true)
    {
        $option = get_option($this->prefix.'_'.$key, $default);

        $to_arrays = array();
        if (in_array($key, $to_arrays)) return $this->options_string_to_array($option);

        if ($key == 'social_media') {
            return stripslashes($option);
        }

        return $option;
    }

    /**
     * Get current URL
     *
     * @return string url
     */
    public function get_current_url()
    {
        if (!isset($_SERVER['HTTP_HOST']) || !isset($_SERVER['REQUEST_URI'])) return null;

        return sprintf(
            "%s://%s%s",
            isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ? 'https' : 'http',
            $_SERVER['HTTP_HOST'],
            $_SERVER['REQUEST_URI']
        );
    }

    /**
     * Convert options listed in string format into an array
     * Example...
     *   chicago : Chicago
     *   new_york : New York
     * Converts to...
     *   array (
     *       'chicago' => 'Chicago',
     *       'new_york' => 'New York'
     *   );
     *
     * @param string $string options as string
     * @return array options as array
     */
    public function options_string_to_array($string) {
        $options = array();
        if (!empty($string)) {
            $exploded_string = explode("\r\n", $string);
            foreach ($exploded_string as $option) {
                $o = explode(' : ', $option, 2);
                $options[$o[0]] = $o[1];
            }
        }
        return $options;
    }

    /**
     * Build WP_Error
     *
     * @param string $code
     * @param string $message
     * @return WP_Error
     */
    private function err($code, $message)
    {
        if (!empty($this->err) && is_wp_error($this->err)) {
            $this->err->add($code, $message);
        } else {
            $this->err = new WP_Error($code, $message);
        }
        return $this->err;
    }

}