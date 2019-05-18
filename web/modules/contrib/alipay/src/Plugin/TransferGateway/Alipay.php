<?php

namespace Drupal\alipay\Plugin\TransferGateway;

use Drupal\Component\Utility\NestedArray;
use Drupal\Console\Bootstrap\Drupal;
use Drupal\Core\Form\FormStateInterface;
use Drupal\finance\Entity\WithdrawInterface;
use Drupal\finance\Plugin\TransferGatewayBase;
use Drupal\entity\BundleFieldDefinition;
use Omnipay\Alipay\AopAppGateway;
use Omnipay\Alipay\Requests\AopTransferToAccountRequest;
use Omnipay\Alipay\Responses\AopTransferToAccountResponse;
use Omnipay\Omnipay;

/**
 * @TransferGateway(
 *   id = "alipay",
 *   label = @Translation("Alipay")
 * )
 */
class Alipay extends TransferGatewayBase {
  /**
   * @inheritdoc
   */
  public function buildFieldDefinitions() {
    $fields['alipay_account'] = BundleFieldDefinition::create('string')
      ->setLabel(t('转账目标账号'))
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => -9,
      ]);

    $fields['alipay_name'] = BundleFieldDefinition::create('string')
      ->setLabel(t('转账目标姓名'))
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => -9,
      ]);

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {

    $form['app_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('App ID'),
      '#description' => $this->t('绑定支付的APP ID'),
      '#default_value' => $this->configuration['app_id'],
      '#required' => TRUE,
    ];

    $form['app_private_key_path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Private key path'),
      '#description' => $this->t('The app private key'),
      '#default_value' => $this->configuration['app_private_key_path']
    ];

    $form['alipay_public_key_path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Public key path'),
      '#description' => $this->t('The alipay public key'),
      '#default_value' => $this->configuration['alipay_public_key_path']
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    if (!$form_state->getErrors()) {
      $values = $form_state->getValue($form['#parents']);
      $this->configuration['app_id'] = $values['app_id'];
      $this->configuration['app_private_key_path'] = $values['app_private_key_path'];
      $this->configuration['alipay_public_key_path'] = $values['alipay_public_key_path'];
    }
  }

  /**
   * 转账
   * @param WithdrawInterface $withdraw
   * @return bool
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  public function transfer(WithdrawInterface $withdraw) {
    //return true; // 直接成功，方便测试
    $transfer = $this->getSDK();

    /** @var AopTransferToAccountRequest $request */
    $request = $transfer->transfer();
    $request->setBizContent([
      'out_biz_no'      => $withdraw->id(),
      'payee_type' => 'ALIPAY_LOGONID',
      'payee_account' => $withdraw->getTransferMethod()->get('alipay_account')->value,
      'amount' => '0.1',//$withdraw->getAmount()->getNumber(),
      'payer_show_name' => \Drupal::config('system.site')->get('name') . '：' . $withdraw->getName(),
      'payee_real_name' => $withdraw->getTransferMethod()->get('alipay_name')->value,
      'remark' => $withdraw->getName()
    ]);

    /** @var AopTransferToAccountResponse $response */
    $response = $request->send();

    if (!$response->isSuccessful()) \Drupal::logger('alipay')->error(var_export($response->getData(), true));
    else {
      $order_id = $response->data('alipay_fund_trans_toaccount_transfer_response.order_id');
      $withdraw->setTransactionNumber($order_id);
    }

    return $response->isSuccessful();
  }

  /**
   * @return AopAppGateway
   */
  private function getSDK() {
    /** @var AopAppGateway $gateway */
    $gateway = Omnipay::create('Alipay_AopApp');
    $gateway->setSignType('RSA2'); //RSA/RSA2

    $gateway->setAppId($this->getConfiguration()['app_id']);
    $gateway->setPrivateKey($this->getConfiguration()['app_private_key_path']);
    $gateway->setAlipayPublicKey($this->getConfiguration()['alipay_public_key_path']);

    return $gateway;
  }
}