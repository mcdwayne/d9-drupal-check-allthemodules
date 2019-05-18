<?php

namespace Drupal\text_with_title\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Template\Attribute;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'TextWithTitleAccordionFormatter' formatter.
 *
 * @FieldFormatter(
 *   id = "text_with_title_accordion_formatter",
 *   label = @Translation("Accordion"),
 *   field_types = {
 *     "text_with_title_field"
 *   }
 * )
 */
class TextWithTitleAccordionFormatter extends FormatterBase {

  /**
   * Overide the view method so we can wrap the result in the accordion markup.
   */
  public function view(FieldItemListInterface $items, $langcode = NULL) {
    // Default the language to the current content language.
    if (empty($langcode)) {
      $langcode = \Drupal::languageManager()
        ->getCurrentLanguage(LanguageInterface::TYPE_CONTENT)
        ->getId();
    }

    $entity = $items->getEntity();
    $this->accordion_group_id = $entity->getEntityTypeId() . '_' . $entity->bundle() . '_' . $entity->id();

    $elements = $this->viewElements($items, $langcode);

    $build = [
      '#theme' => 'text_with_title_accordion',
      '#panels' => $elements,
      '#id' => $this->accordion_group_id,
    ];
    return $build;
  }

  /**
   * Define how the field type is displayed.
   *
   * Inside this method we can customize how the field is displayed inside
   * pages.
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {

      // Generate a unique ID for this item.
      $id = $this->accordion_group_id . '--accordion-' . $delta;

      $description_attributes = [];

      // Body attributes.
      $body_attributes = [
        'id' => $id,
      ];


      // Heading attributes.
      $heading_attributes = [
        'data-toggle' => 'collapse',
      ];

      if ($this->getSetting('only_one_open')) {
        // Parent id.
        $entity = $items->getEntity();
        $parent_id = $entity->getEntityTypeId() . '_' . $entity->bundle() . '_' . $entity->id();

        $heading_attributes['data-parent'] = '#' . $parent_id;
      }

      $attributes = [];

      $elements[$delta] = [
        '#theme' => 'text_with_title_panel',
        '#heading' => [
          '#plain_text' => $item->title,
        ],
        '#heading_attributes' => new Attribute($heading_attributes),
        '#collapsible' => TRUE,
        '#collapsed' => TRUE,
        '#attributes' => new Attribute($attributes),
        '#id' => $id,
        '#target' => '#' . $id,
        '#description' => [
          'attributes' => new Attribute($description_attributes),
          'content' => '',
          'position' => 'before',
        ],
        '#body' => [
          '#type' => 'processed_text',
          '#text' => $item->text['value'],
          '#format' => $item->text['format'],
          '#langcode' => $langcode,
        ],
        '#body_attributes' => new Attribute($body_attributes),
      ];
    }

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
        'only_one_open' => TRUE,
      ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element['only_one_open'] = [
      '#title' => t('Allow only one panel to be open at any one time.'),
      '#description' => t('Disable this option to allow multiple panels to be visible at one time.'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('only_one_open'),
    ];

    return $element;
  }

}
