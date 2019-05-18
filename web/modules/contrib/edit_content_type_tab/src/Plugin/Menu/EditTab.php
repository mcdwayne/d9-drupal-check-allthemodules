<?php
/**
 * @file
 * Contains \Drupal\edit_content_type_tab\Plugin\Menu\EditTab.
 */

namespace Drupal\edit_content_type_tab\Plugin\Menu;
use Drupal\Core\Menu\LocalTaskDefault;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides route parameters needed to change the title of the
 * edit_content_type_tab local task item.
 */
class EditTab extends LocalTaskDefault {
  /**
   * {@inheritdoc}
   */

  /**
   * Set the title of the task tab dynamically based upon the content type
   * of the current node, determined by the node id in the URL.
   * @return string
   */
  public function getTitle(Request $request = NULL) {
    $options = array();
    if (!empty($this->pluginDefinition['title_context'])) {
      $options['context'] = $this->pluginDefinition['title_context'];
    }

    // Get the current node object from the URL. Note that here we are
    // using a requestService object so we don't directly access the
    // request, in case for any reason we ever need to pass in a request
    //that is not HTTP.
    $requestService = \Drupal::service('edit_content_type_tab.request_service');
    $requestService->setRequest(\Drupal::request());
    $node = $requestService->getRequest()->attributes->get('node');

    // Ensure that we are actually editing a node, not just a
    // path with a node parameter.
    if(gettype($node) == "object") {
      // Get the type of the node as a string
      //$type = $node->gettype();
      $type = node_get_type_label($node);
      $parameter = $this->pluginDefinition['title']->render();

      return t($parameter, array('@type_name' => $type), $options);
    } else {
      return '';
    }
  }
}
