<?php
/**
 * Meta Boxes
 *
 * A class to easily add custom meta boxes and fields programmatically
 *
 * Requires WordPress 3.0+
 *
 * @version 0.4
 * @author DLS Software Studios
 * @copyright 2013 DLS Software Studios <http://www.dlssoftwarestudios.com/>
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 */

class DLS_Meta_Boxes
{

    /**
     * Meta Box Definition
     *
     * @var array $meta_box
     */
    public $prefix = 'dlsmb';
    private $meta_box;

    /**
     * Initialize Class
     *
     * @param array $meta_box
     */
    public function __construct($meta_box)
    {
        // Clean Input
        $defaults = array(
            'id' => null, // HTML 'id' attribute of the edit screen section
            'title' => ' ',
            'post_type' => 'page',
            'context' => 'normal', // ('normal', 'advanced', or 'side')
            'priority' => 'default', // ('high', 'core', 'default' or 'low')
            'fields' => array( // the value of this field is not set as a default, just included for reference
                'label' => null,
                'key' => null,
                'type' => 'text',
                'style' => null,
                'append' => null, // append after field
                'limit' => 0, // limit characters on input fields
            ),
        );
        $this->meta_box = array_intersect_key($meta_box + $defaults, $defaults);

        add_action('add_meta_boxes', array(&$this, 'add_meta_boxes'));
        add_action('save_post', array(&$this, 'save_details'));
        add_action('wp_ajax_dlsmb_image_data', array(&$this, 'ajax_image_data'));
        add_action('admin_enqueue_scripts', array(&$this, 'admin_enqueue_scripts'));
        add_filter('media_upload_tabs', array($this, 'remove_media_library_tab'));
    }

    /**
     * Admin Init to actually add the meta box
     */
    public function add_meta_boxes()
    {
        add_meta_box(
            $this->meta_box['id'],
            $this->meta_box['title'],
            array($this, 'display_fields'),
            $this->meta_box['post_type'],
            $this->meta_box['context'],
            $this->meta_box['priority']
        );
    }

    /**
     * Display the fields in the edit pages
     */
    public function display_fields()
    {
        global $post;
        $custom = get_post_custom($post->ID);

        foreach ($this->meta_box['fields'] as $field) {
            $value = (isset($custom[$field['key']])) ? $custom[$field['key']][0] : null;
            $this->_display_field($field, $value);
        }
        reset($this->meta_box);
    }

