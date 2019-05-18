<?php

namespace Drupal\monitoring_icinga\Controller;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller class to provide useful information to setup Monitoring Icinga.
 */
class ConfigController extends ControllerBase {

  /**
   * The site path.
   *
   * @var string
   */
  protected $sitePath;

  /**
   * Constructs a new ConfigController object.
   *
   * @param string $site_path
   *   The site path.
   */
  public function __construct($site_path) {
    $this->sitePath = $site_path;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('site.path')
    );
  }

  /**
   * Controller method to show the Icinga active connection configuration.
   *
   * @return array
   *   The active setup page render array.
   */
  public function active() {
    $output = [];

    $output[] = [
      '#theme' => 'monitoring_icinga__config_box',
      '#description' => $this->t('Active services check means Icinga server itself is responsible for contacting host to retrieve status data. Info on this page will help you to configure this setup.'),
    ];

    $output[] = [
      '#theme' => 'monitoring_icinga__config_box',
      '#title' => $this->t('Prerequisites'),
      '#description' => $this->t('Before continuing with following setup you need to have Icinga server up and running and the NRPE connector at the monitored system. Refer to the README.txt for a brief setup procedure.'),
    ];

    $output[] = [
      '#theme' => 'monitoring_icinga__config_box',
      '#title' => $this->t('Command definition to issue the remote NRPE call'),
      '#description' => $this->t('In the /etc/icinga/commands.cfg file add following command definition.'),
      '#code' => $this->codeFromTemplate('monitoring_icinga', 'command'),
      '#code_height' => '70',
    ];

    $output[] = $this->icingaDefinitionOutput();

    $conf_path = explode('/', $this->sitePath);

    $output[] = [
      '#theme' => 'monitoring_icinga__config_box',
      '#title' => $this->t('NRPE configuration'),
      '#description' => $this->t('After installing NRPE on the monitored machine in the /etc/nagios/nrpe_local.cfg add following code.'),
      '#code' => $this->codeFromTemplate('monitoring_icinga', 'nrpe_local', [
        '@root' => DRUPAL_ROOT,
        '@uri' => array_pop($conf_path),
        '@site_key' => monitoring_host_key(),
        '@ip' => $_SERVER['SERVER_ADDR'],
      ]),
      '#code_height' => '300',
    ];

    return $output;
  }

  /**
   * Controller method to show the Icinga passive connection configuration.
   *
   * @return array
   *   The passive setup page render array.
   */
  public function passive() {
    $output = [];

    drupal_set_message(t('Passive result checing is not recommended as in case of the monitored system failure the Icinga server will not get notified of this fact. Due to this the <a href=":url">passive checks with freshness checking</a> is recommended.',
          [':url' => \Drupal::url('monitoring_icinga.config_passive')]), 'warning');

    $output[] = [
      '#theme' => 'monitoring_icinga__config_box',
      '#description' => $this->t('Passive services check means the host is responsible for contacting submitting the status data. Info on this page will help you to configure this setup.'),
    ];

    $output[] = [
      '#theme' => 'monitoring_icinga__config_box',
      '#title' => $this->t('Prerequisites'),
      '#description' => $this->t('Before continuing with following setup you need to have Icinga server up and running with "nsca" daemon and the "send_nsca" command at the monitored system. Refer to the README.txt for a brief setup procedure.'),
    ];

    $output[] = $this->icingaDefinitionOutput(FALSE);

    $conf_path = explode('/', $this->sitePath);

    $output[] = [
      '#theme' => 'monitoring_icinga__config_box',
      '#title' => $this->t('Setting up send_nsca command'),
      '#description' => $this->t('Following script is a working example of retrieving the check results and submitting them to the Icinga server. Create such script at your monitored system and hook it into cron tab to run every five minutes. Make sure all paths are correct.',
        ['@name' => str_replace('.', '_', monitoring_host())]),
      '#code' => $this->codeFromTemplate('monitoring_icinga', 'submit_check_result', [
        '@central_server' => 'REPLACE WITH YOUR ICINGA SERVER ADDRESS',
        '@host_name' => monitoring_host(),
        '@root' => DRUPAL_ROOT,
        '@uri' => array_pop($conf_path),
      ]),
      '#code_height' => '300',
    ];

    return $output;
  }

  /**
   * Controller method to show the Icinga passive freshness connection setup.
   *
   * @return array
   *   The passive with freshness setup page render array.
   */
  public function passiveFreshness() {
    $output = [];

    $output[] = [
      '#theme' => 'monitoring_icinga__config_box',
      '#description' => $this->t('This approach combines passive and active result checks. In case Icinga freshness check of a sensor will not pass it will conduct a separate active check of the sensor.'),
    ];

    $output[] = [
      '#theme' => 'monitoring_icinga__config_box',
      '#title' => $this->t('Prerequisites'),
      '#description' => $this->t('Before continuing with following setup you need to have Icinga server up and running and the NRPE connector at the monitored system. Refer to the README.txt for a brief setup procedure.'),
    ];

    $output[] = [
      '#theme' => 'monitoring_icinga__config_box',
      '#title' => $this->t('Command definition to issue the remote NRPE call'),
      '#description' => $this->t('In the /etc/icinga/commands.cfg file add following command definition.'),
      '#code' => $this->codeFromTemplate('monitoring_icinga', 'command'),
      '#code_height' => '70',
    ];

    $output[] = $this->icingaDefinitionOutput(FALSE, TRUE);

    $conf_path = explode('/', $this->sitePath);

    $output[] = [
      '#theme' => 'monitoring_icinga__config_box',
      '#title' => $this->t('NRPE configuration'),
      '#description' => $this->t('After installing NRPE on the monitored machine in the /etc/nagios/nrpe_local.cfg add following code.'),
      '#code' => $this->codeFromTemplate('monitoring_icinga', 'nrpe_local', [
        '@root' => DRUPAL_ROOT,
        '@uri' => array_pop($conf_path),
        '@site_key' => monitoring_host_key(),
        '@ip' => $_SERVER['SERVER_ADDR'],
      ]),
      '#code_height' => '300',
    ];

    $output[] = [
      '#theme' => 'monitoring_icinga__config_box',
      '#title' => $this->t('Setting up send_nsca command'),
      '#description' => $this->t('Following script is a working example of retrieving the check results and submitting them to the Icinga server. Create such script at your monitored system and hook it into cron tab to run every five minutes. Make sure all paths are correct.',
        ['@name' => str_replace('.', '_', monitoring_host())]),
      '#code' => $this->codeFromTemplate('monitoring_icinga', 'submit_check_result', [
        '@central_server' => 'REPLACE WITH YOUR ICINGA SERVER ADDRESS',
        '@host_name' => monitoring_host(),
        '@root' => DRUPAL_ROOT,
        '@uri' => array_pop($conf_path),
      ]),
      '#code_height' => '300',
    ];

    return $output;
  }

  /**
   * Creates the object definitions output part.
   *
   * @param bool $active_checks
   *   Boolean indicating whether it is an active connection or not.
   * @param bool $check_freshness
   *   Boolean indicating whether it is necessary to check freshness or not.
   *
   * @return array
   *   Renderable array.
   */
  public function icingaDefinitionOutput($active_checks = TRUE, $check_freshness = FALSE) {
    $host = monitoring_host();
    $host_def = $this->codeFromTemplate('monitoring_icinga', 'host', [
      '@host' => $host,
      '@ip' => $_SERVER['SERVER_ADDR'],
    ]);

    $services_def = [];
    $servicegroups = [];
    foreach (monitoring_sensor_manager()->getEnabledSensorConfig() as $sensor_name => $sensor_info) {
      $services_def[] = $this->codeFromTemplate('monitoring_icinga', 'service', [
        '@host' => $host,
        '@service_description' => monitoring_icinga_service_description($sensor_info),
        '@sensor_name' => $sensor_name,
        '@site_key' => monitoring_host_key(),
        '@description' => $sensor_info->getDescription(),
        '@active_checks' => (int) $active_checks,
        '@passive_checks' => (int) (!$active_checks),
        '@check_freshness' => (int) $check_freshness,
        '@check_command' => ($check_freshness ? 'service_is_stale' : 'check_drupal'),
      ]);

      $category = strtolower(str_replace(' ', '_', $sensor_info->getCategory()));
      $servicegroups[$category]['alias'] = $category;
      $servicegroups[$category]['members'][] = $host . ',' . monitoring_icinga_service_description($sensor_info);
    }
    $services_def = implode("\n\n", $services_def);

    $servicegroups_def = [];
    foreach ($servicegroups as $name => $servicegroup) {
      $servicegroups_def[] = $this->codeFromTemplate('monitoring_icinga', 'servicegroup', [
        '@name' => monitoring_host_key() . '_' . $name,
        '@alias' => $servicegroup['alias'],
        '@members' => implode(', ', $servicegroup['members']),
      ]);
    }
    $servicegroups_def = implode("\n", $servicegroups_def);

    return [
      '#theme' => 'monitoring_icinga__config_box',
      '#title' => $this->t('Host and services configuration'),
      '#description' => $this->t('At the Icinga server create /etc/icnga/objects/@name_icinga.cfg file with following code. Note that the code changes based on which sensor are enabled/disabled.',
        ['@name' => monitoring_host_key()]),
      '#code' => "; === HOST DEFINITION ===\n" . $host_def . "\n; === SERVICEGROUPS DEFINITIONS ===\n" . $servicegroups_def . "\n; === DRUPAL SERVICES DEFINITIONS ===\n" . $services_def,
      '#code_height' => '300',
    ];
  }

  /**
   * Get config code with dynamic variables.
   *
   * @param string $module
   *   Module name.
   * @param string $type
   *   Config type.
   * @param array $variables
   *   Dynamic values.
   *
   * @return \Drupal\Component\Render\FormattableMarkup
   *   Config code.
   */
  protected function codeFromTemplate($module, $type, array $variables = []) {
    $code = file_get_contents(drupal_get_path('module', $module) . '/config_tpl/' . $type . '.txt');
    return new FormattableMarkup($code, $variables);
  }

}
