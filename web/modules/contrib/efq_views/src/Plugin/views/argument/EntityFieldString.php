<?php

/**
 * @file
 * Definition of Drupal\efq_views\Plugin\views\argument\EntityFieldString.
 */

namespace Drupal\efq_views\Plugin\views\argument;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\argument\String;

/**
 * String argument handler for fields.
 *
 * @ingroup views_argument_handlers
 *
 * @ViewsArgument("efq_field_string")
 */
class EntityFieldString extends String {

  /**
   * {@inheritdoc}
   */
  function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::options_form($form, $form_state);
    // We don't support glossary currently.
    unset($form['glossary']);
  }

  /**
   * {@inheritdoc}
   */
  function query() {
    $argument = $this->argument;
    if (!empty($this->options['transform_dash'])) {
      $argument = strtr($argument, '-', ' ');
    }

    $this->query->query->fieldCondition($this->definition['field_name'], $this->real_field, $argument, '=');
  }

}