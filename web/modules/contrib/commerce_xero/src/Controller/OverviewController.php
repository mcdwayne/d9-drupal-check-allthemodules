<?php

namespace Drupal\commerce_xero\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Placeholder controller.
 *
 * @todo Remove this in place of a view of transactions, etc...
 */
class OverviewController extends ControllerBase {

  /**
   * Provides some placeholder text to describe this page.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return array
   *   A render array.
   */
  public function overview(Request $request) {
    return [
      'intro' => [
        '#markup' => $this->t('You may configure your store to have a variety of integrations to your Xero accounting system depending on'),
      ],
      'list' => [
        '#theme' => 'item_list',
        '#items' => [
          $this->t('Commerce payment method'),
          $this->t('Xero data type e.g. Invoices, bank transactions, etc...'),
          $this->t('Xero revenue and bank account'),
        ],
      ],
    ];
  }

}
