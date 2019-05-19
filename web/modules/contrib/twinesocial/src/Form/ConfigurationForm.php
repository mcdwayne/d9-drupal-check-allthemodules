<?php

namespace Drupal\twinesocial\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Url;
/**
 * Class ConfigurationForm.
 */
class ConfigurationForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'twinesocial.configuration',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'twinesocial_admin_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('twinesocial.configuration');
    $reset = \Drupal::request()->query->get('twinesocial_reset');

    if(!$reset){
      $details = $this->getAccountDetails($config->get('account_id'));

      if($details) {
        return new RedirectResponse(\Drupal::url('twinesocial.twinesocial_settings_form', ['account_id' => $config->get('account_id')]));
      }
    }
    else{
      drupal_set_message(t('Account Deactivated.'), 'status');
      $config->clear('account_id');
      $config->save();
    }

    $form['account_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Twinesocial Account ID'),
      '#description' => $this->t('You can find your accound ID at the top left menu option when you click on your name, it\'s in the format of 13-XXXXXX'),
      '#default_value' => $config->get('account_id'),
      '#required' => TRUE
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Authenticate'),
    ];

    //return parent::buildForm($form, $form_state);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    $details = $this->getAccountDetails($form_state->getValue('account_id'));
    if(!$details) {
      $form_state->setErrorByName('account_id', t('Error while aouthenticating, please check the account id you entered'));
    }
    return;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $messages = \Drupal::messenger()->deleteAll();
    drupal_set_message(t('Account Activated.'), 'status');
    $this->config('twinesocial.configuration')
      ->set('account_id', $form_state->getValue('account_id'))
      ->save();
      $form_state->setRedirect('twinesocial.twinesocial_settings_form',[
          'account_id' => $form_state->getValue('account_id')
        ]);
  }
  public function getAccountDetails($account_id) {
    $url = 'https://apps.twinesocial.com/api/v1?method=accountinfo&accountId='.$account_id;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_URL,$url);
    $result=curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpcode == 200) {
      return json_decode($result);
    }
    else{
      return false;
    }
  }

}
