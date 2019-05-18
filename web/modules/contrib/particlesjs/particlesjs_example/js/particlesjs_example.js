/**
 * @file
 * Add Particles on the block provided by this example module.
 */

(function ($, Drupal) {
  Drupal.behaviors.particlesjs_example = {
    attach: function (context, settings) {

      // Particles.
      particlesJS.load('particles', 'modules/contrib/particlesjs/particlesjs_example/particles.json');

    }
  };
})(jQuery, Drupal);
