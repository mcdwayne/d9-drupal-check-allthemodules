<?php

/**
 * @file
 * Contains \Drupal\saml_sp\Form\GovDeliveryTestSubscriptionsForm.
 */

namespace Drupal\govdelivery\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Url;
use Drupal\Core\Link;

class GovDeliveryTestSubscriptionsForm extends ConfigFormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'govdelivery_test_subscriptions';
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /*
    $config = $this->config('saml_sp.settings');
    $values = $form_state->getValues();
    $this->configRecurse($config, $values['contact'], 'contact');
    $this->configRecurse($config, $values['organization'], 'organization');
    $this->configRecurse($config, $values['security'], 'security');
    $config->set('strict', $values['strict']);
    $config->set('debug', $values['debug']);
    $config->set('key_location', $values['key_location']);
    $config->set('cert_location', $values['cert_location']);
    $config->set('entity_id', $values['entity_id']);

    $config->save();

    if (method_exists($this, '_submitForm')) {
      $this->_submitForm($form, $form_state);
    }
    */

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    /*
    // ensure the cert and key files are provided and exist in the system if
    // signed or encryption options require them
    $values = $form_state->getValues();
    if (
      $values['security']['authnRequestsSigned'] ||
      $values['security']['logoutRequestSigned'] ||
      $values['security']['logoutResponseSigned'] ||
      $values['security']['wantNameIdEncrypted'] ||
      $values['security']['signMetaData']
    ) {
      foreach (['key_location', 'cert_location'] AS $key) {
        if (empty($values[$key])) {
          $form_state->setError($form[$key], $this->t('The %field must be provided.', array('%field' => $form[$key]['#title'])));
        }
        else if (!file_exists($values[$key])) {
          $form_state->setError($form[$key], $this->t('The %input file does not exist.', array('%input' => $values[$key])));
        }
      }
    }
    */
  }

  /**
   * recursively go through the set values to set the configuration
   */
  protected function configRecurse($config, $values, $base = '') {
    foreach ($values AS $var => $value) {
      if (!empty($base)) {
        $v = $base . '.' . $var;
      }
      else {
        $v = $var;
      }
      if (!is_array($value)) {
        $config->set($v, $value);
      }
      else {
        $this->configRecurse($config, $value, $v);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['govdelivery.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form = [], FormStateInterface $form_state) {
    $config = $this->config('govdelivery.settings');




    return parent::buildForm($form, $form_state);
  }
}