    /**
     * Display field
     *
     * @param array $field
     * @param string $value
     * @param string $repeater_key
     * @param int $repeater_count
     */
    private function _display_field($field, $value, $repeater_key=null, $repeater_count=0)
    {
        $field_name = (is_null($repeater_key)) ? $field['key'] : $repeater_key.'['.$repeater_count.']['.$field['key'].']';

        echo '<div class="dlsmb-field dlsmb-field-type-'.$field['type'].' dlsmb-field-key-'.$field['type'].'-'.$field['key'].'">';

        if (!empty($field['label'])) echo '<label for="'.$field['key'].'" class="dlsmb-main-label">' . $field['label'] . '</label>';

        switch ($field['type']) {

            case 'textarea':
                echo '<textarea name="' . $field_name . '" style="width: 98%; ' . (!empty($field['style']) ? ' '.$field['style'] : null) . '"' . (!empty($field['rows']) ? ' rows="'.$field['rows'].'"' : null) . '>' . $value . '</textarea>';
                break;

            case 'radio':
                foreach ($field['options'] as $opt_key=>$opt_val) {
                    $checked = ($value == $opt_val) ? ' checked="checked"' : null;
                    echo '<input type="radio" name="' . $field_name. '" value="'.$opt_val.'" id="'.$field['key'].'-'.$opt_key.'"'.$checked.'> <label for="'.$field['key'].'-'.$opt_key.'">'.$opt_val.'</label><br />';
                }
                break;

            case 'checkbox':
                $checked = ($value === 'true') ? ' checked="checked"' : null;
                echo '<input type="checkbox" name="' . $field_name. '" value="true" id="'.$field['key'].'"'.$checked.'> <label for="'.$field['key'].'-'.$field['key'].'" class="dlsmb-checkbox">'.$field['label'].'</label><br />';
                break;

            case 'checkboxes':
                foreach ($field['options'] as $opt_key=>$opt_val) {
                    $checked = ($value == $opt_val) ? ' checked="checked"' : null;
                    echo '<input type="checkbox" name="' . $field_name. '" value="'.$opt_val.'" id="'.$field['key'].'-'.$opt_key.'"'.$checked.'> <label for="'.$field['key'].'-'.$opt_key.'">'.$opt_val.'</label><br />';
                }
                break;

            case 'select':
                echo '<select name="' . $field_name. '" id="'.$field['key'].'" data-placeholder="'.$value.'">';
                foreach ($field['options'] as $opt_key=>$opt_val) {
                    $checked = ($value == $opt_key) ? ' selected="selected"' : null;
                    echo '<option value="'.$opt_key.'" '.$checked.'>'.$opt_val.'</option>';
                }
                echo '</select>';
                break;

            case 'image':
                if (!empty($value)) $image = wp_get_attachment_image_src($value, 'thumbnail');
                $image_style = (!empty($image)) ? 'style="width: '.$image[2].'px;"' : null;
                $image_output = (!empty($image)) ? '<img src="'.$image[0].'" height="'.$image[1].'" width="'.$image[2].'" alt="" />' : null;
                echo '
                    <div class="dlsmb-image-wrap" '.$image_style.'>
                        <span class="dlsmb-preview-image">'.$image_output.'</span>
                        <input type="hidden" name="' . $field_name. '" class="button dlsmb-input-image" value="'.$value.'" />
                        <input type="button" class="button dlsmb-add-image" value="Add Image" />
                        <input type="button" class="button dlsmb-remove-image" value="x" title="Remove" />
                        <img class="dlsmb-ajax-loading" alt="" src="'.admin_url('images/wpspin_light.gif').'">
                    </div>
                ';
                break;

            case 'repeater':
                $value = (array) maybe_unserialize($value);
                $blank_row = array();

                echo "\n\n".'<table><thead><tr>'."\n";

                foreach ($field['fields'] as $f) {
                    echo '<th>' . $f['label'] . '</th>'."\n";
                    $blank_row[$f['key']] = null;
                }
                reset($field['fields']);
                if (!in_array($blank_row, $value)) $value[] = $blank_row;
                $value[] = $blank_row; // for template

                echo "\n".'</tr></thead><tbody>'."\n";

                if (!empty($value) && is_array($value)) {
                    $i = 0;
                    foreach ($value as $k=>$v) {
                        if (($i+1) == count($value)) echo "<!--\n";
                        echo '<tr class="dlsmb-repeater-'.$field['key'].'-row"'.((($i+1) != count($value)) ? ' id="dlsmb-repeater-'.$field['key'].'-row-'.$i.'"': null).'>'."\n";
                        if (($i+1) == count($value)) echo '<td class="dlsmb-sort"></td>'."\n"; // TODO: add this in via jQuery
                        foreach($field['fields'] AS $f) {
                            echo '<td>'."\n";
                            $this->_display_field($f, (isset($v[$f['key']]) ? $v[$f['key']] : null), $field['key'], $i);
                            echo '</td>'."\n";
                            echo '<td class="dlsmb-js-add-remove"><a href="#" class="dlsmb-js-add">+</a> <a href="#" class="dlsmb-js-remove">x</a></td>'."\n";
                        }
                        echo '</tr>'."\n";
                        if (($i+1) == count($value)) echo "\n-->\n";
                        reset($field['fields']);
                        $i++;
                    }
                }

                echo '</tbody></table>'."\n\n";
                break;

            case 'datepicker':
                // NOTE: Display format is 'Y-m-d', Database format is 'Ymd'
                if (!empty($value)) $value = date('Y-m-d', strtotime($value));
                echo '<input type="text" name="' . $field_name . '" value="' . $value . '" class="dlsmb-datepicker"' . (!empty($field['style']) ? ' style="'.$field['style'].'"' : null) . ' />';
                break;

            case 'text':
            default:
                $limit = (!empty($field['limit'])) ? ' maxlength="'.$field['limit'].'"' : null;
                echo '<input type="text" name="' . $field_name . '" value="' . $value . '"' . (!empty($field['style']) ? ' style="'.$field['style'].'"' : null) . $limit . ' />';

        }

        if (!empty($field['append'])) echo $field['append'];

        echo '</div>';

    }

