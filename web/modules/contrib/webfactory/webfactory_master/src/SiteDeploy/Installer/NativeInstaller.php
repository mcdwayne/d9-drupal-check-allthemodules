<?php

namespace Drupal\webfactory_master\SiteDeploy\Installer;

use Drupal\Core\Database\Database;
use Drupal\webfactory_master\Entity\SatelliteEntity;
use Drupal\webfactory_master\SiteDeploy\SiteInstallerInterface;
use Drupal\webfactory_master\SiteDeploy\Sql\SqlDriverFactory;

/**
 * Class NativeInstaller encapsulates native drupal install process.
 *
 * @package Drupal\webfactory_master\SiteDeploy\Installer
 */
class NativeInstaller implements SiteInstallerInterface {

  /**
   * Given satellite to deploy.
   *
   * @var \Drupal\webfactory_master\Entity\SatelliteEntity
   */
  protected $satellite;

  /**
   * Target site directory (under /sites/ dir).
   *
   * @var string
   */
  protected $siteDir;

  /**
   * Profile settings pass to the drupal install process.
   *
   * @var array
   */
  protected $profileSettings;

  /**
   * Webfactory master host.
   *
   * @var string
   */
  protected $masterHost;

  /**
   * Privilege database account credentials.
   *
   * @var array
   */
  protected $masterDbInfo;

  /**
   * Target satellite database connector.
   *
   * @var array
   */
  protected $db;

  /**
   * Initialize native installer.
   *
   * @param \Drupal\webfactory_master\Entity\SatelliteEntity $sat_entity
   *   Satellite to deploy.
   * @param array $db_info
   *   Database deployment information.
   */
  public function __construct(SatelliteEntity $sat_entity, $db_info) {
    $this->satellite = $sat_entity;

    // Privileged account to handle install process.
    $this->masterDbInfo = [
      'username'  => $db_info['master_login'],
      'password'  => $db_info['master_pwd'],
    ];

    // Drupal satellite db connector.
    $this->db = [
      'driver'    => $db_info['driver'],
      'username'  => $db_info['username'],
      'password'  => $db_info['password'],
      'host'      => $db_info['host'],
      'port'      => $db_info['port'],
      'database'  => $db_info['database'],
      'db_prefix' => $db_info['db_prefix'],
    ];
  }

  /**
   * Prepare satellite installation.
   *
   * Before launching install process, we have to :
   * - fake $_SERVER['HTTP_HOST'] (install process will identify the right
   * subdir)
   * - prepare given profile settings
   * - create the database (or empty an existing one)
   * - prepare filesystem with mandatory settings.php, files dir, etc.
   */
  public function prepare() {
    $host = $this->satellite->get('directory');

    $this->siteDir = $host;
    $this->masterHost = $_SERVER['HTTP_HOST'];

    $_SERVER['HTTP_HOST']       = $host;
    $_SERVER['SERVER_PORT']     = '';
    $_SERVER['REQUEST_URI']     = '';
    $_SERVER['REQUEST_METHOD']  = NULL;
    $_SERVER['SERVER_SOFTWARE'] = NULL;
    $_SERVER['HTTP_USER_AGENT'] = NULL;

    $this->profileSettings = [
      'parameters' => [
        'profile' => $this->satellite->get('profile'),
        'langcode' => $this->satellite->get('language'),
      ],
      'forms' => [
        'install_settings_form' => [
          'driver' => $this->db['driver'],
          $this->db['driver'] => $this->db,
          'op' => 'Save and continue',
        ],
        'install_configure_form' => [
          'site_name' => $this->satellite->get('siteName'),
          'site_mail' => $this->satellite->get('mail'),
          'account' => [
            'name' => 'admin',
            'mail' => $this->satellite->get('mail'),
            'pass' => [
              'pass1' => 'admin',
              'pass2' => 'admin',
            ],
          ],
          'update_status_module' => [
            1 => TRUE,
            2 => TRUE,
          ],
          'clean_url' => TRUE,
          'op' => 'Save and continue',
        ],
      ],
    ];

    $this->prepareDatabase();
    $this->prepareDirectories();
  }

