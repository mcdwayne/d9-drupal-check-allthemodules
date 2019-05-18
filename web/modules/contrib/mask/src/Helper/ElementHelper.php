<?php

namespace Drupal\mask\Helper;

use Drupal\Core\Form\FormStateInterface;

/**
 * Helper class to process Form API elements that support the #mask attribute.
 */
class ElementHelper implements ElementHelperInterface {

  /**
   * {@inheritdoc}
   */
  public function elementInfoAlter(array &$info, array $defaults = []) {
    $defaults += [
      'value' => '',
      'reverse' => FALSE,
      'clearifnotmatch' => FALSE,
      'selectonfocus' => FALSE,
    ];
    $info['#mask'] = $defaults;
    $info['#process'][] = [__CLASS__, 'processElement'];
  }

  /**
   * {@inheritdoc}
   */
  public static function processElement(array &$element, FormStateInterface $form_state, array &$complete_form) {
    if (!empty($element['#mask']['value'])) {
      // Adds HTML attributes to the element.
      foreach ($element['#mask'] as $option => $value) {
        if (!empty($value)) {
          // Appends the option name to the attribute name.
          $attr = "data-mask-$option";

          // Except for the value, all other options are booleans.
          if ($option != 'value') {
            $value = $value ? 'true' : 'false';
          }

          // Adds the attribute.
          $element['#attributes'][$attr] = is_string($value) ? $value : (string) $value;
        }
      }

      // Attaches the JavaScript library and settings.
      $translation_config = \Drupal::config('mask.settings')->get('translation');
      $element['#attached']['drupalSettings']['mask']['translation'] = $translation_config ?: [];
      $element['#attached']['library'][] = 'mask/mask';
    }
    return $element;
  }

}
