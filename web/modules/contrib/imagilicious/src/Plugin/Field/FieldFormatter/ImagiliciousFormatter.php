<?php

namespace Drupal\imagilicious\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\file\Entity\File;
use Drupal\image\Plugin\Field\FieldFormatter\ImageFormatterBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'imagilicious' formatter.
 *
 * @FieldFormatter(
 *   id = "imagilicious",
 *   label = @Translation("Imagilicious"),
 *   field_types = {
 *     "image"
 *   }
 * )
 */
class ImagiliciousFormatter extends ImageFormatterBase implements ContainerFactoryPluginInterface {

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
   *   Any third party settings settings.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
  }

  /**
   * {@inheritdoc}
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
  public static function defaultSettings() {
    return array(
      'columns' => 100
    ) + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {

    $element['columns'] = [
      '#title' => t('Columns'),
      '#type' => 'number',
      '#default_value' => $this->getSetting('columns'),
      '#description' => $this->t('Each column is 5 pixels wide.'),
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = array();

    $summary[] = t('Columns: @columns', array('@columns' => $this->getSetting('columns')));

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {

    $elements = array();
    $files = $this->getEntitiesToView($items, $langcode);

    // Early opt-out if the field is empty.
    if (empty($files)) {
      return $elements;
    }

    foreach ($files as $delta => $file) {
      /** @var File $file */
      switch ($file->getMimeType()) {
        case 'image/png':
          $im = imagecreatefrompng($file->getFileUri());
          break;
        case 'image/jpg':
        case 'image/jpeg':
          $im = imagecreatefromjpeg($file->getFileUri());
          break;
        default:
          $im = NULL;
      }

      if ($im) {

        $rows = array();

        $width = $file->_referringItem->width;
        $height = $file->_referringItem->height;
        $step = $width / $this->getSetting('columns');

        for ($y = 0; $y < $height; $y += $step) {
          $row = array();
          for ($x = 0; $x < $width; $x += $step) {
            $color = imagecolorat($im, $x, $y);
            $row[] = array(
              'data' => '',
              'bgcolor' => '#' . dechex($color),
            );
          }
          $rows[] = $row;
        }

        $elements[$delta] = array(
          '#type' => 'table',
          '#rows' => $rows,
          '#attached' => array(
            'library' => array(
              'imagilicious/imagilicious'
            )
          ),
          '#attributes' => array(
            'class' => 'imagilicious',
            'border' => 0,
          ),
        );
      }
    }

    return $elements;
  }

}