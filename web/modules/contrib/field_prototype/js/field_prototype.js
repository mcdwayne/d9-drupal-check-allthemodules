/**
 * Created by rabbi on 4/28/2017.
 */

(function($) {
    Drupal.behaviors.fieldPrototype = {

        attach: function (context, settings) {
            // Update list of field instances when bundle selection changes
            $('#edit-fields-clone-entity-type').change(function() {
                bundle_selectlist = $('#edit-fields-clone-bundle');
                instance_selectlist = $('#edit-fields-clone-instance');
                new_instance = $(this).val();
                entity_bundles = settings.entity_instances[new_instance];
                currentField = instance_selectlist.val();
                selectedField = (currentField in entity_bundles ? currentField : null);

                bundle_selectlist.PopulateOptions(entity_bundles, selectedField);
            });

            // Update list of field instances when bundle selection changes
            $('#edit-fields-clone-bundle').change(function() {
                field_selectlist = $('#edit-fields-clone-instance');
                new_entity_type = $('#edit-fields-clone-entity-type').val();
                new_bundle = $(this).val();
                bundle_fields = settings.instance_bundles[new_entity_type][new_bundle];
                currentField = field_selectlist.val();
                selectedField = (currentField in bundle_fields ? currentField : null);

                field_selectlist.PopulateOptions(bundle_fields, selectedField);
            });

            // Admin entity type to bundle
            $('#edit-other-entity-type').change(function() {
                bundle_selectlist = $('#edit-other-bundle');
                new_entity_type = $(this).val();
                console.log(new_entity_type);
                entity_bundles = drupalSettings.field_prototype.bundles[new_entity_type];
                console.log(entity_bundles);
                currentField = bundle_selectlist.val();

                bundle_selectlist.PopulateOptions(entity_bundles, null);
            });
        }
    };

    /**
     * Populates options in a select input.
     */
    jQuery.fn.PopulateOptions = function (options, selected) {
        return this.each(function () {
            var disabled = false;
            if (options.length == 0) {
                options = [this.initialValue];
                disabled = true;
            }

            // If possible, keep the same widget selected when changing field type.
            // This is based on textual value, since the internal value might be
            // different (options_buttons vs. node_reference_buttons).
            var previousSelectedText = this.options[this.selectedIndex].text;

            var html = '<option value="" selected="selected">- Select a field -</option>';
            jQuery.each(options, function (value, text) {
                // Figure out which value should be selected. The 'selected' param
                // takes precedence.
                var is_selected = ((typeof selected != 'undefined' && value == selected) || (typeof selected == 'undefined' && text == previousSelectedText));
                html += '<option value="' + value + '"' + (is_selected ? ' selected="selected"' : '') + '>' + text + '</option>';
            });

            $(this).html(html).attr('disabled', disabled ? 'disabled' : false);
        });
    };
})(jQuery);