<?php

namespace Drupal\just_giving\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'just_giving_widget_type' widget.
 *
 * @FieldWidget(
 *   id = "just_giving_widget_type",
 *   label = @Translation("Just Giving widget"),
 *   field_types = {
 *     "just_giving_field_type"
 *   }
 * )
 */
class JustGivingWidgetType extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'size' => 60,
      'placeholder_page_story' => '',
      'placeholder_summary_what' => '',
      'placeholder_summary_why' => '',
      ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = [];

    $elements['placeholder_page_story'] = [
      '#type' => 'textarea',
      '#title' => t('Placeholder'),
      '#default_value' => $this->getSetting('placeholder_page_story'),
      '#description' => t('Text that will be shown inside the field until a value is entered. This hint is usually a sample value or a brief description of the expected format.'),
    ];

    $elements['placeholder_summary_what'] = [
      '#type' => 'textarea',
      '#title' => t('Placeholder'),
      '#default_value' => $this->getSetting('placeholder_summary_what'),
      '#description' => t('Text that will be shown inside the field until a value is entered. This hint is usually a sample value or a brief description of the expected format.'),
    ];

    $elements['placeholder_summary_why'] = [
      '#type' => 'textarea',
      '#title' => t('Placeholder'),
      '#default_value' => $this->getSetting('placeholder_summary_why'),
      '#description' => t('Text that will be shown inside the field until a value is entered. This hint is usually a sample value or a brief description of the expected format.'),
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {

    $element = [
      '#type' => 'details',
      '#title' => t('Just Giving Content'),
      '#group' => 'advanced',
      '#weight' => 100,
    ];

    if ($this->getFieldSetting('jg_page_type') == "campaign") {
      $element['cause_id'] = [
        '#type' => 'number',
        '#group' => 'jg_group',
        '#title' => t('Campaign ID'),
        '#default_value' => isset($items[$delta]->cause_id) ? $items[$delta]->cause_id : NULL,
        '#maxlength' => 11,
      ];
    }

    $element['event_id'] = [
      '#type' => 'number',
      '#group' => 'jg_group',
      '#title' => t('Event ID'),
      '#default_value' => isset($items[$delta]->event_id) ? $items[$delta]->event_id : NULL,
      '#maxlength' => 11,
    ];

    $element['page_story'] = [
      '#type' => 'textarea',
      '#title' => t('Page Story'),
      '#group' => 'jg_group',
      '#default_value' => isset($items[$delta]->page_story) ? $items[$delta]->page_story : NULL,
      '#size' => $this->getSetting('size'),
      '#placeholder' => $this->getSetting('placeholder_page_story'),
      '#maxlength' => $this->getFieldSetting('max_length'),
    ];

    $element['page_summary_what'] = [
      '#type' => 'textarea',
      '#title' => t('Page Summary What'),
      '#group' => 'jg_group',
      '#default_value' => isset($items[$delta]->page_summary_what) ? $items[$delta]->page_summary_what : NULL,
      '#size' => $this->getSetting('size'),
      '#placeholder' => $this->getSetting('placeholder_summary_what'),
      '#maxlength' => $this->getFieldSetting('short_length'),
    ];

    $element['page_summary_why'] = [
      '#type' => 'textarea',
      '#title' => t('Page Summary Why'),
      '#group' => 'jg_group',
      '#default_value' => isset($items[$delta]->page_summary_why) ? $items[$delta]->page_summary_why : NULL,
      '#size' => $this->getSetting('size'),
      '#placeholder' => $this->getSetting('placeholder_summary_why'),
      '#maxlength' => $this->getFieldSetting('short_length'),
    ];

    $element['suggested_target_amount'] = [
      '#type' => 'number',
      '#group' => 'jg_group',
      '#title' => t('Suggest Target Amount'),
      '#default_value' => isset($items[$delta]->suggested_target_amount) ? $items[$delta]->suggested_target_amount : NULL,
      '#maxlength' => '12',
    ];

    $element['charity_id'] = [
      '#type' => 'hidden',
      '#group' => 'jg_group',
      '#title' => t('Charity ID'),
      '#default_value' => isset($items[$delta]->charity_id) ? $items[$delta]->charity_id : NULL,
      '#maxlength' => 11,
    ];

    $element['page_type'] = [
      '#type' => 'hidden',
      '#group' => 'jg_group',
      '#title' => t('Page Type'),
      '#default_value' => isset($items[$delta]->page_type) ? $items[$delta]->page_type : NULL,
      '#placeholder' => $this->getSetting('placeholder_summary_why'),
      '#maxlength' => $this->getFieldSetting('short_length'),
    ];

    return $element;
  }

}
