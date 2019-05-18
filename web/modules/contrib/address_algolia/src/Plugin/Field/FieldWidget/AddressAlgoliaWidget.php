<?php

namespace Drupal\address_algolia\Plugin\Field\FieldWidget;

use Drupal\address\Plugin\Field\FieldWidget\AddressDefaultWidget;
use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'address_algolia' field widget.
 *
 * @FieldWidget(
 *   id = "address_algolia",
 *   label = @Translation("Address Algolia"),
 *   field_types = {
 *     "address"
 *   }
 * )
 */
class AddressAlgoliaWidget extends AddressDefaultWidget {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'use_algolia_autocomplete' => FALSE,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);
    $elements['use_algolia_autocomplete'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use autocompletion from the <a href=":library_url" target="_blank">Algolia Places library</a>.', [':library_url' => 'https://community.algolia.com/places']),
      '#default_value' => $this->getSetting('use_algolia_autocomplete'),
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $settings_summary = parent::settingsSummary();
    if ($this->getSetting('use_algolia_autocomplete')) {
      $settings_summary += [t('Algolia autocompletion enabled.')];
    }
    return $settings_summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);
    if ($this->getSetting('use_algolia_autocomplete')) {
      // Add our library to the element.
      $element['#attached']['library'][] = 'address_algolia/address_algolia.form_autocomplete';
      // Expose the field name to the javascript.
      $field_name = $this->fieldDefinition->getName();
      $field_name_id = Html::cleanCssIdentifier($field_name);
      $settings = [
        'field_name' => $field_name_id,
      ];
      $element['#attached']['drupalSettings']['address_algolia'] = $settings;
    }
    return $element;
  }

}
