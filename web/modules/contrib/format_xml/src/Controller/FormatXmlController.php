<?php
/**
 * @file
 * Contains \Drupal\format_xml\Controller\FormatXmlController.
 */
namespace Drupal\format_xml\Controller;


use Drupal\Core\Controller\ControllerBase;


class FormatXmlController extends ControllerBase {
  public function content() {
    return array(
        '#markup' => '' . t('Hello there!') . '',
    );
  }
}