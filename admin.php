<?php
/**
 * Admin Class
 */

if (!class_exists('DLSPB_Data')) require_once dirname(__FILE__).'/data.php';
if (!class_exists('DLS_Meta_Boxes')) require_once dirname(__FILE__) . '/lib/dls-meta-boxes/meta-boxes.php';

class DLSPB_Admin
{

    public $data;
    public $prefix;
    private $admin_settings_slug;
    public $go_pro = '<div style="float: right; padding: .6em; text-align: center;" id="dlspb-go-pro"><a class="button-primary" href="http://www.dlssoftwarestudios.com/portfolio-builder-wordpress-plugin/" target="_blank">Upgrade to <strong>Portfolio Builder Pro</strong></a></div>';

    public function __construct()
    {
        $this->data = new DLSPB_Data();
        $this->prefix = $this->data->prefix;
        $this->admin_settings_slug = $this->prefix.'-settings';

        add_action('admin_head', array(&$this, 'head'));
        add_action('admin_footer', array(&$this, 'footer'));
    }

    /**
     * Admin Menu
     */
    public function menu()
    {
        add_options_page('Portfolio Builder Settings', 'Portfolio Builder', 'manage_options', $this->admin_settings_slug, array(&$this, 'options'));
    }

    /**
     * Page: Options/Settings
     */
    function options()
    {
        if (!current_user_can('manage_options'))  {
            wp_die(__('You do not have sufficient permissions to access this page.', $this->prefix));
        }

        $layouts = array(
            '1col' => __('1 Column', $this->prefix),
            '2col' => __('2 Column', $this->prefix),
            '3col' => __('3 Column', $this->prefix),
        );
        $filter_position_options = array(
            'below-subhead' => __('Below Sub-heading', $this->prefix),
            'above-subhead' => __('Above Sub-heading', $this->prefix),
        );
        $crop = array(
            'false' => __('Soft proportional crop mode', $this->prefix),
            'true' => __('Hard crop mode', $this->prefix),
        );

        // Define Options
        $options = array(
            __('Taxonomy', $this->prefix),
            array('Define Taxonomy', $this->prefix.'_taxonomies', 'custom-taxonomies', sprintf(__('Want more taxonomies?  <a href="%s" target="_blank">Check out the Pro Version</a>', $this->prefix), 'http://www.dlssoftwarestudios.com/portfolio-builder-wordpress-plugin')),
            __('Options', $this->prefix),
            array('List Layout', $this->prefix.'_list_layout', 'dropdown', null, $layouts),
            array('Filter Position', $this->prefix.'_filter_position', 'dropdown', __('The Sub-heading would be the heading that the page is being filtered by.', $this->prefix), $filter_position_options),
            array('Social Media', $this->prefix.'_social_media', 'textarea', __('Copy and paste your code here for your favorite social media buttons.  Note sure what to use?  Try <a href="http://www.addthis.com">AddThis.com</a>', $this->prefix), $layouts),
            __('Listing Page Settings', $this->prefix),
            array('Image Height', $this->prefix.'_banner_height', 'text', null),
            array('Image Width', $this->prefix.'_banner_width', 'text', null),
            array('Image Crop', $this->prefix.'_banner_crop', 'dropdown', null, $crop),
        );

        // Display Options
        $hidden_field_name = 'submit_hidden';
        echo '
            <div class="wrap '.$this->prefix.'">
                '.$this->go_pro.'
                <div id="icon-options-general" class="icon32"><br /></div>
                <h2>' . __('Portfolio Builder', $this->prefix) . ' ' . __('Settings', $this->prefix). '</h2>
                <form name="form1" method="post" action="">
        ';

        $num_saved = 0;
        foreach ($options AS $key=>$o) {
            if (!is_array($o)) {
                if ($key !== 0) echo '</table>';
                echo '<h3>'.$o.'</h3>';
                echo '<table class="form-table">';
                continue;
            }
            if ($key === 0) echo '<table class="form-table">';
            $opt_label = (isset($o[0])) ? $o[0] : null;
            $opt_name = (isset($o[1])) ? $o[1] : null;
            $opt_type = (isset($o[2])) ? $o[2] : null;
            $opt_note = (isset($o[3])) ? $o[3] : null;
            $opt_options = (isset($o[4])) ? $o[4] : null;
            $opt_val = get_option($opt_name);

            if(isset($_POST[$hidden_field_name]) && $_POST[ $hidden_field_name ] == 'Y') {
                $opt_val = (isset($_POST[$opt_name])) ? $_POST[$opt_name] : null;

                // Clean taxonomy slugs
                if ($opt_name == $this->prefix.'_taxonomies') {
                    foreach ($opt_val as $k=>$v) {
                        $opt_val[$k]['slug'] = sanitize_title_with_dashes($v['slug']);
                        $opt_val[$k]['slug'] = str_replace('-', '_', $opt_val[$k]['slug']);
                    }
                    reset($opt_val);
                }

                $updated = update_option($opt_name, $opt_val);
                $num_saved++;

                // Set flag to flush rewrite on next page load
                if ($opt_name === $this->prefix.'_taxonomies' && $updated) {
                    set_transient($this->prefix.'_flush_rewrite_rules', true);
                }

                if ($num_saved === 1) echo '<div class="updated"><p><strong>'.__('Settings saved.', $this->prefix).'</strong></p></div>';
            }

            echo '
                <tr valign="top">
                    <th scope="row">'.__($opt_label.":", $this->prefix).'</th>
                    <td>
            ';

            switch ($opt_type) {
                case 'text':
                    echo '<input type="text" name="'.$opt_name.'" value="'.esc_attr($opt_val).'" size="20">';
                    break;
                case 'password':
                    echo '<input type="password" name="'.$opt_name.'" value="'.esc_attr($opt_val).'" size="20">';
                    break;
                case 'textarea':
                    echo '<textarea name="'.$opt_name.'" rows="5" style="width: 100%;">'.stripslashes($opt_val).'</textarea>';
                    break;
                case 'checkbox':
                    echo '<input type="checkbox" name="'.$opt_name.'" value="true"'.(($opt_val === 'true') ? ' checked="checked"' : '').' size="20">';
                    break;
                case 'dropdown':
                    echo '<select name="'.$opt_name.'">';
                    foreach($opt_options AS $k=>$v) {
                        $selected = ($opt_val == $k) ? ' selected="selected"' : '';
                        echo '<option value="'.$k.'"'.$selected.'>'.$v.'</option>';
                    }
                    echo '</select>';
                    break;
                case 'custom-taxonomies':
                    $opt_val = (array) maybe_unserialize($opt_val);

                    // Clean and add empty at end if needed
                    $default = array(
                        'slug' => null,
                        'singular' => null,
                        'plural' => null,
                        'hierarchical' => null,
                        'filter' => null,
                        'relate' => null,
                    );
                    foreach ($opt_val as $k=>$v) $opt_val[$k] = array_intersect_key((array)$v + $default, $default);
                    reset($opt_val);
                    if (!in_array($default, $opt_val)) $opt_val[] = $default;

                    echo '
                        <table class="'.$this->prefix.'-settings-table">
                            <thead>
                                <tr>
                                    <th>Name (singular)</th>
                                    <th>Name (plural)</th>
                                    <th>Slug</th>
                                    <th>Hierarchical</th>
                                    <th>Filterable</th>
                                    <th>Relatable</th>
                                </tr>
                            </thead>
                            <tbody>
                            ';

                            $wp_tax_check = new WP();
                            $tax_alert = null;

                            $i=0;
                            foreach ($opt_val as $v) {
                                $tax_alert_class = null;
                                if (in_array($v['slug'], $wp_tax_check->public_query_vars)) {
                                    $tax_alert_class = 'dlspb-settings-row-alert';
                                    $tax_alert .= '<p>'.sprintf(__('Invalid taxonomy slug... "%s" is pre-defined in WordPress. Please change this slug to prevent functionality errors.', 'dlspb'), $v['slug']).'</p>';
                                }
                                echo '
                                    <tr id="dlspb-settings-row-'.$opt_name.'-'.$i.'" class="'.$tax_alert_class.'">
                                        <td><input type="text" name="'.$opt_name.'['.$i.'][singular]" value="'.esc_attr($v['singular']).'" /></td>
                                        <td><input type="text" name="'.$opt_name.'['.$i.'][plural]" value="'.esc_attr($v['plural']).'" /></td>
                                        <td class="dlspb-settings-col-'.$opt_name.'-slug"><input type="text" name="'.$opt_name.'['.$i.'][slug]" value="'.esc_attr($v['slug']).'" /></td>
                                        <td><input type="checkbox" name="'.$opt_name.'['.$i.'][hierarchical]" value="true"'.(($v['hierarchical'] === 'true') ? ' checked="checked"' : '').' /></td>
                                        <td><input type="checkbox" name="'.$opt_name.'['.$i.'][filter]" value="true"'.(($v['filter'] === 'true') ? ' checked="checked"' : '').' /></td>
                                        <td><input type="checkbox" name="'.$opt_name.'['.$i.'][relate]" value="true"'.(($v['relate'] === 'true') ? ' checked="checked"' : '').' /></td>
                                    </tr>
                                ';
                                $i++;
                            }
                            echo '
                            </tbody>
                        </table>
                    ';
                    if (!empty($tax_alert)) echo '<div class="error">'.$tax_alert.'</div>';
                    break;
            }

            echo '
                        <span class="description">'.$opt_note.'</span>
                    </tr>
                </tr>
            ';

        }

        echo '
                    </table>
                    <hr />
                    <p class="submit">
                        <input type="hidden" name="'.$hidden_field_name.'" value="Y">
                        <input type="submit" name="Submit" class="button-primary" value="'.esc_attr('Save Changes').'" />
                    </p>

                </form>
            </div><!-- .wrap -->
        ';
    }

