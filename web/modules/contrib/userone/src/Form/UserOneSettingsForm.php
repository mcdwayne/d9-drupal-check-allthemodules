<?php

namespace Drupal\userone\Form;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;

/**
 * User One configuration form.
 */
class UserOneSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['userone.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'userone_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('userone.settings');
    $ban_url = Url::fromRoute('ban.admin_page')->toString();

    $form['edit_access_info'] = [
      '#type' => 'item',
      '#title' => $this->t('Access to user one edit blocked'),
      '#markup' => $this->t('No account except user one account can edit user one account.'),
    ];

    $form['view_access_info'] = [
      '#type' => 'item',
      '#title' => $this->t('Access to user one profile blocked'),
      '#markup' => $this->t('No account except user one account can view user one account.'),
    ];

    $form['failed_login'] = [
      '#type' => 'details',
      '#title' => $this->t('Allowed failed login attempts'),
      '#description' => $this->t("This setting exposes Drupal's built-in configuration values otherwise inaccessible. It applies to all users, not just user one."),
      '#open' => TRUE,
    ];

    $form['failed_login']['user_failed_login_ip_limit'] = [
      '#type' => 'select',
      '#title' => $this->t('Allowed failed login attempts for an IP address (default 50)'),
      '#options' => $this->getFailedLoginAttemptsOptions(),
      '#default_value' => $config->get('user_failed_login_ip_limit'),
      '#description' => $this->t("Do not allow any login from the current user's IP if the limit has been reached. Default is 50 failed attempts allowed in one hour. This is independent of the per-user limit to catch attempts from one IP to log in to many different user accounts.  We have a reasonably high limit since there may be only one apparent IP for all users at an institution."),
    ];

    $form['failed_login']['user_failed_login_ip_window'] = [
      '#type' => 'select',
      '#title' => $this->t('Failed login window for an IP address (default 1 hour)'),
      '#options' => $this->getFailedLoginWindowOptions(),
      '#default_value' => $config->get('user_failed_login_ip_window'),
      '#description' => $this->t('Time period during which failed logins are accounted for.'),
    ];

    $form['failed_login']['block_ip_on_failed_login_ip'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Permanently block IP when failed logins breaks threshold. (<a href="@url">See blocked IPs</a>)',
        ['@url' => $ban_url]
      ),
      '#default_value' => $config->get('block_ip_on_failed_login_ip'),
      '#description' => $this->t('User one account will be notified when an IP is blocked.'),
    ];

    $form['failed_login']['divider'] = [
      '#type' => 'item',
      '#markup' => '<hr/>',
    ];

    $form['failed_login']['user_failed_login_user_limit'] = [
      '#type' => 'select',
      '#title' => $this->t('Allowed failed login attempts for an account (default 5)'),
      '#options' => $this->getFailedLoginAttemptsOptions(),
      '#default_value' => $config->get('user_failed_login_user_limit'),
      '#description' => $this->t('User will be allowed to attempt logins this many times within the period (see below).'),
    ];

    $form['failed_login']['user_failed_login_user_window'] = [
      '#type' => 'select',
      '#title' => $this->t('Failed login window for an account (default 6 hours)'),
      '#options' => $this->getFailedLoginWindowOptions(),
      '#default_value' => $config->get('user_failed_login_user_window'),
      '#description' => $this->t('Number of failed logins for an account will be accounted for this period'),
    ];

    $form['failed_login']['block_ip_on_failed_login_user1'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Permanently block IP when failed logins <strong>for user one</strong> breaks threshold. (<a href="@url">See blocked IPs</a>)',
        ['@url' => $ban_url]
      ),
      '#default_value' => $config->get('block_ip_on_failed_login_user1'),
      '#description' => $this->t('User one account will be notified when an IP is blocked.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('userone.settings');

    foreach ($form_state->getValues() as $key => $value) {
      $config->set($key, $value);
    }
    $config->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * Get allowed failed login attempts for an IP address.
   *
   * @return array
   *   An array of options.
   */
  public function getFailedLoginAttemptsOptions() {
    return [
      1 => 1,
      2 => 2,
      3 => 3,
      4 => 4,
      5 => 5,
      10 => 10,
      25 => 25,
      50 => 50,
      75 => 75,
      100 => 100,
      250 => 250,
    ];
  }

  /**
   * Get failed login window for an IP address.
   *
   * @return array
   *   An array of options.
   */
  public function getFailedLoginWindowOptions() {
    return [
      300 => '5 minutes',
      600 => '10 minutes',
      900 => '15 minutes',
      1800 => '30 minutes',
      2700 => '45 minutes',
      3600 => '1 hour',
      7200 => '2 hours',
      10800 => '3 hours',
      14400 => '4 hours',
      18000 => '5 hours',
      21600 => '6 hours',
      28800 => '8 hours',
      36000 => '10 hours',
      43200 => '12 hours',
      86400 => '24 hours',
    ];
  }

  /**
   * Allow access only for user one.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(AccountInterface $account) {
    if ($account->id() == 1) {
      return AccessResult::allowed();
    }

    return AccessResult::forbidden();
  }

}
