<?php

namespace Drupal\image_background_formatter\Plugin\Field\FieldFormatter;

use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ImageBackgroundFormatter.
 *
 * @package Drupal\image_background_formatter\Plugin\Field\Formatter
 * @FieldFormatter(
 *   id = "image_background_formatter",
 *   label = @Translation("Image Background Formatter"),
 *   field_types = {
 *    "image"
 *   }
 * )
 */
class ImageBackgroundFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * EntityTypeManager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  private $entityTypeManager;

  /**
   * ImageBackgroundFormatter constructor.
   *
   * @param string $plugin_id
   *   Plugin id.
   * @param string $plugin_definition
   *   Plugin definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   Field definition.
   * @param array $settings
   *   Settings.
   * @param string $label
   *   Label.
   * @param string $view_mode
   *   View mode.
   * @param array $third_party_settings
   *   Third party settings.
   * @param \Drupal\Core\Entity\EntityTypeManager $entityTypeManager
   *   EntityTypeManager.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, EntityTypeManager $entityTypeManager) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * View Elements.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $items
   *   Items.
   * @param string $langcode
   *   Langcode.
   *
   * @return array
   *   Rendered array.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = [];
    $settings = $this->getSettings();

    $style = $this->entityTypeManager->getStorage('image_style')->load($settings['image_style']);

    foreach ($items as $delta => $item) {

      $element[$delta] = [
        '#theme' => 'image_background_formatter',
        '#img' => isset($style) ? $style->buildUrl($item->entity->getFileUri()) : file_create_url($item->entity->getFileUri()),
        '#attached' => [
          'library' => 'image_background_formatter/image_background_formatter',
        ],
      ];
    }

    return $element;
  }

  /**
   * Default settings.
   *
   * @return array
   *   Array settings.
   */
  public static function defaultSettings() {

    return [
      'image_style' => '_original',
    ] + parent::defaultSettings();
  }

  /**
   * Settings summary.
   *
   * @return array|string[]
   *   Array summary.
   */
  public function settingsSummary() {
    $settings = $this->getSettings();
    $summary = [];

    if ($settings['image_style']) {
      $summary[] = $this->t('Image style: @image_style', ['@image_style' => $settings['image_style']]);
    }

    return $summary;
  }

  /**
   * Settings form.
   *
   * @param array $form
   *   Form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   *
   * @return array|mixed
   *   Form array.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $settings = $this->getSettings();

    $styles = $this->entityTypeManager->getStorage('image_style')->loadMultiple();
    $options = [];
    $options['default'] = $this->t('Original size');

    foreach ($styles as $key => $style) {
      $options[$key] = $style->label();
    }

    $elements['image_style'] = [
      '#type' => 'select',
      '#title' => $this->t('Image style'),
      '#default_value' => $settings['image_style'],
      '#empty_value' => 'default',
      '#options' => $options,
    ];

    return $elements;
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
      $configuration['third_party_settings'],
      $container->get('entity_type.manager')
    );
  }

}
