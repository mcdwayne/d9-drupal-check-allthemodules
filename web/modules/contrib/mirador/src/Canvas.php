<?php

/**
 * @file
 * Creates canvas for mirador.
 */

namespace Drupal\mirador;

/**
 * Creates canvas for mirador.
 */
class Canvas {

  protected $objectId;
  protected $objectLabel;

  /**
   * Initiate the canvas.
   */
  public function __construct($id, $label) {

    $this->objectId = $id;
    $this->objectLabel = $label;
  }

  /**
   * Add image to canvas.
   */
  public function setImage($thumbnail_uri, $image_uri, $resource_uri, $format, $width, $height) {

    $this->thumbnail_uri = $thumbnail_uri;
    $this->imageUri = $image_uri;
    $this->resource_uri = $resource_uri;
    $this->imageFormat = $format;
    $this->imageWidth = $width;
    $this->imageHeight = $height;
  }

  /**
   * Creates the manifest canvas array.
   */
  public function toArray() {

    $manifest_canvas = array(
      '@type' => 'sc:Canvas',
      '@id' => $this->objectId,
      'label' => $this->objectLabel,
      'height' => $this->imageHeight,
      'width' => $this->imageWidth,
      'thumbnail' => array(
        '@id' => $this->thumbnail_uri,
        'service' => array(
          '@context' => 'http://iiif.io/api/image/2/context.json',
          '@id' => $this->resource_uri,
          'profile' => 'http://iiif.io/api/image/2/level2.json',
        ),
      ),
      'images' => array(
        array(
          '@id' => $this->imageUri,
          '@type' => 'oa:Annotation',
          'motivation' => 'sc:Painting',
          'on' => $this->objectId,
          'resource' => array(
            '@id' => $this->resource_uri,
            '@type' => 'dctypes:Image',
            'format' => $this->imageFormat,
            'height' => $this->imageHeight,
            'width' => $this->imageWidth,
            'service' => array(
              '@context' => 'http://iiif.io/api/image/2/context.json',
              '@id' => $this->resource_uri,
              'profile' => 'http://iiif.io/api/image/2/level2.json',
            ),
          ),
        ),
      ),
    );
    return $manifest_canvas;
  }

}
