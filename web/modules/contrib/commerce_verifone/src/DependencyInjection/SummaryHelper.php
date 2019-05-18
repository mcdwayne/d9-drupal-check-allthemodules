<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is released under commercial license by Lamia Oy.
 *
 * @copyright Copyright (c) 2019 Lamia Oy (https://lamia.fi)
 */


namespace Drupal\commerce_verifone\DependencyInjection;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;

class SummaryHelper
{
  use StringTranslationTrait;

  protected $_keysHelper;
  protected $_configurationHelper;

  protected $_gatewayId;
  protected $_configuration;
  protected $_defaultConfiguration;

  public function __construct($gatewayId, $configuration, $defaultConfiguration)
  {

    $this->_gatewayId = $gatewayId;
    $this->_configuration = $configuration;
    $this->_defaultConfiguration = $defaultConfiguration;

    $this->_keysHelper = new KeysHelper();
    $this->_configurationHelper = new ConfigurationHelper($gatewayId, $configuration, $defaultConfiguration);
  }

  public function getConfigurationDataForDisplay()
  {

    /** Data for display */
    $display = array();

    $display['isLiveMode'] = array(
      'label' => $this->t('Mode'),
      'value' => $this->_configurationHelper->isLiveMode() ? $this->t('Production') : $this->t('Test'),
      'has_desc' => false,
      'has_desc_class' => false
    );

    $display['merchantCode'] = array(
      'label' => $this->t('Verifone Payment merchant agreement code'),
      'value' => $this->_configurationHelper->getMerchantAgreement(),
      'has_desc' => false,
      'has_desc_class' => false
    );
    if ($this->_configurationHelper->getMerchantAgreement() === $this->_configurationHelper->getMerchantAgreementDefault()) {
      $display['merchantCode']['desc'] = $this->t('Default test merchant agreement uses');
      $display['merchantCode']['desc_class'] = 'info';
      $display['merchantCode']['has_desc'] = true;
      $display['merchantCode']['has_desc_class'] = true;
    }

    $display['delayedUrl'] = array(
      'label' => $this->t('Delayed success url'),
      'value' => Url::fromRoute('commerce_verifone.successDelayed', [], ['absolute' => TRUE])->toString(),
      'desc' => $this->t('This is the url that you need to copy to payment provider settings in their portal.'),
      'desc_class' => 'success',
      'has_desc' => true,
      'has_desc_class' => true
    );

    $display['keyHandlingMode'] = array(
      'label' => $this->t('Key handling mode'),
      'value' => $this->_configurationHelper->isKeySimpleMode() ? $this->t('Automatic (Simple)') : $this->t('Manual (Advanced)'),
      'has_desc' => false,
      'has_desc_class' => false
    );

    $display['paymentServiceKey'] = array(
      'label' => $this->t('Path and filename of Verifone Payment public key file'),
      'value' => $this->_configurationHelper->getPaymentPublicKeyPath(),
      'has_desc' => false,
      'has_desc_class' => false
    );

    if (file_exists($this->_configurationHelper->getPaymentPublicKeyPath())) {
      $display['paymentServiceKey']['desc'] = $this->t('Key file is available');
      $display['paymentServiceKey']['desc_class'] = 'success';
      $display['paymentServiceKey']['has_desc'] = true;
      $display['paymentServiceKey']['has_desc_class'] = true;
    } else {
      $display['paymentServiceKey']['desc'] = $this->t('Problem with load key file. Please contact with customer service');
      $display['paymentServiceKey']['desc_class'] = 'success';
      $display['paymentServiceKey']['has_desc'] = true;
      $display['paymentServiceKey']['has_desc_class'] = true;
    }

    if ($this->_configurationHelper->isKeyAdvancedMode()) {

      $path = $this->_configurationHelper->getKeysDirectory();

      $display['directory'] = array(
        'label' => $this->t('Directory for store keys'),
        'value' => $path,
        'has_desc' => false,
        'has_desc_class' => false
      );
      if (file_exists($path) && is_writable($path)) {
        $display['directory']['desc'] = $this->t('Directory configured properly');
        $display['directory']['desc_class'] = 'success';
        $display['directory']['has_desc'] = true;
        $display['directory']['has_desc_class'] = true;
      } else {
        $display['directory']['desc'] = $this->t('Problem with directory configuration. Please check configuration and save.');
        $display['directory']['desc_class'] = 'error';
        $display['directory']['has_desc'] = true;
        $display['directory']['has_desc_class'] = true;
      }

      if($this->_configurationHelper->getMerchantAgreementDefault() !== $this->_configurationHelper->getMerchantAgreement()) {
        $display['shopPrivateKey'] = array(
          'label' => $this->t('Path and filename of shop private key file'),
          'value' => $this->_configurationHelper->getShopPrivateKeyPath(),
          'has_desc' => false,
          'has_desc_class' => false
        );

        if (file_exists($display['shopPrivateKey']['value']) && !empty($this->_configurationHelper->getShopPrivateKeyFileName())) {
          $display['shopPrivateKey']['desc'] = $this->t('Key file is available');
          $display['shopPrivateKey']['desc_class'] = 'success';
          $display['shopPrivateKey']['has_desc'] = true;
          $display['shopPrivateKey']['has_desc_class'] = true;
        } else {
          $display['shopPrivateKey']['desc'] = $this->t('Key file is not available');
          $display['shopPrivateKey']['desc_class'] = 'error';
          $display['shopPrivateKey']['has_desc'] = true;
          $display['shopPrivateKey']['has_desc_class'] = true;
        }
      } else {
        $display['shopPrivateKey'] = array(
          'label' => $this->t('Path and filename of shop private key file'),
          'value' => '',
          'has_desc' => true,
          'has_desc_class' => true,
          'desc' => $this->t('Default key file is used'),
          'desc_class' => 'info'
        );
      }
    } else {

      $display['shopPrivateKey'] = array(
        'label' => $this->t('Path and filename of shop private key file'),
        'value' => 'Key file stored in database',
        'has_desc' => false,
        'has_desc_class' => false
      );

      if ($this->_configurationHelper->getShopPrivateKey() !== null && $this->_configurationHelper->getShopPrivateKey() !== $this->_configurationHelper->getShopPrivateKeyDefault()) {
        $display['shopPrivateKey']['desc'] = $this->t('Key file is available');
        $display['shopPrivateKey']['desc_class'] = 'success';
        $display['shopPrivateKey']['has_desc'] = true;
        $display['shopPrivateKey']['has_desc_class'] = true;
      } elseif(!$this->_configurationHelper->isLiveMode() && $this->_configurationHelper->getMerchantAgreement() === $this->_configurationHelper->getMerchantAgreementDefault()) {
        $display['shopPrivateKey']['desc'] = $this->t('Default key file is used');
        $display['shopPrivateKey']['desc_class'] = 'info';
        $display['shopPrivateKey']['has_desc'] = true;
        $display['shopPrivateKey']['has_desc_class'] = true;
      } else {
        $display['shopPrivateKey']['desc'] = $this->t('Problem with fetch shop private key file. Please check configuration and/or generate key');
        $display['shopPrivateKey']['desc_class'] = 'error';
        $display['shopPrivateKey']['has_desc'] = true;
        $display['shopPrivateKey']['has_desc_class'] = true;
      }
    }

    if ($this->_configurationHelper->isKeySimpleMode() && $this->_configurationHelper->getShopPublicKey() === null && $this->_configurationHelper->getMerchantAgreement() !== $this->_configurationHelper->getMerchantAgreementDefault()) {
      $display['shopPublicKeyContent'] = array(
        'label' => $this->t('Public key file'),
        'value' => '',
        'has_desc' => true,
        'has_desc_class' => true,
        'desc' => $this->t('Problem with fetch shop public key file. Please check configuration and/or generate key'),
        'desc_class' => 'error'
      );
    } elseif ($this->_configurationHelper->getShopPublicKey() !== null) {
      $display['shopPublicKeyContent'] = array(
        'label' => $this->t('Public key file'),
        'value' => $this->_configurationHelper->getShopPublicKey(),
        'has_desc' => true,
        'has_desc_class' => true,
        'desc' => $this->t('Please, copy this key to payment operator configuration settings, otherwise, the payment will be broken.'),
        'desc_class' => 'success'
      );
    }

    return $display;
  }
}