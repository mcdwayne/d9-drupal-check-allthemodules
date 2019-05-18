<?php

/**
 * @file
 * Contains \Drupal\mirdador\Controller\MiradorManifestController.
 */

namespace Drupal\mirador\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\mirador\SharedCanvasManifest;
use Drupal\mirador\Canvas;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Yaml\Parser;

/**
 * Controller routines for manifest creation.
 */
class MiradorManifestController extends ControllerBase {

  /**
   * Page callback: Returns manifest json.
   */
  public function getManifest($entity_type, $field_name, $entity_id) {
    $mime_type = "image/jpg";
    // Set a default value for width and height, if none specified by the user.
    $width = $height = 4217;

    // Fetch the IIIF image server location from settings.
    $config = \Drupal::config('mirador.settings');
    $iiif_image_server_location = $config->get('iiif_server');

    // Load the entity.
    $entity = entity_load($entity_type, $entity_id);

    //Fetch the mirador settings.
    $field_view = $entity->$field_name->view('mirador');
    $mirador_settings = $field_view[0]['#settings']['mirador_settings'];
    // Parse the mirador settings YAML.
    $yaml = new Parser();
    $settings = $yaml->parse($mirador_settings);
     // Fetch the width, if specified.
    if (!empty($settings['width'])) {
      $width = $settings['width'];
      unset($settings['width']);
    }
    // Fetch the height, if specified.
    if (!empty($settings['height'])) {
      $height = $settings['height'];
      unset($settings['height']);
    }
    // Get the resource data.
    $resource_data = $this->getResourceData($field_name, $entity);
    $image_path = $resource_data['image_path'];
    if (isset($resource_data['mime_type'])) {
      $mime_type = $resource_data['mime_type'];
    }

    // Create the resource URL.
    $resource_url = $iiif_image_server_location . $image_path;

    // Set the resource url as canvas and manifest ID.
    $id = $canvas_id = $resource_url;

    // Generate Metadata.
    $this->generateMetadata($settings, $entity);

    // Invoke the hook to alter the metadata.
    \Drupal::moduleHandler()->invokeAll('mirador_metadata_alter', array(&$entity));

    // Get the metadata.
    $image_viewer_data = $entity->content['image_viewer_data'];

    // Create an instance of SharedCanvasManifest class.
    $manifest = new SharedCanvasManifest($id, $image_viewer_data);

    // Create canvas.
    $canvas = new Canvas($canvas_id, $image_viewer_data['label']);
    $canvas->setImage($resource_url, $resource_url, $resource_url, $mime_type, $width, $height);
    $manifest->addCanvas($canvas);
    $sc_manifest = $manifest->getManifest();

    return new JsonResponse($sc_manifest);
  }

  /**
   * Generates metadata for an entity.
   */
  public function generateMetadata($settings, &$entity) {
    $image_viewer_data = array();
    $image_viewer_data['attributes'] = $image_viewer_data['license'] = $image_viewer_data['logo'] = NULL;
    $image_viewer_data['metadata'] = array();
    // Set a default label and description, if none specified by the user.
    $metadata['label'] = $metadata['description'] = $entity->get('title')->getValue();

    // Fetch the label, if specified.
    if (!empty($settings['label'])) {
      $label_setting = $settings['label'];
      $image_viewer_data['label'] = $entity->$label_setting->value;
      unset($settings['label']);
    }
    // Fetch the description, if specified.
    if (!empty($settings['description'])) {
      $desc_setting = $settings['description'];
      $image_viewer_data['description'] = $entity->$desc_setting->value;
      unset($settings['description']);
    }
    // Fetch the rights value, if specified.
    if (!empty($settings['license'])) {
      $licence_setting = $settings['license'];
      $image_viewer_data['license'] = $entity->$licence_setting->value;
      unset($settings['license']);
    }
    // Fetch the $attributes value, if specified.
    if (!empty($settings['attribution'])) {
      $attribution_setting = $settings['attribution'];
      $image_viewer_data['attribution'] = $entity->$attribution_setting->value;
      unset($settings['attribution']);
    }
    // Fetch the logo value, if specified.
    if (!empty($settings['logo'])) {
      $logo_setting = $settings['logo'];
      $image_viewer_data['logo'] = $entity->$logo_setting->value;
      unset($settings['logo']);
    }
    if (!empty($settings)) {
      // Loop through the settings to generate metadata.
      foreach ($settings as $key => $setting) {
        $value = $entity->get($setting)->getValue();
        $image_viewer_data['metadata'][] = array(
          'label' => $key,
          'value' => $value[0]['value'],
        );
      }
    }
    $entity->content['image_viewer_data'] = $image_viewer_data;
  }

  /**
   * Get the resource data.
   */
  public function getResourceData($field_name, $entity) {
    $output = array();
    $field_value = $entity->get($field_name)->getValue();
    if (isset($field_value['0']['target_id'])) {
      // Load the image and take file uri.
      $fid = $field_value[0]['target_id'];
      $file = file_load($fid);

      // Get the file mimetype.
      $mime_type = $file->get('filemime')->getValue();
      $output['mime_type'] = $mime_type[0]['value'];

      $uri = $file->getFileUri();
      // Exploding the image URI, as the public location
      // will be specified in IIF Server.
      $image_path = explode("public://", $uri);
      $output['image_path'] = $image_path[1];
    }
    else {
      $image_path = $entity->get($field_name)->getValue();
      $output['image_path'] = $image_path[0]['value'];
    }
    return $output;
  }

}
