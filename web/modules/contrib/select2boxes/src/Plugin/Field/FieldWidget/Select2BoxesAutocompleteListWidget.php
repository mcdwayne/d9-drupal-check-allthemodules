<?php

namespace Drupal\select2boxes\Plugin\Field\FieldWidget;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\OptionsSelectWidget;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\select2boxes\MinSearchLengthTrait;

/**
 * Class Select2BoxesAutocompleteList.
 *
 * @FieldWidget(
 *   id = "select2boxes_autocomplete_list",
 *   label = @Translation("Select2 boxes"),
 *   description = @Translation("An autocomplete list field."),
 *   field_types = {
 *     "list_integer",
 *     "list_float",
 *     "list_string",
 *     "language_field",
 *     "language",
 *     "country",
 *   },
 *   multiple_values = TRUE
 * )
 *
 * @package Drupal\select2boxes\Plugin\Field\FieldWidget
 */
class Select2BoxesAutocompleteListWidget extends OptionsSelectWidget {
  use MinSearchLengthTrait;

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    // Since country field options are built in the different way
    // - we have to specify options array manually
    // using country manager service.
    // Same thing for language field.
    if ($this->fieldDefinition->getType() === 'country') {
      $this->setCountriesList();
    }
    elseif ($this->fieldDefinition->getType() === 'language') {
      $this->setLanguagesList();
    }
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    // Add required attributes for the select2 autocomplete.
    $element['#attributes'] = [
      // Disable core autocomplete.
      'data-jquery-once-autocomplete'         => 'true',
      'data-select2-autocomplete-list-widget' => 'true',
      'class'                                 => ['select2-widget'],
    ];
    // Remove "- None -" option in case of multiple values field setting.
    if ($this->fieldDefinition->getFieldStorageDefinition()->isMultiple()) {
      unset($element['#options']['_none']);
      $element['#attributes']['data-select2-multiple'] = 'true';
    }

    // Small workaround for the core's built-in language field.
    if ($this->fieldDefinition->getType() === 'language') {
      // Rebuild options list using full list of the language options.
      $languages = \Drupal::languageManager()
        ->getLanguages(LanguageInterface::STATE_ALL);
      $element['#options'] = array_map(function ($language) {
        /** @var \Drupal\Core\Language\LanguageInterface $language */
        return $language->getName();
      }, $languages);
      // Set the default value from the editing entity.
      if ($items->getEntity() instanceof EntityInterface) {
        $element['#default_value'] = $items->getEntity()->language()->getId();
      }
    }

    // Handle country icons if needed for Flag's sub-modules.
    $this->includeFlagsIcons($element);
    // Set the additional attribute for limiting
    // the search input visibility if specified.
    $this->limitSearchByMinLength($element['#attributes']);
    // Attach library.
    $element['#attached']['library'][] = 'select2boxes/widget';
    return $element;
  }

  /**
   * Include flags icons to the options.
   *
   * If the "Include flag icons" option is enabled.
   *
   * @param array &$element
   *   Element's render array.
   */
  protected function includeFlagsIcons(array &$element) {
    // Include flags is only possible if flags module enabled,
    // field type is one of the 'language_field', 'language', 'country'
    // and an appropriate setting in the fields widget form has been enabled.
    $types = ['language_field', 'language', 'country'];
    if (\Drupal::moduleHandler()->moduleExists('flags')
      && in_array($this->fieldDefinition->getType(), $types)
      && $this->getThirdPartySetting('select2boxes', 'enable_flags') == '1'
    ) {
      // Create a map of country or language dependent classes.
      $flags = [];
      $mapper = $this->fieldDefinition->getType() == 'country'
        ? \Drupal::service('flags.mapping.country')
        : \Drupal::service('flags.mapping.language');
      foreach (array_keys($element['#options']) as $key) {
        $flags[$key] = [
          'flag',
          'flag-' . $mapper->map($key),
          $mapper->getExtraClasses()[0],
        ];
      }
      // Merge these values to have all countries
      // and languages flags in the same place to prevent missing flags icons.
      if (!isset($element['#attached']['drupalSettings']['flagsClasses'])) {
        $element['#attached']['drupalSettings']['flagsClasses'] = [];
      }
      $element['#attached']['drupalSettings']['flagsClasses'] += $flags;
      $element['#attached']['drupalSettings']['flagsFields'][$this->fieldDefinition->getName()] = TRUE;
      $element['#attached']['library'][] = 'flags/flags';
    }
  }

  /**
   * Specifies the languages list options if needed.
   */
  protected function setLanguagesList() {
    if (!isset($this->options)) {
      // Specify the default "none" option in case of using single-value widget.
      $this->options = ['_none' => $this->t('- None -')];
      // Add the languages list using language manager service.
      $this->options += array_map(function ($language) {
        /** @var \Drupal\Core\Language\LanguageInterface $language */
        return $language->getName();
      }, \Drupal::languageManager()->getLanguages());
    }
  }

  /**
   * Specifies the countries list options if needed.
   */
  protected function setCountriesList() {
    if (!isset($this->options)) {
      // Specify the default "none" option in case of using single-value widget.
      $this->options = ['_none' => $this->t('- None -')];
      // Add the countries list using country manager service.
      $this->options += \Drupal::service('country_manager')->getList();
    }
  }

}
