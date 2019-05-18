<?php

/**
 * @file
 * The "Desk-Net Credentials" form.
 */

namespace Drupal\desk_net\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\desk_net\Controller\ModuleSettings;
use Drupal\desk_net\Collection\NoticesCollection;
use Drupal\desk_net\Controller\RequestsController;

/**
 * Implements the Authorize Form.
 */
class DeskNetCredentialsForm extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'desk_net_credentials';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $html = '<h2>' . t('Desk-Net Credentials') . '</h2>';
    $html .= t('Enter here the API credentials of your Desk-Net account (not your 
   personal Desk-Net user credentials). If you do not have these API credentials
   please request them from');
    $html .= ' <a href="mailto:support@desk-net.com">support@desk-net.com</a>';

    $form['html'] = array(
      '#markup' => $html,
    );
    $form['desk_net_login'] = array(
      '#type' => 'textfield',
      '#title' => t('Desk-Net API Login'),
      '#default_value' => ModuleSettings::variableGet('desk_net_login'),
      '#size' => 30,
      '#maxlength' => 50,
      '#required' => TRUE,
    );

    $form['desk_net_password'] = array(
      '#type' => 'password',
      '#title' => t('Desk-Net API Password'),
      '#size' => 30,
      '#maxlength' => 50,
      '#required' => TRUE,
    );

    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Save'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if (!empty($form_state->getValues())) {
      // Getting new credentials.
      $user_login = $form_state->getValue('desk_net_login');
      $user_password = $form_state->getValue('desk_net_password');

      if (!empty($user_login) && !empty($user_password)) {
        $token = RequestsController::getToken($user_login, $user_password);

        if ($token != FALSE) {
          drupal_set_message(NoticesCollection::getNotice(7), 'status');

          // Saving Desk-Net credentials.
          ModuleSettings::variableSet('desk_net_login', $user_login);
          ModuleSettings::variableSet('desk_net_password', $user_password);
        }
        else {
          drupal_set_message(NoticesCollection::getNotice(8), 'error');
        }
      }
    }
  }
}
