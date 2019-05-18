<?php

namespace Drupal\file_image_formatter\Plugin\Field\FieldFormatter;


use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Field\EntityReferenceFieldItemListInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\TypedData\TranslatableInterface;
use Drupal\Core\Url;
use Drupal\file\FileInterface;
use Drupal\image\Plugin\Field\FieldFormatter\ImageFormatter;

/**
 * An image formatter for file fields.
 *
 * @FieldFormatter(
 *   id = "field_image_formatter",
 *   label = @Translation("Field Image Formatter"),
 *   field_types = {
 *     "file"
 *   }
 * )
 */
class FileImageFormatter extends ImageFormatter {

  /**
   * {@inheritdoc}
   */
  protected function getEntitiesToView(EntityReferenceFieldItemListInterface $items, $langcode) {
    $entities = array();

    foreach ($items as $delta => $item) {
      // Ignore items where no entity could be loaded in prepareView().
      if ($item->_loaded) {
        $entity = $item->entity;

        // Set the entity in the correct language for display.
        if ($entity instanceof TranslatableInterface) {
          $entity = \Drupal::service('entity.repository')->getTranslationFromContext($entity, $langcode);
        }

        $access = $this->checkAccess($entity);
        // Add the access result's cacheability, ::view() needs it.
        $item->_accessCacheability = CacheableMetadata::createFromObject($access);
        if ($access->isAllowed()) {
          // Add the referring item, in case the formatter needs it.
          $entity->_referringItem = $items[$delta];
          $entities[$delta] = $entity;
        }
      }
    }

    return $entities;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    /** @var \Drupal\Core\Field\EntityReferenceFieldItemListInterface $items */
    $files = $this->getEntitiesToView($items, $langcode);

    if (empty($files)) {
      return $elements;
    }

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

    $image_style_setting = $this->getSetting('image_style');

    // Collect cache tags to be added for each item in the field.
    $base_cache_tags = [];
    if (!empty($image_style_setting)) {
      $image_style = $this->imageStyleStorage->load($image_style_setting);
      $base_cache_tags = $image_style->getCacheTags();
    }

    // Only render file fields that have images.
    foreach ($files as $delta => $file) {
      if ($this->isImage($file)) {
        $cache_contexts = [];
        if (isset($link_file)) {
          $image_uri = $file->getFileUri();
          $url = Url::fromUri(file_create_url($image_uri));
          $cache_contexts[] = 'url.site';
        }
        $cache_tags = Cache::mergeTags($base_cache_tags, $file->getCacheTags());

        // Extract field item attributes for the theme function, and unset them
        // from the $item so that the field template does not re-render them.
        $item = $file->_referringItem;
        $item_attributes = $item->_attributes;
        unset($item->_attributes);

        $elements[$delta] = [
          '#theme' => 'image_formatter',
          '#item' => $item,
          '#item_attributes' => $item_attributes,
          '#image_style' => $image_style_setting,
          '#url' => $url,
          '#cache' => array(
            'tags' => $cache_tags,
            'contexts' => $cache_contexts,
          ),
        ];
      }
    }

    return $elements;
  }

  /**
   * Detect that a file item matches an image mimetype.
   *
   * @param \Drupal\file\FileInterface $file
   *   The file item to check.
   *
   * @return bool
   *   TRUE if the file extension matches the allowed extension.
   */
  protected function isImage(FileInterface $file) {
    return preg_match('/^image/i', $file->getMimeType()) === 1;
  }

}
