<?php

namespace Drupal\cocoen_beforeafter\Plugin\Field\FieldFormatter;

use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'Cocoen Before After Image' formatter.
 *
 * @FieldFormatter(
 *   id = "cocoen_before_after_image",
 *   label = @Translation("Cocoen Before After Image"),
 *   field_types = {
 *     "image"
 *   }
 * )
 */
class CocoenBeforeAfterImageFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * EntityTypeManager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  private $entityTypeManager;

  /**
   * CocoenBeforeAfterImageFormatter constructor.
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
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'image_style' => '',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
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
   * {@inheritdoc}
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
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $settings = $this->getSettings();

    $style = $this->entityTypeManager->getStorage('image_style')->load($settings['image_style']);
    $images = [];

    foreach ($items as $delta => $item) {
      $images[] = isset($style) ? $style->buildUrl($item->entity->getFileUri()) : file_create_url($item->entity->getFileUri());

      if (count($images) == 2) {
        break;
      }
    }

    return [
      '#theme' => 'cocoen_before_after_image',
      '#images' => $images,
      '#attached' => [
        'library' => [
          'cocoen_beforeafter/cocoen_beforeafter',
        ],
      ],
    ];
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
