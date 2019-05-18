<?php

namespace Drupal\imagepin\Plugin\imagepin\Widget;

use Drupal\Core\Form\FormStateInterface;
use Drupal\imagepin\Plugin\WidgetBase;

/**
 * The text widget plugin.
 *
 * @Widget(
 *   id = "text",
 *   label = @Translation("Text"),
 * )
 */
class TextWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formNewElement(array &$form, FormStateInterface $form_state) {
    $element = [];

    // TODO Required fields currently don't work.
    // Form API documentation lacks here, again.
    $element['text'] = [
      '#type' => 'textfield',
      '#title' => t('Text'),
      '#required' => FALSE,
      '#weight' => 10,
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function previewContent($value) {
    return ['#markup' => '<p>' . $value['text'] . '</p>'];
  }

  /**
   * {@inheritdoc}
   */
  public function viewContent($value) {
    return ['#markup' => '<p>' . $value['text'] . '</p>'];
  }

}
