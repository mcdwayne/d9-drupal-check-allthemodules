<?php
/**
 * @file
 * Contains \Drupal\yandex_metrics\Form\YandexMetricsCounterSettingsForm.
 */

namespace Drupal\yandex_services_auth\Form;

use Drupal\Core\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Provides form for Yandex Services Authorization.
 */
class YandexServicesAuthForm implements FormInterface {

  /**
   * Implements \Drupal\Core\Form\FormInterface::getFormID().
   */
  public function getFormID() {
    return 'yandex_services_auth_form';
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::buildForm().
   */
  public function buildForm(array $form, array &$form_state, $action = NULL) {

    $auth_token = \Drupal::state()->get('yandex_services_auth_token') ?: '';

    if (!empty($auth_token)) {
      $auth_status = '<span style="color:green;">' . t('The application is already authorized.') . '</span>';
    }
    else {
      $auth_status = '<span style="color:red;">' . t('The application is not authorized yet.') . '</span>';
    }
    $form['yandex_services_auth_text'] = array(
      '#type' => 'item',
      '#markup' => $auth_status,
    );

    $form['yandex_services_auth_client_id'] = array(
      '#type' => 'textfield',
      '#title' => t('Client ID'),
      '#description' => t('Your application client ID.'),
      '#required' => TRUE,
      '#default_value' => \Drupal::state()->get('yandex_services_auth_client_id') ?: '',
    );

    $form['yandex_services_auth_client_secret'] = array(
      '#type' => 'textfield',
      '#title' => t('Client Secret'),
      '#description' => t('Your application secret.'),
      '#default_value' => \Drupal::state()->get('yandex_services_auth_client_secret') ?: '',
    );

    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => !empty($auth_token) ? t('Re-Authorize') : t('Authorize'),
    );

    return $form;
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::validateForm().
   */
  public function validateForm(array &$form, array &$form_state) {
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::submitForm().
   */
  public function submitForm(array &$form, array &$form_state) {

    $client_id = $form_state['values']['yandex_services_auth_client_id'];

    \Drupal::state()->set('yandex_services_auth_client_id', $form_state['values']['yandex_services_auth_client_id']);
    \Drupal::state()->set('yandex_services_auth_client_secret', $form_state['values']['yandex_services_auth_client_secret']);

    $request_url = 'https://oauth.yandex.ru/authorize';
    $params = array(
      'response_type' => 'code',
      'client_id' => $client_id,
    );

    $form_state['redirect'] = url($request_url, array('query' => $params, 'external' => TRUE));
  }
}
