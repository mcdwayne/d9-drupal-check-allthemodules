<?php

namespace Drupal\onepilot\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\File\FileSystem;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\onepilot\Exceptions\CmsPilotException;

/**
 * OnePilot controller handles
 *
 * /onepilot/validate
 * /onepilot/ping
 */
class OnePilotController extends ControllerBase {

  /**
   * @var Symfony\Component\HttpFoundation\RequestStack
   */
  private $requestStack;

  /**
   * The module handler.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * @var Drupal\Core\File\FileSystem
   */
  private $fileSystem;

  /**
   * Constructor.
   *
   * @param Symfony\Component\HttpFoundation\RequestStack $request_stack
   */
  public function __construct(
    ModuleHandlerInterface $module_handler,
    RequestStack $request_stack,
    FileSystem $file_system
  ) {
    $this->requestStack = $request_stack;
    $this->moduleHandler = $module_handler;
    $this->fileSystem = $file_system;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('module_handler'),
      $container->get('request_stack'),
      $container->get('file_system')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function ping() {
    $response = new JsonResponse();
    $response->setData(['message' => 'pong']);
    $response->setMaxAge(10);

    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function validate() {
    // Verify signature is correct
    $this->auth();

    $this->moduleHandler->loadInclude('update', 'inc', 'update.manager');
    $this->moduleHandler->loadInclude('update', 'inc', 'update.compare');

    $available = update_get_available(TRUE);

    $packages = update_calculate_project_data($available);

    $response = new JsonResponse();
    $response->setData([
      'core' => $this->getCore($packages),
      'extra' => [],
      'files' => $this->getFiles(),
      'plugins' => $this->getPlugins($packages),
      'servers' => $this->getServers($packages)
    ]);
    $response->setMaxAge(86400);

    return $response;
  }

  /**
   * Validate the token that gets sent
   */
  private function auth() {
    $request = $this->requestStack->getCurrentRequest();

    $signature = $request->headers->get('hash');
    $stamp = $request->headers->get('stamp');

    $config = \Drupal::config('onepilot.settings');

    $private_key = $config->get('private_key');

    if (!$signature) {
      throw new CmsPilotException('no-verification-key', 403);
    }

    if (!$private_key) {
      throw new CmsPilotException('no-private-key-configured', 403);
    }

    $hash = hash_hmac('sha256', $stamp, $private_key);

    if (!hash_equals($hash, $signature)) {
      throw new CmsPilotException('bad-authentification', 403);
    }

    return true;
  }

  /**
   * Validate the timestamp from 1pilot
   */
  private function validateTimestamp() {
    $request = $this->requestStack->getCurrentRequest();

    $stamp = $request->headers->get('stamp');

    $config = \Drupal::config('onepilot.settings');

    $skip_timestamp_validation = (bool) $config->get('skip_timestamp');

    if ($skip_timestamp_validation === false) {
      return;
    }

    if (($stamp > time() - 360) && ($stamp < time() + 360)) {
      return;
    }

    throw new CmsPilotException('bad-timestamp', 403);
  }

  /**
   * Get the core details
   */
  private function getCore($packages) {
    $new_version = ($packages['drupal']['status'] === UPDATE_CURRENT) ? null : true;
    $last_available_version = $packages['drupal']['latest_version'];

    return [
      'last_available_version' => $last_available_version,
      'new_version' => $new_version,
      'version' => $packages['drupal']['info']['version']
    ];
  }

  /**
   * Get any extra details
   */
  private function getExtra($packages) {
    return [];
  }

  /**
   * Get file details
   */
  private function getFiles() {
    $filesProperties = [];

    $public_folder = dirname($_SERVER["SCRIPT_FILENAME"]);

    $files = [
      DRUPAL_ROOT . '/sites/default/settings.php' => 'settings.php',
      $public_folder . '/index.php' => 'index.php'
    ];

    foreach ($files as $absolutePath => $relativePath) {

      if (!file_exists($absolutePath)) {
          continue;
      }

      $fp = fopen($absolutePath, 'r');
      $fstat = fstat($fp);
      fclose($fp);

      $filesProperties[] = [
        'path'     => $relativePath,
        'size'     => $fstat['size'],
        'mtime'    => $fstat['mtime'],
        'checksum' => md5_file($absolutePath),
      ];
    }

    return $filesProperties;
  }

  /**
   * Get the plugin details
   */
  private function getPlugins($packages) {
    $extensions = [];

    foreach ($packages as $package_name => $package) {
      $extension = [
        'version'     => $package['info']['version'],
        'new_version' => null,
        'name'        => $package_name,
        'code'        => $package['link'],
        'type'        => 'plugin',
        'active'      => $package['project_status'],
        'changelog'   => null,
      ];

      if ($package && array_key_exists('link', $package)) {
        $extension['authorurl'] = $package['link'];
      }

      if (
        $package['status'] === UPDATE_NOT_SECURE ||
        $package['status'] === UPDATE_REVOKED ||
        $package['status'] === UPDATE_NOT_SUPPORTED ||
        $package['status'] === UPDATE_NOT_CURRENT
      ) {
        $extension['new_version'] = (isset($package['latest_version'])) ? $package['latest_version'] : true;
      }

      if (array_key_exists('releases', $package) && $extension['new_version']) {
        $changelog = [];
        foreach ($package['releases'] as $release) {
          $changelog[$release['version']] = "View changelog at - " . $release['release_link'];
        }

        $extension['changelog'] = $changelog;
      }

      $extensions[] = $extension;
    }

    return $extensions;
  }

  /**
   * Get the server details
   */
  private function getServers($packages) {
    $query = \Drupal::database()->query( "SELECT version();" );
    $db_version = $query->fetchField();

    return [
      'mysql' => $db_version,
      'php' => phpversion(),
      'web' => $_SERVER['SERVER_SOFTWARE']
    ];
  }

}
