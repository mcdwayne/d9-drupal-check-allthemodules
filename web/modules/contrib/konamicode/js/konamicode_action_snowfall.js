/**
 * @file
 * JavaScript code for the Alert action.
 */

Drupal.konamicode_action_snowfall = function () {
  'use strict';
  let action_data = Drupal.get_action_field_info(drupalSettings.konamicode.actions, 'snowfall');
  if (action_data !== false) {
    // Defaults.
    let config = {
        flakeCount : action_data.konamicodeSnowfallFlakeCount || 35,
        flakeColor :  action_data.konamicodeSnowfallFlakeColor || '#ffffff',
        flakePosition: action_data.konamicodeSnowfallFlakePosition ||'absolute',
        flakeIndex: action_data.konamicodeSnowfallFlakeIndex || 999999,
        minSize : action_data.konamicodeSnowfallFlakeMinSize || 1,
        maxSize : action_data.konamicodeSnowfallFlakeMaxSize || 2,
        minSpeed : action_data.konamicodeSnowfallFlakeMinSpeed || 1,
        maxSpeed : action_data.konamicodeSnowfallFlakeMaxSpeed || 5,
        round : action_data.konamicodeSnowfallFlakeRound || false,
        shadow : action_data.konamicodeSnowfallFlakeShadow || false,
        collection : action_data.konamicodeSnowfallFlakeCollection ||false,
        collectionHeight : action_data.konamicodeSnowfallFlakeCollectionHeight || 40,
        deviceorientation : action_data.konamicodeSnowfallFlakeDeviceOrientation || false,
        image : false
    };

    if (action_data.konamicodeSnowfallFlakeUseImage === 1) {
      // TODO: In the future support a custom image upload.
      config.image = '/libraries/snowfall/assets/images/flake.png';
    }

    // Ho Ho Ho Let it snow, Let it snow, Let it snow.
    jQuery('body').snowfall(config);
  }
};
