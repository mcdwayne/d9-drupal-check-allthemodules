/**
 * @file
 * JavaScript code for the Replace Images action.
 */

Drupal.konamicode_action_replace_images = function () {
  'use strict';

  let action_data = Drupal.get_action_field_info(drupalSettings.konamicode.actions, 'replace_images');
  if (action_data !== false) {
    let enabled = [];
    // This is an object and not an array, so we can't loop.
    let integrations = action_data.konamicodeReplaceImagesIntegrations;

    // Update the list below when new integrations are added.
    enabled.push((integrations.baby === 'baby') ? 'https://hendrasusanto.com/placebabies/' : null);
    enabled.push((integrations.bacon === 'bacon') ? 'https://baconmockup.com/' : null);
    enabled.push((integrations.bear === 'bear') ? 'https://placebear.com/' : null);
    enabled.push((integrations.beer === 'beer') ? 'https://placebeer.com/' : null);
    enabled.push((integrations.cage === 'cage') ? 'https://www.placecage.com/' : null);
    enabled.push((integrations.geese === 'geese') ? 'https://placegeese.com/' : null);
    enabled.push((integrations.kitten === 'kitten') ? 'https://placekitten.com/' : null);
    enabled.push((integrations.picsum === 'picsum') ? 'https://picsum.photos/' : null);
    enabled.push((integrations.pixel === 'pixel') ? 'http://lorempixel.com/' : null);

    // Filter out empty elements.
    enabled = enabled.filter(Boolean);
    // In case nothing was selected, we default to kittens.
    if (enabled.length === 0) {
      enabled.push('https://placekitten.com/');
    }

    // Replace every image element on the page it's src with a random image.
    jQuery('img').each(function () {
      let image_width = jQuery(this).width();
      let image_height = jQuery(this).height();
      let source = enabled[Math.floor(Math.random() * enabled.length)];
      jQuery(this).attr('src', source + image_width + '/' + image_height);
    });
  }
};
