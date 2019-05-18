<?php
/**
 * @file
 * Contains \Drupal\pathauto\PathautoWidget.
 */

namespace Drupal\pathauto_i18n;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\pathauto\PathautoWidget;
use Drupal\Core\Language\LanguageInterface;

/**
 * Extends the Pathauto widget.
 */
class Pathautoi18nWidget extends PathautoWidget {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);
    $entity = $items->getEntity();
    $pattern = \Drupal::service('pathauto.generator')->getPatternByEntity($entity);
    // @todo check access.
    $access = TRUE;

    if (empty($pattern) || empty($entity) || !$access) {
      return $element;
    }

    $language_field_name = 'langcode[0][value]';

    // @todo get default value.
    // $default = pathauto_i18n_get_bundle_default($entity_type, $bundle);
    $element['pathauto_i18n_status'] = [
      '#type' => 'checkbox',
      '#title' => t('Generate automatic URL alias for all languages'),
      '#description' => t('Allows you to generate aliases for all available languages.'),
      // @todo use default value.
      // '#default_value' => isset($entity->path['pathauto_i18n_status']) ? $entity->path['pathauto_i18n_status'] : $default,
      '#weight' => -0.99,
    ];
    $element['pathauto_i18n_undefined_language_tip'] = [
      '#type' => 'item',
      '#markup' => t('URL alias for "Language neutral" <strong>won\'t be created</strong>, because you use automatic alias.') . '</strong>',
      '#weight' => -0.98,
      '#states' => [
        'visible' => [
          'select[name="' . $language_field_name . '"]' => ['value' => LanguageInterface::LANGCODE_NOT_SPECIFIED],
          'input[name="path[0][pathauto]"]' => ['checked' => TRUE],
          'input[name="path[0][pathauto_i18n_status]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $element['pathauto_i18n_undefined_language_custom_tip'] = [
      '#type' => 'item',
      '#markup' => t('URL alias for "Language neutral" <strong>will be created</strong>, because you use custom alias.'),
      '#weight' => -0.98,
      '#states' => [
        'visible' => [
          'select[name="' . $language_field_name . '"]' => ['value' => LanguageInterface::LANGCODE_NOT_SPECIFIED],
          'input[name="path[0][pathauto]"]' => ['checked' => FALSE],
          'input[name="path[0][pathauto_i18n_status]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    return $element;
  }
}
