<?php

namespace Drupal\wistia_integration\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\RendererInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'wistia_field_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "wistia_field_formatter",
 *   label = @Translation("Wistia field formatter"),
 *   field_types = {
 *     "wistia_video_field"
 *   }
 * )
 */
class WistiaFieldFormatter extends FormatterBase {

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

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $summary[] = $this->t('Height: @height', ['@height' => $this->getSetting('height')]);
    $summary[] = $this->t('Width: @width', ['@width' => $this->getSetting('width')]);

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {
      $elements[$delta] = ['#markup' => $this->viewValue($item)];
    }

    return $elements;
  }

  /**
   * Generate the output appropriate for one field item.
   *
   * @param \Drupal\Core\Field\FieldItemInterface $item
   *   One field item.
   *
   * @return string
   *   The textual output generated.
   */
  protected function viewValue(FieldItemInterface $item) {
    $video_id = Html::escape($item->value);

    $height = $this->getSetting('height') . 'px';
    $width = $this->getSetting('width') . 'px';
    $element = [
      '#type' => 'inline_template',
      '#template' => '<div class="wistia_embed wistia_async_' . $video_id . '" style="height:' . $height . ';width:' . $width . ';">&nbsp;</div>',
      '#attached' => [
        'library' => [
          'wistia_integration/wistia-iframe-formatter',
        ],
      ],
    ];

    return \Drupal::service('renderer')->render($element);
  }

  /**
   * {@inheritdoc}
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, RendererInterface $renderer) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $field_definition,
      $settings,
      $label,
      $view_mode,
      $third_party_settings,
      $container->get('renderer')
    );
  }

}
