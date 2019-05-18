<?php

namespace Drupal\panels_breadcrumbs\Form;

use Drupal\page_manager\PageVariantInterface;

/**
 * Trait PageBreadcrumbsFormTrait.
 *
 * @package Drupal\panels_breadcrumbs\Form
 */
trait PageBreadcrumbsFormTrait {

  /**
   * Get panels breadcrumbs settings keys.
   */
  public static function getSettingsKeys() {
    return [
      'state',
      'titles',
      'paths',
      'home',
      'home_text',
    ];
  }

  /**
   * Get types of tokens based on contexts.
   */
  public static function getTypesOfTokens(PageVariantInterface $page_variant) {
    $types = [];
    foreach ($page_variant->getContexts() as $id => $context) {
      if ($type = \Drupal::service('token.entity_mapper')->getTokenTypeForEntityType($id)) {
        $types[] = $type;
      }
    }
    return $types;
  }

}
