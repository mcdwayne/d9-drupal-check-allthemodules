<?php

namespace Drupal\cloudmersiveantivirus\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StreamWrapper\StreamWrapperInterface;
use Drupal\Core\Url;

use Drupal\cloudmersiveantivirus\Config;
use Drupal\cloudmersiveantivirus\Scanner;

/**
 * Configure file system settings for this site.
 */
class CloudmersiveAntivirusConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'cloudmersiveantivirus_system_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['cloudmersiveantivirus.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('cloudmersiveantivirus.settings');

    $form['enabled'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Cloudmersive Anti-virus integration'),
      '#default_value' => $config->get('enabled'),
    );

    $form['scan_mechanism_wrapper'] = array(
      '#type' => 'details',
      '#title' => $this->t('Scan mechanism'),
      '#open' => TRUE,
    );
    $form['scan_mechanism_wrapper']['scan_mode'] = array(
      '#type' => 'radios',
      '#title' => $this->t('Scan mechanism'),
      '#options' => array(
        Config::MODE_CLOUDMERSIVE  => $this->t('Cloudmersive Anti-virus API'),
        //Config::MODE_DAEMON      => $this->t('Daemon mode (over TCP/IP)'),
        //Config::MODE_UNIX_SOCKET => $this->t('Daemon mode (over Unix socket)')
      ),
      '#default_value' => $config->get('scan_mode'),
      '#description' => $this->t("Configure how Drupal connects to Cloudmersive Anti-virus. <a href='https://account.cloudmersive.com/signup'>Get key now</a>"),
    );



    // Configuration if CloudmersiveAntivirus is set to Executable mode.
    $form['scan_mechanism_wrapper']['mode_executable'] = array(
      '#type' => 'details',
      '#title' => $this->t('Cloudmersive Anti-virus API configuration'),
      '#open' => TRUE,
      '#states' => array(
        'visible' => array(
          ':input[name="scan_mode"]' => array('value' => Config::MODE_EXECUTABLE),
        ),
      ),
    );
    $form['scan_mechanism_wrapper']['mode_executable']['executable_path'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Cloudmersive Anti-virus API Key'),
      '#default_value' => $config->get('mode_executable.executable_path'),
      '#maxlength' => 255,
      // '#description' => t('The path to the CloudmersiveAntivirus executable. Defaults to %default_path.', array('%default_path' => CLOUDMERSIVEAV_DEFAULT_PATH)),
    );
 /*   $form['scan_mechanism_wrapper']['mode_executable']['executable_parameters'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Executable parameters'),
      '#default_value' => $config->get('mode_executable.executable_parameters') ?: '',
      '#maxlength' => 255,
      '#description' => $this->t('Optional parameters to pass to the clamscan executable, e.g. %example.', array('%example' => '--max-recursion=10')),
    );*/


    // Configuration if CloudmersiveAntivirus is set to Daemon mode.
    $form['scan_mechanism_wrapper']['mode_daemon_tcpip'] = array(
      '#type' => 'details',
      '#title' => $this->t('Daemon mode configuration (over TCP/IP)'),
      '#open' => TRUE,
      '#states' => array(
        'visible' => array(
          ':input[name="scan_mode"]' => array('value' => Config::MODE_DAEMON),
        ),
      ),
    );
    $form['scan_mechanism_wrapper']['mode_daemon_tcpip']['hostname'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Cloudmersive API Key'),
      '#default_value' => $config->get('mode_daemon_tcpip.hostname'),
      '#maxlength' => 255,
      // '#description' => t('The hostname for the CloudmersiveAntivirus daemon. Defaults to %default_host.', array('%default_host' => CLOUDMERSIVEAV_DEFAULT_HOST)),
    );
