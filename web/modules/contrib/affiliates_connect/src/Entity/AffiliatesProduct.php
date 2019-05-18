<?php

namespace Drupal\affiliates_connect\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\user\UserInterface;

/**
 * Defines the Affiliates Product entity.
 *
 * @ingroup affiliates_connect
 *
 * @ContentEntityType(
 *   id = "affiliates_product",
 *   label = @Translation("Affiliates Product"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\affiliates_connect\AffiliatesProductListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *
 *     "form" = {
 *       "default" = "Drupal\affiliates_connect\Form\AffiliatesProductForm",
 *       "add" = "Drupal\affiliates_connect\Form\AffiliatesProductForm",
 *       "edit" = "Drupal\affiliates_connect\Form\AffiliatesProductForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\affiliates_connect\AffiliatesProductAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "affiliates_product",
 *   admin_permission = "administer affiliates product entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "uid" = "uid",
 *     "langcode" = "langcode",
 *     "status" = "status",
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/affiliates_connect/product/{affiliates_product}",
 *     "add-form" = "/admin/structure/affiliates_connect/product/add",
 *     "edit-form" = "/admin/structure/affiliates_connect/product/{affiliates_product}/edit",
 *     "delete-form" = "/admin/structure/affiliates_connect/product/{affiliates_product}/delete",
 *     "collection" = "/admin/structure/affiliates_connect/products",
 *   },
 *   field_ui_base_route = "entity.affiliates_product.collection"
 * )
 */
