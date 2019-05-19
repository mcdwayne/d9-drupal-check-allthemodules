<?php

namespace Drupal\spambot\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\State\StateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Settings form to save the configuration for Spambot.
 */
class SpambotSettingsForm extends ConfigFormBase {

  const SPAMBOT_ACTION_NONE = 0;
  const SPAMBOT_ACTION_BLOCK = 1;
  const SPAMBOT_ACTION_DELETE = 2;

  /**
   * This will hold Database connection object.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Entity type manager service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Constructs a \Drupal\system\ConfigFormBase object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Database\Connection $connection
   *   Constructs a Connection object.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Provides an interface for an entity type and its metadata.
   * @param StateInterface $state
   *   Provides an interface for state.
   */
  public function __construct(ConfigFactoryInterface $config_factory, Connection $connection, EntityTypeManagerInterface $entityTypeManager, StateInterface $state) {
    parent::__construct($config_factory);

    $this->connection = $connection;
    $this->entityTypeManager = $entityTypeManager;
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('database'),
      $container->get('entity.manager'),
      $container->get('state')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'spambot_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['spambot.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $numbers = [
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
      150 => 150,
      200 => 200,
    ];

    $config = $this->config('spambot.settings');

    // Fieldset for set up spam criteria.
    $form['criteria'] = [
      '#type' => 'details',
      '#title' => $this->t('Spammer criteria'),
      '#description' => $this->t('A user account or an attempted user registration will be deemed a spammer if the email, username, or IP address has been reported to www.stopforumspam.com more times than the following thresholds.'),
      '#collapsible' => TRUE,
    ];
    $form['criteria']['spambot_criteria_email'] = [
      '#type' => 'select',
      '#title' => $this->t('Number of times the email has been reported is equal to or more than'),
      '#description' => $this->t('If the email address for a user or user registration has been reported to www.stopforumspam.com this many times, then it is deemed as a spammer.'),
      '#options' => [
        0 => $this->t("Don't use email as a criteria"),
      ] + $numbers,
      '#default_value' => $config->get('spambot_criteria_email'),
    ];
    $form['criteria']['spambot_criteria_username'] = [
      '#type' => 'select',
      '#title' => $this->t('Number of times the username has been reported is equal to or more than'),
      '#description' => $this->t('If the username for a user or user registration has been reported to www.stopforumspam.com this many times, then it is deemed as a spammer. Be careful about using this option as you may accidentally block genuine users who happen to choose the same username as a known spammer.'),
      '#options' => [
        0 => $this->t("Don't use username as a criteria"),
      ] + $numbers,
      '#default_value' => $config->get('spambot_criteria_username'),
    ];
    $form['criteria']['spambot_criteria_ip'] = [
      '#type' => 'select',
      '#title' => $this->t('Number of times the IP address has been reported is equal to or more than'),
      '#description' => $this->t('If the IP address for a user or user registration has been reported to www.stopforumspam.com this many times, then it is deemed as a spammer. Be careful about setting this threshold too low as IP addresses can change.'),
      '#options' => [
        0 => $this->t("Don't use IP address as a criteria"),
      ] + $numbers,
      '#default_value' => $config->get('spambot_criteria_ip'),
    ];

    // White lists.
    $form['spambot_whitelist'] = [
      '#type' => 'details',
      '#title' => $this->t('Whitelists'),
      '#collapsible' => TRUE,
    ];
    $form['spambot_whitelist']['spambot_whitelist_email'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Allowed email addresses'),
      '#description' => $this->t('Enter email addresses, one per line.'),
      '#default_value' => implode(PHP_EOL, $config->get('spambot_whitelist_email_list')),
    ];
    $form['spambot_whitelist']['spambot_whitelist_username'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Allowed usernames'),
      '#description' => $this->t('Enter usernames, one per line.'),
      '#default_value' => implode(PHP_EOL, $config->get('spambot_whitelist_username_list')),
    ];
    $form['spambot_whitelist']['spambot_whitelist_ip'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Allowed IP addresses'),
      '#description' => $this->t('Enter IP addresses, one per line.'),
      '#default_value' => implode(PHP_EOL, $config->get('spambot_whitelist_ip_list')),
    ];

    // Fieldset for configure protecting at user register form.
    $form['register'] = [
      '#type' => 'details',
      '#title' => $this->t('User registration'),
      '#collapsible' => TRUE,
    ];
    $form['register']['spambot_user_register_protect'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Protect the user registration form'),
      '#description' => $this->t('If ticked, new user registrations will be tested if they match any known spammers and blacklisted.'),
      '#default_value' => $config->get('spambot_user_register_protect'),
    ];

    $sleep_options = [$this->t("Don't delay"), $this->t('1 second')];
    foreach ([2, 3, 4, 5, 10, 20, 30] as $num) {
      $sleep_options[$num] = $this->t('@num seconds', ['@num' => $num]);
    }
    $form['register']['spambot_blacklisted_delay'] = [
      '#type' => 'select',
      '#title' => $this->t('If blacklisted, delay for'),
      '#description' => $this->t('If an attempted user registration is blacklisted, you can choose to deliberately delay the request. This can be useful for slowing them down if they continually try to register.<br />Be careful about choosing too large a value for this as it may exceed your PHP max_execution_time.'),
      '#options' => $sleep_options,
      '#default_value' => $config->get('spambot_blacklisted_delay'),
    ];

    // Fieldset for set up scanning of existing accounts.
    $form['existing'] = [
      '#type' => 'details',
      '#title' => $this->t('Scan existing accounts'),
      '#description' => $this->t("This module can also scan existing user accounts to see if they are known spammers. It works by checking user accounts with increasing uid's ie. user id 2, 3, 4 etc during cron."),
      '#collapsible' => TRUE,
    ];
    $form['existing']['spambot_cron_user_limit'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Maximum number of user accounts to scan per cron'),
      '#description' => $this->t('Enter the number of user accounts to scan for each cron. If you do not want to scan existing user accounts, leave this as 0.<br />Be careful not to make this value too large, as it will slow your cron execution down and may cause your site to query www.stopforumspam.com more times than allowed each day.'),
      '#size' => 10,
      '#default_value' => $config->get('spambot_cron_user_limit'),
    ];
    $form['existing']['spambot_check_blocked_accounts'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Scan blocked accounts'),
      '#description' => $this->t('Tick this to scan blocked accounts. Otherwise blocked accounts are not scanned.'),
      '#default_value' => $config->get('spambot_check_blocked_accounts'),
    ];
    $form['existing']['spambot_spam_account_action'] = [
      '#type' => 'select',
      '#title' => $this->t('Action to take'),
      '#description' => $this->t('Please select what action to take for user accounts which are found to be spammers.<br />No action will be taken against accounts with the permission <em>protected from spambot scans</em> but they will be logged.'),
      '#options' => [
        self::SPAMBOT_ACTION_NONE => $this->t('None, just log it.'),
        self::SPAMBOT_ACTION_BLOCK => $this->t('Block user account'),
        self::SPAMBOT_ACTION_DELETE => $this->t('Delete user account'),
      ],
      '#default_value' => $config->get('spambot_spam_account_action'),
    ];

    // Get scan status.
    $suffix = '';
    if ($last_uid = $this->state->get('spambot_last_checked_uid', 0)) {
      $num_checked = $this->connection->select('users', 'u')
        ->fields('u', ['uid'])
        ->condition('u.uid', 1, '>')
        ->condition('u.uid', $last_uid, '<=')
        ->countQuery()
        ->execute()
        ->fetchField();

      $num_left = $this->connection->select('users', 'u')
        ->fields('u', ['uid'])
        ->condition('u.uid', 1, '>')
        ->condition('u.uid', $last_uid, '>')
        ->countQuery()
        ->execute()
        ->fetchField();

      $last_uid = $this->connection->select('users', 'u')
        ->fields('u', ['uid'])
        ->condition('u.uid', 1, '>=')
        ->condition('u.uid', $last_uid, '<=')
        ->orderBy('u.uid', 'DESC')
        ->range(0, 1)
        ->execute()
        ->fetchField();

      /** @var \Drupal\user\UserInterface $account */
      $account = $this->entityTypeManager->getStorage('user')->load($last_uid);
      $suffix = '<br />';
      $renderableLink = Link::fromTextAndUrl($account->label(), $account->toUrl())->toRenderable();
      $suffix .= $this->t('The last checked user account is: %account (uid %uid)', [
        '%account' => render($renderableLink),
        '%uid' => $account->id(),
      ]);
    }
    else {
      $num_checked = 0;
      $num_left = $this->connection->select('users')
        ->fields('users')
        ->condition('uid', 1, '>')
        ->countQuery()
        ->execute()
        ->fetchField();
    }

    $text = $this->t('Accounts checked: %checked, Accounts remaining: %remaining', [
      '%checked' => $num_checked,
      '%remaining' => $num_left,
    ]);
    $form['existing']['message'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Scan status'),
      '#description' => $text . $suffix,
    ];
    $form['existing']['spambot_last_checked_uid'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Continue scanning after this user id'),
      '#size' => 10,
      '#description' => $this->t('Scanning of existing user accounts has progressed to, and including, user id @uid and will continue by scanning accounts after user id @uid. If you wish to change where the scan continues scanning from, enter a different user id here. If you wish to scan all users again, enter a value of 0.', [
        '@uid' => $last_uid,
      ]),
      '#default_value' => $last_uid,
    ];

    // Fieldset for set up messages which will be displayed for blocked users.
    $form['messages'] = [
      '#type' => 'details',
      '#title' => $this->t('Blocked messages'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
    ];
    $form['messages']['spambot_blocked_message_email'] = [
      '#type' => 'textarea',
      '#title' => $this->t('User registration blocked message (blocked email address)'),
      '#rows' => 1,
      '#default_value' => $config->get('spambot_blocked_message_email'),
      '#description' => $this->t('Message to display when user action is blocked due to email address. <br />Showing a specific reason why registration was blocked may make spambot easier to circumvent.<br />The following tokens are available: <em>@email %email @username %username @ip %ip</em>'),
    ];
    $form['messages']['spambot_blocked_message_username'] = [
      '#type' => 'textarea',
      '#title' => $this->t('User registration blocked message (blocked username)'),
      '#rows' => 1,
      '#default_value' => $config->get('spambot_blocked_message_username'),
      '#description' => $this->t('Message to display when user action is blocked due to username.<br />The following tokens are available: <em>@email %email @username %username @ip %ip</em>'),
    ];
    $form['messages']['spambot_blocked_message_ip'] = [
      '#type' => 'textarea',
      '#title' => $this->t('User registration blocked message (blocked ip address)'),
      '#rows' => 1,
      '#default_value' => $config->get('spambot_blocked_message_ip'),
      '#description' => $this->t('Message to display when user action is blocked due to ip address.<br />The following tokens are available: <em>@email %email @username %username @ip %ip</em>'),
    ];

    // Fieldset for configure log rules.
    $form['logging'] = [
      '#type' => 'details',
      '#title' => $this->t('Log information'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
    ];
    $form['logging']['spambot_log_blocked_registration'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Log information about blocked registrations into Drupal log'),
      '#default_value' => $config->get('spambot_log_blocked_registration'),
    ];

    // StopFormSpam API key.
    $form['spambot_sfs_api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('www.stopforumspam.com API key'),
      '#description' => $this->t('If you wish to report spammers to Stop Forum Spam, you need to register for an API key at the <a href="http://www.stopforumspam.com">Stop Forum Spam</a> website.'),
      '#default_value' => $config->get('spambot_sfs_api_key'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('spambot.settings');

    $whitelist_email = explode(PHP_EOL, $form_state->getValue('spambot_whitelist_email'));
    $whitelist_username = explode(PHP_EOL, $form_state->getValue('spambot_whitelist_username'));
    $whitelist_ip = explode(PHP_EOL, $form_state->getValue('spambot_whitelist_ip'));
    $config->set('spambot_criteria_email', $form_state->getValue('spambot_criteria_email'))
      ->set('spambot_criteria_username', $form_state->getValue('spambot_criteria_username'))
      ->set('spambot_criteria_ip', $form_state->getValue('spambot_criteria_ip'))
      ->set('spambot_whitelist_email_list', array_map('trim', array_filter($whitelist_email)))
      ->set('spambot_whitelist_username_list', array_map('trim', array_filter($whitelist_username)))
      ->set('spambot_whitelist_ip_list', array_map('trim', array_filter($whitelist_ip)))
      ->set('spambot_user_register_protect', $form_state->getValue('spambot_user_register_protect'))
      ->set('spambot_blacklisted_delay', $form_state->getValue('spambot_blacklisted_delay'))
      ->set('spambot_cron_user_limit', $form_state->getValue('spambot_cron_user_limit'))
      ->set('spambot_check_blocked_accounts', $form_state->getValue('spambot_check_blocked_accounts'))
      ->set('spambot_spam_account_action', $form_state->getValue('spambot_spam_account_action'))
      ->set('spambot_blocked_message_email', $form_state->getValue('spambot_blocked_message_email'))
      ->set('spambot_blocked_message_username', $form_state->getValue('spambot_blocked_message_username'))
      ->set('spambot_blocked_message_ip', $form_state->getValue('spambot_blocked_message_ip'))
      ->set('spambot_log_blocked_registration', $form_state->getValue('spambot_log_blocked_registration'))
      ->set('spambot_sfs_api_key', $form_state->getValue('spambot_sfs_api_key'))
      ->save();
    $this->state->set('spambot_last_checked_uid', $form_state->getValue('spambot_last_checked_uid'));
    parent::submitForm($form, $form_state);
  }

}
