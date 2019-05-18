<?php

namespace Drupal\drd;

use Drupal\Core\Database\Database;
use Drupal\drd\Entity\DomainInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Build a local copy of a domain into a given working directory.
 */
class DomainLocalCopy {

  /**
   * DRD settings.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $drdConfig;

  /**
   * Decrypted local db password.
   *
   * @var string
   */
  protected $localDbPass;

  /**
   * Logging progress.
   *
   * @var string[]
   */
  protected $log = [];

  /**
   * Drupal root directory.
   *
   * @var string
   */
  protected $drupalDir;

  /**
   * Project root directory.
   *
   * @var string
   */
  protected $workingDir;

  /**
   * Project settings directory.
   *
   * @var string
   */
  protected $settingsDir;

  /**
   * DRD domain entity for which to create a local copy.
   *
   * @var \Drupal\drd\Entity\DomainInterface
   */
  protected $domain;

  /**
   * All domain GLOBALS.
   *
   * @var array
   */
  protected $domainGlobals;

  /**
   * All domain settings.
   *
   * @var array
   */
  protected $domainSettings;

  /**
   * Drupal core version of the domain.
   *
   * @var int
   */
  protected $coreVersion;

  /**
   * Get the activity log.
   *
   * @return string[]
   *   The activity log.
   */
  public function getLog() {
    return $this->log;
  }

  /**
   * Set the Drupal root directory.
   *
   * @param string $drupalDir
   *   Drupal root directory.
   *
   * @return $this
   */
  public function setDrupalDirectory($drupalDir) {
    $this->drupalDir = $drupalDir;
    return $this;
  }

  /**
   * Set the project root directory.
   *
   * @param string $workingDir
   *   The root directory.
   *
   * @return $this
   */
  public function setWorkingDirectory($workingDir) {
    $this->workingDir = $workingDir;
    return $this;
  }

  /**
   * Set the DRD domain entity.
   *
   * @param \Drupal\drd\Entity\DomainInterface $domain
   *   The domain entity.
   *
   * @return $this
   */
  public function setDomain(DomainInterface $domain) {
    $this->drdConfig = \Drupal::config('drd.general');
    $this->localDbPass = $this->drdConfig->get('local.db.pass');
    \Drupal::service('drd.encrypt')->decrypt($this->localDbPass);
    $this->domain = $domain;
    $this->domainGlobals = $this->domain->getRemoteGlobals();
    $this->domainSettings = $this->domain->getRemoteSettings();
    $this->coreVersion = $this->domain->getCore()->getDrupalRelease()->getMajor()->getCoreVersion();
    $this->settingsDir = implode(DIRECTORY_SEPARATOR, [
      $this->drupalDir,
      'sites',
      $this->domain->getLocalUrl(),
    ]);

    // Create or update sites.php.
    $sites_file = implode(DIRECTORY_SEPARATOR, [
      $this->drupalDir,
      'sites',
      'sites.php',
    ]);
    if (!file_exists($sites_file)) {
      file_put_contents($sites_file, '<?php');
    }
    file_put_contents(
      $sites_file,
      PHP_EOL . '$sites["' . $this->domain->getLocalUrl() . '"] = "' . $this->domain->getLocalUrl() . '";',
      FILE_APPEND
    );

    return $this;
  }

  /**
   * Setup the local copy when everything else has been prepared.
   *
   * @return bool
   *   TRUE if the copy was created successfully.
   */
  public function setup() {
    $databases = $this->domain->database();
    if (empty($databases)) {
      return FALSE;
    }
    foreach ($databases as $key => $targets) {
      foreach ($targets as $target => $def) {
        $databases[$key][$target]['database'] = $this->buildDatabase($key, $target, $def);
      }
    }

    $options = [
      'drd' => [
        'db' => [
          'user' => $this->drdConfig->get('local.db.user'),
          'pass' => $this->localDbPass,
        ],
      ],
      'databases' => $databases,
      'database_config' => Database::getConnectionInfo()['default'],
      'globals' => $this->domainGlobals,
      'settings' => $this->domainSettings,
      'tempdir' => drupal_realpath(file_directory_temp()),
      'url' => $this->domain->getLocalUrl(),
    ];

    $this->mkdir($this->settingsDir);

    $templatefilename = drupal_get_path('module', 'drd') . '/templates/DomainLocalCopy.v' . $this->coreVersion . '.settings.php.twig';

    $twig_loader = new \Twig_Loader_Array([]);
    $twig = new \Twig_Environment($twig_loader);
    $twig_loader->setTemplate('settings', file_get_contents($templatefilename));
    $rendered = $twig->render('settings', $options);
    file_put_contents($this->settingsDir . DIRECTORY_SEPARATOR . 'settings.php', $rendered);

    $this->mkdir($this->drupalDir . DIRECTORY_SEPARATOR . $this->domainSettings['file_public_path']);
    $this->mkdir($this->drupalDir . DIRECTORY_SEPARATOR . $this->domainSettings['file_private_path']);
    foreach ($this->domainGlobals['config_directories'] as $config_directory) {
      $this->mkdir($this->drupalDir . DIRECTORY_SEPARATOR . $config_directory);
    }
    return TRUE;
  }

