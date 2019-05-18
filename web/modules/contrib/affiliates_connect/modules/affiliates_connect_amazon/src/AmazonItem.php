<?php

namespace Drupal\affiliates_connect_amazon;

/**
 * Class AmazonItem to create individual item object from XML.
 */
class AmazonItem {

  /**
   * Author of the product.
   * @var string
   */
  public $Author = '';

  /**
   * Amazon Standard Identification Number.
   * @var string
   */
  public $ASIN = '';

  /**
   * Brand of the product.
   * @var string
   */
  public $Brand = '';

  /**
   * $Manufacturer/Seller of the product.
   * @var string
   */
  public $Manufacturer = '';

  /**
   * Category of the product.
   * @var string
   */
  public $ProductGroup = '';

  /**
   * Title of the product.
   * @var string
   */
  public $Title = '';

  /**
   * URL of the product.
   * @var string
   */
  public $URL = '';

  /**
   * Binding of product
   * @var string
   */
  public $Binding = '';

  /**
   * M.R.P of the product.
   * @var string
   */
  public $Price = '';

  /**
   * Selling price of the product.
   * @var string
   */
  public $SellingPrice = '';

  /**
   * Currency Code of the product according to the locale.
   * @var string
   */
  public $CurrencyCode = '';

  /**
   * Department of the product.
   * @var string
   */
  public $Department = '';

  /**
   * Warranty of the product.
   * @var string
   */
  public $Warranty = '';

  /**
   * Size of the product.
   * @var string
   */
  public $Size = '';

  /**
   * Color of the product.
   * @var string
   */
  public $Color = '';

  /**
   * Image urls of the product.
   * @var array
   */
  private $Images = [];

  /**
   * Smaller than a Small image.
   */
  const IMAGE_SWATCH = 'SwatchImage';

  /**
   * Thumbnail and Small images are the same size.
   */
  const IMAGE_SMALL = 'SmallImage';

  /**
   * Thumbnail and Small images are the same size.
   */
  const IMAGE_THUMBNAIL = 'ThumbnailImage';

  /**
   * Tiny Image.
   */
  const IMAGE_TINY = 'TinyImage';

  /**
   * Medium Image.
   */
  const IMAGE_MEDIUM = 'MediumImage';

  /**
   * Large Image.
   */
  const IMAGE_LARGE = 'LargeImage';

  /**
   * Euro currency.
   */
  const CURRENCY_EUR = 'EUR';

  /**
   * US Dollar.
   */
  const CURRENCY_USD = 'USD';

  /**
   * British Pound.
   */
  const CURRENCY_GBP = 'GBP';

  /**
   * Japanese currency.
   */
  const CURRENCY_JPY = 'JPY';

  /**
   * Indian Currency.
   */
  const CURRENCY_INR = 'INR';

  /**
   * Brazilian Real Currency of Brazil.
   */
  const CURRENCY_BRL = 'BRL';

  /**
   * Canadian currency.
   */
  const CURRENCY_CAD = 'CAD';

  /**
   * Chinese Yuan currency of China.
   */
  const CURRENCY_CNY = 'CNY';

