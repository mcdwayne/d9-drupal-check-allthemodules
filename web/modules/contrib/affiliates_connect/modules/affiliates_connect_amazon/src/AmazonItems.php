<?php

namespace Drupal\affiliates_connect_amazon;

/**
 * Class AmazonItems to create the array of AmazonItem objects.
 */
class AmazonItems {

    /**
     * Number of products that Amazon returns.
     * @var string
     */
    public $TotalResults = '';

    /**
     * Number of pages of products that Amazon returns.
     * @var string
     */
    public $TotalPages = '';

    /**
     * URL of Amazon page which contain more products.
     * @var string
     */
    public $MoreSearchResultsUrl = '';

    /**
     * Array of AmazonItem objects
     * @var array
     */
    public $Items = [];

    /**
    * Create an instance of AmazonItems with a SimpleXMLElement object.
    *
    * @param SimpleXMLElement $XML
    * @return AmazonItems
    */
    public static function createWithXml($XML) {

        $AmazonItems = new AmazonItems();

        $XML = $XML->Items;

        if(isset($XML->TotalResults))
        $AmazonItems->TotalResults = (int) $XML->TotalResults;
        if(isset($XML->TotalPages))
        $AmazonItems->TotalPages = (int) $XML->TotalPages;
        if(isset($XML->MoreSearchResultsUrl))
        $AmazonItems->MoreSearchResultsUrl = (string) $XML->MoreSearchResultsUrl;

        foreach($XML->Item as $XMLItem)
        $AmazonItems->Items[] = AmazonItem::createWithXml($XMLItem);

        return $AmazonItems;
    }

    public function __toString() {
        return 'AmazonItems';
    }
}
