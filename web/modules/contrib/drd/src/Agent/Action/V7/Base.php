<?php

namespace Drupal\drd\Agent\Action\V7;

/**
 * Base class for Remote DRD Action Code for Drupal 7.
 */
abstract class Base extends \Drupal\drd\Agent\Action\Base {

  /**
   * {@inheritdoc}
   */
  public static function promoteUser() {
    global $user;
    $user = user_load(1);
  }

  /**
   * {@inheritdoc}
   */
  public static function getCryptInstance($uuid) {
    $authorised = variable_get('drd_agent_authorised', array());
    if (empty($authorised[$uuid])) {
      return FALSE;
    }

    return \Drupal\drd\Crypt\Base::getInstance(
      $authorised[$uuid]['crypt'],
      (array) $authorised[$uuid]['cryptsetting']
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function authorize($remoteSetupToken) {
    include_once drupal_get_path('module', 'drd_agent') . '/drd_agent.admin.inc';
    drd_agent_setup($remoteSetupToken);
  }

  /**
   * {@inheritdoc}
   */
  public static function getDbInfo() {
    return \Database::getConnectionInfo();
  }

  /**
   * {@inheritdoc}
   */
  public static function watchdog($message, array $variables = array(), $severity = 5, $link = NULL) {
    if (self::getDebugMode()) {
      watchdog('DRD Remote', $message, $variables, $severity, $link);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function ott($token, $remoteSetupToken) {
    $ott = variable_get('drd_agent_ott', FALSE);
    if (!$ott) {
      self::watchdog('No OTT available', array(), WATCHDOG_ERROR);
      return FALSE;
    }
    variable_del('drd_agent_ott');
    if (empty($ott['expires']) || $ott['expires'] < REQUEST_TIME) {
      self::watchdog('OTT expired', array(), WATCHDOG_ERROR);
      return FALSE;
    }
    if (empty($ott['token']) || $ott['token'] != $token) {
      self::watchdog('Token missmatch: !local / !remote', array('!local' => $ott['token'], '!remote' => $token), WATCHDOG_ERROR);
      return FALSE;
    }

    include_once drupal_get_path('module', 'drd_agent') . '/drd_agent.admin.inc';
    drd_agent_setup($remoteSetupToken);
    self::watchdog('OTT config completed', array(), WATCHDOG_INFO);
    return TRUE;
  }

}
