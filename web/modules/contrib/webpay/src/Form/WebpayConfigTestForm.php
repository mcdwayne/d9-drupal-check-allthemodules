<?php

namespace Drupal\webpay\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\webpay\Entity\WebpayConfigInterface;
use Drupal\webpay\WebpayNormalService;
use Drupal\Core\Url;


/**
 * Form to test the conection with Webpay.
 */
class WebpayConfigTestForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'wepbay_config_test_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, WebpayConfigInterface $webpay_config = NULL) {
    $request = \Drupal::request();

    $end = $request->query->get('end');
    $token = $request->request->get('token_ws');

    $form['information'] = [
      '#type' => 'container',
    ];

    if ($end && $token && ($transaction = webpay_get_transaction_by_token($token))) {
      // Check if the transaction exists.
      $form_state->set('step', 'end');

      $form['information']['voucher'] = [
        '#theme' => 'webpay_voucher',
        '#transaction' => $transaction,
      ];

      $form['init'] = [
        '#type' => 'submit',
        '#value' => t('Try again'),
      ];
    }


    $step = $form_state->get('step');
    if (empty($step)) {
      $form_state->set('step', 'init');

      $form['information']['introduction'] = [
        '#type' => 'inline_template',
        '#template' => '<p>{% trans %}This section can help you to test the connection with Webpay. Press "Test connection" to start.{% endtrans %}</p>',
      ];

      $form['init'] = [
        '#type' => 'submit',
        '#value' => $this->t('Test connection'),
      ];
    }
    elseif ($step == 'go') {
      $data_test = $form_state->get('data_test');
      $amount = $data_test['amount'];
      $order_number = $data_test['order_number'];

      $end_url = new Url('entity.webpay_config.test', [
        'end' => 'end',
        'webpay_config' => $webpay_config->id(),
      ], ['absolute' => TRUE]);

      $webpayService = new WebpayNormalService($webpay_config, 'test_webpay');
      $response = $webpayService->initTransaction($order_number, $amount, $end_url);

      if (!$response) {
        $form['information']['problem'] = [
          '#type' => 'inline_template',
          '#template' => '<p>{% trans %}Exists some problems with the connection with Webpay. Check the keys.{% endtrans %}</p>',
        ];
      }
      else {
        $form['information']['success'] = [
          '#type' => 'inline_template',
          '#template' => '<p>{% trans %}The site successfully connected to Webpay. Now you can do the transaction. Press "Go to Webpay".{% endtrans %}</p>',
        ];
        $form['information']['data'] = [
          '#theme' => 'item_list',
          '#items' => [
            $this->t('Amount') . ': $' . number_format($amount, 0),
            $this->t('Order Number') . ': ' . $order_number,
          ],
        ];
        $form['#action'] = $response->url;
        $form['token_ws'] = [
          '#type' => 'hidden',
          '#value' => $response->token,
        ];
        $form['go'] = [
          '#type' => 'submit',
          '#value' => $this->t('Go to Webpay'),
        ];
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->set('step', 'go');
    $form_state->setRebuild();

    $form_state->set('data_test', [
      'amount' => rand(10000, 150000),
      'order_number' => rand(1, 50000),
    ]);
  }
}
