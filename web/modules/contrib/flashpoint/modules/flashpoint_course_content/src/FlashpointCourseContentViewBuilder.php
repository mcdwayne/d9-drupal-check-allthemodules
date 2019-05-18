<?php

namespace Drupal\flashpoint_course_content;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityViewBuilder;

/**
 * View Builder for flashpoint_course_content
 *
 * @ingroup entity_api
 */
class FlashpointCourseContentViewBuilder extends EntityViewBuilder {

  /**
   * {@inheritdoc}
   */
  public function view(EntityInterface $entity, $view_mode = 'full', $langcode = NULL) {
    $moduleHandler = \Drupal::service('module_handler');
    if ($moduleHandler->moduleExists('flashpoint_lrs_client')) {
      $account = \Drupal::currentUser();
      $course = $entity->getCourse();
      $spectra_params = [
        'actor' => [
          'source_id' => $account->id(),
          'name' => $account->getAccountName(),
          'type' => 'user',
        ],
        'action' => [
          'name' => 'viewed',
          'type' => 'flashpoint_course_action'
        ],
        'object' => [
          'source_id' => $entity->id(),
          'name' => $entity->label(),
          'type' => $entity->getEntityTypeId(),
        ],
        'context' => [
          'source_id' => $course->id(),
          'name' => $course->label(),
          'type' => $course->getEntityTypeId(),
        ]
      ];

      $config_data = \Drupal::configFactory()->get('flashpoint.settings')->getRawData();
      $plugin_manager = \Drupal::service('plugin.manager.flashpoint_lrs_client');
      $plugin_definitions = $plugin_manager->getDefinitions();
      $plugin = isset($plugin_definitions[$config_data['lrs_client']]['class']) ? $plugin_definitions[$config_data['lrs_client']]['class'] : 'default';
      $post = $plugin::recordEvent($account, $this, $spectra_params, $config_data);
    }
    return parent::view($entity, $view_mode, $langcode);;
  }

}
