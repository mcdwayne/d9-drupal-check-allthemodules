<?php

namespace Drupal\ip2country\Form;

use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Locale\CountryManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\Entity\User;
use Drupal\user\UserDataInterface;
use Drupal\ip2country\Ip2CountryManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Configure ip2country settings for this site.
 */
class Ip2CountrySettingsForm extends ConfigFormBase {

  /**
   * The current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $currentRequest;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The current user's data.
   *
   * @var \Drupal\user\UserDataInterface
   */
  protected $userData;

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $stateService;

  /**
   * The date.formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The country_manager service.
   *
   * @var \Drupal\Core\Locale\CountryManagerInterface
   */
  protected $countryManager;

  /**
   * The ip2country.manager service.
   *
   * @var \Drupal\ip2country\Ip2CountryManagerInterface
   */
  protected $ip2countryManager;

  /**
   * Constructs an Ip2CountryController.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack.
   * @param \Drupal\Core\Session\AccountInterface $currentUser
   *   The current user.
   * @param \Drupal\user\UserDataInterface $userData
   *   The current user's data.
   * @param \Drupal\Core\State\StateInterface $stateService
   *   The state service.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $dateFormatter
   *   The date formatter service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler.
   * @param \Drupal\Core\Locale\CountryManagerInterface $countryManager
   *   The country_manager service.
   * @param \Drupal\ip2country\Ip2CountryManagerInterface $ip2countryManager
   *   The ip2country.manager service.
   */
  public function __construct(RequestStack $requestStack, AccountInterface $currentUser, UserDataInterface $userData, StateInterface $stateService, DateFormatterInterface $dateFormatter, ModuleHandlerInterface $moduleHandler, CountryManagerInterface $countryManager, Ip2CountryManagerInterface $ip2countryManager) {
    $this->currentRequest = $requestStack->getCurrentRequest();
    $this->currentUser = $currentUser;
    $this->userData = $userData;
    $this->stateService = $stateService;
    $this->dateFormatter = $dateFormatter;
    $this->moduleHandler = $moduleHandler;
    $this->countryManager = $countryManager;
    $this->ip2countryManager = $ip2countryManager;
    // Utility functions for loading IP/Country DB from external sources.
    $this->moduleHandler->loadInclude('ip2country', 'inc');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('request_stack'),
      $container->get('current_user'),
      $container->get('user.data'),
      $container->get('state'),
      $container->get('date.formatter'),
      $container->get('module_handler'),
      $container->get('country_manager'),
      $container->get('ip2country.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ip2country_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'ip2country.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $ip2country_config = $this->config('ip2country.settings');

    $form['#attached']['library'][] = 'ip2country/ip2country.settings';

    // Container for database update preference forms.
    $form['ip2country_database_update'] = [
      '#type'  => 'details',
      '#title' => $this->t('Database updates'),
      '#open'  => TRUE,
    ];

    // Form to enable watchdog logging of updates.
    $form['ip2country_database_update']['ip2country_watchdog'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Log database updates to watchdog'),
      '#default_value' => $ip2country_config->get('watchdog'),
    ];

    // Form to choose RIR.
    $form['ip2country_database_update']['ip2country_rir'] = [
      '#type'          => 'select',
      '#title'         => $this->t('Regional Internet Registry'),
      '#options'       => ['afrinic' => 'AFRINIC', 'apnic' => 'APNIC', 'arin' => 'ARIN', 'lacnic' => 'LACNIC', 'ripe' => 'RIPE'],
      '#default_value' => $ip2country_config->get('rir'),
      '#description'   => $this->t('Database will be downloaded from the selected RIR. You may find that the regional server nearest you has the best response time, but note that AFRINIC provides only its own subset of registration data.'),
    ];

    // Form to enable MD5 checksum of downloaded databases.
    $form['ip2country_database_update']['ip2country_md5_checksum'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Perform MD5 checksum comparison'),
      '#description'   => $this->t("Compare MD5 checksum downloaded from the RIR with MD5 checksum calculated locally to ensure the data has not been corrupted. RIRs don't always store current checksums, so if this option is checked your database updates may sometimes fail."),
      '#default_value' => $ip2country_config->get('md5_checksum'),
    ];

    $intervals = [86400, 302400, 604800, 1209600, 2419200];
    $period = array_map([$this->dateFormatter, 'formatInterval'], array_combine($intervals, $intervals));
    $period[0] = $this->t('Never');

    // Form to set automatic update interval.
    $form['ip2country_database_update']['ip2country_update_interval'] = [
      '#type'          => 'select',
      '#title'         => $this->t('Database update frequency'),
      '#default_value' => $ip2country_config->get('update_interval'),
      '#options'       => $period,
      '#description'   => $this->t('Database will be automatically updated via cron.php. Cron must be enabled for this to work. Default period is 1 week (604800 seconds).'),
    ];

    $update_time = $this->stateService->get('ip2country_last_update');
    if (!empty($update_time)) {
      $message = $this->t(
        'Database last updated on @date at @time from @registry server.',
        [
          '@date' => $this->dateFormatter->format($update_time, 'ip2country_date'),
          '@time' => $this->dateFormatter->format($update_time, 'ip2country_time'),
          '@registry' => mb_strtoupper($this->stateService->get('ip2country_last_update_rir')),
        ]
      );
    }
    else {
      $message = $this->t('Database is empty. You may fill the database by pressing the @update button.', ['@update' => $this->t('Update')]);
    }

    // Form to customize database insertion batch size.
    $form['ip2country_database_update']['ip2country_update_batch_size'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Database update batch size'),
      '#default_value' => $ip2country_config->get('batch_size'),
      '#description'   => $this->t('The number of rows to insert simultaneously. A larger number means faster filling of the database, but more system resources (memory usage).'),
    ];

    // Form to initiate manual updating of the IP-Country database.
    $form['ip2country_database_update']['ip2country_update_database'] = [
      '#type'        => 'button',
      '#value'       => $this->t('Update'),
      '#executes_submit_callback' => FALSE,
      '#prefix'      => '<p>' . $this->t('The IP to Country Database may be updated manually by pressing the "Update" button below. Note, this may take several minutes. Changes to the above settings will not be permanently saved unless you press the "Save configuration" button at the bottom of this page.') . '</p>',
      '#suffix'      => '<span id="dbthrobber" class="message">' . $message . '</span>',
    ];

    // Container for manual lookup.
    $form['ip2country_manual_lookup'] = [
      '#type'        => 'details',
      '#title'       => $this->t('Manual lookup'),
      '#description' => $this->t('Examine database values'),
      '#open'        => TRUE,
    ];

    // Form for IP address for manual lookup.
    $form['ip2country_manual_lookup']['ip2country_lookup'] = [
      '#type'        => 'textfield',
      '#title'       => $this->t('Manual lookup'),
      '#description' => $this->t('Enter IP address'),
      //'#element_validate' => [[$this, 'validateIp']],
    ];

    // Form to initiate manual lookup.
    $form['ip2country_manual_lookup']['ip2country_lookup_button'] = [
      '#type'        => 'button',
      '#value'       => $this->t('Lookup'),
      '#executes_submit_callback' => FALSE,
      '#prefix'      => '<div>' . $this->t('An IP address may be looked up in the database by entering the address above then pressing the Lookup button below.') . '</div>',
      '#suffix'      => '<span id="lookup-message" class="message"></span>',
    ];

    // Container for debugging preference forms.
    $form['ip2country_debug_preferences'] = [
      '#type'        => 'details',
      '#title'       => $this->t('Debug preferences'),
      '#description' => $this->t('Set debugging values'),
      '#open'        => TRUE,
    ];

    // Form to turn on debugging.
    $form['ip2country_debug_preferences']['ip2country_debug'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Admin debug'),
      '#default_value' => $ip2country_config->get('debug'),
      '#description'   => $this->t('Enables administrator to spoof an IP Address or Country for debugging purposes.'),
    ];

    // Form to select Dummy Country or Dummy IP Address for testing.
    $form['ip2country_debug_preferences']['ip2country_test_type'] = [
      '#type'          => 'radios',
      '#title'         => $this->t('Select which parameter to spoof'),
      '#default_value' => $ip2country_config->get('test_type'),
      '#options'       => [$this->t('Country'), $this->t('IP Address')],
    ];

    $ip_current = $this->currentRequest->getClientIp();

    // Form to enter Country to spoof.
    $default_country = $ip2country_config->get('test_country');
    $default_country = empty($default_country) ? $this->ip2countryManager->getCountry($ip_current) : $default_country;
    $form['ip2country_debug_preferences']['ip2country_test_country'] = [
      '#type'          => 'select',
      '#title'         => $this->t('Country to use for testing'),
      '#default_value' => $default_country,
      '#options'       => $this->countryManager->getList(),
    ];

    // Form to enter IP address to spoof.
    $test_ip_address = $ip2country_config->get('test_ip_address');
    $test_ip_address = empty($test_ip_address) ? $ip_current : $test_ip_address;
    $form['ip2country_debug_preferences']['ip2country_test_ip_address'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('IP address to use for testing'),
      '#default_value' => $test_ip_address,
      '#element_validate' => [[$this, 'validateIp']],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    return parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    $ip2country_config = $this->config('ip2country.settings');

    $ip2country_config
      ->setData([
        'watchdog' => (boolean) $values['ip2country_watchdog'],
        'rir' => (string) $values['ip2country_rir'],
        'md5_checksum' => (boolean) $values['ip2country_md5_checksum'],
        'update_interval' => (integer) $values['ip2country_update_interval'],
        'batch_size' => (integer) $values['ip2country_update_batch_size'],
        'debug' => (boolean) $values['ip2country_debug'],
        'test_type' => (integer) $values['ip2country_test_type'],
        'test_country' => (string) $values['ip2country_test_country'],
        'test_ip_address' => (string) $values['ip2country_test_ip_address'],
      ])
      ->save();

    // Check to see if debug set.
    if ($values['ip2country_debug']) {
      // Debug on.
      if ($values['ip2country_test_type']) {
        // Dummy IP Address.
        $ip = $values['ip2country_test_ip_address'];
        $country_code = $this->ip2countryManager->getCountry($ip);
      }
      else {
        // Dummy Country.
        $country_code = $values['ip2country_test_country'];
      }
      $country_list = $this->countryManager->getList();
      $country_name = $country_list[$country_code];
      $this->messenger()->addMessage($this->t('Using DEBUG value for Country - @country (@code)', ['@country' => $country_name, '@code' => $country_code]));
    }
    else {
      // Debug off - make sure we set/reset IP/Country to their real values.
      $ip = $this->currentRequest->getClientIp();
      $country_code = $this->ip2countryManager->getCountry($ip);
      $this->messenger()->addMessage($this->t('Using ACTUAL value for Country - @country', ['@country' => $country_code]));
    }

    // Finally, save country, if it has been determined.
    if ($country_code) {
      // Store the ISO country code in the $user object.
      $account_id = $this->currentUser->id();
      $account = User::load($account_id);
      $account->country_iso_code_2 = $country_code;
      $this->userData->set('ip2country', $account_id, 'country_iso_code_2', $country_code);
    }

    parent::submitForm($form, $form_state);
  }

  /**
   * Element validation handler for IP address input.
   */
  public function validateIp($element, FormStateInterface $form_state) {
    $ip_address = $element['#value'];
    if (!filter_var($ip_address, FILTER_VALIDATE_IP)) {
      $form_state->setError($element, $this->t('The IP address you entered is invalid. Please enter an address in the form xxx.xxx.xxx.xxx where xxx is between 0 and 255 inclusive.'));
    }
  }

}
