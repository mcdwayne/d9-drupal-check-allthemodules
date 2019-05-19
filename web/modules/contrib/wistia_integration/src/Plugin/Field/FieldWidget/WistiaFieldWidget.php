<?php

namespace Drupal\wistia_integration\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'wistia_field_widget' widget.
 *
 * @FieldWidget(
 *   id = "wistia_field_widget",
 *   label = @Translation("Wistia field widget"),
 *   field_types = {
 *     "wistia_video_field"
 *   }
 * )
 */
class WistiaFieldWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'height' => 360,
      'width' => 640,
      'projectId' => '',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = [];

    $elements['height'] = [
      '#type' => 'number',
      '#title' => $this->t('Height'),
      '#default_value' => $this->getSetting('height'),
      '#required' => TRUE,
      '#min' => 1,
    ];

    $elements['width'] = [
      '#type' => 'number',
      '#title' => $this->t('Width'),
      '#default_value' => $this->getSetting('width'),
      '#required' => TRUE,
      '#min' => 1,
    ];

    $elements['projectId'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Project Id'),
      '#default_value' => $this->getSetting('projectId'),
      '#required' => TRUE,
      '#description' => $this->t('Project id from wistia where videos should be saved.'),
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $summary[] = $this->t('Height: @height', ['@height' => $this->getSetting('height')]);
    $summary[] = $this->t('Width: @width', ['@width' => $this->getSetting('width')]);
    $summary[] = $this->t('Project Id: @project_id', ['@project_id' => $this->getSetting('projectId')]);

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $config = \Drupal::config('wistia_integration.wistiasettings');
    $token = $config->get('wistia_token');

    $element['value'] = $element + [
      '#type' => 'textfield',
      '#default_value' => isset($items[$delta]->value) ? $items[$delta]->value : NULL,
      '#maxlength' => $this->getFieldSetting('max_length'),
      '#attributes' => [
        'class' => [
          'wistia-video-id',
          'element-invisible',
        ],
      ],
      '#attached' => [
        'library' => [
          'wistia_integration/wistia-library',
        ],
        'drupalSettings' => [
          'token' => $token,
          'projectId' => $this->getSetting('projectId'),
        ],
      ],
    ];

    $height = $this->getSetting('height') . 'px';
    $width = $this->getSetting('width') . 'px';

    $element['upload_widget'] = [
      '#type' => 'inline_template',
      '#template' => '<div id="wistia_uploader" style="height:' . $height . ';width:' . $width . ';"></div>',
    ];

    return $element;
  }

}
