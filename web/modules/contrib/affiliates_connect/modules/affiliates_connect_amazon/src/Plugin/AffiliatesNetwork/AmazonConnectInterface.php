<?php

namespace Drupal\affiliates_connect_amazon\Plugin\AffiliatesNetwork;

use Drupal\affiliates_connect\AffiliatesNetworkInterface;

/**
 * Provides an interface for Amazon Affiliates network plugin.
 */
interface AmazonConnectInterface extends AffiliatesNetworkInterface {

  /**
   * Prepares the signatured link.
   *
   * @return null
   */
  public function prepareLink();

  /**
   * Returns the signatured link.
   *
   * @return AmazonConnect url
   */
  public function getLink();


  /**
   * Executes the associated operation.
   *
   * @return SimpleXMLElement
   */
  public function execute();

  /**
   * Sets a single option in the request. Note that Timestamp and Signature are
   * automatically calculated and will be ignored.
   *
   * @param string $name
   *   The name of the option to set.
   * @param string $value
   *   The value for that option.
   *
   * @return AmazonConnect
   */
  public function setOption($name, $value);

  /**
   * Sets multiple options in single call. Note that Timestamp and Signature are
   * automatically calculated and will be ignored.
   *
   * @param array $options
   *   Options in the form of (string) optionName => (string) optionValue.
   *
   * @return AmazonConnect
   */
  public function setOptions(array $options);

  /**
   * Returns the result of an Amazon request.
   *
   * @return array
   */
  public function getResults();

  /**
   * Returns the signature for an Amazon API.
   *
   * @return string
   */
  public function createSignature(string $signatureString);

  /**
   *  ItemLookup returns an item’s ASIN, Manufacturer, ProductGroup, and
   *  Title of the item.
   *
   * @see https://docs.aws.amazon.com/AWSECommerceService/latest/DG/ItemLookup.html
   *
   * @param string $asin
   *   Amazon Standard Identification Number
   *
   * @return AmazonConnect
   */
  public function itemLookup(string $asin);

  /**
   *  The ItemSearch operation searches for items on Amazon.
   *
   * @see https://docs.aws.amazon.com/AWSECommerceService/latest/DG/ItemSearch.html
   *
   * @param string $keyword
   *   A word or phrase that describes an item, including author, description and so on.
   * @param string $searchIndex
   *   The product category to search.
   *
   * @return AmazonConnect
   */
  public function itemSearch(string $keyword, string $searchIndex);

  /**
   * Returns the Clean formateed data.
   *
   * @param SimpleXMLElement $XML SimpleXMLElement object.
   *
   * @return AmazonItems
   */
  public function cleanResult($XML);

  /**
   * Unset the elements from options array.
   *
   * @param string $name Name of the key.
   */
  public function unsetOption(string $name);

}
