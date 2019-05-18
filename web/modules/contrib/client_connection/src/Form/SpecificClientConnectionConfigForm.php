<?php

namespace Drupal\client_connection\Form;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;

/**
 * Class SpecificClientConnectionForm.
 */
class SpecificClientConnectionConfigForm extends ClientConnectionConfigForm {

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    $actions['delete']['#access'] = FALSE;
    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $form['label']['#access'] = FALSE;
    $form['id']['#access'] = FALSE;

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityFromRouteMatch(RouteMatchInterface $route_match, $entity_type_id) {
    /** @var \Drupal\client_connection\Entity\Storage\ClientConnectionConfigStorageInterface $storage */
    $storage = $this->entityTypeManager->getStorage($entity_type_id);

    $channel_id = $route_match->getRouteObject()->getDefault('client_connection_channel') ?: 'site';

    $values = [
      'pluginId' => $route_match->getRouteObject()->getDefault('client_connection_plugin'),
      'instanceId' => $route_match->getRouteObject()->getDefault('client_connection_id'),
      'channels' => [$channel_id],
    ];

    // Get entity via id pair if it exists.
    if (!is_null($values['pluginId']) && !is_null($values['instanceId'])) {
      $entity_id = $storage->findId($values['pluginId'], $values['instanceId'], $channel_id);

      if (!is_null($entity_id) && $entity = $storage->load($entity_id)) {
        return $entity;
      }
    }

    $values['label'] = $route_match->getRouteObject()->getDefault('_title');

    return $storage->create($values);
  }

  /**
   * {@inheritdoc}
   */
  protected function getRedirectUrl(EntityInterface $entity) {
    return new Url('client_connection.settings');
  }

}
