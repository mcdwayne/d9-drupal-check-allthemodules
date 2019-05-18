<?php

namespace Drupal\outlook_calendar\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;

/**
 * Class OutlookAccountController.
 *
 * @package Drupal\outlook_calendar\Controller
 */
class OutlookAccountController extends ControllerBase {

  /**
   * {@inheritdoc}
   */
  public function eventsDisplay() {
    // Create table header.
    $header = [
      'id' => $this->t('ID'),
      'mail' => $this->t('MAIL'),
      'opt' => $this->t('EDIT LINK'),
      'opt1' => $this->t('DELETE LINK'),
    ];

    $form['account'] = [
      '#title' => $this->t('+Add Account'),
      '#type' => 'link',
      '#url' => Url::fromRoute('outlook_calendar.create'),
    ];
    // Display data in site.
    $form['table'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => outlook_calendar_account_list(),
      '#empty' => $this->t('No users found'),
    ];
    return $form;
  }

}
