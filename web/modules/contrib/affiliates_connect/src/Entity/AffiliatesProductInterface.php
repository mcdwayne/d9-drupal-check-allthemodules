<?php

namespace Drupal\affiliates_connect\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Affiliates Product entities.
 *
 * @ingroup affiliates_connect
 */
interface AffiliatesProductInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  // Add get/set methods for your configuration properties here.

  /**
   * Gets the Affiliates Product name.
   *
   * @return string
   *   Name of the Affiliates Product.
   */
  public function getName();

  /**
   * Sets the Affiliates Product name.
   *
   * @param string $name
   *   The Affiliates Product name.
   *
   * @return \Drupal\affiliates_connect\Entity\AffiliatesProductInterface
   *   The called Affiliates Product entity.
   */
  public function setName($name);

  /**
   * Gets the Affiliates Product creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Affiliates Product.
   */
  public function getCreatedTime();

  /**
   * Sets the Affiliates Product creation timestamp.
   *
   * @param int $timestamp
   *   The Affiliates Product creation timestamp.
   *
   * @return \Drupal\affiliates_connect\Entity\AffiliatesProductInterface
   *   The called Affiliates Product entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Affiliates Product published status indicator.
   *
   * Unpublished Affiliates Product are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Affiliates Product is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Affiliates Product.
   *
   * @param bool $published
   *   TRUE to set this Affiliates Product to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\affiliates_connect\Entity\AffiliatesProductInterface
   *   The called Affiliates Product entity.
   */
  public function setPublished($published);


  /**
   * Gets the plugin id associated with the affiliates_connnect.
   *
   * @return string
   *   Affiliates Connect Plugin ID.
   */
  public function getPluginId();

  /**
   * Sets the plugin id associated with the affiliates_connnect.
   *
   * @param string $plugin_id
   *   The Affiliates Connect Plugin ID.
   *
   * @return \Drupal\affiliates_connect\Entity\AffiliatesProductInterface
   *   The called Affiliates Product entity.
   */
  public function setPluginId($plugin_id);

  /**
   * Gets the product id of the product that is unique.
   *
   * @return string
   *   The product ID of the product.
   */
  public function getProductId();

  /**
   * Sets the product id of the product that is unique.
   *
   * @param string $product_id
   *   The product ID of the product.
   *
   * @return \Drupal\affiliates_connect\Entity\AffiliatesProductInterface
   *   The called Affiliates Product entity.
   */
  public function setProductId($product_id);


  /**
   * Gets the description of the product.
   *
   * @return string
   *   Description of the product.
   */
  public function getProductDescription();

  /**
   * Sets the Description of the product.
   *
   * @param string $product_description
   *   Description of the product.
   *
   * @return \Drupal\affiliates_connect\Entity\AffiliatesProductInterface
   *   The called Affiliates Product entity.
   */
  public function setProductDescription($product_description);

  /**
   * Gets the warranty details of the product.
   *
   * @return string
   *   Warranty details of the product.
   */
  public function getWarranty();

  /**
   * Sets the warranty details of the product.
   *
   * @param string $product_warranty
   *   Warranty details of the product.
   *
   * @return \Drupal\affiliates_connect\Entity\AffiliatesProductInterface
   *   The called Affiliates Product entity.
   */
  public function setWarranty($product_warranty);

  /**
   * Gets the image urls of the product.
   *
   * @return string
   *   Image urls of the product.
   */
  public function getImageUrls();

  /**
   * Sets the image urls of the product.
   *
   * @param string $image_urls
   *   Image urls of the product.
   *
   * @return \Drupal\affiliates_connect\Entity\AffiliatesProductInterface
   *   The called Affiliates Product entity.
   */
  public function setImageUrls($image_urls);

  /**
   * Gets the Category of the product.
   *
   * @return string
   *   Category of the product.
   */
  public function getCategory();

  /**
   * Sets the Category of the product.
   *
   * @param string $product_family
   *   Category of the product.
   *
   * @return \Drupal\affiliates_connect\Entity\AffiliatesProductInterface
   *   The called Affiliates Product entity.
   */
  public function setCategory($product_family);

  /**
   * Gets the currency of the product.
   *
   * @return string
   *   Currency of the product.
   */
  public function getCurrency();

  /**
   * Sets the currency of the product.
   *
   * @param string $currency
   *   Currency of the product.
   *
   * @return \Drupal\affiliates_connect\Entity\AffiliatesProductInterface
   *   The called Affiliates Product entity.
   */
  public function setCurrency($currency);

  /**
   * Gets the M.R.P of the product.
   *
   * @return string
   *   M.R.P of the product.
   */
  public function getMaximumRetailPrice();

  /**
   * Sets the M.R.P of the product.
   *
   * @param string $maximum_retail_price
   *   M.R.P of the product.
   *
   * @return \Drupal\affiliates_connect\Entity\AffiliatesProductInterface
   *   The called Affiliates Product entity.
   */
  public function setMaximumRetailPrice($maximum_retail_price);

  /**
   * Gets the vendor selling price of the product.
   *
   * @return string
   *   Vendor Selling Price of the product.
   */
  public function getVendorSellingPrice();

  /**
   * Sets the vendor selling price of the product.
   *
   * @param string $vendor_selling_price
   *   Vendor Selling Price of the product.
   *
   * @return \Drupal\affiliates_connect\Entity\AffiliatesProductInterface
   *   The called Affiliates Product entity.
   */
  public function setVendorSellingPrice($vendor_selling_price);

  /**
   * Gets the vendor special price of the product.
   *
   * @return string
   *   Vendor Special Price of the product.
   */
  public function getVendorSpecialPrice();

  /**
   * Sets the vendor special price of the product.
   *
   * @param string $vendor_special_price
   *   Vendor Special Price of the product.
   *
   * @return \Drupal\affiliates_connect\Entity\AffiliatesProductInterface
   *   The called Affiliates Product entity.
   */
  public function setVendorSpecialPrice($vendor_special_price);

  /**
   * Gets the url of the product.
   *
   * @return string
   *   URL of the product.
   */
  public function getProductUrl();

  /**
   * Sets the url of the product.
   *
   * @param string $product_url
   *   URL of the product.
   *
   * @return \Drupal\affiliates_connect\Entity\AffiliatesProductInterface
   *   The called Affiliates Product entity.
   */
  public function setProductUrl($product_url);

  /**
   * Gets the brand of the product.
   *
   * @return string
   *   Brand of the product.
   */
  public function getProductBrand();

  /**
   * Sets the brand of the product.
   *
   * @param string $product_brand
   *   Brand of the product.
   *
   * @return \Drupal\affiliates_connect\Entity\AffiliatesProductInterface
   *   The called Affiliates Product entity.
   */
  public function setProductBrand($product_brand);

  /**
   * Gets the availability of the product.
   *
   * @return bool
   *   Availability of the product.
   */
  public function getProductAvailability();

  /**
   * Sets the availability of the product.
   *
   * @param bool $in_stock
   *   Availability of the product.
   *
   * @return \Drupal\affiliates_connect\Entity\AffiliatesProductInterface
   *   The called Affiliates Product entity.
   */
  public function setProductAvailability($in_stock);


  /**
   * Gets the availability of cash on delivery on the product.
   *
   * @return bool
   *   Cash on Delivery of the product.
   */
  public function getProductCodAvailability();

  /**
   * Sets the availability of cash on delivery on the product.
   *
   * @param bool $cod_available
   *   Cash on Delivery of the product.
   *
   * @return \Drupal\affiliates_connect\Entity\AffiliatesProductInterface
   *   The called Affiliates Product entity.
   */
  public function setProductCodAvailability($cod_available);

  /**
   * Gets the discount percentage on the product price.
   *
   * @return string
   *   Discount percentage on the product price.
   */
  public function getDiscount();

  /**
   * Sets the discount percentage on the product price.
   *
   * @param string $discount_percentage
   *   Discount percentage on the product price.
   *
   * @return \Drupal\affiliates_connect\Entity\AffiliatesProductInterface
   *   The called Affiliates Product entity.
   */
  public function setDiscount($discount_percentage);

  /**
   * Gets the offers on the product.
   *
   * @return string
   *   Offers on the product.
   */
  public function getOffers();

  /**
   * Sets the offers on the product.
   *
   * @param string $offers
   *   Offers on the product.
   *
   * @return \Drupal\affiliates_connect\Entity\AffiliatesProductInterface
   *   The called Affiliates Product entity.
   */
  public function setOffers($offers);

  /**
   * Gets the size on the product.
   *
   * @return string
   *   Size on the product.
   */
  public function getSize();

  /**
   * Sets the size on the product.
   *
   * @param string $size
   *   Size on the product.
   *
   * @return \Drupal\affiliates_connect\Entity\AffiliatesProductInterface
   *   The called Affiliates Product entity.
   */
  public function setSize($size);

  /**
   * Gets the color on the product.
   *
   * @return string
   *   Color on the product.
   */
  public function getColor();

  /**
   * Sets the color on the product.
   *
   * @param string $color
   *   Color on the product.
   *
   * @return \Drupal\affiliates_connect\Entity\AffiliatesProductInterface
   *   The called Affiliates Product entity.
   */
  public function setColor($color);

  /**
   * Gets the seller name of the product.
   *
   * @return string
   *   Seller Name of the product.
   */
  public function getSellerName();

  /**
   * Sets the seller name of the product.
   *
   * @param string $seller_name
   *   Seller Name of the product.
   *
   * @return \Drupal\affiliates_connect\Entity\AffiliatesProductInterface
   *   The called Affiliates Product entity.
   */
  public function setSellerName($seller_name);

  /**
   * Gets the seller avaerage rating of the product.
   *
   * @return string
   *   Seller avaerage rating of the product.
   */
  public function getSellerAverageRating();

  /**
   * Sets the seller avaerage rating of the product.
   *
   * @param string $seller_average_rating
   *   Seller avaerage rating of the product.
   *
   * @return \Drupal\affiliates_connect\Entity\AffiliatesProductInterface
   *   The called Affiliates Product entity.
   */
  public function setSellerAverageRating($seller_average_rating);

  /**
   * Gets the additional data from affiliare APIs or scraper.
   *
   * @return string
   *   Additional data of the product.
   */
  public function getAdditionalData();

  /**
   * Sets the additional data from affiliare APIs or scraper.
   *
   * @param string $additional_data
   *   Additional data of the product.
   *
   * @return \Drupal\affiliates_connect\Entity\AffiliatesProductInterface
   *   The called Affiliates Product entity.
   */
  public function setAdditionalData($additional_data);

  /**
   * Create if not found else update the existing.
   *
   * @param array $value
   *  product data
   * @param ImmutableConfig $config
   *  configuration of the plugin
   */
  public static function createOrUpdate(array $value, ImmutableConfig $config);

}
