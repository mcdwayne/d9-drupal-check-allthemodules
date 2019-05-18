<?php

/**
 * @file
 * Definition of Drupal\efq_views\Plugin\views\argument\EntityString.
 */

namespace Drupal\efq_views\Plugin\views\argument;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\argument\String;

/**
 * String Argument handler for entity properties.
 *
 * @ingroup views_argument_handlers
 *
 * @ViewsArgument("efq_entity_string")
 */
class EntityString extends String {

  /**
   * {@inheritdoc}
   */
  function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    // We don't support glossary currently.
    unset($form['glossary']);
  }

  /**
   * {@inheritdoc}
   */
  function query($group_by = false) {
    $argument = $this->argument;
    if (!empty($this->options['transform_dash'])) {
      $argument = strtr($argument, '-', ' ');
    }

    $this->query->query->propertyCondition($this->real_field, $argument, '=');
  }

}
