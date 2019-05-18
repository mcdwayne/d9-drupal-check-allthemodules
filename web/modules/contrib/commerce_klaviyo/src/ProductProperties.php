<?php

namespace Drupal\commerce_klaviyo;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\EntityReferenceFieldItemList;
use Drupal\taxonomy\TermInterface;

/**
 * The Klaviyo ProductProperties object.
 *
 * @package Drupal\commerce_klaviyo
 */
class ProductProperties extends KlaviyoPropertiesBase {

  /**
   * The commerce_klaviyo module configuration.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $klaviyoSettings;

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityInterface $source_entity) {
    parent::__construct($config_factory, $source_entity);
    $this->assertEntity($source_entity);
    $this->klaviyoSettings = $this->configFactory
      ->get('commerce_klaviyo.settings');
  }

  /**
   * {@inheritdoc}
   */
  public function getProperties() {
    /** @var \Drupal\commerce_product\Entity\ProductInterface $product */
    $product = $this->getSourceEntity();
    $this->properties['ProductName'] = $product->getTitle();
    $this->properties['ProductID'] = $product->id();
    $this->properties['ProductURL'] = $product
      ->toUrl('canonical', ['absolute' => TRUE])
      ->toString();

    if ($default_variation = $product->getDefaultVariation()) {
      $this->properties['Price'] = $default_variation->getPrice()->getNumber();
    }

    $this->prepareConfigurableFields();

    return $this->properties;
  }

  /**
   * Prepares Product Brand/Category/ImageUrl properties.
   */
  protected function prepareConfigurableFields() {
    /** @var \Drupal\commerce_product\Entity\ProductInterface $product */
    $product = $this->getSourceEntity();
    $klaviyo_settings = $this->klaviyoSettings->get('product_types');
    $bundle = $product->bundle();
    $klaviyo_settings = $klaviyo_settings[$bundle] ?: [];

    foreach (['category_field', 'brand_field'] as $field_name_setting) {
      if (!empty($klaviyo_settings[$field_name_setting])) {
        $field = $product->get($klaviyo_settings[$field_name_setting]);

        if ($field && $field instanceof EntityReferenceFieldItemList) {
          if (!$field->isEmpty()) {
            $labels = [];
            foreach ($field->referencedEntities() as $entity) {
              if ($entity instanceof TermInterface) {
                $labels[] = $entity->label();
              }
            }

            if (!empty($labels)) {
              switch ($field_name_setting) {
                case 'category_field':
                  $this->properties['Categories'] = $labels;
                  break;

                case 'brand_field':
                  $this->properties['Brand'] = reset($labels);
                  break;
              }
            }
          }
        }
      }
    }

    if (!empty($klaviyo_settings['image_field'])) {
      $field = $product->get($klaviyo_settings['image_field']);

      if ($field && $field instanceof EntityReferenceFieldItemList) {
        if (!$field->isEmpty() && $file_entity = $field->first()->entity) {
          /** @var \Drupal\file\Entity\File $file_entity */
          $uri = $file_entity->getFileUri();

          if (function_exists('file_create_url')) {
            $this->properties['ImageURL'] = file_create_url($uri);
          }
        }
      }
    }
  }

  /**
   * Asserts that the given entity is a product.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   */
  protected function assertEntity(EntityInterface $entity) {
    if ($entity->getEntityTypeId() != 'commerce_product') {
      throw new \InvalidArgumentException(sprintf('The OrderProperties a "commerce_product" entity, but a "%s" entity was given.', $entity->getEntityTypeId()));
    }
  }

}
