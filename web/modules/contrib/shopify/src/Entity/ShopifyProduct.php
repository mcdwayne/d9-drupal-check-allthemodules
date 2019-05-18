<?php

namespace Drupal\shopify\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\file\FileInterface;
use Drupal\shopify\ShopifyProductInterface;
use Drupal\taxonomy\Entity\Term;
use Drupal\user\UserInterface;

/**
 * Defines the Shopify product entity.
 *
 * @ingroup shopify
 *
 * @ContentEntityType(
 *   id = "shopify_product",
 *   label = @Translation("Shopify product"),
 *   handlers = {
 *     "view_builder" = "Drupal\shopify\ShopifyProductViewBuilder",
 *     "list_builder" = "Drupal\shopify\ShopifyProductListBuilder",
 *     "views_data" = "Drupal\shopify\ShopifyProductViewsData",
 *
 *     "form" = {
 *       "default" = "Drupal\shopify\Entity\Form\ShopifyProductForm",
 *       "add" = "Drupal\shopify\Entity\Form\ShopifyProductForm",
 *       "edit" = "Drupal\shopify\Entity\Form\ShopifyProductForm",
 *       "delete" = "Drupal\shopify\Entity\Form\ShopifyProductDeleteForm",
 *     },
 *     "access" = "Drupal\shopify\ShopifyProductAccessControlHandler",
 *   },
 *   base_table = "shopify_product",
 *   data_table = "shopify_product_field_data",
 *   admin_permission = "administer ShopifyProduct entity",
 *   translatable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "title",
 *     "langcode" = "langcode",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/store/product/{shopify_product}",
 *     "edit-form" = "/admin/shopify_product/{shopify_product}/edit",
 *     "delete-form" = "/admin/shopify_product/{shopify_product}/delete"
 *   },
 *   field_ui_base_route = "shopify_product.settings"
 * )
 */
class ShopifyProduct extends ContentEntityBase implements ShopifyProductInterface {
  use EntityChangedTrait;
  use ShopifyEntityTrait;

