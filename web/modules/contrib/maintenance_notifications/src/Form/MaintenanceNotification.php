<?php

namespace Drupal\maintenance_notifications\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure hello settings for this site.
 */
class MaintenanceNotification extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'maintenance_notifications_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'maintenance_notifications.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('maintenance_notifications.settings');

    $form['send_mail'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Send Maintenance Notification'),
      '#default_value' => $config->get('send_mail'),
    );

    $form['users_list'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Email(s) to send notification to'),
      '#description' => $this->t('For multiple emails enter comma separated values'),
      '#default_value' => $config->get('users_list'),
      '#states' => array(
        'visible' => array(':input[name=send_mail]' => array('checked' => TRUE)),
      ),
    );
    $form['online'] = array(
      '#type' => 'fieldset',
      '#title' => t('When site goes online'),
      '#states' => array(
        'visible' => array(':input[name=send_mail]' => array('checked' => TRUE)),
      ),
    );
    $form['online']['mail_subject_online'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Mail subject'),
      '#default_value' => $config->get('mail_subject_online'),
      '#states' => array(
        'visible' => array(':input[name=send_mail]' => array('checked' => TRUE)),
      ),
    );
    $form['online']['mail_body_online'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Mail body'),
      '#default_value' => $config->get('mail_body_online'),
      '#states' => array(
        'visible' => array(':input[name=send_mail]' => array('checked' => TRUE)),
      ),
    );
    $form['offline'] = array(
      '#type' => 'fieldset',
      '#title' => t('When site goes offline'),
      '#states' => array(
        'visible' => array(':input[name=send_mail]' => array('checked' => TRUE)),
      ),
    );
    $form['offline']['mail_subject_offline'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Mail subject'),
      '#default_value' => $config->get('mail_subject_offline'),
      '#states' => array(
        'visible' => array(':input[name=send_mail]' => array('checked' => TRUE)),
      ),
    );

    $form['offline']['mail_body_offline'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Mail body'),
      '#default_value' => $config->get('mail_body_offline'),
      '#states' => array(
        'visible' => array(':input[name=send_mail]' => array('checked' => TRUE)),
      ),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * Custom form validation for email.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('send_mail')) {
      foreach ($form_state->getValues() as $key => $vals) {
        if (!$form_state->getValue($key)) {
          $form_state->setErrorByName($key, 'The fields highlighted below are required');
        }
      }
    }
    $maintenance_users_list = $form_state->getValue('users_list');
    if ($maintenance_users_list) {
      $maintenance_users_list = explode(',', $maintenance_users_list);
      foreach ($maintenance_users_list as $maintenance_user) {
        $maintenance_user = trim($maintenance_user);
        if (!\Drupal::service('email.validator')->isValid($maintenance_user)) {
          $form_state->setErrorByName('users_list', $this->t('Invalid e-mail detected @maintenance_user.', ['@maintenance_user' => $maintenance_user]));
          break;
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('maintenance_notifications.settings')
    	->set('send_mail', $form_state->getValue('send_mail'))
    	->set('users_list', $form_state->getValue('users_list'))
    	->set('mail_subject_online', $form_state->getValue('mail_subject_online'))
    	->set('mail_body_online', $form_state->getValue('mail_body_online'))
    	->set('mail_subject_offline', $form_state->getValue('mail_subject_offline'))
    	->set('mail_body_offline', $form_state->getValue('mail_body_offline'))
	->save();
    parent::submitForm($form, $form_state);
  }

}
