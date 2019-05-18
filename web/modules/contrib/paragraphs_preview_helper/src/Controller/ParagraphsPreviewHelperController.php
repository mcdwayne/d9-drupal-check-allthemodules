<?php

namespace Drupal\paragraphs_preview_helper\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;

/**
 * Paragraphs Preview Helper Controller.
 */
class ParagraphsPreviewHelperController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * Display preview string edit form.
   *
   * @param string $paragraphs_type
   *
   * @return array
   */
  public function edit($paragraphs_type) {
    return \Drupal::formBuilder()->getForm('Drupal\paragraphs_preview_helper\Form\ParagraphsPreviewHelperForm', $paragraphs_type);
  }
}
