<?php

namespace Drupal\field_collection_access;

use Drupal\Core\Entity\EntityViewBuilder;
use Drupal\Core\Render\Element;

/**
 * Alter core Entity builder.
 *
 * Alter View Builder for Field Collection Items to apply access control
 * limitations and hide restrictred content.
 */
class FieldCollectionItemViewBuilder extends EntityViewBuilder {

  /**
   * Overrides buildMultiple to hide blocked content.
   */
  public function buildMultiple(array $build_list) {
    $res = parent::buildMultiple($build_list);
    foreach ($res as $key => &$element) {
      $entity = $element["#field_collection_item"];
      $operation = 'view';
      if ($entity->getHostId() && !$entity->access($operation)) {
        // If user does not have access, hide hide all children, and setblocked
        // property.
        $element_children = Element::children($element);
        foreach ($element_children as $key) {
          $element[$key]["#access"] = FALSE;
        }
        $element["#field_collection_access_restricted"] = TRUE;

        // Build blocked content message, and allow other modules to alter
        // textdomain
        // TODO: Add setting to allow you to globaly hide the message.
        $message = [
          "#prefix" => "<div class='fci_restriction_message'>",
          "#type" => "markup",
          "#markup" => "Additional Blocked Content exists on this node.",
          "#suffix" => "<div class='fci_restriction_message'>",
        ];
        \Drupal::moduleHandler()->alter('field_collection_access_restriction_message', $entity, $operation, $message);
        $element["restriction_message"] = $message;
      }
    }
    return $res;
  }

}
