<?php

namespace Drupal\affiliates_connect_amazon;

/**
* Class AmazonImage to generate image url with width and height
*/
class AmazonImage {

    /**
     * URL of the picture.
     * @var string
     */
    public $URL = '';

    /**
     * Width of the picture.
     * @var string
     */
    public $Width = '';

    /**
     * Height of the picture.
     * @var string
     */
    public $Height = '';
    /**
    * Create an instance of AmazonItem with a SimpleXMLElement object.
    *
    * @param SimpleXMLElement $XML
    * @return AmazonImage
    */
    public static function createWithXml($XML) {
        $image = new AmazonImage();
        $image->URL = (string) $XML->URL;
        $image->Width = (int) $XML->Height;
        $image->Height = (int) $XML->Width;

        return $image;
    }

    public function __toString() {
        return 'AmazonImage';
    }
}