/*    $form['scan_mechanism_wrapper']['mode_daemon_tcpip']['port'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Port'),
      '#default_value' => $config->get('mode_daemon_tcpip.port'),
      '#size' => 6,
      '#maxlength' => 8,
      // '#description' => t('The port for the CloudmersiveAntivirus daemon.  Defaults to port %default_port.  Must be between 1 and 65535.', array('%default_port' => CLOUDMERSIVEAV_DEFAULT_PORT)),
    );*/


    // Configuration if CloudmersiveAntivirus is set to Daemon mode over Unix socket.
    $form['scan_mechanism_wrapper']['mode_daemon_unixsocket'] = array(
      '#type' => 'details',
      '#title' => $this->t('Daemon mode configuration (over Unix socket)'),
      '#open' => TRUE,
      '#states' => array(
        'visible' => array(
          ':input[name="scan_mode"]' => array('value' => Config::MODE_UNIX_SOCKET),
        ),
      ),
    );
    $form['scan_mechanism_wrapper']['mode_daemon_unixsocket']['unixsocket'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Socket path'),
      '#default_value' => $config->get('mode_daemon_unixsocket.unixsocket'),
      '#maxlength' => 255,
      // '#description' => t('The unix socket path for the CloudmersiveAntivirus daemon. Defaults to %default_socket.', array('%default_socket' => CLOUDMERSIVEAV_DEFAULT_UNIX_SOCKET)),
    );

    $form['outage_actions_wrapper'] = array(
      '#type' => 'details',
      '#title' => $this->t('Outage behaviour'),
      '#open' => TRUE,
    );
    $form['outage_actions_wrapper']['outage_action'] = array(
      '#type' => 'radios',
      '#title' => $this->t('Behaviour when Cloudmersive Antivirus API is unavailable'),
      '#options' => array(
        Config::OUTAGE_BLOCK_UNCHECKED => $this->t('Block unchecked files'),
        Config::OUTAGE_ALLOW_UNCHECKED => $this->t('Allow unchecked files'),
      ),
      '#default_value' => $config->get('outage_action'),
    );


    // Allow scanning according to scheme-wrapper.
    $form['schemes'] = array(
      '#type' => 'details',
      '#title' => 'Scannable schemes / stream wrappers',
      '#open' => TRUE,

      '#description' => $this->t('By default only @local schemes are scannable.',
         array('@local' => $this->l('STREAM_WRAPPERS_LOCAL', Url::fromUri('https://api.drupal.org/api/drupal/includes!stream_wrappers.inc/7')))),
    );

    $local_schemes  = $this->scheme_wrappers_available('local');
    $remote_schemes = $this->scheme_wrappers_available('remote');

    if (count($local_schemes)) {
      $form['schemes']['cloudmersiveantivirus_local_schemes'] = array(
        '#type' => 'checkboxes',
        '#title' => $this->t('Local schemes'),
        '#options' => $local_schemes,
        '#default_value' => $this->scheme_wrappers_to_scan('local'),
      );
    }
    if (count($remote_schemes)) {
      $form['schemes']['cloudmersiveantivirus_remote_schemes'] = array(
        '#type' => 'checkboxes',
        '#title' => $this->t('Remote schemes'),
        '#options' => $remote_schemes,
        '#default_value' => $this->scheme_wrappers_to_scan('remote'),
      );
    }


    $form['verbosity_wrapper'] = array(
      '#type' => 'details',
      '#title' => $this->t('Verbosity'),
      '#open' => TRUE,
    );
    $form['verbosity_wrapper']['verbosity'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Verbose'),
      '#description' => $this->t('Verbose mode will log all scanned files, including files which pass the Cloudmersive Antivirus scan.'),
      '#default_value' => $config->get('verbosity'),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    // Check that:
    // - the executable path exists
    // - the unix socket exists
    // - Drupal can connect to the hostname/port (warn but don't fail)
    
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    // Configure the stream-wrapper schemes that are overridden.
    // Local schemes behave differently to remote schemes.
    $local_schemes_to_scan  = (is_array($form_state->getValue('cloudmersiveantivirus_local_schemes')))
      ? array_filter($form_state->getValue('cloudmersiveantivirus_local_schemes'))
      : array();
    $remote_schemes_to_scan  = (is_array($form_state->getValue('cloudmersiveantivirus_remote_schemes')))
      ? array_filter($form_state->getValue('cloudmersiveantivirus_remote_schemes'))
      : array();
    $overridden_schemes = array_merge(
      $this->get_overridden_schemes('local',  $local_schemes_to_scan),
      $this->get_overridden_schemes('remote', $remote_schemes_to_scan)
    );

    $this->config('cloudmersiveantivirus.settings')
      ->set('enabled', $form_state->getValue('enabled'))
      ->set('outage_action', $form_state->getValue('outage_action'))
      ->set('overridden_schemes', $overridden_schemes)
      ->set('scan_mode', $form_state->getValue('scan_mode'))
      ->set('verbosity', $form_state->getValue('verbosity'))

      ->set('mode_executable.executable_path', $form_state->getValue('executable_path'))
      ->set('mode_executable.executable_parameters', $form_state->getValue('executable_parameters'))

      ->set('mode_daemon_tcpip.hostname', $form_state->getValue('hostname'))
      ->set('mode_daemon_tcpip.port', $form_state->getValue('port'))

      ->set('mode_daemon_unixsocket.unixsocket', $form_state->getValue('unixsocket'))

      ->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * List the available stream-wrappers, according to whether the
   * stream-wrapper is local or remote.
   *
   * @param string $type
   *   Either 'local' (for local stream-wrappers), or 'remote'.
   *
   * @return array
   *   Array of the names of scheme-wrappers, indexed by the machine-name of
   *   the scheme-wrapper.
   *   For example: array('public' => 'public://').
   */
  public function scheme_wrappers_available($type) {
    $mgr = \Drupal::service('stream_wrapper_manager');

    switch ($type) {
      case 'local':
        $schemes = array_keys($mgr->getWrappers(StreamWrapperInterface::LOCAL));
        break;

      case 'remote':
        $schemes = array_keys(array_diff_key(
          $mgr->getWrappers(StreamWrapperInterface::ALL),
          $mgr->getWrappers(StreamWrapperInterface::LOCAL)
        ));
        break;
    }

    $options = array();
    foreach ($schemes as $scheme) {
      $options[$scheme] = $scheme . '://';
    }
    return $options;
  }

  /**
   * List the stream-wrapper schemes that are configured to be scannable,
   * according to whether the scheme is local or remote.
   *
   * @param string $type
   *   Either 'local' (for local stream-wrappers), or 'remote'.
   *
   * @return array
   *   Unindexed array of the machine-names of stream-wrappers that should be
   *   scanned.
   *   For example: array('public', 'private').
   */
  public function scheme_wrappers_to_scan($type) {
    switch ($type) {
      case 'local':
        $schemes = array_keys($this->scheme_wrappers_available('local'));
        break;

      case 'remote':
        $schemes = array_keys($this->scheme_wrappers_available('remote'));
        break;
    }

    return array_filter($schemes, array('\Drupal\cloudmersiveantivirus\Scanner', 'isSchemeScannable'));
  }

  /**
   * List which schemes have been overridden.
   *
   * @param string $type
   *   Type of stream-wrapper: either 'local' or 'remote'.
   * @param array $schemes_to_scan
   *   Unindexed array, listing the schemes that should be scanned.
   *
   * @return array
   *   List of the schemes that have been overridden for this particular
   *   stream-wrapper type.
   */
  public function get_overridden_schemes($type, $schemes_to_scan) {
    $available_schemes = $this->scheme_wrappers_available($type);
    switch ($type) {
      case 'local':
        $overridden = array_diff_key($available_schemes, $schemes_to_scan);
        return array_keys($overridden);

      case 'remote':
        $overridden = array_intersect_key($available_schemes, $schemes_to_scan);
        return array_keys($overridden);
    }
  }
}
