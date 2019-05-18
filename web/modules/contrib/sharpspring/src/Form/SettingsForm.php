<?php

namespace Drupal\sharpspring\Form;

use Drupal\Core\Form\ConfigFormBase;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\sharpspring\SharpSpringClass;

/**
 * Defines the SharpSpring settings form
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'sharpspring_admin_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'sharpspring.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, Request $request = NULL) {
    $sharpspring_config = $this->config('sharpspring.settings');

    $form['account'] = array(
      '#type' => 'fieldset',
      '#title' => t('Tracking Settings'),
    );

    $form['account']['sharpspring_account'] = array(
      '#type' => 'textfield',
      '#title' => t('Web Property ID'),
      '#default_value' => $sharpspring_config->get('sharpspring_account'),
      '#size' => 15,
      '#maxlength' => 20,
      '#required' => TRUE,
      '#description' => t('This ID is unique to each site you want to track separately, and is in the form of KOI-xxxxxxx.'),
    );

    $form['account']['sharpspring_domain'] = array(
      '#type' => 'textfield',
      '#title' => t('Web Property Domain'),
      '#default_value' => $sharpspring_config->get('sharpspring_domain'),
      '#size' => 60,
      '#maxlength' => 60,
      '#required' => TRUE,
      '#description' => t("This is the SharpSpring sub-domain to which tracking information is sent. It is defined in the tracking code as '_setDomain'. Omit the protocol and path: it should fit the following format: koi-xxxxxx.sharpspring.com"),
    );

    $form['account']['finding_values'] = array(
      '#markup' => t('For more information on how to find and set these values please see <a href="http://help.sharpspring.com/customer/portal/articles/1497453-how-to-insert-sharpspring-tracking-code-how-to-add-additional-sites" target="_blank">http://help.sharpspring.com/customer/portal/articles/1497453-how-to-insert-sharpspring-tracking-code-how-to-add-additional-sites</a>'),
    );

    $api_url = Url::fromUri('https://app.sharpspring.com/settings/pubapi');
    $form['api'] = array(
      '#type' => 'fieldset',
      '#title' => t('API Settings'),
      '#description' => t('For advanced integrations, input your Account ID and Secret Key from the :url', array(':url' => Link::fromTextAndUrl('SharpSpring Public API', $api_url))),
    );

    $form['api']['sharpspring_api_account_id'] = array(
      '#title' => t('Account ID'),
      '#type' => 'textfield',
      '#default_value' => $sharpspring_config->get('sharpspring_api_account_id'),
      '#size' => 40,
      '#maxlength' => 40,
    );

    $form['api']['sharpspring_api_secret_key'] = array(
      '#title' => t('Secret Key'),
      '#type' => 'textfield',
      '#default_value' => $sharpspring_config->get('sharpspring_api_secret_key'),
      '#size' => 40,
      '#maxlength' => 40,
    );


    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    // Replace all type of dashes (n-dash, m-dash, minus) with the normal dashes.
    $values['sharpspring_account'] = str_replace(array('–', '—', '−',), '-', $values['sharpspring_account']);

    $this->config('sharpspring.settings')
      ->set('sharpspring_account', $values['sharpspring_account'])
      ->set('sharpspring_domain', $values['sharpspring_domain'])
      ->set('sharpspring_api_account_id', $values['sharpspring_api_account_id'])
      ->set('sharpspring_api_secret_key', $values['sharpspring_api_secret_key'])
      ->save();
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    // Replace all type of dashes (n-dash, m-dash, minus) with the normal dashes.
    $values['sharpspring_account'] = str_replace(array('–', '—', '−',), '-', $values['sharpspring_account']);

    if (!SharpSpringClass::validate_id($values['sharpspring_account'])) {
      $form_state->setErrorByName('sharpspring_account', t('A valid SharpSpring Web Property ID is case sensitive and formatted like KOI-xxxxxxx.'));
    }

    if (!SharpSpringClass::validate_domain($values['sharpspring_domain'])) {
      $form_state->setErrorByName('sharpspring_domain', t('A valid SharpSpring Domain is formatted like koi-XXXXXX.sharpspring.com. Try removing the leading protocol (e.g. https://) or trailing path. (e.g. /somepath).'));
    }

    parent::validateForm($form, $form_state);
  }

}
