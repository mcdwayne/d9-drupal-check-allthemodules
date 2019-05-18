<?php
namespace Drupal\shopify_api\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Shopify;

class ShopifyApiAdminForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'shopify_api_admin_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'shopify_api.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    module_load_install('shopify_api');

    $config = $this->config('shopify_api.settings');

    // Connection
    $form['connection'] = array(
      '#type' => 'details',
      '#title' => t('Connection'),
      '#open' => TRUE,
    );
    $form['connection']['help'] = array(
      '#type' => 'details',
      '#title' => t('Help'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
    );
    $form['connection']['help']['list'] = array(
      '#theme' => 'item_list',
      '#type' => 'ol',
      '#items' => array(
        t('Log in to your Shopify store in order to access the administration section.'),
        t('Click on "Apps" on the left-side menu.'),
        t('Click "Private Apps" on the top-right of the page.'),
        t('Enter a name for the application. This is private and the name does not matter.'),
        t('Click "Save App".'),
        t('Copy the API Key, Password, and Shared Secret values into the connection form.'),
        t('Enter your Shopify store URL as the "Domain". It should be in the format of [STORE_NAME].myshopify.com.'),
        t('Click "Save configuration".'),
      ),
    );
    $form['connection']['domain'] = array(
      '#type' => 'textfield',
      '#title' => t('Domain'),
      '#required' => TRUE,
      '#default_value' => $config->get('domain'),
      '#description' => t('Do not include http:// or https://.'),
    );
    $form['connection']['api_key'] = array(
      '#type' => 'textfield',
      '#title' => t('API key'),
      '#required' => TRUE,
      '#default_value' => $config->get('api_key'),
    );
    $form['connection']['password'] = array(
      '#type' => 'textfield',
      '#title' => t('Password'),
      '#required' => TRUE,
      '#default_value' => $config->get('password'),
    );
    $form['connection']['shared_secret'] = array(
      '#type' => 'textfield',
      '#title' => t('Shared Secret'),
      '#required' => TRUE,
      '#default_value' => $config->get('shared_secret'),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    try {
      $client = new Shopify\PrivateApp($form_state->getValue('domain'), $form_state->getValue('api_key'), $form_state->getValue('password'), $form_state->getValue('shared_secret'));
      $shop_info = $client->getShopInfo();
      drupal_set_message(t('Successfully connected to %store.', ['%store' => $shop_info->name]));
    } catch (\Exception $e) {
      $form_state->setErrorByName(NULL, 'API Error: ' . $e->getMessage());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('shopify_api.settings')
      ->set('domain', $form_state->getValue('domain'))
      ->set('api_key', $form_state->getValue('api_key'))
      ->set('password', $form_state->getValue('password'))
      ->set('shared_secret', $form_state->getValue('shared_secret'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