  /**
   * Remove temporary databases again.
   */
  public function dropDatabases() {
    $databases = $this->domain->database();
    if (empty($databases)) {
      return;
    }
    foreach ($databases as $key => $targets) {
      foreach ($targets as $target => $def) {
        $this->buildDatabase($key, $target, $def, TRUE);
      }
    }
  }

  /**
   * Create a directory taking care of symbolic links.
   *
   * @param string $dir
   *   Name of the directory.
   *
   * @throws \Exception
   */
  private function mkdir($dir) {
    $fs = new Filesystem();
    if ($fs->exists($dir)) {
      if (is_dir($dir) || is_link($dir)) {
        return;
      }
      throw new \Exception('Can not create directory ' . $dir);
    }
    if (is_link($dir)) {
      $target = readlink($dir);
      if ($target[0] == DIRECTORY_SEPARATOR) {
        $dir = $target;
      }
      else {
        $startparts = explode(DIRECTORY_SEPARATOR, $dir);
        $targetparts = explode(DIRECTORY_SEPARATOR, $target);
        array_unshift($targetparts, '..');
        while ($targetparts[0] == '..') {
          array_shift($targetparts);
          array_pop($startparts);
        }
        $parts = array_merge($startparts, $targetparts);
        $dir = implode(DIRECTORY_SEPARATOR, $parts);
      }
      $this->mkdir($dir);
      return;
    }
    $fs->mkdir($dir);
  }

  /**
   * Create and import a temporary database.
   *
   * @param string $key
   *   The database key.
   * @param string $target
   *   The database target.
   * @param array $def
   *   The database definition/configuration.
   * @param bool $drop
   *   Set to TRUE to drop the database again.
   *
   * @return string
   *   Name of the temporary database.
   */
  private function buildDatabase($key, $target, array $def, $drop = FALSE) {
    $database = implode('_', [
      'drd',
      'dump',
      $this->domain->id(),
      $key,
      $target,
    ]);
    $output = [];

    $config = Database::getConnectionInfo()['default'];
    $credentialsfile = drupal_realpath(\Drupal::service('file_system')->tempnam('temporary://', 'mysql'));

    $cmd = [
      'mysql',
      '--defaults-extra-file=' . $credentialsfile,
    ];
    $credentials = [
      '[mysql]',
      'host = ' . $config['host'],
      'port = ' . $config['port'],
      'user = ' . $this->drdConfig->get('local.db.user'),
      'password = ' . $this->localDbPass,
    ];

    file_put_contents($credentialsfile, implode("\n", $credentials));
    chmod($credentialsfile, 0600);

    $instruction = $drop ? 'DROP' : 'CREATE';
    $prepare = array_merge($cmd, [
      '--execute="DROP DATABASE IF EXISTS ' . $database . '; ' . $instruction . ' DATABASE ' . $database . ';"',
    ]);
    exec(implode(' ', $prepare), $output, $ret);
    if ($ret !== 0) {
      $output[] = $drop ?
        'Can not drop database' :
        'Can not prepare database.';
    }
    elseif (!$drop) {
      $import = array_merge($cmd, [
        $database,
        '<' . $def['file'],
      ]);
      exec(implode(' ', $import), $output, $ret);
      if ($ret !== 0) {
        $output[] = 'Can not import database.';
      }
    }

    unlink($credentialsfile);

    foreach ($output as $item) {
      $this->log[] = $item;
    }

    return $database;
  }

}
