(function ($, Drupal, settings) {
    Drupal.behaviors.paypalinsights_question = {
        attach: function (context) {
            $('#One').click(function () {
                $('#collapseOne').slideToggle('slow');
            });
            $('#Two').click(function () {
                $('#collapseTwo').slideToggle('slow');
            });
            $('#Three').click(function () {
                $('#collapseThree').slideToggle('slow');
            });
            $('#Four').click(function () {
                $('#collapseFour').slideToggle('slow');
            });
        }
    }
})(jQuery, Drupal, drupalSettings);
