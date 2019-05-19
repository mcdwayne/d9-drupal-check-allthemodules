<?php

namespace Drupal\templating\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'module_widget_type' widget.
 *
 * @FieldWidget(
 *   id = "module_widget_type",
 *   label = @Translation("Module widget type"),
 *   field_types = {
 *     "module_field_type"
 *   }
 * )
 */
class ModuleWidgetType extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'size' => 60,
      'placeholder' => '',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = [];

    $elements['size'] = [
      '#type' => 'number',
      '#title' => t('Size of textfield'),
      '#default_value' => $this->getSetting('size'),
      '#required' => TRUE,
      '#min' => 1,
    ];
    $elements['placeholder'] = [
      '#type' => 'textfield',
      '#title' => t('Placeholder'),
      '#default_value' => $this->getSetting('placeholder'),
      '#description' => t('Text that will be shown inside the field until a value is entered. This hint is usually a sample value or a brief description of the expected format.'),
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    $summary[] = t('Textfield size: @size', ['@size' => $this->getSetting('size')]);
    if (!empty($this->getSetting('placeholder'))) {
      $summary[] = t('Placeholder: @placeholder', ['@placeholder' => $this->getSetting('placeholder')]);
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $module_handler = \Drupal::service('module_handler');
    $module_list = ($module_handler->getModuleList());
    $module_select = [];
    foreach ($module_list as $key => $mod) {
      $path = ($mod->getPath());
      if (strpos($path, 'modules/contrib') === FALSE
        && strpos($path, 'core/modules') === FALSE
        && strpos($path, 'core/profiles') === FALSE
      ) {
        $module_select[$key] = $key;
      }
    }

    $element['value'] = $element +  [
      '#type' => 'select',
      '#title' => 'Module list',
      '#options' => $module_select,
//        '#placeholder' => $this->getSetting('placeholder'),
//        '#size' => $this->getSetting('size'),
        '#default_value' => isset($items[$delta]->value) ? $items[$delta]->value : NULL,
    ];

//    $element['value'] = $element + [
//      '#type' => 'textfield',
//      '#default_value' => isset($items[$delta]->value) ? $items[$delta]->value : NULL,
//      '#size' => $this->getSetting('size'),
//      '#placeholder' => $this->getSetting('placeholder'),
//      '#maxlength' => $this->getFieldSetting('max_length'),
//    ];

    return $element;
  }

}
