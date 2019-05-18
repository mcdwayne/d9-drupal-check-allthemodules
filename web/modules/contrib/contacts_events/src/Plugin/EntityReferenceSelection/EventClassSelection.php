<?php

namespace Drupal\contacts_events\Plugin\EntityReferenceSelection;

use Drupal\Core\Entity\Plugin\EntityReferenceSelection\DefaultSelection;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides specific access control for event classes.
 *
 * @EntityReferenceSelection(
 *   id = "default:contacts_events_class",
 *   label = @Translation("Event class selection"),
 *   entity_types = {"contacts_events_class"},
 *   group = "default",
 *   weight = 1
 * )
 */
class EventClassSelection extends DefaultSelection {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'type' => NULL,
      'sort' => [
        'field' => 'weight',
        'direction' => 'asc',
      ],
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    // Disable custom sorting and auto creation.
    $form['sort']['#access'] = FALSE;
    $form['auto_create']['#access'] = FALSE;

    // Add our type filter.
    $form['type'] = [
      '#type' => 'select',
      '#title' => $this->t('Type'),
      '#required' => TRUE,
      '#default_value' => $this->configuration['type'],
      '#options' => [],
    ];

    $order_item_types = $this->entityManager
      ->getStorage('commerce_order_item_type')
      ->loadMultiple();
    foreach ($order_item_types as $type) {
      $form['type']['#options'][$type->id()] = $type->label();
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function buildEntityQuery($match = NULL, $match_operator = 'CONTAINS') {
    $query = parent::buildEntityQuery($match, $match_operator);

    // Filter by type, always including global.
    $types = ['global'];
    if ($this->configuration['type']) {
      $types[] = $this->configuration['type'];
    }
    $query->condition('type', $types, 'IN');

    return $query;
  }

}
