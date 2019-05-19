<?php

namespace Drupal\username_less\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Contribute form.
 */
class UsernameLessForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'username_less_config_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['usernameless.settings'];
  }

  public function getConfigurationForm($config=null,$key = null) {
    
    $default_values = $config->get();
    $form["user_register[$key]"] = array(
      '#type' => 'checkbox',
      '#title' => t('Username is not required on registration page.'),
      '#default_value' => isset($key)?$default_values[$key]['user_register']:$default_values['user_register'],
    );
    $form["confirm[$key]"] = array(
      '#type' => 'checkbox',
      '#title' => t('Confirm Password is not required on registration page.'),
      '#default_value' => isset($key)?$default_values[$key]['confirm']:$default_values['confirm'],
    );
    $form["user_login[$key]"] = array(
      '#type' => 'radios',
      '#title' => t(''),
      '#default_value' => isset($key)?$default_values[$key]['user_login']:$default_values['user_login'],
      '#options' => array(
        0 => $this->t('Only Email is used on login page'),
        1 => $this->t('Only Username is used on login page'),
        2 => $this->t('Use both username or Email on login page'),
      ),
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('usernameless.settings');    
    $ismodule_enable = \Drupal::moduleHandler()->moduleExists('domain');
    if ($ismodule_enable == TRUE) {
      $loader = \Drupal::service('domain.loader');
      $Alldomains = $loader->loadMultiple();
      if (count($Alldomains) > 0) {
        foreach ($Alldomains as $key => $value) {
          $hostName = $value->get('name');
          $form[$key] = array(
            '#type' => 'details',
            '#title' => t('Configuration for the @hostname', array("@hostname" => $hostName)),
            '#open' => TRUE,
          );
          $form[$key][] = $this->getConfigurationForm($config, $key);
        }
      }
      else {
        $form[] = $this->getConfigurationForm($config);
      }
    }
    else {
      $form[] = $this->getConfigurationForm($config);
    }

    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Submit'),
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getUserInput();
    try {
      $ismodule_enable = \Drupal::moduleHandler()->moduleExists('domain');
      if ($ismodule_enable == TRUE) {
        $domainLoader = \Drupal::service('domain.loader');
        $Alldomains = $domainLoader->loadMultiple();

        if (count($Alldomains) > 0) {
          foreach ($Alldomains as $key => $value) {
            $this->config('usernameless.settings')
                ->set("{$key}.user_register", $values['user_register'][$key])
                ->set("{$key}.user_login", $values['user_login'][$key])
                ->set("{$key}.confirm", $values['confirm'][$key])
                ->save();
          }
        }
      }else{
        $this->config('usernameless.settings')
                ->set("user_register", $values['user_register'][0])
                ->set("user_login", $values['user_login'][0])
                ->set("confirm", $values['confirm'][0])
                ->save();
      }
      drupal_set_message('Setting saved successfully');
    }
    catch (Exception $e) {
      drupal_set_message('Caught exception in username less module: ' . $e->getMessage(), 'error');
    }
  }
}
