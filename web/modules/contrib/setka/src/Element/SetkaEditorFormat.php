<?php

namespace Drupal\setka_editor\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\filter\Element\TextFormat;

/**
 * Provides a Setka Editor text format render element.
 *
 * @RenderElement("setka_editor_format")
 */
class SetkaEditorFormat extends TextFormat {

  /**
   * {@inheritdoc}
   */
  public static function processFormat(&$element, FormStateInterface $form_state, &$complete_form) {
    $element['#type'] = 'text_format';
    $parentFormat = parent::processFormat($element, $form_state, $complete_form);
    unset($parentFormat['format']);
    return $parentFormat;
  }

}
