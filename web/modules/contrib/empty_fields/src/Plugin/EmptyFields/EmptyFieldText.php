<?php

namespace Drupal\empty_fields\Plugin\EmptyFields;

use Drupal\empty_fields\EmptyFieldPluginBase;

/**
 * Defines EmptyFieldText.
 *
 * @EmptyField(
 *   id = "text",
 *   title = @Translation("Display Custom Text")
 * )
 */
class EmptyFieldText extends EmptyFieldPluginBase  {

  /**
   * {@inheritdoc}
   */
  public function defaults() {
    return [
      'empty_text' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function form($context) {
    $form['empty_text'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Display Custom Text'),
      '#default_value' => isset($this->options['empty_text']) ? $this->options['empty_text'] : '',
      '#description' => $this->t('Display text if the field is empty.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function react($context) {
    $args = [
      $context['entity_type'] => $context['entity'],
      'user' => \Drupal::currentUser(),
    ];
    $text = \Drupal::token()
      ->replace($this->options['empty_text'], $args, ['clear' => TRUE]);
    return ['#markup' => $text];
  }

  /**
   * {@inheritdoc}
   */
  public function summaryText() {
    return $this->t('Empty Text: @empty_text', ['@empty_text' => $this->options['empty_text']]);
  }

}
