<?php

namespace Drupal\webpay\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\webpay\Entity\WebpayConfig;
use Freshwork\Transbank\CertificationBagFactory;
use Freshwork\Transbank\CertificationBag;

/**
 * Class AddCertificationWebpayConfigForm.
 */
class AddCertificationWebpayConfigForm extends ConfirmFormBase {


  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'add_certification_webpay_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('This create the certification commerce code.');
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    if ($config = WebpayConfig::load('certification')) {
      $form_state->setErrorByName('', $this->t('Already exist the certification configuration.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $bag = CertificationBagFactory::integrationWebpayNormal();

    $webpay_config = WebpayConfig::create([
      'id' => 'certification',
      'name' => 'Certification',
      'commerce_code' => '597020000541',
      'environment' => CertificationBag::INTEGRATION,
      'log' => TRUE,
      'client_certificate' => $bag->getClientCertificate(),
      'private_key' => $bag->getClientPrivateKey(),
      'server_certificate' => $bag->getServerCertificate(),
    ]);

    $webpay_config->save();

    $form_state->setRedirect('entity.webpay_config.collection');
  }


  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to create the certification configuration?');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.webpay_config.collection');;
  }

}
