<?php

namespace Drupal\section_workflow\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Render\Element;

/**
 * Class DefaultController.
 *
 * @package Drupal\section_workflow\Controller
 */
class DefaultController extends ControllerBase {

  /**
   * Provides the admin page for any section.
   *
   * @return array
   *   A render array as expected by drupal_render().
   */
  public function adminSectionNode() {
    $node_workflow_service = \Drupal::service('section_workflow.section_node');
    $content = $node_workflow_service->section_workflow_context_admin();

    $build = [
      '#theme' => 'section_admin_node',
      '#content' => $content,
      '#type' => 'markup',
      '#markup' => ''
    ];
    return $build;
  }

  /**
   * Provides a list of links to content that can be added from the current
   * context.  The context being the node and it's place within the section config.
   *
   * @return array
   *   A render array as expected by drupal_render().
   */
  public function addToSection() {
    $content = array();
    $section_service = \Drupal::service('section_workflow.section_node');
    // Get a list of content types that we can add from this point within with
    // section.
    $valid_section_types = $section_service->addToSection();
    // Prepare array to be rendered for each content type.
    if (count($valid_section_types) > 0) {
      foreach ($valid_section_types as $key => $section_type) {
        // Ensure we have access according to permissions and ready the content
        // to be rendered.
        $content_type = \Drupal::entityTypeManager()
          ->getStorage('node_type')
          ->load($section_type['content_type']);
        if (isset($content_type)) {
          $access = $this->entityTypeManager()
            ->getAccessControlHandler('node')
            ->createAccess($content_type->id(), NULL, [], TRUE);
          if ($access->isAllowed()) {
            $content[$key]['context_key_name'] = $section_type['context_key_name'];
            $content[$key]['context_key_description'] = $section_type['context_key_description'];
            $content[$key]['section_type'] = $section_type['section_type'];
            $content[$key]['content_type'] = $section_type['content_type'];
            $content[$key]['content_type_label'] = $content_type->label();
            $content[$key]['content_type_description'] = $content_type->getDescription();
          }
        }
      }
    }

    $build = [
      '#theme' => 'section_add_content',
      '#content' => $content,
      '#parent_nid' => $section_service->parentNID,
    ];
    return $build;
  }


  /**
   * Provides a list of links so to be able to add new sections as available
   * according to the section config.
   *
   * @return array
   *   A render array as expected by drupal_render().
   */
  public function addNewSection() {
    $content = array();
    $section_service = \Drupal::service('section_workflow.section_node');

    // Get config for all top level landing pages per section config file.
    $landing_pages = $section_service->getLandingPages();
    foreach ($landing_pages as $landing_key => $landing_page) {
      // Ensure we have access according to permissions and ready the content
      // to be rendered.
      $content_type = \Drupal::entityTypeManager()->getStorage('node_type')->load($landing_page['content_type']);
      if (isset($content_type)) {
        // Check content type access as per permissions.
        $access = $this->entityTypeManager()
          ->getAccessControlHandler('node')
          ->createAccess($content_type->id(), NULL, [], TRUE);

        // If we have permissions then prepare array to render content.
        if ($access->isAllowed()) {
          // Assign config to the render array.
          $content[$landing_key]['config_label'] = $landing_page['config_label'];
          $content[$landing_key]['config_key'] = $landing_page['config_key'];
          $content[$landing_key]['content_type'] = $landing_page['content_type'];
          $content[$landing_key]['type_description'] = $content_type->getDescription();
          $content[$landing_key]['context_description'] = $landing_page['context_description'];
        }
      }
    }

    $build = [
      '#theme' => 'section_add_section',
      '#content' => $content,
      '#parent_nid' => $section_service->parentNID,
    ];
    return $build;
  }
}
