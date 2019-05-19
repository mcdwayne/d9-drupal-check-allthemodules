<?php

namespace Drupal\field_orbit\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\TypedData\ListDataDefinition;
use Drupal\Core\Url;

/**
 * Plugin implementation of the 'Orbit' formatter.
 *
 * @FieldFormatter(
 *   id = "orbit_media",
 *   label = @Translation("Zurb Orbit Slider"),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class OrbitMediaFormatter extends OrbitFormatter {

  /**
   * {@inheritdoc}
   *
   * This has to be overridden because FileFormatterBase expects $item to be
   * of type \Drupal\file\Plugin\Field\FieldType\FileItem and calls
   * isDisplayed() which is not in FieldItemInterface.
   */
  protected function needsEntityLoad(EntityReferenceItem $item) {
    return !$item->hasNewEntity();
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    // This formatter is only available for entity types that reference
    // media items.
    return ($field_definition->getFieldStorageDefinition()
      ->getSetting('target_type') == 'media');
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $media_items = $this->getEntitiesToView($items, $langcode);

    // Early opt-out if the field is empty.
    if (empty($media_items)) {
      return $elements;
    }

    // Initialize the list definition type to mock image field values.
    $list_definition = ListDataDefinition::create('field_item:image');
    /** @var \Drupal\Core\Field\FieldItemListInterface $items_list */
    $items_list = \Drupal::typedDataManager()->create($list_definition);
    foreach ($media_items as $key => $item) {

      // Currently only image media bundles are supported.
      if ($item->get('field_media_image')->isEmpty()) {
        continue;
      }

      $image_item = $item->get('field_media_image')->first();

      // Add the file entity to the items list.
      $items_list->appendItem($image_item->getValue());

      // Store other values used by the template.
      $files[$key] = $image_item->entity;
      $images[$key] = [
        '#theme' => 'image_formatter',
        '#item' => $image_item,
        '#item_attributes' => [],
        '#image_style' => $this->getSetting('image_style'),
        '#url' => Url::fromUri(file_create_url($image_item->entity->get('uri')->value)),
      ];
    }

    static $orbit_count;
    $orbit_count = (is_int($orbit_count)) ? $orbit_count + 1 : 1;

    $elements = [];
    $entity = [];
    $links = [
      'image_link' => 'path',
      'caption_link' => 'caption_path',
    ];

    // Loop through required links (because image and
    // caption can have different links).
    foreach ($items_list as $delta => $item) {
      // Set Image caption.
      if ($this->getSetting('caption') != '') {
        $caption_settings = $this->getSetting('caption');
        if ($caption_settings == 'title') {
          $item_settings[$delta]['caption'] = $item->getValue()['title'];
        }
        elseif ($caption_settings == 'alt') {
          $item_settings[$delta]['caption'] = $item->getValue()['alt'];
        }
        $item->set('caption', $item_settings[$delta]['caption']);
      }
      // Set Image and Caption Link.
      foreach ($links as $setting => $path) {
        if ($this->getSetting($setting) != '') {
          switch ($this->getSetting($setting)) {
            case 'content':
              $entity = $items[$delta]->getEntity();
              if (!$entity->isNew()) {
                $uri = $entity->urlInfo();
                $uri = !empty($uri) ? $uri : '';
                $item->set($path, $uri);
                $images[$delta]['#url'] = $uri;
              }
              break;

            case 'file':
              foreach ($files as $file_delta => $file) {
                $image_uri = $file->getFileUri();
                $uri = Url::fromUri(file_create_url($image_uri));
                $uri = !empty($uri) ? $uri : '';
                $items_list[$file_delta]->set($path, $uri);
              }
              break;
          }
        }
      }
    }

    $defaults = $this->defaultSettings();

    if (count($items_list)) {
      // Only include non-default values to minimize html output.
      $options = [];
      foreach ($defaults as $key => $setting) {
        // Don't pass these to orbit.
        if ($key == 'caption_link' || $key == 'caption' || $key == 'image_style') {
          continue;
        }
        if ($this->getSetting($key) != $setting) {
          $options[$key] = $this->getSetting($key);
        }
      }

      $elements[] = [
        '#theme' => 'field_orbit',
        '#items' => $items_list,
        '#options' => $options,
        '#entity' => $entity,
        '#image' => $images,
        '#orbit_id' => $orbit_count,
      ];
    }

    return $elements;
  }

}
