<?php

namespace Drupal\data_tree_builder_example\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\data_tree_builder\Form\DataTreeBuilderFormBase;

/**
 * Form definition for factors.
 */
class DataTreeBuilderExampleForm extends DataTreeBuilderFormBase {

  /**
   * {@inheritdoc}
   */
  const CONFIG_NAME = 'data_tree_builder_example.config';

  /**
   * {@inheritdoc}
   */
  const AJAX_ROUTE = 'data_tree_builder_example.ajax';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'data_tree_builder_example';
  }

  /**
   * Get table fields.
   */
  protected function getTableFields($element, $values) {
    $element['percentage'] = [
      '#type' => 'number',
      '#title' => $this->t('Percentage'),
      '#title_display' => 'hide',
      '#min' => 0,
      '#step' => 0.1,
      '#size' => 15,
      '#field_suffix' => '%',
      '#default_value' => isset($values['percentage']) ? $values['percentage'] : '',
    ];

    return $element;
  }

  /**
   * Helper function to get parameters form fields.
   */
  protected function getParameterForm(array $element, array $parameters, FormStateInterface $form_state) {

    $element['some_parameter'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Some parameter'),
    ];

    // Set default values.
    foreach ($parameters as $parameter => $value) {
      if (isset($element[$parameter])) {
        $element[$parameter]['#default_value'] = $value;
      }
    }

    return $element;
  }

}
