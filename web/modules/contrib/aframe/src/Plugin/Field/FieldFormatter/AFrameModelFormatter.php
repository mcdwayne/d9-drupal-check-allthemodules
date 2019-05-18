<?php

namespace Drupal\aframe\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\file\Plugin\Field\FieldFormatter\FileFormatterBase;
use Drupal\aframe\AFrameComponentPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;


/**
 * Plugin implementation of the 'aframe_model' formatter.
 *
 * @FieldFormatter(
 *   id = "aframe_model",
 *   label = @Translation("A-Frame Model"),
 *   field_types = {
 *     "file"
 *   }
 * )
 */
class AFrameModelFormatter extends FileFormatterBase implements ContainerFactoryPluginInterface {

  use AFrameFormatterTrait;

  /**
   * The AFrame component manager.
   *
   * @var \Drupal\aframe\AFrameComponentPluginManager
   */
  protected $componentManager;

   /**
   * Constructs an AFrameModelFormatter object.
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
   * @param \Drupal\aframe\AFrameComponentPluginManager $component_manager
   *   The AFrame component manager.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, AFrameComponentPluginManager $component_manager) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->componentManager = $component_manager;
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
      $configuration['third_party_settings'],
      $container->get('plugin.manager.aframe.component')
    );
  }


  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $defaults = [];

    // Get A-Frame global formatter settings defaults.
    $defaults += AFrameFormatterTrait::globalDefaultSettings();

    return $defaults;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = [];

    // Get A-Frame global formatter settings form.
    $element += $this->globalSettingsForm($form, $form_state);

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    // Get A-Frame global formatter settings summary.
    $summary = array_merge($summary, $this->globalSettingsSummary());

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $files = $this->getEntitiesToView($items, $langcode);

    // Early opt-out if the field is empty.
    if (empty($files)) {
      return $elements;
    }

    /** @var \Drupal\file\Entity\File $file */
    foreach ($files as $delta => $file) {
      $elements[$delta] = [
        '#type'       => pathinfo($file->getFilename())['extension'] == 'obj' ? 'aframe_obj_model' : 'aframe_collada_model',
        '#attributes' => [
          'src'    => file_create_url($file->getFileUri()),
        ],
        '#cache'      => [
          'tags' => $file->getCacheTags(),
        ],
      ];

      // Get A-Frame global formatter attributes.
      $elements[$delta]['#attributes'] += $this->getAttributes();
    }

    return $elements;
  }

}