  /**
   * Launch install process.
   *
   * Be careful by calling this method, install process initialize a kernel
   * in place of current one.
   */
  public function install() {
    $id = $this->satellite->id();

    $security = \Drupal::service('webfactory.services.security');
    $security_config = \Drupal::service('config.factory')->getEditable('webfactory_master.security');
    $satellites = $security_config->get('satellites');
    $token = NULL;
    foreach ($satellites as $id_sat => $satellite) {
      if ($id_sat == $this->satellite->id()) {
        $token = $satellite['token'];
      }
    }

    $secured_pass = $this->satellite->get('pass');
    $ws_user = $this->satellite->get('wsUser');
    $pass    = $security->decrypt($secured_pass, $token);

    $class_loader = \Drupal::getContainer()->get('class_loader');

    Database::removeConnection('default');
    Database::addConnectionInfo('default', 'default', $this->db);

    require_once DRUPAL_ROOT . '/core/includes/install.core.inc';
    install_drupal($class_loader, $this->profileSettings);

    $config = \Drupal::service('config.factory')->getEditable('webfactory_slave.settings');

    $config->set('id', $id);
    $config->set('master_ip', $this->masterHost);
    $config->set('authentificate.username', $ws_user);
    $config->set('authentificate.password', $pass);
    $config->save();
  }

  /**
   * Prepare database before install.
   *
   * Create (and remove existing db).
   */
  protected function prepareDatabase() {
    $driver = SqlDriverFactory::getDriver($this->db['driver']);
    $driver->open($this->db['host'], $this->db['port'],
      $this->masterDbInfo['username'], $this->masterDbInfo['password']);

    if ($driver->dbExists($this->db['database'])) {
      $driver->dropDb($this->db['database']);
    }
    $driver->createDb($this->db['database']);
  }

  /**
   * Prepare drupal site directory.
   *
   * Create settings file, files directory.
   */
  protected function prepareDirectories() {
    $dir = $this->siteDir;
    if (!$dir) {
      \Drupal::logger('[Pre install] Satellite Deploy')->error(
        'No satellite sub directory has been defined.'
      );

      return;
    }

    // Satellite directory.
    $satellite_subdir = "sites/$dir";
    if (!file_exists($satellite_subdir)) {
      if (!is_dir($satellite_subdir)) {
        mkdir($satellite_subdir, 0777, TRUE);

        \Drupal::logger('[Pre install] Satellite Deploy')->info(
          'Satellite sub directory @satellite_sub_directory does not exist and has been created.', ['@satellite_sub_directory' => $satellite_subdir]
        );
      }
    }

    // Files directory will be created by Drupal.
    // Settings.php info wil be filled by Drupal.
    $files_to_copy = array(
      'sites/sites.php'                           => 'sites/example.sites.php',
      $satellite_subdir . '/settings.php'         => 'sites/default/default.settings.php',
      $satellite_subdir . '/services.yml'         => 'sites/default/default.services.yml',
    );

    foreach ($files_to_copy as $destination_file => $source_file) {
      $this->copyFile($source_file, $destination_file);
    }

    // Write in sites.php the new site with its rooting if needed.
    $sites_file = 'sites/sites.php';
    $sites_file_content = file_get_contents($sites_file);
    $line = "\$sites['" . $this->satellite->get('host') . "'] = '" . $dir . "';";
    // Search on each line if the satellite is already registered.
    if (!preg_match('/^' . preg_quote($line) . '/m', $sites_file_content)) {
      if (is_writable($sites_file)) {
        $data = PHP_EOL . $line . PHP_EOL;
        if ($fd = fopen($sites_file, 'a+')) {
          if (!fwrite($fd, $data)) {
            \Drupal::logger('[Pre install] Satellite Deploy')->error(
              'Impossible to register satellite in @file.',
              ['@file' => $sites_file]);
          }
          else {
            \Drupal::logger('[Pre install] Satellite Deploy')->info(
              'Satellite @satellite has been registered in @file.',
              [
                '@satellite' => $this->satellite->id(),
                '@file' => $sites_file,
              ]);
          }
        }
        else {
          \Drupal::logger('[Pre install] Satellite Deploy')->error(
            '@file can not be opened.',
            ['@file' => $sites_file]);
        }
      }
    }
  }

  /**
   * Copy a file from source to destination.
   *
   * @param string $source_file
   *   The source file.
   * @param string $destination_file
   *   The destination file.
   */
  protected function copyFile($source_file, $destination_file) {
    if (!file_exists($destination_file)) {
      if (!copy($source_file, $destination_file)) {
        \Drupal::logger('[Pre install] Satellite Deploy')->error(
          'Failed to copy @source_file to @destination_file',
          [
            '@source_file' => $source_file,
            '@destination_file' => $destination_file,
          ]
        );
      }
      else {
        \Drupal::logger('[Pre install] Satellite Deploy')->info(
          '@source_file has been copied to @destination_file',
          [
            '@source_file' => $source_file,
            '@destination_file' => $destination_file,
          ]
        );
      }
    }
  }

}
