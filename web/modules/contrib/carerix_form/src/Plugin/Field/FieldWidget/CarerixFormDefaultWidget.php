<?php

namespace Drupal\carerix_form\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Field\WidgetInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\carerix_form\CarerixFormFieldsOpen;

/**
 * Plugin implementation of the 'carerix_form_default' widget.
 *
 * @FieldWidget(
 *   id = "carerix_form_default",
 *   label = @Translation("Default"),
 *   field_types = {
 *     "carerix_form"
 *   }
 * )
 */
class CarerixFormDefaultWidget extends WidgetBase implements WidgetInterface {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'placeholder' => '',
      'carerix_form_id' => CarerixFormFieldsOpen::NAME,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element['placeholder'] = [
      '#type' => 'textfield',
      '#title' => t('Placeholder'),
      '#default_value' => $this->getSetting('placeholder'),
      '#description' => t('Text that will be shown inside the field until a value is entered. This hint is usually a sample value or a brief description of the expected format.'),
    ];
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $placeholder = $this->getSetting('placeholder');
    if (!empty($placeholder)) {
      $summary[] = t('Placeholder: @placeholder', ['@placeholder' => $placeholder]);
    }
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $carerixForms = \Drupal::entityTypeManager()->getStorage('carerix_form')->loadMultiple();
    $options = [];
    /** @var \Drupal\carerix_form\Entity\CarerixFormInterface $carerixForm */
    foreach ($carerixForms as $carerixFormId => $carerixForm) {
      $options[$carerixFormId] = $carerixForm->label();
    }
    $element += [
      '#type' => 'fieldset',
    ];
    $element['carerix_form_id'] = [
      '#title' => $this->t('Carerix form'),
      '#type' => 'select',
      '#options' => $options,
      '#default_value' => isset($items[$delta]->carerix_form_id) ? $items[$delta]->carerix_form_id : NULL,
    ];
    $element['pub_id'] = [
      '#title' => $this->t('Carerix publication id'),
      '#type' => 'number',
      '#min' => 0,
      '#placeholder' => $this->getSetting('placeholder'),
      '#default_value' => isset($items[$delta]->pub_id) ? $items[$delta]->pub_id : NULL,
    ];
    return $element;
  }

}
