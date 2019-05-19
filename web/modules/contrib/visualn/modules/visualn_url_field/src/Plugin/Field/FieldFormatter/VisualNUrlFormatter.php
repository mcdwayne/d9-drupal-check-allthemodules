<?php

namespace Drupal\visualn_url_field\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
//use Drupal\Component\Utility\Html;
//use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
//use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\link\Plugin\Field\FieldFormatter\LinkFormatter;
use Drupal\Core\Path\PathValidatorInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\visualn\Manager\DrawerManager;
use Drupal\visualn\Manager\RawResourceFormatManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\visualn\Plugin\VisualNFormatterSettingsTrait;

/**
 * Plugin implementation of the 'visualn_url' formatter.
 *
 * @FieldFormatter(
 *   id = "visualn_url",
 *   label = @Translation("VisualN url"),
 *   field_types = {
 *     "visualn_url"
 *   }
 * )
 */
class VisualNUrlFormatter extends  LinkFormatter implements ContainerFactoryPluginInterface {

  // @todo: move formatter settings methods code into a trait

  use VisualNFormatterSettingsTrait;

  /**
   * The image style entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $visualNStyleStorage;

  /**
   * The visualn drawer manager service.
   *
   * @var \Drupal\visualn\Manager\DrawerManager
   */
  protected $visualNDrawerManager;

  /**
   * The visualn resource format manager service.
   *
   * @var \Drupal\visualn\Manager\RawResourceFormatManager
   */
  protected $visualNResourceFormatManager;

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
    $container->get('path.validator'),
    $container->get('entity_type.manager')->getStorage('visualn_style'),
    $container->get('plugin.manager.visualn.drawer'),
    $container->get('plugin.manager.visualn.raw_resource_format')
    );
  }

  /**
   * Constructs a VisualNResourceFormatter object.
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
   * @param \Drupal\visualn\Manager\DrawerManager $visualn_drawer_manager
   *   The visualn drawer manager service.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, PathValidatorInterface $path_validator, EntityStorageInterface $visualn_style_storage, DrawerManager $visualn_drawer_manager, RawResourceFormatManager $visualn_resource_format_manager) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings, $path_validator);
    $this->visualNStyleStorage = $visualn_style_storage;
    $this->visualNDrawerManager = $visualn_drawer_manager;
    $this->visualNResourceFormatManager = $visualn_resource_format_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'show_resource_link' => 0,
    ] + self::visualnDefaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = $this->visualnSettingsForm($form, $form_state);
    $form['show_resource_link'] = [
      '#type' => 'checkbox',
      '#title' => t('Show resource link'),
      '#default_value' => $this->getSetting('show_resource_link'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = $this->visualnSettingsSummary();
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    // @todo: this is almost a copy from visualn_file_field formatter
    $elements = $this->visualnViewElements($items, $langcode);
    if ($this->getSetting('show_resource_link') == 0) {
      foreach ($elements as $delta => $element) {
        unset($elements[$delta]['#context']['element_build']);
      }
    }
    return $elements;
  }


  public function getRawInput($element, $item) {
    $url = $this->buildUrl($item)->toString();
    $raw_input = [
      'file_url' => $url,
    ];

    return $raw_input;
  }

}