    /**
     * Add settings link on plugin page
     *
     * @param array $links
     * @return array
     */
    function settings_link($links)
    {
        $settings_link = '<a href="options-general.php?page='.$this->admin_settings_slug.'">Settings</a>';
        array_unshift($links, $settings_link);
        return $links;
    }

    /**
     * Admin head
     */
    public function head()
    {
        wp_enqueue_style(
            $this->prefix.'-admin',
            plugins_url('css/admin.css', __FILE__),
            ('jquery')
        );
    }

    /**
     * Add to admin footer
     */
    public function footer()
    {
    ?>
        <script type="text/javascript">
            (function($) {
                $(document).ready(function(){

                    if ($('.dlspb-settings-table TBODY TR').is('*')) {
                        var last_css_id = $(".dlspb-settings-table TBODY TR").last().attr('id');
                        var row_key = last_css_id.substr(last_css_id.lastIndexOf("-") + 1);
                        $(".dlspb-js-add").live('click', function() {
                            row_key++;
                            var new_row = '<tr id="dlspb-settings-row-' + row_key + '">' +
                                '<td><input type="text" name="dlspb_taxonomies[' + row_key + '][slug]" value="" /></td>' +
                                '<td><input type="text" name="dlspb_taxonomies[' + row_key + '][singular]" value="" /></td>' +
                                '<td><input type="text" name="dlspb_taxonomies[' + row_key + '][plural]" value="" /></td>' +
                                '<td><input type="checkbox" name="dlspb_taxonomies[' + row_key + '][hierarchical]" value="true" /></td>' +
                                '<td><input type="checkbox" name="dlspb_taxonomies[' + row_key + '][filter]" value="true" /></td>' +
                                '<td><input type="checkbox" name="dlspb_taxonomies[' + row_key + '][relate]" value="true" /></td>' +
                                '</tr>';
                            $(this).closest("TR").after(new_row);
                            return false;
                        });
                        $(".dlspb-js-remove").live('click', function() {
                            if ($('.dlspb-settings-table TBODY TR').length == 1) {
                                $(this).prev().trigger('click');
                            }
                            $(this).closest("TR").remove();
                            return false;
                        });
                    }

                });
            })(jQuery);
        </script>
    <?php
    }

}