    /**
     * Save details when data is updated
     */
    public function save_details()
    {
        global $post;
        if (!isset($post->ID)) return;
        if (isset($_POST['action']) && $_POST['action'] === 'autosave') return;
        if (!in_array($post->post_type, (array)$this->meta_box['post_type'])) return;

        foreach ($this->meta_box['fields'] as $field) {

            // Remove blanks from repeater
            if ($field['type'] == 'repeater' && isset($_POST[$field['key']])) {
                foreach($_POST[$field['key']] as $key=>$value) {
                    $has_value = false;
                    foreach ($value as $v) {
                        if (isset($v) && $v != '') {
                            $has_value = true;
                            break;
                        }
                    }
                    if ($has_value === false) unset($_POST[$field['key']][$key]);
                }
                reset($_POST[$field['key']]);
            }

            if (isset($_POST[$field['key']])) $value = $_POST[$field['key']];

            // Convert display date format to db date Ymd
            if ($field['type'] == 'datepicker') {
                if (!empty($value) && $value != '0000-00-00') {
                    $value = date('Ymd', strtotime($value));
                }
            }

            $value = (isset($value)) ? $value : null;
            update_post_meta($post->ID, $field['key'], $value);
        }
        reset($this->meta_box);

        do_action($this->prefix.'_save_details_after', $this->meta_box);
    }

    /**
     * Echo image data in JSON format (for use with AJAX)
     */
    public function ajax_image_data()
    {
        $options = array(
            'attachment_url' => null,
        );
        $options = array_merge($options, $_GET);

        $return = array();

        if(empty($options['attachment_url'])) die(0);

        $id = $this->get_attachment_id_by_url($options['attachment_url']);
        $image = wp_get_attachment_image_src($id, 'thumbnail');

        $return[] = array(
            'id' => $id,
            'preview' => $image[0],
            'preview_height' => $image[1],
            'preview_width' => $image[2],
            'url' => $options['attachment_url'],
        );

        echo json_encode($return);
        die(0);
    }

    /**
     * Get attachment id from an attachment URL
     *
     * @param string $url
     * @return int
     */
    public function get_attachment_id_by_url($url)
    {
        $urls = (array) $url;
        if (strpos($url, 'http:') === 0) {
            $urls[] = str_replace('http:', 'https:', $url);
        } elseif (strpos($url, 'https:') === 0) {
            $urls[]  = str_replace('https:', 'http:', $url);
        }
        global $wpdb;
        $query = "SELECT ID FROM {$wpdb->posts} WHERE guid IN ('".implode("', '", $urls)."')";
        $id = $wpdb->get_var($query);
        return $id;
    }

    /**
     * Remove media library tab
     *
     * @param $tabs
     * @return mixed
     */
    public function remove_media_library_tab($tabs)
    {
        if (isset($_REQUEST['dlsmb']) && $_REQUEST['dlsmb'] == 'yes') {
            unset($tabs['type_url']);
            return $tabs;
        }
        return $tabs;
    }

    /**
     * Admin Enqueue Scripts
     */
    public function admin_enqueue_scripts()
    {
        // Datepicker (check in case older WP version since it didn't have jquery-ui-datepicker)
        if (!wp_script_is('jquery-ui-datepicker', 'registered')) {
            wp_enqueue_script(
                'jquery-ui-datepicker',
                plugins_url('assets/jquery.ui.datepicker.min.js', __FILE__),
                array('jquery-ui-core'),
                '1.8.9',
                1
            );
        }
        wp_enqueue_script('jquery-ui-datepicker');

        // Enqueue jQuery UI CSS that matches UI version
        global $wp_scripts;
        $ui_core = (isset($wp_scripts->registered['jquery-ui-core'])) ? $wp_scripts->registered['jquery-ui-core'] : null;
        if (
            isset($wp_scripts->registered['jquery-ui-core'])
            && !empty($ui_core->ver)
            && $url = (is_ssl() ? 'https' : 'http') . '://ajax.googleapis.com/ajax/libs/jqueryui/'.$ui_core->ver.'/themes/smoothness/jquery-ui.css'
        ) {
            if ($this->_external_file_exists($url)) {
                wp_enqueue_style('dlsmb-jquery-ui', $url, array(), $ui_core->ver);
            }
        }

        wp_enqueue_script(
            'dlsmb-jquery-comments',
            plugins_url('assets/jquery.comments.js', __FILE__),
            array('jquery'),
            true
        );

        wp_enqueue_script(
            'dlsmb-main',
            plugins_url('assets/admin.js', __FILE__),
            array('jquery', 'dlsmb-jquery-comments', 'jquery-ui-sortable'),
            true
        );
        wp_enqueue_style(
            'dlsmb-style',
            plugins_url('assets/style.css', __FILE__)
        );
    }

    private function _external_file_exists($url)
    {
        $file_headers = @get_headers($url);
        if($file_headers[0] == 'HTTP/1.1 404 Not Found') {
            return false;
        }
        return true;
    }

}