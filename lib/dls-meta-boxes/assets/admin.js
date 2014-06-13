var dlsmb = {
    //admin_url : '',
    post_id : 0,
    //validation : false,
    text : {
        'gallery_tb_title_add' : "Add Image to Gallery"
    },
    conditional_logic : {},
    sortable_helper : null,
    tinyMCE_settings : null
};

(function($){

    $(document).ready(function() {

        function dlsmb_update_image_field(wrap) {
            var img = wrap.children('.dlsmb-preview-image').children('img');
            if (img.length) {
                wrap.children('.dlsmb-add-image').hide();
                wrap.children('.dlsmb-remove-image').show();
            } else {
                wrap.children('.dlsmb-input-image').val('');
                wrap.children('.dlsmb-add-image').show();
                wrap.children('.dlsmb-remove-image').hide();
            }
        }

        $(document).on('click', '.dlsmb-remove-image', function() {
            $(this).parent().children('.dlsmb-preview-image').html('');
            dlsmb_update_image_field($(this).parent());
            return;
        });

        $(document).on('click', '.dlsmb-add-image', function() {
            inputField = $(this).prev('.dlsmb-input-image');
            tb_show('Select Image', 'media-upload.php?post_id=' + dlsmb.post_id + '&type=image&dlsmb=yes&TB_iframe=true');
            window.send_to_editor = function(html) {
                url = $(html).attr('href');
                inputField.parent().children('.dlsmb-ajax-loading').show();

                $.ajax({
                    url: ajaxurl,
                    data : {
                        action: 'dlsmb_image_data',
                        attachment_url: url
                    },
                    cache: false,
                    dataType: "json",
                    success: function(json) {
                        if (!json) return;
                        var item = json[0];
                        inputField.val(item.id);
                        inputField.parent().children('.dlsmb-ajax-loading').hide();
                        inputField.parent().children('.dlsmb-preview-image').html('<img src="'+ item.preview + '" height="' + item.preview_height + '" width="' + item.preview_width + '" alt="" />');
                        dlsmb_update_image_field(inputField.parent());
                    }
                });

                tb_remove();
            };
            return false;
        });

        $('.dlsmb-field-type-image .dlsmb-image-wrap').each(function(index) {
            dlsmb_update_image_field($(this));
        });

        // Add/Remove Repeaters
        if ($('.dlsmb-field-type-repeater TBODY TR').is('*')) {
            var last_css_id = $(".dlsmb-field-type-repeater TBODY TR").last().attr('id');
            var row_key = last_css_id.substr(last_css_id.lastIndexOf("-") + 1);
            $(".dlsmb-js-add").live('click', function() {
                // Find all comments within the given collection.
                var new_row_template = $(this).closest('.dlsmb-field-type-repeater').comments();

                // Update row_keys
                row_key++;
                new_element = $(this).closest("TR").after(new_row_template.get(0).nodeValue).next('TR');
                new_element.attr('id', new_element.attr('class').split(' ')[0] + '-' + row_key);
                new_element_name = new_element.find('.dlsmb-input-image').attr('name');
                new_element_name_prefix = new_element_name.substr(0, new_element_name.indexOf('[') + 1);
                new_element_name_suffix = new_element_name.substr(new_element_name.indexOf(']'));
                new_element.find('.dlsmb-input-image').attr('name', new_element_name_prefix + row_key + new_element_name_suffix);
                dlsmb_update_image_field(new_element.find('.dlsmb-image-wrap'));
                return false;
            });
            $(".dlsmb-js-remove").live('click', function() {
                if ($('.dlsmb-field-type-repeater TBODY TR').length == 1) {
                    $(this).prev().trigger('click');
                }
                $(this).closest("TR").remove();
                return false;
            });
        }

        // Sort Repeaters
        $('.dlsmb-field-type-repeater TABLE').find('TR').each(function(){
            $(this).children('TH').eq(0).before('<th class="dlsmb-sort"></th>');
            $(this).children('TD').eq(0).before('<td class="dlsmb-sort"></td>');
        });

        $(".dlsmb-field-type-repeater TABLE TBODY").sortable({
            distance: 5,
            opacity: 0.6,
            cursor: 'move'
        });

        $(".dlsmb-datepicker").datepicker({
            dateFormat: 'yy-mm-dd',
            changeMonth: true,
            changeYear: true,
            selectOtherMonths: true,
            showOtherMonths: true
        });

        // Limit breed parents to first level only
        var dlspet_qs = {};
        $.each(document.location.search.substr(1).split('&'),function(c,q){
            var i = q.split('=');
            if (typeof i[0] != 'undefined' && typeof i[1] != 'undefined') {
                dlspet_qs[i[0].toString()] = i[1].toString();
            }
        });
        if (dlspet_qs['taxonomy'] == 'dlspet_breed') {
            // Remove all but first level
            $('#parent OPTION').each(function() {
                if ($(this).attr('class') != 'level-0' && $(this).attr('class') != '') {
                    $(this).remove();
                }
            });
        }

    });

})(jQuery);