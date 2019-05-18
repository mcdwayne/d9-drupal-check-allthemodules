(function($) {

  Drupal.behaviors.OoyalaSettings = {
    attach: function(context, settings) {
      $('[name="player_id"]').trigger('click');

      $('.form-item-custom-css textarea').change(function() {
        var $input = $(this)
          , value = $input.val().trim()
        ;

        $input.toggleClass('ooyala-invalid-value', value !== '' && !value.match(new RegExp(settings.Ooyala.cssPattern)));
      }).trigger('change');

      // Get options for player_id
      // Validate JSON input
      $('.ooyala-json-input').each(function() {
        $(this).change(function() {
          var $input = $(this) // Input JSON
            , $json = $('input[data-for="' + this.name + '"]')
            , value = $input.val().trim() // Trimmed value from input
            , json = false // Validity flag
          ;

          if(!value) {
            $json.val('');
          }
          else try {
            // encapsulating braces are optional
            if(!/^\s*\{[\s\S]*\}\s*$/.test(value)) {
              value = '{' + value + '}';
            }

            // eval(), with justification:
            // Yes, this could execute some arbitrary code, but this only happening in the context of
            // the admin area, as a means to see if this is a 'plain' JS object or string of JSON.
            // This will prevent the user from inadvertantly passing arbitrary code to the shortcode,
            // which in turn would put it right into a script tag ending up on the front end.
            value = eval('(' + value + ')');

            // arrays, or primitives need not apply. allow empty objects so we can put templates
            // in that result in empty objects.
            if(typeof value == 'object' && !Array.isArray(value)) {
              json = JSON.stringify(value);

              $json.val(json);
            }
            else {
              $json.val('');
            }
          } catch(e) {
            // some error along the way...not valid JSON or JS object
            json = false;
          }

          $input.toggleClass('ooyala-invalid-value', !!value && !json);
        });
      });
    }
  };
})(jQuery);

