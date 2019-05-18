<?php

namespace Drupal\webpay\Controller;

use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Drupal\webpay\Entity\WebpayTransaction;
use Drupal\Core\Render\BareHtmlPageRenderer;
use Drupal\Core\Controller\ControllerBase;
use Drupal\webpay\Entity\WebpayConfig;
use Drupal\webpay\Entity\WebpayConfigInterface;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\webpay\WebpayNormalService;

/**
 * Class WebpayController.
 */
class WebpayController extends ControllerBase {

  /**
   * Return page.
   */
  public function return($commerce_system_id, WebpayConfigInterface $webpay_config) {
    $request = \Drupal::request()->request;
    $token = $request->get('token_ws');

    if (!$token) {
      throw new AccessDeniedHttpException();
    }

    try {
      $webpayService = new WebpayNormalService($webpay_config, $commerce_system_id);
    }
    catch (\Exception $err) {
      return $this->returnErrorMessage();
    }
    $response = $webpayService->getTransactionResult($token);

    if (!$response) {
      return $this->returnErrorMessage();
    }

    // Create the transaction.
    $transaction = WebpayTransaction::create([
      'config_id' => $webpay_config->id(),
      'commerce_code' => $webpay_config->get('commerce_code'),
      'commerce_system_id' => $commerce_system_id,
      'token' => $token,
      'order_number' => $response->buyOrder,
      'session_id' => $response->sessionId,
      'transaction_date' => strtotime($response->transactionDate),
      'vci' => $response->VCI,
      'card_number' => $response->cardDetail->cardNumber,
      'authorization_code' => $response->detailOutput->authorizationCode,
      'payment_type_code' => $response->detailOutput->paymentTypeCode,
      'response_code' => $response->detailOutput->responseCode,
      'amount' => $response->detailOutput->amount,
      'shares_number' => $response->detailOutput->sharesNumber,
    ]);

    try {
      $transaction->save();
    } catch (\Exception $err) {
      $log_message = 'Failed to save the transaction in the database: @err';
      $log_variable = ['@err' => $err->getMessage()];

      \Drupal::logger('webpay')->error($log_message, $log_variable);

      return $this->returnErrorMessage();
    }

    if (($transaction->get('vci')->value == "TSY" || $transaction->get('vci')->value == "") && $transaction->get('response_code')->value === 0) {

      $webpayService->invokeTransactionAccepted($transaction);

      $attachments = \Drupal::service('html_response.attachments_processor');
      $renderer = \Drupal::service('renderer');

      $bareHtmlPageRenderer = new BareHtmlPageRenderer($renderer, $attachments);
      $response = $bareHtmlPageRenderer->renderBarePage([], $this->t('Webpay Return'), 'webpay_return', [
        '#token' => $token,
        '#url' => $response->urlRedirection,
        '#attached' => [
          'library' => [
            'webpay/return',
          ],
        ],
      ]);

      return $response;
    }

    return $webpayService->invokeTransactionRejected($transaction);
  }


  /**
   * Failure page.
   */
  public function failure($token) {
    if (!$transaction = webpay_get_transaction_by_token($token)) {
      throw new AccessDeniedHttpException();
    }

    $build = [
      '#theme' => 'webpay_failure',
      '#order_id' => $transaction->get('order_number')->value,
    ];

    return $build;
  }


  /**
   * Logs page of a webpay config.
   */
  public function logs(WebpayConfigInterface $webpay_config) {
    $build = [];

    $logs = $webpay_config->getLogs();

    if (!empty($logs)) {
      foreach ($logs as $name => $log) {
        $build[$name] = [
          '#type' => 'details',
          '#title' => $name,
        ];
        $build[$name]['logs'] = [
          '#type' => 'inline_template',
          '#template' => '<textarea style="width: 100%; height: 400px; resize: none;" readonly>{{ log }}</textarea>',
          '#context' => ['log' => $log],
        ];
      }
    }
    else {
      $build['empty'] = [
        '#markup' => $this->t('No logs'),
      ];
    }
    

    return $build;
  }


  /**
   * Helper method to show a simple message to the page.
   */
  protected function returnErrorMessage() {
    $message = $this->t('If the error persists, please contact the site administrator.');
    $title = $this->t('Some error detected');

    return [
      '#title' => $title,
      '#type' => 'inline_template',
      '#template' => $message,
    ];
  }


  /**
   * Check access to create the certification configuration.
   */
  public function addCertificationWebpayAccess(AccountInterface $account) {
    $cache_tags = ['config:webpay_config_list'];
    if ($config = WebpayConfig::load('certification')) {
      return AccessResult::forbidden()->addCacheTags($cache_tags);
    }

    return AccessResult::allowedIfHasPermission($account, 'webpay administer')->addCacheTags($cache_tags);
  }
}
