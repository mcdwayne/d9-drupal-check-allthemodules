<?php

/**
 * @file
 * Contains Drupal\logout_redirect\Form\LogoutBackConfigForm.
 */

namespace Drupal\logout_redirect\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Database;

/**
 * Displays the disable browser back button settings form.
 */
class LogoutBackConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'logut_redirect_config.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'logout_redirect_config_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function pageTitle() {
    return 'Logout Redirect Configuration';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('logut_redirect_config.settings');

    // Markup for explaination
    $explaination_text = "<div>If you log out of a Drupal site and then hit 
      the back button, you can see pages from the authenticated user's previous 
      session</div><div>So after user logging off if browser back button is
      clicked, this module is redirecting user to default drupal login page or
      you can redirect that user to custom login page which can be set by 
      below textfield</div><br>";

    // Markup for example
    $form['explaination_text'] = [
      '#type' => 'markup',
      '#markup' => $explaination_text,
    ];

    $example_text = "<div>For example you want to redirect user to drupal login page"
        . "<b>(xyz.com/user/login) then enter /user/login </b> in textfield"
        . "<div>And if you want redirect user to  custom login page "
        . "<b>(xyz.com/login) then enter /login</b> in textfield </div>";
    // Markup for example
    $form['example_text'] = [
      '#type' => 'markup',
      '#markup' => $example_text,
    ];

    $form['redirect'] = array(
      '#type' => 'textfield',
      '#title' => $this->t("Add url of page on which you want's to  "
          . "redirect if browser back button is clicked after logout"),
      //'#required' => TRUE,
      '#default_value' => $config->get('logout_redirect'),
    );

    // Submit button
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $logout_redirect_string = trim($form_state->getValue('redirect'));
    $logout_redirect_url = (strstr($logout_redirect_string, ',') ? substr($logout_redirect_string, 0, strpos($logout_redirect_string, ',')) : $logout_redirect_string);
    $this->configFactory->getEditable('logut_redirect_config.settings')
        // Set the submitted configuration setting
        ->set('logout_redirect', $logout_redirect_url)
        ->save();
  }

}
