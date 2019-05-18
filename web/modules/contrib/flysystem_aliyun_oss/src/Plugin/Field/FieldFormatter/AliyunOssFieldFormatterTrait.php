<?php

namespace Drupal\flysystem_aliyun_oss\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;

/**
 * Trait AliyunOssFieldFormatterTrait.
 *
 * @package Drupal\flysystem_aliyun_oss\Plugin\Field\FieldFormatter
 */
trait AliyunOssFieldFormatterTrait {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {

    $elements = parent::viewElements($items, $langcode);

    /* @var \Drupal\Core\Site\Settings $settings */
    $settings = \Drupal::service('settings');

    $field_storage_definition = $this->fieldDefinition->getFieldStorageDefinition();

    $scheme = $field_storage_definition->getSetting('uri_scheme');

    $flysystem_schemas = $settings->get('flysystem', NULL);

    if ($scheme !== NULL && $flysystem_schemas !== NULL) {
      if (array_key_exists($scheme, $flysystem_schemas)) {
        if (isset($flysystem_schemas[$scheme]['driver']) && $flysystem_schemas[$scheme]['driver'] === 'aliyun_oss') {
          if (isset($flysystem_schemas[$scheme]['config']['expire'])) {
            $expire = $flysystem_schemas[$scheme]['config']['expire'];
            if (is_int($expire) && $expire > 0 && $expire <= 64800) {
              foreach ($items as $delta => $item) {
                $elements[$delta]['#cache'] = [
                  'max-age' => $expire,
                ];
              }
              $elements['#cache'] = [
                'max-age' => $expire,
              ];
            }
          }
        }
      }
    }

    return $elements;
  }

}
