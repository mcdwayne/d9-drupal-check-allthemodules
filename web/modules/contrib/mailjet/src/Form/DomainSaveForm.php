<?php
/**
 *  * @file
 *  * Contains \Drupal\mailjet\Form\DomainSettingsForm.
 *  */

namespace Drupal\mailjet\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

class DomainSaveForm extends ConfigFormBase {

  public function getFormId() {

    return 'domain_save_custom_admin_form';

  }

  protected function getEditableConfigNames() {

    return ['config.save_domain'];

  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form = [];

    $form['domain'] = [
      '#type' => 'textfield',
      '#title' => t('Domain'),
      '#description' => t('Please enter the domain name that you will be adding to your Mailjet account.'),
      '#required' => FALSE,
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Add'),
    ];

    return  $form ;

  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

    $form_values = $form_state->getValues();

    if (!preg_match("/^(([a-zA-Z0-9]|[a-zA-Z0-9][a-zA-Z0-9\-]*[a-zA-Z0-9])\.)+([A-Za-z0-9]|[A-Za-z0-9][A-Za-z0-9\-]*[A-Za-z0-9])$/", $form_values['domain'])) {
      $form_state->setErrorByName('domain', t('Please enter a valid domain name.'));
      return FALSE;
    }
    return TRUE;

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    global $base_url;

    $domain = $form_state->getValue('domain');
    if (mailjet_user_domain_add($domain)) {
      drupal_set_message(t('Your domain @domain has successfully been added to Mailjet. 
      To confirm domain ownership you must create an empty text file and place it in the root folder of the website.  
      The file name is shown in the table below on the same line as your newly added domain.', [
        '@domain' => $domain,
      ]));

      $response = new RedirectResponse($base_url . '/admin/config/system/mailjet/domains');
      $response->send();

    }
    else {
      return FALSE;
    }

  }
}