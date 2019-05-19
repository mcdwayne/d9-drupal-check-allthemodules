<?php

/**
 * @file
 * Definition of Drupal\social_counters\Plugin\views\field\SocialCountersConfigLabel
 */

namespace Drupal\social_counters\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\NodeType;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Field handler for config label.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("social_counters_config_label")
 */
class SocialCountersConfigLabel extends FieldPluginBase {

  /**
   * @{inheritdoc}
   */
  public function query() {
    // Leave empty to avoid a query on this field.
  }

  /**
   * @{inheritdoc}
   */
  public function render(ResultRow $values) {
    $entity = $values->_entity;
    $config = $entity->get('config');
    $label = NULL;

    if ($config->count() > 0) {
      $label = $config->entity->label();
    }

    return $label;
  }
}
