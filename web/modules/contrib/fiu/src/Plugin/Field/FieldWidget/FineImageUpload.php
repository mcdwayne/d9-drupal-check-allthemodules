<?php

namespace Drupal\fiu\Plugin\Field\FieldWidget;

use Drupal\image\Plugin\Field\FieldWidget\ImageWidget;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;

/**
 * Plugin implementation of the 'fine_image' widget.
 *
 * @FieldWidget(
 *   id = "fine_image",
 *   label = @Translation("Fine image upload"),
 *   field_types = {
 *     "image"
 *   }
 * )
 */
class FineImageUpload extends ImageWidget {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'fiu_show_file_name' => 'none',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    // Disable html5 validation.
    $form['#attributes']['novalidate'] = 'novalidate';

    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    $element['#fiu_show_file_name'] = $this->getSetting('fiu_show_file_name');
    $element['#preview_image_style'] = 'fine_image';
    $element['#title'] = $this->t('Add a new file');

    // Attache library.
    $form['#attached']['library'][] = 'fiu/image';

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  protected function formMultipleElements(FieldItemListInterface $items, array &$form, FormStateInterface $form_state) {
    $elements = parent::formMultipleElements($items, $form, $form_state);

    $cardinality = $this->fieldDefinition->getFieldStorageDefinition()->getCardinality();
    switch ($cardinality) {
      case FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED:
        $is_multiple = TRUE;
        break;

      default:
        $is_multiple = ($cardinality > 1);
        break;
    }

    if ($is_multiple) {
      $elements['#theme'] = 'fine_image_widget_multiple';
    }
    else {
      $elements['#theme'] = 'fine_image_widget_unitary';
    }

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public static function process($element, FormStateInterface $form_state, $form) {
    $element = parent::process($element, $form_state, $form);

    $element['#theme'] = 'fine_image_widget';

    if (isset($element['upload_button'])) {
      $element['upload_button']['#ajax']['progress']['type'] = 'fiu_progress';
    }
    if (isset($element['remove_button'])) {
      $element['remove_button']['#ajax']['progress']['type'] = 'fiu_progress';
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);

    $options = [
      'none'      => t('Nothing'),
      'file_name' => t('Only file name'),
      'file_dir'  => t('File fidectory and name'),
    ];
    $element['fiu_show_file_name'] = [
      '#title' => t('Show file name'),
      '#type' => 'select',
      '#options' => $options,
      '#default_value' => $this->getSetting('fiu_show_file_name'),
      '#description' => t('The file name will be shown under image while editing the content.'),
      '#weight' => 10,
    ];

    // Remove progress indicator.
    unset($element['progress_indicator']);

    // Remove preview image style.
    unset($element['preview_image_style']);

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    return $summary;
  }

}