  /**
  * Create an instance of AmazonItem with a SimpleXMLElement object. (->Items)
  *
  * @param SimpleXMLElement $XML
  * @return AmazonItems
  */
  public static function createWithXml($XML) {
    $ItemAttrubutes = $XML->ItemAttributes;

    $AmazonItem = new AmazonItem();

    if(isset($XML->ASIN))
    $AmazonItem->ASIN = (string) $XML->ASIN;

    if(isset($ItemAttrubutes->Binding))
    $AmazonItem->Binding = (string) $ItemAttrubutes->Binding;

    if(isset($ItemAttrubutes->Brand))
    $AmazonItem->Brand = (string) $ItemAttrubutes->Brand;

    if(isset($ItemAttrubutes->Department))
    $AmazonItem->Department = (string) $ItemAttrubutes->Department;

    if(isset($ItemAttrubutes->Manufacturer))
    $AmazonItem->Manufacturer = (string) $ItemAttrubutes->Manufacturer;

    if(isset($ItemAttrubutes->ProductGroup))
    $AmazonItem->ProductGroup = (string) $ItemAttrubutes->ProductGroup;

    if(isset($ItemAttrubutes->Title))
    $AmazonItem->Title = (string) $ItemAttrubutes->Title;

    if(isset($ItemAttrubutes->Warranty))
    $AmazonItem->Warranty = (string) $ItemAttrubutes->Warranty;

    if(isset($ItemAttrubutes->ListPrice->Amount))
    $AmazonItem->Price = (int) $ItemAttrubutes->ListPrice->Amount;

    if(isset($ItemAttrubutes->ListPrice->CurrencyCode))
    $AmazonItem->CurrencyCode = (string) $ItemAttrubutes->ListPrice->CurrencyCode;


    if(isset($XML->DetailPageURL))
    $AmazonItem->URL = (string) $XML->DetailPageURL;

    if (isset($XML->OfferSummary->LowestNewPrice))
    $AmazonItem->SellingPrice = (int) $XML->OfferSummary->LowestNewPrice->Amount;

    if(isset($ItemAttrubutes->Author))
    $AmazonItem->Author = (string) $ItemAttrubutes->Author;

    if(isset($ItemAttrubutes->Size))
    $AmazonItem->Size = (string) $ItemAttrubutes->Size;

    if(isset($ItemAttrubutes->Color))
    $AmazonItem->Color = (string) $ItemAttrubutes->Color;


    $AmazonImageSet = $XML->ImageSets->ImageSet;
    if(isset($XML->ImageSets->ImageSet->SwatchImage))
    $AmazonItem->Images[AmazonItem::IMAGE_SWATCH] = AmazonImage::createWithXml($XML->ImageSets->ImageSet->SwatchImage);
    if(isset($XML->ImageSets->ImageSet->SmallImage))
    $AmazonItem->Images[AmazonItem::IMAGE_SMALL] = AmazonImage::createWithXml($XML->ImageSets->ImageSet->SmallImage);
    if(isset($XML->ImageSets->ImageSet->ThumbnailImage))
    $AmazonItem->Images[AmazonItem::IMAGE_THUMBNAIL] = AmazonImage::createWithXml($XML->ImageSets->ImageSet->ThumbnailImage);
    if(isset($XML->ImageSets->ImageSet->TinyImage))
    $AmazonItem->Images[AmazonItem::IMAGE_TINY] = AmazonImage::createWithXml($XML->ImageSets->ImageSet->TinyImage);
    if(isset($XML->ImageSets->ImageSet->MediumImage))
    $AmazonItem->Images[AmazonItem::IMAGE_MEDIUM] = AmazonImage::createWithXml($XML->ImageSets->ImageSet->MediumImage);
    if(isset($XML->ImageSets->ImageSet->LargeImage))
    $AmazonItem->Images[AmazonItem::IMAGE_LARGE] = AmazonImage::createWithXml($XML->ImageSets->ImageSet->LargeImage);

    return $AmazonItem;
  }

  /**
  * Return currency symbol of $this->CurrencyCode
  *
  * @return string
  */
  public function getCurrency() {
      switch($this->CurrencyCode) {
          case AmazonItem::CURRENCY_EUR :
              return 'EUR;';
          break;
          case AmazonItem::CURRENCY_USD :
              return '$';
          break;
          case AmazonItem::CURRENCY_JPY :
              return '￥';
          break;
          case AmazonItem::CURRENCY_GBP :
              return '£';
          break;
          case AmazonItem::CURRENCY_INR :
              return '₹';
          break;
          case AmazonItem::CURRENCY_BRL :
              return '‎R$';
          break;
          case AmazonItem::CURRENCY_CAD :
              return 'CDN$';
          break;
          case AmazonItem::CURRENCY_CNY :
              return '￥';
          break;
      }
      return '';
  }

  /**
  * Return $this->Price divide by 100. (Exemple : 16.2, 99.99)
  *
  * @return string
  */
  public function getPrice() {
      if($this->Price == '')
      return '';

      return round($this->Price/100, 2);
  }

  /**
  * Return $this->SellingPrice divide by 100. (Exemple : 16.2, 99.99)
  *
  * @return string
  */
  public function getSellingPrice() {
      if($this->SellingPrice == '')
      return '';

      return round($this->SellingPrice/100, 2);
  }

  /**
  * Return an AmazonImage object.
  *
  * @param string $size Use constant IMAGE_(.*) of AmazonItem class
  * @return AmazonImage
  */
  public function getImage($size) {
      return $this->Images[$size];
  }

  public function __toString() {
      return 'AmazonItem';
  }
}
