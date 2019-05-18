<?php

namespace Drupal\clu\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;

/**
 * Class CLUSession.
 *
 * @package Drupal\clu\Controller
 */
class CLUSession extends ControllerBase {

  /**
   * Getsessiondetail.
   *
   * @param string $sessionid
   *   A session id
   *
   * @return array
   */
  public function getSessionDetail($sessionid) {
    $query = \Drupal::database()->select('sessions', 's');
    $query->join('users_field_data', 'u', 's.uid = u.uid');
    $query->condition('s.sid', $sessionid, '=');
    $query->fields('u', ['name']);
    $query->fields('u', ['mail']);
    $query->fields('s', ['uid']);
    $query->fields('s', ['sid']);
    $query->fields('s', ['timestamp']);
    $query->fields('s', ['hostname']);
    $query->fields('s', ['session']);
    $results = $query->execute()->fetchAll();

    foreach ($results as $result) {
      $output['clu']['session'] = [
        '#type' => 'fieldset',
        '#title' => t('Session Details'),
        '#collapsible' => FALSE,
        '#collapsed' => FALSE,
      ];
      $output['clu']['session']['name'] = [
        '#type' => 'markup',
        '#markup' => 'User Name : ' . $result->name . ' </br>',
      ];
      $output['clu']['session']['mail'] = [
        '#type' => 'markup',
        '#markup' => 'Mail : ' . $result->mail . ' </br>',
      ];
      $output['clu']['session']['uid'] = [
        '#type' => 'markup',
        '#markup' => 'Uid : ' . $result->uid . ' </br>',
      ];
      $output['clu']['session']['sid'] = [
        '#type' => 'markup',
        '#markup' => 'Sid : ' . $result->sid . ' </br>',
      ];
      $output['clu']['session']['timestamp'] = [
        '#type' => 'markup',
        '#markup' => 'Timestamp : ' . $result->timestamp . ' </br>',
      ];
      $output['clu']['session']['hostname'] = [
        '#type' => 'markup',
        '#markup' => 'Hostname : ' . $result->hostname . ' </br>',
      ];
      $output['clu']['session']['session_data'] = [
        '#type' => 'markup',
        '#markup' => 'Session : ' . $result->session . ' </br>',
      ];
    }
    $output['clu']['back'] = [
      '#type' => 'link',
      '#title' => $this->t('Back'),
      '#attributes' => [
        'class' => ['button', 'button--primary'],
      ],
      '#url' => Url::fromRoute('clu.c_l_u_listing_users'),
    ];

    return $output;
  }

}
