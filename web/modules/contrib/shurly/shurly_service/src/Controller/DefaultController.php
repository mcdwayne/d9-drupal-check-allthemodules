<?php

namespace Drupal\shurly_service\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Default controller for the shurly_service module.
 */
class DefaultController extends ControllerBase {

  public function shurly_service_access_api_key(Drupal\Core\Session\AccountInterface $account) {
    if ($_REQUEST['apiKey']) {
      module_load_include('inc', 'shurly_service', 'shurly_api_keys');
      $api_validation = (is_numeric(shurly_get_uid($_REQUEST['apiKey']))) ? TRUE : FALSE;
    }

    return \Drupal::currentUser()->hasPermission('Create short URLs') && $api_validation;
  }

  public function shurly_service_shorten() {
    $defaults = [
      'format' => 'json',
      // 'domain' => NULL,
    'longUrl' => '',
      'shortUrl' => NULL,
      'apiKey' => NULL,
      'func' => 'urlData',
      // function name for padded JSON
    ];
    $input = $_GET + $defaults;

    module_load_include('inc', 'shurly_service', 'shurly_api_keys');
    $uid = isset($input['apiKey']) ? shurly_get_uid($input['apiKey']) : NULL;
    $account = $uid ? \Drupal::entityTypeManager()->getStorage('user')->load($uid) : NULL;
    $access = $account->hasPermission('Create short URLs');

    if ($access) {
      // If the user doesn't have access to request a custom short URL from the
    // service, reset it to NULL.
      if (!$account->hasPermission('Request custom short URL')) {
        $input['shortUrl'] = NULL;
      }
      $data = shurly_shorten($input['longUrl'], $input['shortUrl'], $account);
    }
    else {
      $data = [
        'success' => FALSE,
        'error' => t('Invalid API key'),
      ];
    }
    shurly_service_output($data, $input);
  }

  public function shurly_service_expand() {
    $defaults = [
      'format' => 'json',
      // 'domain' => NULL,
      //'longUrl' => '',
    'shortUrl' => '',
      // 'login' => NULL,
    'apiKey' => NULL,
      'func' => 'urlData',
      // function name for padded JSON
    ];
    $input = $_GET + $defaults;

    module_load_include('inc', 'shurly_service', 'shurly_api_keys');
    $uid = isset($input['apiKey']) ? shurly_get_uid($input['apiKey']) : NULL;
    $account = $uid ? \Drupal::entityTypeManager()->getStorage('user')->load($uid) : NULL;
    $access = $account->hasPermission('Expand short URLs');

    if ($access) {
      $path = array_pop(explode('/', parse_url($input['shortUrl'], PHP_URL_PATH))); // only works with clean URLs
      $data = shurly_expand($path, $account);
    }
    else {
      $data = [
        'success' => FALSE,
        'error' => t('Invalid API key'),
      ];
    }

    shurly_service_output($data, $input);

  }

}
