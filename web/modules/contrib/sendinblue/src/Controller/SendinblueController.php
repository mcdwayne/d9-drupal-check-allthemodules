<?php

namespace Drupal\sendinblue\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\sendinblue\SendinblueManager;

/**
 * Returns responses for entity browser routes.
 */
class SendinblueController extends ControllerBase {

  /**
   * Checks access for a specific request.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   */
  public function access(AccountInterface $account) {
    return AccessResult::allowedIf(SendinblueManager::isLoggedInState());
  }

  /**
   * Return cusotm page if user ligin or logout.
   */
  public function home() {
    if (SendinblueManager::isLoggedInState()) {
      $home_controller = SendinblueManager::generateHomeLogin();
      $home_controller['#theme'] = 'generateHomeLogin';
    }
    else {
      $home_controller = SendinblueManager::generateHomeLogout();
      $home_controller['#theme'] = 'generateHomeLogout';
    }

    return $home_controller;
  }

  /**
   * Return page for list page (Iframe Sib)
   */
  public function listPage() {
    $listPage_controller['#theme'] = 'iframe_page';
    $listPage_controller['#url_iframe'] = [
      '#plain_text' => SendinblueManager::generateListLogin(),
    ];

    return $listPage_controller;
  }

  /**
   * Return page for compaigns page (Iframe Sib)
   */
  public function listCampaigns() {
    $listPage_controller['#theme'] = 'iframe_page';
    $listPage_controller['#url_iframe'] = [
      '#plain_text' => SendinblueManager::generateCampaignLogin(),
    ];

    return $listPage_controller;
  }

  /**
   * Return page for statistics page (Iframe Sib)
   */
  public function statisticsPage() {
    $listPage_controller['#theme'] = 'iframe_page';
    $listPage_controller['#url_iframe'] = [
      '#plain_text' => SendinblueManager::generateStatisticLogin(),
    ];

    return $listPage_controller;
  }

}
