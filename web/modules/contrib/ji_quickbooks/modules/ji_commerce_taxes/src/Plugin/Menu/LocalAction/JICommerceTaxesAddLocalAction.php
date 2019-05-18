<?php

namespace Drupal\ji_commerce_taxes\Plugin\Menu\LocalAction;

use Drupal\Core\Menu\LocalActionDefault;
use Symfony\Component\HttpFoundation\Request;

/**
 * Defines a local action plugin with a dynamic title.
 */
class JICommerceTaxesAddLocalAction extends LocalActionDefault {

  /**
   * {@inheritdoc}
   */
  public function getTitle(Request $request = NULL) {
    return t('@arg', ['@arg' => 'Sync QuickBooks taxes']);
  }

}
