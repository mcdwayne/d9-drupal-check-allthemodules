<?php

namespace Drupal\friends\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;

/**
 * Defines dynamic local tasks.
 */
class DynamicLocalTasks extends DeriverBase {

  /**
   * A key => value pair of the allowed friends Types.
   *
   * @var array
   */
  protected $friendsTypes;

  /**
   * Constructs a new DynamicLocalTasks object.
   */
  public function __construct() {
    $this->friendsTypes = \Drupal::service('friends.default')->getAllowedTypes();
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $userId = \Drupal::routeMatch()->getRawParameter('user');
    foreach ($this->friendsTypes as $key => $value) {
      $this->derivatives['friends.requests:' . $key] = $base_plugin_definition;
      $this->derivatives['friends.requests:' . $key]['title'] = t('Add as a @type', ['@type' => $value]);
      $this->derivatives['friends.requests:' . $key]['route_name'] = 'friends.friends_api_controller_request';
      $this->derivatives['friends.requests:' . $key]['route_parameters'] = [
        'user' => $userId,
        'type' => $key,
      ];

      $this->derivatives['friends.requests:' . $key]['options'] = [
        'attributes' => [
          'id' => 'friends-api-add-as--' . $key,
          'class' => [
            'use-ajax',
          ],
        ],
      ];

      $this->derivatives['friends.requests:' . $key]['cache_tags'] = [
        'friends:add:' . $userId . ':type:' . $key,
      ];
    }

    return $this->derivatives;
  }

}
