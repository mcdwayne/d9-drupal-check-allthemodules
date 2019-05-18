<?php

namespace Drupal\pagarme_marketplace\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\pagarme\Pagarme\PagarmeSdk;
use Drupal\Core\Url;

/**
 * Class IndexController.
 *
 * @package Drupal\pagarme_marketplace\Controller
 */
class IndexController extends ControllerBase {

  public function companiesList() {
    $pagarme_payment_gateways = \Drupal::entityTypeManager()->getStorage('commerce_payment_gateway')->loadByProperties(['plugin' => array('pagarme_modal', 'pagarme_billet', 'pagarme_credit_card')]);

    $content = [];
    foreach ($pagarme_payment_gateways as $payment_gateway) {
      $config = $payment_gateway->get('configuration');
      $api_key = $config['pagarme_api_key'];
      if (empty($content[$api_key])){
        $pagarme = new PagarmeSdk($api_key);
        $company = $pagarme->getCompanyInfo();
        $content[$api_key] = [
          'title' => $company->name,
          'description' => $company->full_name,
          'url' => Url::fromRoute('pagarme_marketplace.company', array('company' => $api_key))
        ];
      }
    }

    if (count($content)) {
      $build = [
        '#theme' => 'admin_block_content',
        '#content' => $content,
      ];
    } else {
      $build = [
        '#markup' => t('You do not have any administrative items.'),
      ];
    }

    return $build;
  }

  public function companyManagement($company) {
    $content = [];
    $content['info'] = [
      'title' => t('Company information'),
      'description' => t('Primary recipient information'),
      'url' => Url::fromRoute('pagarme_marketplace.company_detail', array('company' => $company))
    ];

    $content['recipients'] = [
      'title' => t('Recipients'),
      'description' => t('List of registered recipients'),
      'url' => Url::fromRoute('pagarme_marketplace.company_recipients', array('company' => $company))
    ];

    $content['split_rules'] = [
      'title' => t('Split rules'),
      'description' => t('List of product split rules'),
      'url' => Url::fromRoute('pagarme_marketplace.company_split_rules', array('company' => $company))
    ];

    $content['transactions'] = [
      'title' => t('Transactions'),
      'description' => t('Transaction management'),
      'url' => Url::fromRoute('pagarme_marketplace.company_transactions', array('company' => $company))
    ];

    $content['transfers'] = [
      'title' => t('Transfers'),
      'description' => t('Transfers management'),
      'url' => Url::fromRoute('pagarme_marketplace.company_transfers', array('company' => $company))
    ];

    if (count($content)) {
      $build = [
        '#theme' => 'admin_block_content',
        '#content' => $content,
      ];
    } else {
      $build = [
        '#markup' => t('You do not have any administrative items.'),
      ];
    }

    return $build;
  }
}
