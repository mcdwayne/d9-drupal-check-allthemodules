<?php

namespace Drupal\config_default_image\Plugin\Field\FieldFormatter;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\File\FileSystem;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\file\Entity\File;
use Drupal\image\Plugin\Field\FieldFormatter\ImageFormatter;
use Symfony\Component\DependencyInjection\ContainerInterface;


/**
 * Plugin implementation of the 'image' formatter.
 *
 * @FieldFormatter(
 *   id = "config_default_image",
 *   label = @Translation("Image or default image"),
 *   field_types = {
 *     "image"
 *   },
 *   quickedit = {
 *     "editor" = "image"
 *   }
 * )
 */
class ConfigDefaultImageFormatter extends ImageFormatter {

  /**
   * The file system.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

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
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Entity\EntityStorageInterface $image_style_storage
   *   The image style storage.
   * @param \Drupal\Core\File\FileSystem $file_system
   *   The file system.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, AccountInterface $current_user, EntityStorageInterface $image_style_storage, FileSystem $file_system) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings, $current_user, $image_style_storage);
    $this->fileSystem = $file_system;
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
      $container->get('current_user'),
      $container->get('entity_type.manager')->getStorage('image_style'),
      $container->get('file_system')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
        'default_image' => [
          'path' => '',
          'use_image_style' => FALSE,
          'alt' => '',
          'title' => '',
          'width' => NULL,
          'height' => NULL,
        ],
      ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   * @see \Drupal\image\Plugin\Field\FieldType\ImageItem
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);

    $settings = $this->getSettings();

    $element['default_image'] = [
      '#type' => 'details',
      '#title' => t('Default image'),
      '#open' => TRUE,
      '#required' => TRUE,
    ];
    $element['default_image']['path'] = [
      '#type' => 'textfield',
      '#title' => t('Image path'),
      '#description' => t('Drupal path to the image to be shown if no image is uploaded (the image would typically be in a git-managed directory so that it can be deployed easily). Example: /themes/custom/my_theme/img/default_image.jpg'),
      '#default_value' => $settings['default_image']['path'],
      '#required' => TRUE,
      //TODO validate path
    ];
    $element['default_image']['use_image_style'] = [
      '#type' => 'checkbox',
      '#title' => t('Apply the image style'),
      '#description' => t('Check this box to use the image style on the default image'),
      '#default_value' => $settings['default_image']['use_image_style'],
    ];
    $element['default_image']['alt'] = [
      '#type' => 'textfield',
      '#title' => t('Alternative text'),
      '#description' => t('This text will be used by screen readers, search engines, and when the image cannot be loaded.'),
      '#default_value' => $settings['default_image']['alt'],
      '#maxlength' => 512,
    ];
    $element['default_image']['title'] = [
      '#type' => 'textfield',
      '#title' => t('Title'),
      '#description' => t('The title attribute is used as a tooltip when the mouse hovers over the image.'),
      '#default_value' => $settings['default_image']['title'],
      '#maxlength' => 1024,
    ];
    $element['default_image']['width'] = [
      '#type' => 'value',
      '#value' => $settings['default_image']['width'],
    ];
    $element['default_image']['height'] = [
      '#type' => 'value',
      '#value' => $settings['default_image']['height'],
    ];
    $element['default_image']['#description'] = t('If no image is set for the field (not even a field-level default image), this image will be shown on display.');

    return $element;
  }


  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();
    $summary[] = t('Fallback to a default image');
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = parent::viewElements($items, $langcode);
    if (empty($elements)) {
      $default_image = $this->getSetting('default_image');
      $image_path = $default_image['path'];
      if (!empty($image_path)) {
        if ($default_image['use_image_style']) {
          // $image_path must be ready for
          // Drupal\image\Entity\ImageStyle::buildUri().
          // This needs a valid scheme.
          // As long as https://www.drupal.org/project/drupal/issues/1308152 is
          // not fixed, files stored outside from public, private and temporary
          // directories have no scheme.
          // So that if our path has no scheme, we copy the file to the public
          // files directory and add it as scheme.
          if (!file_uri_scheme($image_path)) {
            $image_path = ltrim($image_path, '/');
            $destination = 'public://config_default_image/' . $image_path;
            $directory = drupal_dirname($destination);
            file_prepare_directory($directory, FILE_CREATE_DIRECTORY);
            if (!file_exists($destination)) {
              $image_path = file_unmanaged_copy($image_path, $destination);
            }
            else {
              $image_path = $destination;
            }
          }
        }

        $file = File::create([
          'uid' => 0,
          'filename' => $this->fileSystem->basename($image_path),
          'uri' => $image_path,
          'status' => 1,
        ]);

        $url = NULL;
        $image_link_setting = $this->getSetting('image_link');
        // Check if the formatter involves a link.
        if ($image_link_setting == 'content') {
          $entity = $items->getEntity();
          if (!$entity->isNew()) {
            $url = $entity->toUrl();
          }
        }
        elseif ($image_link_setting == 'file') {
          $link_file = TRUE;
        }


        $cache_tags = [];

        $cache_contexts = [];
        if (isset($link_file)) {
          $cache_contexts[] = 'url.site';
        }


        // @see ImageFormatterBase
        // Clone the FieldItemList into a runtime-only object for the formatter,
        // so that the fallback image can be rendered without affecting the
        // field values in the entity being rendered.
        $items = clone $items;
        $items->setValue([
          'target_id' => $file->id(),
          'alt' => $default_image['alt'],
          'title' => $default_image['title'],
          'width' => $default_image['width'],
          'height' => $default_image['height'],
          'entity' => $file,
          '_loaded' => TRUE,
          '_is_default' => TRUE,
        ]);
        $item = $items[0];

        // Extract field item attributes for the theme function, and unset them
        // from the $item so that the field template does not re-render them.
        $item_attributes = $item->_attributes;
        unset($item->_attributes);

        $elements[] = [
          '#theme' => 'image_formatter',
          '#item' => $item,
          '#item_attributes' => $item_attributes,
          '#image_style' => $default_image['use_image_style'] ? $this->getSetting('image_style') : FALSE,
          '#url' => $url,
          '#cache' => [
            'tags' => $cache_tags,
            'contexts' => $cache_contexts,
          ],
        ];
      }

    }
    return $elements;
  }
}
