<?php

namespace Drupal\sfs\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Symfony\Component\DependencyInjection\ContainerInterface;

class SfsConfigForm extends ConfigFormBase {

  /**
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;


  /**
   * SfsConfigForm constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ModuleHandlerInterface $module_handler) {
    parent::__construct($config_factory);

    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['sfs.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'sfs_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('sfs.settings');

    // Fieldset for check.
    $form['check'] = [
      '#type' => 'details',
      '#title' => $this->t('Check activities'),
      '#description' => $this->t('You can include the following activities to be blocked for spammers.'),
      '#collapsible' => TRUE,
    ];
    $form['check']['sfs_check_user_registration'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Check user registration'),
      '#default_value' => $config->get('sfs_check_user_registration'),
    ];
    $form['check']['sfs_check_node'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Check posting content'),
      '#default_value' => $config->get('sfs_check_node'),
    ];
    $form['check']['sfs_check_comment'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Check posting comments'),
      '#default_value' => $config->get('sfs_check_comment'),
    ];
    $form['check']['sfs_check_contact'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Check contact form submissions'),
      '#default_value' => $config->get('sfs_check_contact'),
    ];
    // Fieldset for flood.
    $form['flood'] = [
      '#type' => 'details',
      '#title' => $this->t('Flood protection'),
      '#collapsible' => TRUE,
    ];
    $form['flood']['sfs_flood_delay'] = [
      '#type' => 'select',
      '#title' => $this->t('Delay spammer for'),
      '#description' => $this->t('If an user is blacklisted you can delay the request. This can be useful when spammers flood your site.'),
      '#options' => $this->getFloodOptions(),
      '#default_value' => $config->get('sfs_flood_delay'),
    ];
    $form['flood']['sfs_cache_duration'] = [
      '#type' => 'select',
      '#title' => $this->t('Cache duration'),
      '#description' => $this->t('Cache the results of api calls made to www.stopforumspam.com. Helps to reduce the number of api calls made.'),
      '#options' => $this->getCacheOptions(),
      '#default_value' => $config->get('sfs_cache_duration'),
    ];
    // Fieldset for criteria.
    $form['criteria'] = [
      '#type' => 'details',
      '#title' => $this->t('Spam block criteria'),
      '#description' => $this->t('A user activity will be considered to be spam when the email, username, or IP address has been reported to www.stopforumspam.com more times than the following thresholds.'),
      '#collapsible' => TRUE,
    ];
    $form['criteria']['sfs_criteria_email'] = [
      '#type' => 'select',
      '#title' => $this->t('Number of times the email has been reported is equal to or more than'),
      '#description' => $this->t('If the email address for a user has been reported to www.stopforumspam.com this many times, then the user will be considered to be a spammer.'),
      '#options' => $this->getCriteriaOptions($this->t("Don't use email as a criterium")),
      '#default_value' => $config->get('sfs_criteria_email'),
    ];
    $form['criteria']['sfs_criteria_username'] = [
      '#type' => 'select',
      '#title' => $this->t('Number of times the username has been reported is equal to or more than'),
      '#description' => $this->t('If the username for a user has been reported to www.stopforumspam.com this many times, then the user will be considered to be a spammer. CAUTION: This criterium may result in false positives.'),
      '#options' => $this->getCriteriaOptions($this->t("Don't use username as a criterium")),
      '#default_value' => $config->get('sfs_criteria_username'),
    ];
    $form['criteria']['sfs_criteria_ip'] = [
      '#type' => 'select',
      '#title' => $this->t('Number of times the IP address has been reported is equal to or more than'),
      '#description' => $this->t('If the IP address for a user or user registration has been reported to www.stopforumspam.com this many times, then the user will be considered to be a spammer.'),
      '#options' => $this->getCriteriaOptions($this->t("Don't use IP address as a criterium")),
      '#default_value' => $config->get('sfs_criteria_ip'),
    ];
    // Fieldset for white list.
    $form['whitelist'] = [
      '#type' => 'details',
      '#title' => $this->t('Whitelists'),
      '#collapsible' => TRUE,
    ];
    $form['whitelist']['sfs_whitelist_emails'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Allowed email addresses'),
      '#description' => $this->t('Enter email addresses (one per line).'),
      '#default_value' => $config->get('sfs_whitelist_emails'),
    ];
    $form['whitelist']['sfs_whitelist_usernames'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Allowed usernames'),
      '#description' => $this->t('Enter usernames (one per line).'),
      '#default_value' => $config->get('sfs_whitelist_usernames'),
    ];
    $form['whitelist']['sfs_whitelist_ips'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Allowed IP addresses'),
      '#description' => $this->t('Enter IP addresses (one per line).'),
      '#default_value' => $config->get('sfs_whitelist_ips'),
    ];
    // Fieldset for cron.
    $form['cron'] = [
      '#type' => 'details',
      '#title' => $this->t('Scan user accounts'),
      '#description' => $this->t("Scan existing user accounts to see if they are known spammers or have become known after they registered. This scan is performed in the background in cronjobs."),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
    ];
    $form['cron']['sfs_cron_job'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Scan existing user accounts during cronjobs.'),
      '#description' => $this->t("For now the only action taken against spam accounts is disabling them. Disabling spammer accounts is reported in the Drupal log."),
      '#default_value' => $config->get('sfs_cron_job'),
    ];
    $form['cron']['sfs_cron_blocked_accounts'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Include blocked accounts in the scan.'),
      '#description' => $this->t("For now the only action taken against spam accounts is disabling them. Leave this option unchecked for the time being."),
      '#default_value' => $config->get('sfs_cron_blocked_accounts'),
    ];
    $form['cron']['sfs_cron_account_limit'] = [
      '#type' => 'number',
      '#title' => $this->t('Limit the number of user accounts to scan per cronjob.'),
      '#description' => $this->t('Enter the number of user accounts to scan for each cronjob.<br>Be conservative in your choice. A too high value may cause your site to query www.stopforumspam.com excessively.'),
      '#size' => 5,
      '#default_value' => $config->get('sfs_cron_account_limit'),
    ];
    $form['cron']['sfs_cron_last_uid'] = [
      '#type' => 'number',
      '#title' => $this->t('Continue scanning after this user id'),
      '#size' => 5,
      '#description' => $this->t('Resetting this value to 1 will restart the scanning of all user accounts. Leaving the number will only scan new subscribers when all users have been scanned.<br>It is advisable to stop scanning when all users have been scanned.'),
      '#default_value' => $config->get('sfs_cron_last_uid'),
    ];

    // Fieldset for logging.
    $form['logging'] = [
      '#type' => 'details',
      '#title' => $this->t('Logging'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
    ];
    $form['logging']['sfs_log_successful_request'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Log successful Stop Forum Spam API calls into Drupal log'),
      '#default_value' => $config->get('sfs_log_successful_request'),
    ];
    $form['logging']['sfs_log_found_in_cache'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Log API call results found in the cache into Drupal log'),
      '#default_value' => $config->get('sfs_log_found_in_cache'),
    ];
    $form['logging']['sfs_log_blocked_spam'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Log information about blocked spam into Drupal log'),
      '#default_value' => $config->get('sfs_log_blocked_spam'),
    ];

    $form['sfs_blocked_message'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Blocked message'),
      '#description' => $this->t('Message to diplay when a user action is blocked.'),
      '#default_value' => $config->get('sfs_blocked_message'),
    ];
    $form['sfs_api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('www.stopforumspam.com API Key'),
      '#description' => $this->t('For reporting spammers to StopForumSpam you will need to register for an API Key at the <a href="http://www.stopforumspam.com">StopForumSpam</a> website.'),
      '#default_value' => $config->get('sfs_api_key'),
    ];

    $form['sfs_http_secure'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use secure protocol (https)'),
      '#description' => $this->t('Https is the preferred protocol, while http should only be used as a fallback protocol.'),
      '#default_value' => $config->get('sfs_http_secure'),
    ];

    return parent::buildForm($form, $form_state);

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('sfs.settings');
    $config
    ->set('sfs_check_user_registration', $form_state->getValue('sfs_check_user_registration'))
    ->set('sfs_check_node', $form_state->getValue('sfs_check_node'))
    ->set('sfs_check_comment', $form_state->getValue('sfs_check_comment'))
    ->set('sfs_check_contact', $form_state->getValue('sfs_check_contact'))
    ->set('sfs_flood_delay', $form_state->getValue('sfs_flood_delay'))
    ->set('sfs_cache_duration', $form_state->getValue('sfs_cache_duration'))
    ->set('sfs_criteria_email', $form_state->getValue('sfs_criteria_email'))
    ->set('sfs_criteria_username', $form_state->getValue('sfs_criteria_username'))
    ->set('sfs_criteria_ip', $form_state->getValue('sfs_criteria_ip'))
    ->set('sfs_cron_job', $form_state->getValue('sfs_cron_job'))
    ->set('sfs_cron_blocked_accounts', $form_state->getValue('sfs_cron_blocked_accounts'))
    ->set('sfs_cron_account_limit', $form_state->getValue('sfs_cron_account_limit'))
    ->set('sfs_cron_last_uid', $form_state->getValue('sfs_cron_last_uid'))
    ->set('sfs_whitelist_emails', $form_state->getValue('sfs_whitelist_emails'))
    ->set('sfs_whitelist_usernames', $form_state->getValue('sfs_whitelist_usernames'))
    ->set('sfs_whitelist_ips', $form_state->getValue('sfs_whitelist_ips'))
    ->set('sfs_log_successful_request', $form_state->getValue('sfs_log_successful_request'))
    ->set('sfs_log_found_in_cache', $form_state->getValue('sfs_log_found_in_cache'))
    ->set('sfs_log_blocked_spam', $form_state->getValue('sfs_log_blocked_spam'))
    ->set('sfs_blocked_message', $form_state->getValue('sfs_blocked_message'))
    ->set('sfs_api_key', $form_state->getValue('sfs_api_key'))
    ->set('sfs_http_secure', $form_state->getValue('sfs_http_secure'))
    ->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    if ($form_state->getValue('sfs_check_comment') && !$this->moduleHandler->moduleExists('comment')) {
      $message = $this->t('Please enable the comment module before enabling the spam checking of comments.');
      $form_state->setError($form['sfs_check_comment'], $message);
    }
    if ($form_state->getValue('sfs_check_contact') && !$this->moduleHandler->moduleExists('contact')) {
      $message = $this->t('Please enable the contact module before enabling the spam checking of contact feedback.');
      $form_state->setError($form['sfs_check_contact'], $message);
    }
  }

  /**
   * Options list for spam block criteria.
   *
   * @param TranslatableMarkup $label
   *   Label for option 0 (Do not use).
   *
   * @return array
   *   Spam found in stopforumspam.com count criteria options.
   */
  protected function getCriteriaOptions(TranslatableMarkup $label) {
    return [
      0 => $label,
      1 => 1,
      2 => 2,
      3 => 3,
      4 => 4,
      5 => 5,
      6 => 6,
      7 => 7,
      8 => 8,
      9 => 9,
      10 => 10,
      15 => 15,
      20 => 20,
      30 => 30,
      40 => 40,
      50 => 50,
      60 => 60,
      70 => 70,
      80 => 80,
      90 => 90,
      100 => 100,
      255 => $this->t('Blacklisted by StopForumSpam'),
    ];
  }

  /**
   * Options list for flood protection delay.
   *
   * @return array
   *   Flood delay duration options.
   */
  protected function getFloodOptions() {
    $options = [0 => $this->t("Do not delay"), 1 => $this->t('1 second')];
    foreach ([2, 3, 4, 5, 10, 15, 20, 30] as $second) {
      $options[$second] = $this->t('@number seconds', ['@number' => $second]);
    }
    return $options;
  }

  /**
   * Option list for the cache duration.
   *
   * @return array
   *   Cache duration options.
   */
  protected function getCacheOptions() {
    return [
      0 => $this->t('Do not cache'),
      60 => $this->t('1 minute'),
      120 => $this->t('@num minutes', ['@num' => 2]),
      180 => $this->t('@num minutes', ['@num' => 3]),
      300 => $this->t('@num minutes', ['@num' => 5]),
      600 => $this->t('@num minutes', ['@num' => 10]),
      900 => $this->t('@num minutes', ['@num' => 15]),
      1800 => $this->t('@num minutes', ['@num' => 30]),
      2700 => $this->t('@num minutes', ['@num' => 45]),
      3600 => $this->t('1 hour'),
    ];
  }

}
