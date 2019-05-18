<?php

namespace Drupal\representative_image\Plugin\Field\FieldFormatter;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\image\Plugin\Field\FieldFormatter\ImageFormatter;
use Drupal\representative_image\RepresentativeImagePicker;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'Representative Image' formatter.
 *
 * @FieldFormatter(
 *   id = "representative_image",
 *   label = @Translation("Representative Image"),
 *   field_types = {
 *     "representative_image"
 *   }
 * )
 */
class RepresentativeImageFormatter extends ImageFormatter implements ContainerFactoryPluginInterface {

  /**
   * The representative image picker.
   *
   * @var \Drupal\representative_image\RepresentativeImagePicker
   */
  protected $representativeImagePicker;

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
   * @param \Drupal\representative_image\RepresentativeImagePicker $representative_image_picker
   *   The representative image picker.
   */
  public function __construct(string $plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, string $label, string $view_mode, array $third_party_settings, AccountInterface $current_user, EntityStorageInterface $image_style_storage, RepresentativeImagePicker $representative_image_picker) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings, $current_user, $image_style_storage);
    $this->representativeImagePicker = $representative_image_picker;
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
      $container->get('representative_image.picker')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = [];
    $image_items = $this->representativeImagePicker->getImageFieldItemList($items);

    if (!$image_items || $image_items->isEmpty()) {
      return $element;
    }

    $settings = $this->getSettings();
    $element[] = [
      '#theme' => 'image_formatter',
      '#image_style' => $settings['image_style'],
      '#item' => $image_items,
    ];

    if (!empty($settings['image_link'])) {
      if ($settings['image_link'] == 'content') {
        $element['#url'] = $items->getEntity()->toUrl()->toString();
      }
      elseif ($settings['image_link'] == 'file') {
        $element['#url'] = $image_items->entity->url('canonical');
      }
    }

    return $element;
  }

}
