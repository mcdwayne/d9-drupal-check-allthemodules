<?php

namespace Drupal\vimeo_embed_field\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'vimeo' widget.
 *
 * @FieldWidget(
 *   id = "vimeo",
 *   label = @Translation("Vimeo field widget"),
 *   field_types = {
 *     "vimeo"
 *   }
 * )
 */
class VimeoFieldWidget extends WidgetBase {

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
      '#title' => $this->t('Size of textfield'),
      '#default_value' => $this->getSetting('size'),
      '#required' => TRUE,
      '#min' => 1,
    ];
    $elements['placeholder'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Placeholder'),
      '#default_value' => $this->getSetting('placeholder'),
      '#description' => $this->t('Text that will be shown inside the field until a value is entered. This hint is usually a sample value or a brief description of the expected format.'),
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    $summary[] = $this->t('Textfield size: @size', ['@size' => $this->getSetting('size')]);
    if (!empty($this->getSetting('placeholder'))) {
      $summary[] = $this->t('Placeholder: @placeholder', ['@placeholder' => $this->getSetting('placeholder')]);
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {

    $element += [
      '#type' => 'fieldset',
    ];
    $element['vimeo_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Vimeo Video URL'),
      '#default_value' => isset($items[$delta]->vimeo_url) ? $items[$delta]->vimeo_url : NULL,
      '#size' => $this->getSetting('size'),
      '#placeholder' => $this->getSetting('placeholder'),
      '#maxlength' => '255',
      '#description' => $this->t('Enter vimeo video URL here'),
      '#element_validate' => [[$this, 'validatevimeourl']],
    ];

    return $element;
  }

  /**
   * Validating the vimeo url.
   *
   * {@inheritdoc}
   */
  public function validatevimeourl(&$element, FormStateInterface $form_state, array &$form) {
    $vimeo_url = $element['#value'];
    $id = vimeo_embed_field_get_vimeo_id_from_vimeo_url($vimeo_url);
    if (($id['status'] == 1) && (!empty($id) && (!empty($vimeo_url)))) {
      return TRUE;
    }
    elseif (!empty($vimeo_url)) {
      $form_state->setError($element, $this->t('Enter valid vimeo video URL'));
    }

  }

}
