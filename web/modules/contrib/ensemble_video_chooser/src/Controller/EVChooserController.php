<?php

namespace Drupal\ensemble_video_chooser\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Chooser controller for the ensemble_video_chooser module.
 */
class EVChooserController extends ControllerBase {

  /**
   * Construct our launch page.
   */
  public function chooserLaunch() {
    global $base_url;

    $user = \Drupal::currentUser();

    $url = \Drupal::config('ensemble_video_chooser.settings')->get('ensemble_video_chooser_launch_url');
    $consumer_key = \Drupal::config('ensemble_video_chooser.settings')->get('ensemble_video_chooser_consumer_key');
    $shared_secret = \Drupal::config('ensemble_video_chooser.settings')->get('ensemble_video_chooser_shared_secret');
    $additional_params = \Drupal::config('ensemble_video_chooser.settings')->get('ensemble_video_chooser_custom_params');

    $oauth = new \OAuth($consumer_key, $shared_secret);
    $nonce = md5(microtime() . mt_rand());
    $timestamp = time();
    $oauth->setNonce($nonce);
    $oauth->setTimestamp($timestamp);

    $request_params = array(
      'oauth_version' => '1.0',
      'oauth_nonce' => $nonce,
      'oauth_timestamp' => $timestamp,
      'oauth_consumer_key' => $consumer_key,
      'oauth_callback' => 'about:blank',
      'lis_person_contact_email_primary' => $user->getEmail(),
      'lti_message_type' => 'basic-lti-launch-request',
      'lti_version' => 'LTI-1p0',
      'resource_link_id' => 'TODO',
      'tool_consumer_info_product_family_code' => 'drupal',
      'user_id' => $user->id(),
      'launch_presentation_return_url' => $base_url . '/evchooser/return',
      'custom_drupal_user_login_id' => $user->getAccountName(),
      'oauth_signature_method' => 'HMAC-SHA1',
    );

    $params = explode("\n", $additional_params);
    foreach ($params as $param) {
      $param = trim($param);
      if ($param === '') {
        continue;
      }
      $parts = explode('=', $param);
      if (count($parts) !== 2) {
        continue;
      }
      $parts[0] = trim($parts[0]);
      $parts[1] = trim($parts[1]);
      // Only set params we haven't set here.
      if (!isset($request_params[$parts[0]])) {
        $request_params[$parts[0]] = $parts[1];
      }
    }

    $signature = $oauth->generateSignature('POST', $url, $request_params);

    $request_params['oauth_signature'] = $signature;

    return array(
      '#theme' => 'evchooser_launch',
      '#launch_url' => $url,
      '#launch_data' => $request_params,
    );
  }

  /**
   * Construct our return page.
   */
  public function chooserReturn() {
    return array(
      '#theme' => 'evchooser_return',
    );
  }

}
