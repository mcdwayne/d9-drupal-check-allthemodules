(function ($, Drupal, drupalSettings) {

  'use strict';

  Drupal.behaviors.cpf = {
    attach: function (context, settings) {
      // Quick check for the library dependency.
      var element = null;

      if ($.fn.mask) {
        var mask_plugin = settings.cpf.mask_plugin;
        for (element in mask_plugin.elements) {
          // Apply masking behavior only when applicable.
          if (mask_plugin.elements[element].mask.length) {
            $('#' + mask_plugin.elements[element].id).mask(mask_plugin.elements[element].mask, {reverse: true});
          }
        }
      }

      if (settings.cpf.generator) {
        var generator = settings.cpf.generator;

        for (element in generator.elements) {
          if (generator.elements[element].id.length) {
            // Bindes the click event to the cpf number generation link.
            $('#' + generator.elements[element].id, context).on('click', function (e) {
              e.preventDefault();

              // Retrieves the target input ID attribute.
              var generator_id = $(this).attr('id');
              var target_id = generator.elements[generator_id].target;

              // Generates a valid CPF number and sets the value with it.
              var cpf = Drupal.behaviors.cpf.generate();
              if (generator.mask) {
                cpf = $('#' + target_id).masked(cpf);
              }
              $('#' + target_id).val(cpf);

              return false;
            });
          }
        }
      }
    },
    generate: function () {
      // Randomly generates the first nine digits of the CPF number
      var n = [rand(), rand(), rand(), rand(), rand(), rand(), rand(), rand(), rand()];

      // Calculates the second check digit.
      n[9] = n[8] * 2 + n[7] * 3 + n[6] * 4 + n[5] * 5 + n[4] * 6 + n[3] * 7;
      n[9] += n[2] * 8 + n[1] * 9 + n[0] * 10;
      n[9] = adjust_digit(n[9]);

      // Calculates the second check digit and returns de CPF number.
      n[10] = n[9] * 2 + n[8] * 3 + n[7] * 4 + n[6] * 5 + n[5] * 6 + n[4] * 7;
      n[10] += n[3] * 8 + n[2] * 9 + n[1] * 10 + n[0] * 11;
      n[10] = adjust_digit(n[10]);

      return n.join('');
    }
  };

  function rand() {
    return Math.floor(Math.random() * 10);
  }

  function adjust_digit(d) {
    d = 11 - (d % 11);
    return d >= 10 ? 0 : d;
  }

})(jQuery, Drupal, drupalSettings);
