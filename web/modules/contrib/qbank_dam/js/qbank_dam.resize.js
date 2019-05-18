(function ($, Drupal, drupalSettings) {
    'use strict';

    jQuery('#edit-metadata-mapping-table input').css({
        'width' : '100%'
    });

    jQuery('#edit-metadata-mapping-table tr input[type="submit"]').css({
        'color' : '#ff0606'
    });

    jQuery('body').on('keyup', '#edit-metadata-mapping-table tbody tr input', function(){
        var row = jQuery(this).closest('tr');

        var qbnak_field = row.find('td:nth-child(1) input'),
            drupal_field = row.find('td:nth-child(2) input'),
            qbnak_field_name = qbnak_field.val(),
            drupal_field_name = drupal_field.val();

        if((qbnak_field_name != "" && drupal_field_name == "") || (qbnak_field_name == "" && drupal_field_name != "")){
            if(qbnak_field_name == ""){
                qbnak_field.css({
                    'border-color' : '#ff0606'
                });
            }else{
               qbnak_field.css({
                    'border-color' : '#b8b8b8'
                }); 
            }

            if(drupal_field_name == ""){
                drupal_field.css({
                    'border-color' : '#ff0606'
                });
            }else{
               drupal_field.css({
                    'border-color' : '#b8b8b8'
                }); 
            }

            jQuery('.qbank-dam-config-form input[value="Save configuration"]').prop('disabled', true);         
        }else{
            jQuery('.qbank-dam-config-form input[value="Save configuration"]').prop('disabled', false);
            jQuery('#edit-metadata-mapping-table tbody tr input').css({
                'border-color' : '#b8b8b8'
            });
        }
    })

    Drupal.behaviors.qbankdamResize = {
        attach: function (context, setting) {
            if (jQuery('#entity_browser_iframe_media_qbank').length) {
                var dialog_height = jQuery(window).height() - 200;
                var dialog_width = jQuery(window).width() - 100;

                jQuery('.ui-dialog').css({
                    'height': dialog_height + 'px',
                    'width': dialog_width + 'px',
                    'left': '50px',
                    'top': '50px',
		    'position' : 'fixed'
                });

                jQuery('.ui-dialog-content').css({
                    'height': dialog_height + 'px'
                });

                jQuery('#entity_browser_iframe_media_qbank').css({
                    'height': dialog_height - 5 + 'px'
                });
            }

        }
    };


    jQuery('#qbank-dam-config-form').on('submit', function(e){
        var mapping = createMapingJson();
        if(mapping == false){
            e.preventDefault();
        }

        jQuery('[name="metadata_config"]').val(mapping);
    });


    // Adding new row to mapping table
    jQuery("#edit-btn-add-mapping").click(function (evt) {
        evt.preventDefault();
        jQuery(".metadata_mapping_table_row:last").clone().appendTo("#edit-metadata-mapping-table").find(":text").val("").css({'border-color' : '#b8b8b8'});
        if (jQuery('#edit-metadata-mapping-table tbody tr').length > 1) {
            jQuery('#edit-metadata-mapping-table tbody tr .button.js-form-submit.form-submit').show();
        }
    });

    // Removing row from mapping table

    jQuery("#edit-metadata-mapping-table").on('click', ".button.js-form-submit.form-submit", function (evt) {
        evt.preventDefault();
        if(jQuery("#edit-metadata-mapping-table tbody tr").length > 1){
            jQuery(this).closest('tr').remove();
            if (jQuery('#edit-metadata-mapping-table tbody tr').length == 1) {
                jQuery('#edit-metadata-mapping-table tbody tr .button.js-form-submit.form-submit').hide();
            }
        }    
        return false;
    });
  

    function createMapingJson() {

        var mapping_json = {};
        //run through each row
        jQuery('#edit-metadata-mapping-table tbody tr').each(function (i, row) {

            var row = $(row),
                qbnak_field = row.find('td:nth-child(1) input'),
                drupal_field = row.find('td:nth-child(2) input'),

                qbnak_field_name = qbnak_field.val(),
                drupal_field_name = drupal_field.val();

                if(qbnak_field_name != "" && drupal_field_name != ""){
                    mapping_json[drupal_field_name] = qbnak_field_name;
                }
        });
        return JSON.stringify(mapping_json);
    }

})(jQuery, Drupal, drupalSettings);
