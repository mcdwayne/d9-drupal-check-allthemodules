
/*
    Change numbers on rosters
 */
Drupal.behaviors.selectNumberPlayer = {
    attach: function (context, settings) {
        jQuery('.sl_roster_player_selection').change(function() {
           rand = jQuery(this).attr('data-attribute-number');
           jQuery('.sl_roster_player_number_' + rand).val(drupalSettings.sl_rosters[jQuery( this ).val()]);
        });
    }
};
