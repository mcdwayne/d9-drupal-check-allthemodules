<?php

namespace Drupal\focal_point_focus\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\focal_point\FocalPointManager as FocalPoint;
use Drupal\image\Plugin\Field\FieldFormatter\ImageFormatterBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'focal_point_focus' formatter.
 *
 * @FieldFormatter(
 *   id = "focal_point_focus",
 *   label = @Translation("Focal Point Focus"),
 *   field_types = {
 *     "image"
 *   },
 *   quickedit = {"editor" = "disabled"}
 * )
 */
class FocalPointFocusFormatter extends ImageFormatterBase implements ContainerFactoryPluginInterface {

  /**
   * Constructs an ImageFormatter object.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Third party settings.
   *
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
  }

  /**
   * Creates an instance of the plugin.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The container to pull out services used in the plugin.
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   *
   * @return static
   *   Returns an instance of this plugin.
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings']
    );
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $first_only = $this->getSetting('first-only') == FALSE ? $this->t('Show all') : $this->t('First only.');
    $summary[] = $this->t('First Image Only: @value', ['@value' => $first_only]);
    $title = $this->getSetting('title') == TRUE ? $this->t('Mute') : $this->t('Show Title');
    $summary[] = $this->t('Mute Title: @value', ['@value' => $title]);
    $summary[] = $this->t('Display Height: @valuepx', ['@value' => $this->getSetting('height')]);
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'height' => '300',
      'title' => FALSE,
      'first-only' => FALSE,
    ] + parent::defaultSettings();
  }

  /**
   * Settings form.
   *
   * @param array $form
   *   Form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state array.
   *
   * @return mixed
   *   Returns mixed data.
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {

    $element['item'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => 'clearfix',
      ],
    ];
    $element['item']['first-only'] = [
      '#title' => $this->t('Only show first image (if multivalue)'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('first-only'),
    ];
    $element['item']['title'] = [
      '#title' => $this->t('Mute Title (figcaption)'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('title'),
    ];
    $element['height'] = [
      '#title' => $this->t('Display Height'),
      '#type' => 'number',
      '#field_suffix' => 'px',
      '#default_value' => $this->getSetting('height'),
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = [];
    $properties = [];
    $first_only = $this->getSetting('first-only');
    $title = $this->getSetting('title');
    $final_height = $this->getSetting('height');
    $files = $this->getEntitiesToView($items, $langcode);

    // Early opt-out if the field is empty.
    if (empty($files)) {
      return [];
    }

    // Carry basic image info.
    foreach ($items as $delta => $item) {
      if ($first_only && $delta != 0) {
        continue;
      }
      $this_image = $item->getValue();
      $properties[$delta]['target_id'] = $this_image['target_id'];
      $properties[$delta]['width'] = $this_image['width'];
      $properties[$delta]['height'] = $this_image['height'];
      $properties[$delta]['alt'] = $this_image['alt'];
      $properties[$delta]['title'] = $title == FALSE ? $this_image['title'] : '';
    }

    foreach ($files as $delta => $file) {
      if ($first_only && $delta != 0) {
        continue;
      }
      $properties[$delta]['display_height'] = $final_height;
      $file_uri = $file->getFileUri();
      $properties[$delta]['src'] = file_create_url($file_uri);
      $crop = FocalPoint::getCropEntity($file, 'focal_point');
      if ($crop) {
        $focal_point = $crop->position();
        $properties[$delta]['focal_point_x'] = $focal_point['x'];
        $properties[$delta]['focal_point_y'] = $focal_point['y'];
        $properties[$delta]['x'] = (($properties[$delta]['focal_point_x'] / $properties[$delta]['height']) - .5) * 2;
        $properties[$delta]['y'] = (($properties[$delta]['focal_point_y'] / $properties[$delta]['width']) - .5) * -2;
      }
      else {
        /* No crop!  formatter has been added to image that is not a crop. therefore: render with x|y in image center.. */
        $properties[$delta]['focal_point_x'] = 0;
        $properties[$delta]['focal_point_y'] = 0;
      }
      $element[$delta] = [
        '#theme' => 'focal_point_focus',
        '#focalpoint' => $properties[$delta],
      ];
    }

    return $element;
  }

}
