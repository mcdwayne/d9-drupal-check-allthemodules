<?php

namespace Drupal\drd\Agent\Action\V8;

use Drupal\drd\Crypt\Base as CryptBase;
use Drupal\drd\Agent\Action\Base as ActionBase;
use Drupal\user\Entity\User;
use Psr\Log\LogLevel;

/**
 * Base class for Remote DRD Action Code for Drupal 8.
 */
abstract class Base extends ActionBase {

  /**
   * {@inheritdoc}
   */
  public static function promoteUser() {
    global $user;
    $user = User::load(1);
  }

  /**
   * {@inheritdoc}
   */
  public static function getCryptInstance($uuid) {
    $config = \Drupal::configFactory()->get('drd_agent.settings');
    $authorised = $config->get('authorised');
    if (empty($authorised[$uuid])) {
      return FALSE;
    }

    return CryptBase::getInstance(
      $authorised[$uuid]['crypt'],
      (array) $authorised[$uuid]['cryptsetting']
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function authorize($remoteSetupToken) {
    /* @var \Drupal\drd_agent\Setup $service */
    $service = \Drupal::service('drd_agent.setup');
    $service
      ->setRemoteSetupToken($remoteSetupToken)
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public static function getDbInfo() {
    return \Drupal::database()->getConnectionOptions();
  }

  /**
   * Logging if in debug mode.
   *
   * {@inheritdoc}
   */
  public static function watchdog($message, array $variables = [], $severity = 5, $link = NULL) {
    if (self::getDebugMode()) {
      if ($link) {
        $variables['link'] = $link;
      }
      \Drupal::logger('DRD Remote')->log($severity, $message, $variables);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function ott($token, $remoteSetupToken) {
    $config = \Drupal::configFactory()->getEditable('drd_agent.settings');
    $ott = $config->get('ott');
    if (!$ott) {
      self::watchdog('No OTT available', [], LogLevel::ERROR);
      return FALSE;
    }
    $config->clear('ott');
    if (empty($ott['expires']) || $ott['expires'] < \Drupal::time()->getRequestTime()) {
      self::watchdog('OTT expired', [], LogLevel::ERROR);
      return FALSE;
    }
    if (empty($ott['token']) || $ott['token'] != $token) {
      self::watchdog('Token missmatch: :local / :remote', [':local' => $ott['token'], ':remote' => $token], LogLevel::ERROR);
      return FALSE;
    }

    /* @var \Drupal\drd_agent\Setup $service */
    $service = \Drupal::service('drd_agent.setup');
    $service
      ->setRemoteSetupToken($remoteSetupToken)
      ->execute();
    self::watchdog('OTT config completed', [], LogLevel::INFO);
    return TRUE;
  }

}
