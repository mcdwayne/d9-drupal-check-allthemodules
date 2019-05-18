<?php

/**
 * @file
 * Token integration for the PuRest module.
 */

use Drupal\Core\Render\BubbleableMetadata;

/**
 * Implements hook_token_info().
 */
function purest_token_info() {
  $info = [];

  $info['types']['purest'] = [
    'name' => t('Purest'),
    'description' => t('Tokens related to the Purest module'),
  ];

  $info['tokens']['purest']['front_end_url'] = [
    'name' => t('Front End URL'),
    'description' => t('The front end URL of the site'),
  ];

  if (\Drupal::moduleHandler()->moduleExists('purest_recaptcha')) {
    $info['tokens']['purest']['recaptcha_client_id'] = [
      'name' => t('Recaptcha Client ID'),
      'description' => t('The client ID for the Recaptcha service'),
    ];

    $info['tokens']['purest']['recaptcha_secret_key'] = [
      'name' => t('Recaptcha Secret Key'),
      'description' => t('The secret key for the Recaptcha service'),
    ];
  }

  if (\Drupal::moduleHandler()->moduleExists('purest_user')) {
    $info['tokens']['purest']['register_url'] = [
      'name' => t('Register URL'),
      'description' => t('The register URL to be used in system emails'),
    ];

    $info['tokens']['purest']['activate_url'] = [
      'name' => t('Activate URL'),
      'description' => t('The active URL to be used in system emails'),
    ];

    $info['tokens']['purest']['login_url'] = [
      'name' => t('Login URL'),
      'description' => t('The login URL to be used in system emails'),
    ];

    $info['tokens']['purest']['reset_url'] = [
      'name' => t('Rest URL'),
      'description' => t('The reset URL to be used in system emails'),
    ];

    $info['tokens']['purest']['change_url'] = [
      'name' => t('Change URL'),
      'description' => t('The password change URL to be used in system emails'),
    ];
  }

  return $info;
}

/**
 * Implements hook_tokens().
 */
function purest_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  $replacements = [];

  if ($type == 'purest') {
    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'recaptcha_client_id':
        case 'recaptcha_secret_key':
          $config = \Drupal::service('config.factory')->get('purest_recaptcha.settings');
          $replacements[$original] = $config->get($name);
          break;

        case 'register_url':
        case 'activate_url':
        case 'login_url':
        case 'reset_url':
        case 'change_url':
          $purest_config = \Drupal::service('config.factory')->get('purest.settings');
          $purest_user_config = \Drupal::service('config.factory')->get('purest_user.settings');

          global $base_url;
          $url = $base_url;
          $key = explode('_', $name)[0];

          if ($front_end_url = $purest_config->get('front_end_url')) {
            $url = $front_end_url;
          }

          if ($node_id = $purest_user_config->get($key . '_node')) {
            $url .= \Drupal::service('path.alias_manager')->getAliasByPath('/node/' . $node_id);
          }
          elseif ($node_path = $purest_user_config->get($key . '_path')) {
            $url .= $node_path;
          }

          $replacements[$original] = $url;
          break;

        default:
          $config = \Drupal::service('config.factory')->get('purest.settings');
          $replacements[$original] = $config->get($name);
      }
    }
  }

  return $replacements;
}
