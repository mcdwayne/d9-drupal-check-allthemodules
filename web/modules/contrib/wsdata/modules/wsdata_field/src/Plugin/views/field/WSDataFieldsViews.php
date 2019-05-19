<?php

namespace Drupal\wsdata_field\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\NodeType;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Field handler for the wsdata fields module.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("wsdata_field_views")
 */
class WSDataFieldsViews extends FieldPluginBase {

  /**
   * @{inheritdoc}
   */
  public function query() {
    // Leave empty to avoid a query on this field.
  }

  /**
   * @{inheritdoc}
   */
  protected function defineOptions() {
    return parent::defineOptions();
  }

  /**
   * @{inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
  }

  /**
   * @{inheritdoc}
   */
  public function render(ResultRow $values) {
    $return = '';
    // Based on this field check if the node type has this field.
    $field_name = $this->field;
    $entity = $values->_entity;
    if ($entity->hasField($field_name)) {
      // Load the wsfield config entity.
      $wsfield_config = entity_load('wsfield_config', $field_name);
      $wscall = entity_load('wscall', $wsfield_config->wscall);

      // Get the replacements.
      $replacements = is_array($wsfield_config->replacements) ? $wsfield_config->replacements : [];

      // Create the call based on the wsfield configurations.
      $return = $wscall->call(NULL, $replacements, $wsfield_config->data, array(), $wsfield_config->returnToken, array('node' => $entity));
    }
    return $return;
  }
}
