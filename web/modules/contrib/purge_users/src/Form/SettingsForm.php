<?php

namespace Drupal\purge_users\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class SettingsForm.
 *
 * @package Drupal\purge_users\Form
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    parent::__construct($config_factory);
    $this->config = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Instantiates this form class.
    return new static(
    // Load the service required to construct this class.
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'purge_users_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $user_settings = \Drupal::config('user.settings');
    $anonymous_name = $user_settings->get('anonymous');
    $config = $this->config('purge_users.settings');
    $roles_array = user_role_names(TRUE);
    $moduleHandler = \Drupal::service('module_handler');

    $notification_text = $this->t("Dear User, \n\nYour account has been deleted due the website’s policy to automatically remove users who match certain criteria. If you have concerns regarding the deletion, please talk to the administrator of the website. \n\nThank you");
    $form['never_loggedin_user'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Purge users who have never logged in for'),
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
    );
    $form['never_loggedin_user']['user_never_lastlogin_value'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Value'),
      '#default_value' => $config->get('user_never_lastlogin_value'),
      '#prefix' => '<div class="purge-interval-selector clearfix">',
      '#attributes' => array('class' => array('purge-value')),
    );
    $form['never_loggedin_user']['user_never_lastlogin_period'] = array(
      '#title' => $this->t('Period'),
      '#type' => 'select',
      '#options' => array(
        'days' => $this->t('Days'),
        'month' => $this->t('Months'),
        'year' => $this->t('Year'),
      ),
      '#default_value' => $config->get('user_never_lastlogin_period'),
      '#attributes' => array('class' => array('purge-period')),
      '#suffix' => '</div>',
    );
    $form['never_loggedin_user']['enabled_never_loggedin_users'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Enabled'),
      '#default_value' => $config->get('enabled_never_loggedin_users'),
    );

    $form['not_loggedin_user'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Purge users who have not logged in for'),
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
    );
    $form['not_loggedin_user']['user_lastlogin_value'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Value'),
      '#default_value' => $config->get('user_lastlogin_value'),
      '#prefix' => '<div class="purge-interval-selector clearfix">',
      '#attributes' => array('class' => array('purge-value')),
    );

    $form['not_loggedin_user']['user_lastlogin_period'] = array(
      '#title' => $this->t('Period'),
      '#type' => 'select',
      '#options' => array(
        'days' => $this->t('Days'),
        'month' => $this->t('Months'),
        'year' => $this->t('Year'),
      ),
      '#default_value' => $config->get('user_lastlogin_period'),
      '#attributes' => array('class' => array('purge-period')),
      '#suffix' => '</div>',
    );

    $form['not_loggedin_user']['enabled_loggedin_users'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Enabled'),
      '#default_value' => $config->get('enabled_loggedin_users'),
    );

    $form['not_active_user'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Purge users whose account has not been activated for'),
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
    );
    $form['not_active_user']['user_inactive_value'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Value'),
      '#default_value' => $config->get('user_inactive_value'),
      '#prefix' => '<div class="purge-interval-selector clearfix">',
      '#attributes' => array('class' => array('purge-value')),
    );

    $form['not_active_user']['user_inactive_period'] = array(
      '#title' => $this->t('Period'),
      '#type' => 'select',
      '#options' => array(
        'days' => $this->t('Days'),
        'month' => $this->t('Months'),
        'year' => $this->t('Year'),
      ),
      '#default_value' => $config->get('user_inactive_period'),
      '#attributes' => array('class' => array('purge-period')),
      '#suffix' => '</div>',
    );

    $form['not_active_user']['enabled_inactive_users'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Enabled'),
      '#default_value' => $config->get('enabled_inactive_users'),
    );

    $form['blocked_user'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Purge users who have been blocked for'),
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
    );
    $form['blocked_user']['user_blocked_value'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Value'),
      '#default_value' => $config->get('user_blocked_value'),
      '#prefix' => '<div class="purge-interval-selector clearfix">',
      '#attributes' => array('class' => array('purge-value')),
    );

    $form['blocked_user']['user_blocked_period'] = array(
      '#title' => $this->t('Period'),
      '#type' => 'select',
      '#options' => array(
        'days' => $this->t('Days'),
        'month' => $this->t('Months'),
        'year' => $this->t('Year'),
      ),
      '#default_value' => $config->get('user_blocked_period'),
      '#attributes' => array('class' => array('purge-period')),
      '#suffix' => '</div>',
    );

    $form['blocked_user']['enabled_blocked_users'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Enabled'),
      '#default_value' => $config->get('enabled_blocked_users'),
    );

    $form['purge_users_roles'] = array(
      '#title' => $this->t('Limit purge to the following roles'),
      '#description' => $this->t('Limit users to a particular role.'),
      '#type' => 'checkboxes',
      '#required' => TRUE,
      '#options' => $roles_array,
      '#default_value' => is_array($config->get('purge_users_roles')) ? $config->get('purge_users_roles') : array(),
    );

    $form['purge_user_cancel_method'] = array(
      '#type' => 'radios',
      '#required' => TRUE,
      '#title' => $this->t('When cancelling a user account'),
      '#default_value' => $config->get('purge_user_cancel_method'),
      '#options' => array(
        'user_cancel_reassign' => $this->t('Delete the account and make its content belong to the %anonymous-name user.', array('%anonymous-name' => $anonymous_name)),
        'user_cancel_delete' => $this->t('Delete the account and its content.'),
      ),
    );

    $form['purge_on_cron'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Purge users only on cron run'),
      '#default_value' => $config->get('purge_on_cron'),
    );

    $form['user_notification'] = array(
      '#type' => 'details',
      '#title' => $this->t('User Deletion Notification'),
      '#open' => FALSE,
    );

    $form['user_notification']['inactive_user_notify_text'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Body of user notification e-mail'),
      '#default_value' => $config->get('inactive_user_notify_text') ? $config->get('inactive_user_notify_text') : $notification_text,
      '#cols' => 70,
      '#rows' => 10,
      '#description' => $this->t('Customize the body of the notification e-mail sent to the user.'),
      '#required' => TRUE,
    );

    if ($moduleHandler->moduleExists('token')) {
      $form['user_notification']['token_help'] = array(
        '#theme' => 'token_tree_link',
        '#token_types' => array('user'),
        '#show_restricted' => TRUE,
        '#show_nested' => FALSE,
      );
    }

    $form['user_notification']['send_email_notification'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Enable'),
      '#description' => $this->t('Check to send email notification to purged users.'),
      '#default_value' => $config->get('send_email_notification'),
    );

    $form['purge_users_now'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Purge users now'),
      '#attributes' => array(
        'class' => array(
          'purge-now',
          'button button--primary',
        ),
      ),
      '#submit' => array('::purgeUsersNowSubmit'),
    );
    // Attach library.
    $form['#attached']['library'][] = 'purge_users/styling';

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function getEditableConfigNames() {
    return ['purge_users.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $login_never_value = $form_state->getValue(['user_never_lastlogin_value']);
    $login_never_period = $form_state->getValue(['user_never_lastlogin_period']);
    $login_value = $form_state->getValue(['user_lastlogin_value']);
    $login_period = $form_state->getValue(['user_lastlogin_period']);
    $inactive_value = $form_state->getValue(['user_inactive_value']);
    $inactive_period = $form_state->getValue(['user_inactive_period']);
    $block_value = $form_state->getValue(['user_blocked_value']);
    $block_period = $form_state->getValue(['user_blocked_period']);
    $enable_blocked = $form_state->getValue(['enabled_blocked_users']);
    $enable_loggedin = $form_state->getValue(['enabled_loggedin_users']);
    $enable_never_loggedin = $form_state->getValue(['enabled_never_loggedin_users']);
    $enable_inactive = $form_state->getValue(['enabled_inactive_users']);

    // Validate text field to only contain numeric values.
    if ($login_never_value != '' && !is_numeric($login_never_value) || $login_value != '' && !is_numeric($login_value) || $inactive_value != '' && !is_numeric($inactive_value) || $block_value != '' && !is_numeric($block_value)) {
      $form_state->setErrorByName('Value validator', $this->t('Value must be a number.'));
    }
    // Validate to set purge period more than 10 days.
    if ($login_never_period == 'days' && !empty($login_never_value) && $login_never_value <= 10 || $login_period == 'days' && !empty($login_value) && $login_value <= 10 || $inactive_period == 'days' && !empty($inactive_value) && $inactive_value <= 10 || $block_period == 'days' && !empty($block_value) && $block_value <= 10) {
      $form_state->setErrorByName('Period limit', $this->t('Purge period should be more than 10 days.'));
    }
    // Make sure one of the fieldset is checked.
    if ($enable_loggedin == 0 && $enable_inactive == 0 && $enable_blocked == 0 && $enable_never_loggedin == 0) {
      $form_state->setErrorByName('Enable fieldset', $this->t('Please enable one of the Conditions:  Never logged in users, Not logged in users, Inactive users or Blocked users.'));
    }
    // Check if value field is not empty.
    if ($enable_blocked == 1 && empty($block_value) || $enable_loggedin == 1 && empty($login_value) || $enable_never_loggedin == 1 && empty($login_never_value) || $enable_inactive == 1 && empty($inactive_value)) {
      $form_state->setErrorByName('Empty value field', $this->t('Value should not be empty.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('purge_users.settings')
      ->set('user_never_lastlogin_value', $form_state->getValue('user_never_lastlogin_value'))
      ->set('user_never_lastlogin_period', $form_state->getValue('user_never_lastlogin_period'))
      ->set('user_lastlogin_value', $form_state->getValue('user_lastlogin_value'))
      ->set('user_lastlogin_period', $form_state->getValue('user_lastlogin_period'))
      ->set('user_inactive_value', $form_state->getValue('user_inactive_value'))
      ->set('user_inactive_period', $form_state->getValue('user_inactive_period'))
      ->set('user_blocked_value', $form_state->getValue('user_blocked_value'))
      ->set('user_blocked_period', $form_state->getValue('user_blocked_period'))
      ->set('enabled_never_loggedin_users', $form_state->getValue('enabled_never_loggedin_users'))
      ->set('enabled_loggedin_users', $form_state->getValue('enabled_loggedin_users'))
      ->set('enabled_inactive_users', $form_state->getValue('enabled_inactive_users'))
      ->set('enabled_blocked_users', $form_state->getValue('enabled_blocked_users'))
      ->set('purge_users_roles', $form_state->getValue('purge_users_roles'))
      ->set('purge_on_cron', $form_state->getValue('purge_on_cron'))
      ->set('inactive_user_notify_text', $form_state->getValue('inactive_user_notify_text'))
      ->set('send_email_notification', $form_state->getValue('send_email_notification'))
      ->set('purge_user_cancel_method', $form_state->getValue('purge_user_cancel_method'))
      ->save();
    if ($form_state->getValue('purge_on_cron') == 1) {
      drupal_set_message($this->t('Purge users operation is scheduled for next cron.'));
    }
  }

  /**
   * Submit handler for mass-account cancellation form.
   *
   * @see purge_users_config_form()
   */
  public function purgeUsersNowSubmit($form, FormStateInterface $form_state) {
    // Save form submissions.
    $config = $this->config('purge_users.settings');
    $config->set('user_never_lastlogin_value', $form_state->getValue('user_never_lastlogin_value'))
      ->set('user_never_lastlogin_period', $form_state->getValue('user_never_lastlogin_period'))
      ->set('user_lastlogin_value', $form_state->getValue('user_lastlogin_value'))
      ->set('user_lastlogin_period', $form_state->getValue('user_lastlogin_period'))
      ->set('user_inactive_value', $form_state->getValue('user_inactive_value'))
      ->set('user_inactive_period', $form_state->getValue('user_inactive_period'))
      ->set('user_blocked_value', $form_state->getValue('user_blocked_value'))
      ->set('user_blocked_period', $form_state->getValue('user_blocked_period'))
      ->set('enabled_never_loggedin_users', $form_state->getValue('enabled_never_loggedin_users'))
      ->set('enabled_loggedin_users', $form_state->getValue('enabled_loggedin_users'))
      ->set('enabled_inactive_users', $form_state->getValue('enabled_inactive_users'))
      ->set('enabled_blocked_users', $form_state->getValue('enabled_blocked_users'))
      ->set('purge_users_roles', $form_state->getValue('purge_users_roles'))
      ->set('purge_on_cron', $form_state->getValue('purge_on_cron'))
      ->set('inactive_user_notify_text', $form_state->getValue('inactive_user_notify_text'))
      ->set('send_email_notification', $form_state->getValue('send_email_notification'))
      ->set('purge_user_cancel_method', $form_state->getValue('purge_user_cancel_method'))
      ->save();
    // If there is any user to purge.
    $accounts = purge_users_get_user_ids();
    if (!empty($accounts)) {
      $form_state->setRedirect('purge_users.confirm');
      return;
    }
    else {
      drupal_set_message($this->t('No user account found in the system to purge.'));
    }
  }

}
