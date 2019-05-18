<?php

namespace Drupal\flashpoint_lrs_client\Plugin\flashpoint_lrs_client;

use Drupal\Core\Plugin\PluginBase;
use Drupal\flashpoint_lrs_client\FlashpointLRSClientInterface;
use Drupal\spectra_connect\SpectraConnectUtilities;

/**
 * @FlashpointLRSClient(
 *   id = "default",
 *   label = @Translation("Default LRS Client"),
 * )
 */
class DefaultClient extends PluginBase implements FlashpointLRSClientInterface {

  /**
   * @return string
   *   A string description.
   */
  public function description()
  {
    return $this->t('Default plugin: records passes only.');
  }

  /**
   * As a default, the only event we are recording is a pass event, so any event will trigger a pass event.
   *
   * @param $account
   * @param $entity
   * @param $event_data
   */
  public static function recordEvent($account, $entity, $event_data, $lrs_settings) {
    if (isset($lrs_settings['lrs_connector'])) {
      $event_data['type'] = 'flashpoint_lrs';
      foreach (['actor', 'object', 'context'] as $item) {
        // If the source is already set, do not override
        if (isset($event_data[$item]) && is_array($event_data[$item]) && !isset($event_data[$item]['source'])) {
          $event_data[$item]['source'] = $_SERVER['SERVER_NAME'];
        }
        // If the item is not an array (name only), add the context
        elseif (isset($event_data[$item]) && !is_array($event_data[$item])) {
          $name = $event_data[$item];
        }
      }
      $status = SpectraConnectUtilities::spectraPost($lrs_settings['lrs_connector'], $event_data);
    }
  }

  /**
   * Resets a module course.
   *
   * @param $account
   * @param $course_id
   * @param $lrs_settings
   */
  public static function resetCourse($account, $course_id, $lrs_settings) {
    if (isset($lrs_settings['lrs_connector'])) {
      $account_id = is_numeric($account) ? $account : $account->id();
      $del_data = [
        'search' => [
          'actor' => [
            'source_id' => $account_id,
            'source' => $_SERVER['SERVER_NAME'],
            'type' => 'user',
          ],
          'context' => [
            'source_id' => $course_id,
            'source' => $_SERVER['SERVER_NAME'],
            'type' => 'group',
          ],
        ],
      ];
      $status = SpectraConnectUtilities::spectraDelete($lrs_settings['lrs_connector'], $del_data);
    }
  }

  /**
   * Checks for whether someone has passed a given course or component.
   * @param $account
   * @param $entity
   * @return bool
   */
  public static function checkPassStatus($account, $entity, $lrs_settings) {
    if (isset($lrs_settings['lrs_connector'])) {
      if ($entity->getEntityTypeId() !== 'group') {
        $spectra_params = [
          'actor' => [
            'source_id' => $account->id(),
            'source' => $_SERVER['SERVER_NAME'],
            'name' => $account->getAccountName(),
            'type' => 'user',
          ],
          'object' => [
            'source_id' => $entity->id(),
            'source' => $_SERVER['SERVER_NAME'],
            'name' => $entity->label(),
            'type' => $entity->getEntityTypeId(),
          ],
        ];
        $status = SpectraConnectUtilities::spectraGet($lrs_settings['lrs_connector'], $spectra_params);
        if ($status && $status->getStatusCode() === 200) {
          return empty(json_decode($status->getBody()->getContents(), TRUE)) ? FALSE : TRUE;
        }
        \Drupal::messenger()->addError('We are having trouble contacting the LRS Server. Please inform the site administrator if the problem persists.');
        return FALSE;
      }
    }
    else {

    }
    \Drupal::messenger()->addError('Flashpoint LRS has not been set up. Please inform the site administrator.');
    return FALSE;
  }
}