<?php

namespace Drupal\stock_photo_search\Plugin\Field\FieldFormatter;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Drupal\image\Entity\ImageStyle;
use Drupal\stock_photo_search\ProviderManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the stock photo image field formatter.
 *
 * @FieldFormatter(
 *   id = "stock_photo_search_image_formatter",
 *   label = @Translation("Stock Photo Image"),
 *   field_types = {
 *     "stock_photo_search"
 *   }
 * )
 */
class StockPhotoSearchFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * The embed provider plugin manager.
   *
   * @var \Drupal\stock_photo_search\ProviderManagerInterface
   */
  protected $providerManager;

  /**
   * The image style entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $imageStyleStorage;

  /**
   * Class constant for linking to content.
   */
  const LINK_CONTENT = 'content';

  /**
   * Class constant for linking to the provider URL.
   */
  const LINK_PROVIDER = 'provider';

  /**
   * Constructs a new instance of the plugin.
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
   * @param \Drupal\stock_photo_search\ProviderManagerInterface $provider_manager
   *   The stock photo image provider manager.
   * @param \Drupal\Core\Entity\EntityStorageInterface $image_style_storage
   *   The image style storage.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, ProviderManagerInterface $provider_manager, EntityStorageInterface $image_style_storage) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->providerManager = $provider_manager;
    $this->imageStyleStorage = $image_style_storage;
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
      $container->get('stock_photo_search.provider_manager'),
      $container->get('entity.manager')->getStorage('image_style')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = [];
    foreach ($items as $delta => $item) {
      $provider = $this->providerManager->loadProviderFromInput($item->value);

      if (!$provider) {
        $element[$delta] = ['#theme' => 'stock_photo_search_missing_provider'];
      }
      else {
        $url = FALSE;
        if ($this->getSetting('link_image_to') == static::LINK_CONTENT) {
          $url = $items->getEntity()->toUrl();
        }
        elseif ($this->getSetting('link_image_to') == static::LINK_PROVIDER) {
          $url = Url::fromUri($item->value);
        }
        $provider->downloadImage();
        $element[$delta] = $provider->renderImage($this->getSetting('image_style'), $url);
      }

    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'image_style' => '',
      'link_image_to' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);
    $element['image_style'] = [
      '#title' => $this->t('Image Style'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('image_style'),
      '#required' => FALSE,
      '#options' => image_style_options(),
    ];
    $element['link_image_to'] = [
      '#title' => $this->t('Link image to'),
      '#type' => 'select',
      '#empty_option' => $this->t('- None -'),
      '#default_value' => $this->getSetting('link_image_to'),
      '#options' => [
        static::LINK_CONTENT => $this->t('Content'),
        static::LINK_PROVIDER => $this->t('Provider URL'),
      ],
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $linked = '';
    if (!empty($this->getSetting('link_image_to'))) {
      $linked = $this->getSetting('link_image_to') == static::LINK_CONTENT ? $this->t(', linked to content') : $this->t(', linked to provider');
    }
    $summary[] = $this->t('Stock Photo Image (@style@linked).', [
      '@style' => $this->getSetting('image_style') ? $this->getSetting('image_style') : $this->t('no image style'),
      '@linked' => $linked,
    ]);

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    $dependencies = parent::calculateDependencies();
    $style_id = $this->getSetting('image_style');
    if ($style_id && $style = ImageStyle::load($style_id)) {
      $dependencies[$style->getConfigDependencyKey()][] = $style->getConfigDependencyName();
    }

    return $dependencies;
  }

  /**
   * {@inheritdoc}
   */
  public function onDependencyRemoval(array $dependencies) {
    $changed = parent::onDependencyRemoval($dependencies);
    $style_id = $this->getSetting('image_style');
    if ($style_id && $style = ImageStyle::load($style_id)) {
      if (!empty($dependencies[$style->getConfigDependencyKey()][$style->getConfigDependencyName()])) {
        $replacement_id = $this->imageStyleStorage->getReplacementId($style_id);
        // If a valid replacement has been provided in the storage, replace the
        // image style with the replacement and signal that the formatter plugin
        // settings were updated.
        if ($replacement_id && ImageStyle::load($replacement_id)) {
          $this->setSetting('image_style', $replacement_id);
          $changed = TRUE;
        }
      }
    }

    return $changed;
  }

}