class AffiliatesProduct extends ContentEntityBase implements AffiliatesProductInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    $values += [
      'uid' => \Drupal::currentUser()->id(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->get('name')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setName($name) {
    $this->set('name', $name);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->get('uid')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('uid')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('uid', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('uid', $account->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isPublished() {
    return (bool) $this->getEntityKey('status');
  }

  /**
   * {@inheritdoc}
   */
  public function setPublished($published) {
    $this->set('status', $published ? TRUE : FALSE);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginId() {
    return $this->get('plugin_id')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setPluginId($plugin_id) {
    $this->set('plugin_id', $plugin_id);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getProductId() {
    return $this->get('product_id')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setProductId($product_id) {
    $this->set('product_id', $product_id);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getProductDescription() {
    return $this->get('product_description')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setProductDescription($product_description) {
    $this->set('product_description', $product_description);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getWarranty() {
    return $this->get('product_warranty')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setWarranty($product_warranty) {
    $this->set('product_warranty', $product_warranty);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getImageUrls() {
    return $this->get('image_urls')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setImageUrls($image_urls) {
    $this->set('image_urls', $image_urls);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCategory() {
    return $this->get('product_family')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCategory($product_family) {
    $this->set('product_family', $product_family);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCurrency() {
    return $this->get('currency')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCurrency($currency) {
    $this->set('currency', $currency);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getMaximumRetailPrice() {
    return $this->get('maximum_retail_price')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setMaximumRetailPrice($maximum_retail_price) {
    $this->set('maximum_retail_price', $maximum_retail_price);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getVendorSellingPrice() {
    return $this->get('vendor_selling_price')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setVendorSellingPrice($vendor_selling_price) {
    $this->set('vendor_selling_price', $vendor_selling_price);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getVendorSpecialPrice() {
    return $this->get('vendor_special_price')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setVendorSpecialPrice($vendor_special_price) {
    $this->set('vendor_special_price', $vendor_special_price);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getProductUrl() {
    return $this->get('product_url')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setProductUrl($product_url) {
    $this->set('product_url', $product_url);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getProductBrand() {
    return $this->get('product_brand')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setProductBrand($product_brand) {
    $this->set('product_brand', $product_brand);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getProductAvailability() {
    return (bool) $this->get('in_stock')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setProductAvailability($in_stock) {
    $this->set('in_stock', $in_stock ? TRUE : FALSE);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getProductCodAvailability() {
    return (bool) $this->get('cod_available')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setProductCodAvailability($cod_available) {
    $this->set('cod_available', $cod_available ? TRUE : FALSE);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getDiscount() {
    return $this->get('discount_percentage')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setDiscount($discount_percentage) {
    $this->set('discount_percentage', $discount_percentage);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOffers() {
    return $this->get('offers')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setOffers($offers) {
    $this->set('offers', $offers);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getSize() {
    return $this->get('size')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setSize($size) {
    $this->set('size', $size);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getColor() {
    return $this->get('color')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setColor($color) {
    $this->set('color', $color);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getSellerName() {
    return $this->get('seller_name')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setSellerName($seller_name) {
    $this->set('seller_name', $seller_name);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getSellerAverageRating() {
    return $this->get('seller_average_rating')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setSellerAverageRating($seller_average_rating) {
    $this->set('seller_average_rating', $seller_average_rating);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getAdditionalData() {
    return $this->get('additional_data')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setAdditionalData($additional_data) {
    $this->set('additional_data', $additional_data);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function createOrUpdate(array $value, ImmutableConfig $config)
  {
    $product_id = $value['product_id'];
    $nodes = \Drupal::entityTypeManager()
      ->getStorage('affiliates_product')
      ->loadByProperties(['product_id' => $product_id]);
    $product = reset($nodes);

    if (!$product) {
      $product = Self::create([
        'uid' => \Drupal::currentUser()->id(),
        'name' => $value['name'],
        'plugin_id' => $value['plugin_id'],
        'product_id' => $value['product_id'],
        'product_description' => $value['product_description'],
        'image_urls' => $value['image_urls'],
        'product_family' => $value['product_family'],
        'currency' => $value['currency'],
        'maximum_retail_price' => $value['maximum_retail_price'],
        'vendor_selling_price' => $value['vendor_selling_price'],
        'vendor_special_price' => $value['vendor_special_price'],
        'product_url' => $value['product_url'],
        'product_brand' => $value['product_brand'],
        'in_stock' => $value['in_stock'],
        'cod_available' => $value['cod_available'],
        'discount_percentage' => $value['discount_percentage'],
        'product_warranty' => $value['product_warranty'],
        'offers' => (!empty($value['offers'])) ? implode(',', $value['offers']) : '',
        'size' => $value['size'],
        'color' => $value['color'],
        'seller_name' => $value['seller_name'],
        'seller_average_rating' => $value['seller_average_rating'],
        'additional_data' => $value['additional_data'],
        'status' => 1,
      ]);
      $product->save();
      return;
    }
    if ($config->get('full_content')) {
      $product->setName($value['name']);
      $product->setProductDescription($value['product_description']);
      $product->setImageUrls($value['image_urls']);
      $product->setCurrency($value['currency']);
      $product->setMaximumRetailPrice($value['maximum_retail_price']);
      $product->setVendorSellingPrice($value['vendor_selling_price']);
      $product->setVendorSpecialPrice($value['vendor_special_price']);
      $product->setProductUrl($value['product_url']);
      $product->setProductAvailability($value['in_stock']);
      $product->setProductCodAvailability($value['cod_available']);
      $product->setDiscount($value['discount_percentage']);
      $product->setOffers(implode(',', $value['offers']));
      $product->setSize($value['size']);
      $product->setColor($value['color']);
      $product->setSellerName($value['seller_name']);
      $product->setSellerAverageRating($value['seller_average_rating']);
    }
    if ($config->get('price')) {
      $product->setCurrency($value['currency']);
      $product->setMaximumRetailPrice($value['maximum_retail_price']);
      $product->setVendorSellingPrice($value['vendor_selling_price']);
      $product->setVendorSpecialPrice($value['vendor_special_price']);
      $product->setDiscount($value['discount_percentage']);
    }
    if ($config->get('available')) {
      $product->setProductAvailability($value['in_stock']);
    }
    if ($config->get('size')) {
      $product->setSize($value['size']);
    }
    if ($config->get('color')) {
      $product->setColor($value['color']);
    }
    if ($config->get('offers')) {
      $product->setOffers(implode(',', $value['offers']));
    }
    $product->save();
  }


  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The ID of the Affiliates Product record.'))
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setDescription(t('The user ID of author of the Affiliates Product entity.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'author',
        'weight' => 23,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 5,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Product Name'))
      ->setDescription(t('The name of the product.'))
      ->setSettings([
        'max_length' => 1024,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setRequired(TRUE);

    // Name of the affiliates connect plugin associated.
    $fields['plugin_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Plugin ID'))
      ->setDescription(t('Affiliates Connect Plugin ID.'))
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 1,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 1,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setRequired(FALSE);

    // Product ID of the product that is unique.
    $fields['product_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Product ID'))
      ->setDescription(t('The unique productId of the product.'))
      ->setSettings([
        'max_length' => 50,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 2,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 2,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setRequired(TRUE);

    // Description of the product.
    $fields['product_description'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Product Description'))
      ->setDescription(t('The description of the product.'))
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 3,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 3,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setRequired(FALSE);

    // Warranty details of the product.
    $fields['product_warranty'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Product Warranty'))
      ->setDescription(t('The warranty details of the product.'))
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 18,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 18,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setRequired(FALSE);

    // Image urls of the product.
    $fields['image_urls'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Product Image URLs'))
      ->setDescription(t('The image urls of the product.'))
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 5,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 5,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setRequired(FALSE);

    // Category of the product.
    $fields['product_family'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Product Category'))
      ->setDescription(t('The category of the product.'))
      ->setSettings([
        'max_length' => 1024,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 15,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 15,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setRequired(FALSE);

    // Currency of the product.
    $fields['currency'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Currency'))
      ->setDescription(t('The currency of the product.'))
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 6,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 6,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setRequired(TRUE);

    // M.R.P of the product.
    $fields['maximum_retail_price'] = BaseFieldDefinition::create('string')
      ->setLabel(t('M.R.P'))
      ->setDescription(t('The M.R.P of the product.'))
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 7,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 7,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setRequired(FALSE);

    // Vendor Selling Price of the product.
    $fields['vendor_selling_price'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Price'))
      ->setDescription(t('The Vendor Selling Price of the product.'))
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 8,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 8,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setRequired(FALSE);

    // Vendor Special Price of the product.
    $fields['vendor_special_price'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Deal Price'))
      ->setDescription(t('The Vendor Deal Price of the product.'))
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 9,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 9,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setRequired(FALSE);

    // URL of the product.
    $fields['product_url'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Product URL'))
      ->setDescription(t('The url of the product.'))
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setRequired(TRUE);

    // Brand of the product.
    $fields['product_brand'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Product Brand'))
      ->setDescription(t('The brand of the product.'))
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 11,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 11,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setRequired(FALSE);

    // Availability of the product.
    $fields['in_stock'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Product Availability'))
      ->setDescription(t('The availability of the product.'))
      ->setDefaultValue(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => 13,
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 13,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setRequired(FALSE);

    // Cash on Delivery of the product.
    $fields['cod_available'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Cash on Delivery'))
      ->setDescription(t('The availability of the product for COD.'))
      ->setDefaultValue(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 14,
      ])
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => 14,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setRequired(FALSE);

    // Discount percentage on the product price.
    $fields['discount_percentage'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Discount (%)'))
      ->setDescription(t('The discount on the product in %.'))
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 10,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 10,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setRequired(FALSE);

    // Offers on the product.
    $fields['offers'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Offers'))
      ->setDescription(t('The offers on the product.'))
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 12,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 12,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setRequired(FALSE);

    // Size of the product.
    $fields['size'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Size'))
      ->setDescription(t('The size of the product.'))
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 16,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 16,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setRequired(FALSE);

    // Color of the product.
    $fields['color'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Color'))
      ->setDescription(t('The color of the product'))
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 17,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 17,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setRequired(FALSE);

    // Seller Name of the product.
    $fields['seller_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Seller Name'))
      ->setDescription(t('The Seller Name of the product'))
      ->setSettings([
        'max_length' => 1024,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 19,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 19,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setRequired(FALSE);

    // Seller avaerage rating of the product.
    $fields['seller_average_rating'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Seller Rating'))
      ->setDescription(t('The Seller Rating of the product'))
      ->setSettings([
        'max_length' => 1024,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 20,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 20,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setRequired(FALSE);

    // Additional Data collected from affiliare APIs or scraper.
    $fields['additional_data'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Additional data'))
      ->setDescription(t('The additional data kept for future use.'))
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 21,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 21,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setRequired(FALSE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Publishing status'))
      ->setDescription(t('A boolean indicating whether the Affiliates Product is published.'))
      ->setDefaultValue(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => 22,
      ]);

    // User creation time.
    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    // User modified time.
    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    return $fields;
  }

}
