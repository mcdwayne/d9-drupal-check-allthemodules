<?php

namespace Drupal\imagepin\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Imagepin controller class which delivers UI components.
 */
class ImagepinController extends ControllerBase {

  /**
   * Builds and returns a form for pinning widgets on an image.
   */
  public function pinWidgetsForm(Request $request, $image_fid, $field_name, $entity_type, $bundle, $language, $id) {
    return $this->formBuilder()->getForm('\Drupal\imagepin\Form\PinWidgetsForm', $image_fid, $field_name, $entity_type, $bundle, $language, $id);
  }

}
