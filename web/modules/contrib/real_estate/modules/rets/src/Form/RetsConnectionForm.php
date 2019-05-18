<?php

namespace Drupal\real_estate_rets\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class RetsConnectionForm.
 *
 * @package Drupal\real_estate_rets\Form
 */
class RetsConnectionForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $real_estate_rets_connection = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $real_estate_rets_connection->label(),
      '#description' => $this->t("Label for the RETS Connection."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $real_estate_rets_connection->id(),
      '#machine_name' => [
        'exists' => '\Drupal\real_estate_rets\Entity\RetsConnection::load',
      ],
      '#disabled' => !$real_estate_rets_connection->isNew(),
    ];

    $login_url = $real_estate_rets_connection->isNew() ? $this->getRequest()->query->get('login_url', '') : $real_estate_rets_connection->getLoginUrl();
    $form['login_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Login URL'),
      '#maxlength' => 255,
      '#default_value' => $login_url,
      '#required' => TRUE,
    ];

    $username = $real_estate_rets_connection->isNew() ? $this->getRequest()->query->get('username', '') : $real_estate_rets_connection->getUsername();
    $form['username'] = [
      '#type' => 'textfield',
      '#title' => $this->t('User Name'),
      '#maxlength' => 255,
      '#default_value' => $username,
      '#required' => TRUE,
    ];

    $password = $real_estate_rets_connection->isNew() ? $this->getRequest()->query->get('password', '') : $real_estate_rets_connection->getPassword();
    $form['password'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Password'),
      '#maxlength' => 255,
      '#default_value' => $password,
      '#required' => TRUE,
    ];

    $rets_version = $real_estate_rets_connection->isNew() ? $this->getRequest()->query->get('rets_version', '') : $real_estate_rets_connection->getRetsVersion();
    $form['rets_version'] = [
      '#type' => 'select',
      '#title' => $this->t('RETS Version'),
      '#options' => [
        '1.5' => '1.5',
        '1.7' => '1.7',
        '1.7.1' => '1.7.1',
        '1.7.2' => '1.7.2',
        '1.8' => '1.8',
      ],
      '#maxlength' => 255,
      '#default_value' => $rets_version,
      '#required' => TRUE,
    ];

    $user_agent = $real_estate_rets_connection->isNew() ? $this->getRequest()->query->get('user_agent', 'PHRETS/2.0') : $real_estate_rets_connection->getUserAgent();
    $form['user_agent'] = [
      '#type' => 'textfield',
      '#title' => $this->t('User Agent'),
      '#maxlength' => 255,
      '#default_value' => $user_agent,
      '#required' => FALSE,
    ];

    $user_agent_password = $real_estate_rets_connection->isNew() ? $this->getRequest()->query->get('user_agent_password', '') : $real_estate_rets_connection->getUserAgentPassword();
    $form['user_agent_password'] = [
      '#type' => 'textfield',
      '#title' => $this->t('User Agent Password'),
      '#maxlength' => 255,
      '#default_value' => $user_agent_password,
      '#required' => FALSE,
    ];

    $http_authentication = $real_estate_rets_connection->isNew() ? $this->getRequest()->query->get('http_authentication', 'digest') : $real_estate_rets_connection->getHttpAuthentication();
    $form['http_authentication'] = [
      '#type' => 'select',
      '#title' => $this->t('HTTP Authentication'),
      '#options' => ['digest' => 'digest', 'basic' => 'basic'],
      '#maxlength' => 255,
      '#default_value' => $http_authentication,
      '#required' => FALSE,
    ];

    $use_post_method = $real_estate_rets_connection->isNew() ? $this->getRequest()->query->get('use_post_method', 'false') : $real_estate_rets_connection->getUsePostMethod();
    $form['use_post_method'] = [
      '#type' => 'select',
      '#title' => $this->t('Use POST Method'),
      '#options' => ['false' => 'false', 'true' => 'true'],
      '#maxlength' => 255,
      '#default_value' => $use_post_method,
      '#required' => FALSE,
    ];

    $disable_follow_location = $real_estate_rets_connection->isNew() ? $this->getRequest()->query->get('disable_follow_location', 'false') : $real_estate_rets_connection->getDisableFollowLocation();
    $form['disable_follow_location'] = [
      '#type' => 'select',
      '#title' => $this->t('Disable Follow Location'),
      '#options' => ['false' => 'false', 'true' => 'true'],
      '#maxlength' => 255,
      '#default_value' => $disable_follow_location,
      '#required' => FALSE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $real_estate_rets_connection = $this->entity;
    $status = $real_estate_rets_connection->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label RETS Connection.', [
          '%label' => $real_estate_rets_connection->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label RETS Connection.', [
          '%label' => $real_estate_rets_connection->label(),
        ]));
    }
    $form_state->setRedirectUrl($real_estate_rets_connection->toUrl('collection'));
  }

}
