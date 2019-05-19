<?php

namespace Drupal\zuora\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\zuora\Rest\Client;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class HostedPaymentPageCallbackController implements ContainerInjectionInterface {

  protected $request;

  protected $session;

  protected $rest_client;

  public function __construct(RequestStack $request_stack, SessionInterface $session, Client $client) {
    $this->request = $request_stack->getCurrentRequest();
    $this->session = $session;
    $this->rest_client = $client;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('request_stack'),
      $container->get('session'),
      $container->get('zuora.client.rest')
    );
  }


  /**
   * Checks access for the callback.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Account to check access for.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(AccountInterface $account) {
    $required = ['success', 'token', 'responseFrom', 'signature', 'tenantId'];
    foreach ($required as $required_item) {
      // If any of the required parameters are missing, deny access.
      if (!$this->request->query->has($required_item)) {
        return AccessResult::forbidden('Missing required parameter ' . $required_item);
      }
    }
    $signature = $this->session->get('zuora_payment_page_signature');
    $response = $this->rest_client->post('/rsa-signatures/decrypt', [
      'publicKey' => $signature['key'],
      'method' => 'POST',
      'signature' => $this->request->query->get('signature'),
    ]);
    if ($response['success'] !== TRUE) {
      return AccessResult::forbidden('Signature could not be verified');
    }

    return AccessResult::allowed();
  }

  /**
   * Controller callback to execute JS commands on parent window.
   *
   * @return array
   *   The render array.
   */
  public function callback() {
    $success = ($this->request->query->get('success') == 'true');

    $build = [];
    $build['#attached']['library'][] = 'zuora/payment_page_callback';
    $build['#attached']['drupalSettings']['zuoraPaymentPageCallback'] = [
      'action' => 'submit',
      'success' => $success,
    ];

    if ($success === FALSE) {
      $error_code = Xss::filter($this->request->query->get('errorCode'));
      $error_message = Xss::filter($this->request->query->get('errorMessage'));
      $build['#attached']['drupalSettings']['zuoraPaymentPageCallback']['errorCode'] = $error_code;
      $build['#attached']['drupalSettings']['zuoraPaymentPageCallback']['errorMessage'] = $error_message;
    }
    else {
      $ref_id = Xss::filter($this->request->query->get('refId'));
      $build['#attached']['drupalSettings']['zuoraPaymentPageCallback']['refId'] = $ref_id;

    }

    return $build;
  }

}
