<?php

namespace Drupal\sirv\Plugin\Field\FieldFormatter;

use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\image\Plugin\Field\FieldFormatter\ImageFormatterBase;
use Drupal\s3fs\S3fsServiceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'sirv_image' formatter.
 *
 * @FieldFormatter(
 *   id = "sirv_image",
 *   label = @Translation("Sirv image"),
 *   field_types = {
 *     "image"
 *   }
 * )
 */
class SirvImageFieldFormatter extends ImageFormatterBase implements ContainerFactoryPluginInterface {

  /**
   * The 's3fs' configuration object.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $s3fsConfigObject;

  /**
   * The 's3fs' configuration array.
   *
   * @var array
   */
  protected $s3fsConfig;

  /**
   * The 's3fs' service.
   *
   * @var \Drupal\s3fs\S3fsServiceInterface
   */
  protected $s3fsService;

  /**
   * The Amazon S3 client.
   *
   * @var \Aws\S3\S3Client
   */
  protected $s3;

  /**
   * Constructs a SirvImageFieldFormatter object.
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
   *   Any third party settings.
   * @param ImmutableConfig $s3fs_config_object
   *   The 's3fs' configuration object.
   * @param S3fsServiceInterface $s3fs_service
   *   The 's3fs' service.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, ImmutableConfig $s3fs_config_object, S3fsServiceInterface $s3fs_service) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->s3fsConfigObject = $s3fs_config_object;
    $this->s3fsConfig = $s3fs_config_object->get();
    $this->s3fsService = $s3fs_service;
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
      $container->get('config.factory')->get('s3fs.settings'),
      $container->get('s3fs')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'sirv_profile' => '',
      'sirv_image_options' => '',
      'image_link' => '',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = [];
    $profiles = $this->getSirvImageProfiles();

    $element['sirv_profile'] = array(
      '#title' => t('Sirv profile'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('sirv_profile'),
      '#empty_option' => t('None (original image)'),
      '#options' => $profiles,
    );
    $element['sirv_image_options'] = array(
      '#title' => t('Additional Sirv Image options'),
      '#type' => 'textfield',
      '#description' => t('These options will override profile options. Example of format: %example', array('%example' => 'scale.width=250&grayscale=true')),
      '#default_value' => $this->getSetting('sirv_image_options'),
    );
    $link_types = [
      'content' => t('Content'),
      'file' => t('File'),
    ];
    $element['image_link'] = [
      '#title' => t('Link image to'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('image_link'),
      '#empty_option' => t('Nothing'),
      '#options' => $link_types,
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    $profiles = $this->getSirvImageProfiles();
    if (isset($profiles[$this->getSetting('sirv_profile')])) {
      $summary[] = t('Sirv profile: @profile', array('@profile' => $profiles[$this->getSetting('sirv_profile')]));
    }
    else {
      $summary[] = t('Original image');
    }

    if (!empty($this->getSetting('sirv_image_options'))) {
      $summary[] = t('Additional Sirv Image options');
    }

    $link_types = array(
      'content' => t('Linked to content'),
      'file' => t('Linked to file'),
    );
    // Display this setting only if image is linked.
    if (isset($link_types[$this->getSetting('image_link')])) {
      $summary[] = $link_types[$this->getSetting('image_link')];
    }

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

    $url = NULL;
    $image_link_setting = $this->getSetting('image_link');
    // Check if the formatter involves a link.
    if ($image_link_setting == 'content') {
      $entity = $items->getEntity();
      if (!$entity->isNew()) {
        $url = $entity->urlInfo();
      }
    }
    elseif ($image_link_setting == 'file') {
      $link_file = TRUE;
    }

    $sirv_profile = $this->getSetting('sirv_profile');

    foreach ($files as $delta => $file) {
      // Extract field item attributes for the theme function, and unset them
      // from the $item so that the field template does not re-render them.
      $item = $file->_referringItem;
      $item_attributes = $item->_attributes;
      unset($item->_attributes);

      $elements[$delta] = [
        '#theme' => 'sirv_image_formatter',
        '#item' => $item,
        '#item_attributes' => $item_attributes,
        '#sirv_profile' => $sirv_profile,
        '#url' => $url,
      ];
    }

    return $elements;
  }

  /**
   * Get available Sirv Image profiles.
   */
  public function getSirvImageProfiles() {
    $profiles = array();
    $profiles_directory = 'Profiles';
    $s3client = $this->s3fsService->getAmazonS3Client($this->s3fsConfig);

    $iterator = $s3client->getIterator('ListObjects', array(
      'Bucket' => $this->s3fsConfig['bucket'],
      'Prefix' => $profiles_directory,
    ));

    foreach ($iterator as $object) {
      $key = $object['Key'];
      if (preg_match('#([^/]+)\.profile$#', $key, $matches)) {
        $profiles[$matches[1]] = $matches[1];
      }
    }

    return $profiles;
  }

}
