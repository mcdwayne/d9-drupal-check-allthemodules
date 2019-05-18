<?php
/**
 * @file
 */

namespace Drupal\flashpoint_course\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\group\Entity\GroupInterface;


/**
 * Class FlashpointCourseController
 * @package Drupal\flashpoint_lrs_client\Controller
 */
class FlashpointCourseController extends ControllerBase {

  public static function enroll(GroupInterface $group) {
    return ['#type' => 'markup', '#markup' => 'it works'];
  }

  public static function resetOwnLRSRecords($group) {
    $config_data = \Drupal::configFactory()->get('flashpoint.settings')->getRawData();

    $plugin_manager = \Drupal::service('plugin.manager.flashpoint_lrs_client');
    $plugin_definitions = $plugin_manager->getDefinitions();
    $plugin = isset($plugin_definitions[$config_data['lrs_client']]['class']) ? $plugin_definitions[$config_data['lrs_client']]['class'] : 'default';

    $account = \Drupal::currentUser();
    $reset = $plugin::resetCourse($account, $group, $config_data);

    drupal_set_message('Your course activities have been reset.');

    $return = '/group/' . $group;

    $url = Url::fromUserInput($return);
    $response = new RedirectResponse($url->toString());
    return $response;
  }

  public static function resetAnyLRSRecords($group, $user) {
    $config_data = \Drupal::configFactory()->get('flashpoint.settings')->getRawData();

    $plugin_manager = \Drupal::service('plugin.manager.flashpoint_lrs_client');
    $plugin_definitions = $plugin_manager->getDefinitions();
    $plugin = isset($plugin_definitions[$config_data['lrs_client']]['class']) ? $plugin_definitions[$config_data['lrs_client']]['class'] : 'default';

    $reset = $plugin::resetCourse($user, $group, $config_data);

    drupal_set_message('Your course activities have been reset.');

    $return = '/group/' . $group;
    $url = Url::fromUserInput($return);
    $response = new RedirectResponse($url->toString());
    return $response;
  }

}