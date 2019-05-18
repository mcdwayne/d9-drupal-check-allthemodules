<?php

/**
 * @file
 * Contains Mirador manifest creator.
 */

namespace Drupal\mirador;

/**
 * Creates mirador manifest json.
 */
class SharedCanvasManifest {
  protected $id = '';
  protected $objectLabel = '';
  protected $metadataUri = '';
  protected $canvases = array();

  /**
   * Initiate the manifest.
   */
  public function __construct($id, $image_viewer_data) {
    $this->id = $id;
    $this->objectLabel = $image_viewer_data['label'];
    $this->description = $image_viewer_data['description'];
    $this->attributes = $image_viewer_data['attribution'];
    $this->license = $image_viewer_data['license'];
    $this->logo = $image_viewer_data['logo'];
    $this->metadata = $image_viewer_data['metadata'];
  }

  /**
   * Add a $canvas to $sequences.
   */
  public function addCanvas($canvas = NULL) {
    if ($canvas != NULL) {
      $this->canvases[] = $canvas->toArray();
    }
  }

  /**
   * Build and return a json string based on what we have in the class.
   */
  public function getManifest() {
    $sc_manifest = array(
      '@context' => 'http://iiif.io/api/presentation/2/context.json',
      '@id' => $this->id,
      '@type' => 'sc:Manifest',
      'label' => $this->objectLabel,
      'description' => $this->description,
      'attribution' => $this->attributes,
      'licence' => $this->license,
      'logo' => $this->logo,
      'metadata' => $this->metadata,
      'sequences' => array(
        array(
          '@type' => 'sc:Sequence',
          'id' => $this->id,
          'label' => $this->objectLabel,
          'canvases' => $this->canvases,
        ),
      ),
    );
    return $sc_manifest;
  }

}
