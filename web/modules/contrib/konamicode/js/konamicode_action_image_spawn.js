/**
 * @file
 * JavaScript code for the Image Spawn action.
 */

Drupal.konamicode_action_image_spawn = function () {
  'use strict';
  let action_data = Drupal.get_action_field_info(drupalSettings.konamicode.actions, 'image_spawn');
  if (action_data !== false) {
    let images = action_data.konamicodeImageSpawnImages || '/libraries/image_spawn/assets/images/druplicon-small.png';
    let amount = action_data.konamicodeImageSpawnAmount || 500;
    let delay = action_data.konamicodeImageSpawnDelay || 10;

    jQuery('body').imageSpawn({
      'images': images,
      'amount': amount,
      'delay': delay,
    });
  }
};
