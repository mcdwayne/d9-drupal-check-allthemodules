<?php

namespace Drupal\quick_code\Plugin\Menu\LocalAction;

use Drupal\Core\Menu\LocalActionDefault;
use Symfony\Component\HttpFoundation\Request;

/**
 * Modifies the quick code add form local action.
 */
class QuickCodeAddForm extends LocalActionDefault {

  /**
   * {@inheritdoc}
   */
  public function getTitle(Request $request = NULL) {
    if ($quick_code_type = \Drupal::routeMatch()->getParameter('quick_code_type')) {
      return t('Add %type', ['%type' => $quick_code_type->label()]);
    }
    else {
      return parent::getTitle($request);
    }
  }

}
