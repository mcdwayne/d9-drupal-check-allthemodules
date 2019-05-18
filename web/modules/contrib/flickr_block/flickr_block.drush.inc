<?php

/**
 * @file
 * Contains flickr_block.drush.inc.
 */

use Drupal\flickr_block\FlickrAPI;

/**
 * Implements hook_drush_command().
 */
function flickr_block_drush_command() {
  $items = [];
  $items['flickrb-config'] = [
    'description' => 'Set Flickr configuration',
    'arguments' => [
      'flickr_api_key' => '',
      'flickr_user_id' => '',
    ],
    'aliases' => ['flickrb:config'],
  ];
  $items['flickrb-generate'] = [
    'description' => 'Generate a Fickr Block ina a region for a theme',
    'arguments' => [
      'label' => 'Flickr Block',
      'region' => 'footer',
      'theme' => 'bartik',
    ],
    'aliases' => ['flickrb:generate'],
  ];
  return $items;
}

/**
 * Call back function drush_flickr_block_flickrb_generate().
 */
function drush_flickr_block_flickrb_generate(
  $label,
  $region,
  $theme) {
  $config = \Drupal::config('flickr_block.config');

  $blockEntityManager = \Drupal::service('entity.manager')
    ->getStorage('block');

  $block = $blockEntityManager->create(
    [
      'id' => 'flickr_block_' . time(),
      'plugin' => 'flickr_block',
      'settings' => [
        'label' => $label,
        'flickr_api_key' => $config->get('flickr_api_key'),
        'flickr_user_id' => $config->get('flickr_user_id'),
      ],
      'theme' => $theme,
    ]
  );

  $block->setRegion($region);
  $block->save();

  drush_print('Flickr Block ' . $label . ' is now in ' . $region);
}

/**
 * Call back function drush_flickr_block_flickrb_config().
 */
function drush_flickr_block_flickrb_config(
  $flickr_api_key = '',
  $flickr_user_id = '') {

  $config = \Drupal::configFactory()
    ->getEditable('flickr_block.config');

  $flick = new FlickrAPI();
  $params = $flick->generateParams([
    'flickr_api_key' => $flickr_api_key,
    'flickr_user_id' => $flickr_user_id,
    'flickr_number_photos' => 1,
    'flickr_photoset_id' => NULL,
  ]);

  $response = $flick->call($params);
  if (!$response) {
    drush_print('An error has occurred whit the Flickr API response.');
    return FALSE;
  }
  else {
    if (isset($response['stat']) && $response['stat'] == 'fail') {
      drush_print($response['message'] .
        ' [' . $response['code'] . ']');
      return FALSE;
    }
  }

  $config
    ->set('flickr_api_key', $flickr_api_key)
    ->set('flickr_user_id', $flickr_user_id)
    ->save();

  drush_print('Flickr Block is configured');
}