  const SHOPIFY_COLLECTIONS_VID = 'shopify_collections';
  const SHOPIFY_TAGS_VID = 'shopify_tags';

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage, array &$values) {
    $values = self::formatValues($values);
    parent::preCreate($storage, $values);
    $values += [
      'user_id' => \Drupal::currentUser()->id(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  private static function formatValues(array $values) {
    if (isset($values['id'])) {
      // We don't want to set the incoming product_id as the entity ID.
      $values['product_id'] = $values['id'];
      unset($values['id']);
    }

    if (isset($values['body_html'])) {
      $values['body_html'] = [
        'value' => $values['body_html'],
        'format' => filter_default_format(),
      ];
    }

    // Format timestamps properly.
    self::formatDatetimeAsTimestamp([
      'created_at',
      'published_at',
      'updated_at',
    ], $values);

    // Set the image for this product.
    if (isset($values['image']) && !empty($values['image'])) {
      $file = self::setupProductImage($values['image']->src);
      if ($file instanceof FileInterface) {
        $values['image'] = array(
          'target_id' => $file->id(),
          'alt' => $values['image']->alt,
        );
      }
    }
    else {
      $values['image'] = NULL;
    }

    // Format variant images as File entities.
    if (isset($values['images']) && is_array($values['images']) && !empty($values['images'])) {
      foreach ($values['images'] as $variant_image) {
        if (count($variant_image->variant_ids)) {
          // Setup these images for the variant.
          foreach ($variant_image->variant_ids as $variant_id) {
            foreach ($values['variants'] as &$variant) {
              if ($variant->id == $variant_id) {
                // Set an image for this variant.
                $variant->image = $variant_image;
              }
            }
          }
        }
        else {
          // This image is not attached to a variant, it should be applied to
          // to the extra images field.
          $image_file_interface = self::setupProductImage($variant_image->src);
          $values['extra_images'][] = array(
            'target_id' => $image_file_interface->id(),
            'alt' => $variant_image->alt,
          );
        }
      }
    }

    if (!isset($values['extra_images']) || empty($values['extra_images'])) {
      $values['extra_images'] = [];
    }

    if (isset($values['tags']) && !is_array($values['tags']) && !empty($values['tags'])) {
      $values['tags'] = explode(', ', $values['tags']);
      $values['tags'] = self::setupTags($values['tags']);
    }
    else {
      $values['tags'] = NULL;
    }

    // Format variants as entities.
    if (isset($values['variants']) && is_array($values['variants'])) {
      foreach ($values['variants'] as &$variant) {
        // Attempt to load this variant.
        $entity = ShopifyProductVariant::loadByVariantId($variant->id);
        if ($entity instanceof ShopifyProductVariant) {
          $entity->update((array) $variant);
          $entity->save();
          $variant = $entity;
        }
        elseif (is_object($variant)) {
          $variant = ShopifyProductVariant::create((array) $variant);
        }
      }
    }

    // Convert options.
    if (isset($values['options'])) {
      $values['options'] = serialize($values['options']);
    }
    return $values;
  }

  /**
   * {@inheritdoc}
   */
  private static function setupTags(array $tags = []) {
    $terms = [];
    foreach ($tags as $tag) {
      // Find out if this tag already exists.
      $term = taxonomy_term_load_multiple_by_name($tag, self::SHOPIFY_TAGS_VID);
      $term = reset($term);
      if ($term) {
        $terms[] = $term;
      }
      else {
        // Need to create this term.
        $terms[] = Term::create([
          'name' => $tag,
          'vid' => self::SHOPIFY_TAGS_VID,
        ]);
      }
    }
    return $terms;
  }

  /**
   * Updates existing product and variants.
   *
   * @param array $values
   *   Shopify product array.
   */
  public function update(array $values = []) {
    $entity_id = $this->id();
    $values = self::formatValues($values);
    foreach ($values as $key => $value) {
      if ($this->__isset($key)) {
        $this->set($key, $value);
      }
    }
    $this->set('id', $entity_id);
  }

  /**
   * {@inheritdoc}
   */
  public function delete() {
    // Delete this products image.
    if ($this->image instanceof FileInterface) {
      $this->image->delete();
    }
    // Delete all variants for this product.
    foreach ($this->get('variants') as $variant) {
      $variant = ShopifyProductVariant::load($variant->target_id);
      $variant->delete();
    }
    parent::delete();
  }

  /**
   * Loads a product by it's product_id.
   *
   * @param string $product_id
   *   Shopify product ID.
   *
   * @return ShopifyProduct
   *   Product.
   */
  public static function loadByProductId($product_id) {
    $products = (array) self::loadByProperties(['product_id' => $product_id]);
    return reset($products);
  }

  /**
   * Loads a product that has a variant with the matching variant_id.
   *
   * @param string $variant_id
   *   Shopify variant ID.
   *
   * @return ShopifyProduct
   *   Product.
   */
  public static function loadByVariantId($variant_id) {
    $variant = ShopifyProductVariant::loadByVariantId($variant_id);
    if ($variant instanceof ShopifyProductVariant) {
      $products = (array) self::loadByProperties(['variants' => $variant->id()]);
      return reset($products);
    }
  }

  /**
   * Load products by their properties.
   *
   * @param array $props
   *   Key/value pair of properties to query by.
   *
   * @return ShopifyProduct[]
   *   Products.
   */
  public static function loadByProperties(array $props = []) {
    return \Drupal::entityTypeManager()
      ->getStorage('shopify_product')
      ->loadByProperties($props);
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->get('user_id')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('user_id')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('user_id', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('user_id', $account->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The ID of the Shopify product entity.'))
      ->setReadOnly(TRUE);

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the Shopify product entity.'))
      ->setReadOnly(TRUE);

    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Title'))
      ->setDescription(t('The title of the Shopify product entity.'))
      ->setRequired(TRUE)
      ->setSettings([
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => -50,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -50,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setDescription(t('The user ID of author of the Shopify product entity.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setDefaultValueCallback('Drupal\node\Entity\Node::getCurrentUserId')
      ->setTranslatable(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => -25,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['langcode'] = BaseFieldDefinition::create('language')
      ->setLabel(t('Language code'))
      ->setDescription(t('The language code for the Shopify product entity.'));

    $fields['variants'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Product variants'))
      ->setDescription(t('Product variants for this product.'))
      ->setSetting('target_type', 'shopify_product_variant')
      ->setSetting('handler', 'default')
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
      ->setDisplayOptions('form', [
        // @todo: Would prefer to use inline entity form, but it's buggy, not working...
        // 'type' => 'inline_entity_form_complex'.
        'type' => 'entity_reference_autocomplete_tags',
        'weight' => -25,
        'settings' => [
        // 'match_operator' => 'CONTAINS',
        // 'autocomplete_type' => 'tags',
        // 'placeholder' => ''.
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['product_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Product ID'))
      ->setDefaultValue('')
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 5,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['image'] = BaseFieldDefinition::create('image')
      ->setLabel(t('Image'))
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'image',
        'weight' => -40,
        'settings' => ['image_style' => '', 'image_link' => 'content'],
      ])
      ->setDisplayOptions('form', [
        'type' => 'image_image',
        'weight' => 2,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['extra_images'] = BaseFieldDefinition::create('image')
      ->setLabel(t('Extra Images'))
      ->setDefaultValue('')
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'image',
        'weight' => -35,
      ])
      ->setDisplayOptions('form', [
        'type' => 'image_image',
        'weight' => 2,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['body_html'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Body HTML'))
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'text_default',
        'weight' => -30,
      ])
      ->setDisplayOptions('form', [
        'type' => 'text_textfield',
        'weight' => -2,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['tags'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tags'))
      ->setDescription(t('Product tags.'))
      ->setSetting('target_type', 'taxonomy_term')
      ->setSetting('target_bundles', ['shopify_tags' => 'shopify_tags'])
      ->setSetting('handler', 'default:taxonomy_term')
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'entity_reference_label',
        'weight' => -20,
        'settings' => ['link' => TRUE],
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete_tags',
        'weight' => -25,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['collections'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Collections'))
      ->setDescription(t('Product collections.'))
      ->setSetting('target_type', 'taxonomy_term')
      ->setSetting('target_bundles', ['shopify_collections' => 'shopify_collections'])
      ->setSetting('handler', 'default:taxonomy_term')
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'entity_reference_label',
        'weight' => -10,
        'settings' => ['link' => TRUE],
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete_tags',
        'weight' => -25,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['handle'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Handle'))
      ->setDefaultValue('')
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -1,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['product_type'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Product type'))
      ->setDefaultValue('')
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['published_scope'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Published scope'))
      ->setDefaultValue('')
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 1,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['vendor'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Vendor'))
      ->setDefaultValue('')
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 2,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['options'] = BaseFieldDefinition::create('map')
      ->setLabel(t('Options'))
      ->setDefaultValue('')
      ->setDisplayOptions('form', [
        'type' => 'map',
        'weight' => 2,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last udpated.'));

    $fields['created_at'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the product was created.'));

    $fields['updated_at'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Updated'))
      ->setDescription(t('The time that the product was last updated.'));

    $fields['published_at'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Published'))
      ->setDescription(t('The time that the product was published.'));

    return $fields;
  }

}
