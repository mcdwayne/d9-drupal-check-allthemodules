<?php

namespace Drupal\file_version;

use Drupal\Component\Utility\Crypt;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\PrivateKey;
use Drupal\Core\Site\Settings;

/**
 * Class FileVersion.
 *
 * @package Drupal\file_version
 */
class FileVersion implements FileVersionInterface {

  /**
   * PrivateKey Service.
   *
   * @var \Drupal\Core\PrivateKey
   */
  private $privateKey;

  /**
   * ConfigFactory Service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  private $configFactory;

  /**
   * Module Handler Service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  private $moduleHandler;

  const IMAGE_STYLE_URI_TARGET_PREFIX = 'styles/';

  /**
   * Constructor method of FileVersion Service.
   *
   * @param \Drupal\Core\PrivateKey $private_key
   *   PrivateKey Service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   ConfigFactory Service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   ModuleHandler Service.
   */
  public function __construct(PrivateKey $private_key, ConfigFactoryInterface $config_factory, ModuleHandlerInterface $module_handler) {
    $this->privateKey = $private_key;
    $this->configFactory = $config_factory;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public function addFileVersionToken(&$uri, $original_uri = NULL) {
    if (!$original_uri) {
      $original_uri = $uri;
    }

    $file_version_settings = $this->configFactory->get('file_version.settings');
    $get_parameter_name = $file_version_settings->get('get_parameter_name');
    $whitelist_extensions = $this->getWhitelistedExtensions();
    $extension = pathinfo($uri, PATHINFO_EXTENSION);

    if (
          $file_version_settings->get('enable_all_files')
      ||  ($file_version_settings->get('enable_image_styles') && $this->isImageStyleUri($original_uri))
      ||  in_array($extension, $whitelist_extensions)
    ) {
      $blacklist_extensions = $this->getBlacklistedExtensions();

      if (!in_array($extension, $blacklist_extensions)) {
        $url = UrlHelper::parse($uri);

        if (empty($url['query'][$get_parameter_name])) {
          $query = [
            $get_parameter_name => $this->getFileVersionToken($original_uri),
          ];

          $uri .= (strpos($uri, '?') !== FALSE ? '&' : '?') . UrlHelper::buildQuery($query);
        }
      }
    }
  }

  /**
   * Check if the path is image style path.
   *
   * @param string $uri
   *   Uri to check.
   *
   * @return bool
   *   TRUE if is the uri is an image style uri, FALSE in otherwise.
   */
  private function isImageStyleUri($uri) {
    $target = file_uri_target($uri);
    if ($target) {
      $prefixes_pattern = preg_quote(static::IMAGE_STYLE_URI_TARGET_PREFIX, '/');
      $pattern = '/^' . $prefixes_pattern . '/';
      return preg_match($pattern, $target);
    }
    return FALSE;
  }

  /**
   * Get all whitelisted extensions.
   *
   * @return array
   *   Extensions that belongs to whitelist.
   */
  private function getWhitelistedExtensions() {
    $extension_whitelist = $this->configFactory->get('file_version.settings')->get('extensions_whitelist');
    return $this->parseCommaSeparatedList($extension_whitelist);
  }

  /**
   * Get all blacklisted extensions.
   *
   * @return array
   *   Extensions that belongs to blacklist.
   */
  private function getBlacklistedExtensions() {
    $extension_blacklist = $this->configFactory->get('file_version.settings')->get('extensions_blacklist');
    return $this->parseCommaSeparatedList($extension_blacklist);
  }

  /**
   * {@inheritdoc}
   */
  public function parseCommaSeparatedList($string) {
    $items = explode(',', $string);
    $items = array_map('trim', $items);
    return array_filter($items, function ($value) {
      return $value !== "";
    });
  }

  /**
   * {@inheritdoc}
   */
  public function getFileVersionToken($uri) {
    $modified_file = NULL;
    if (file_exists($uri)) {
      $modified_file = filemtime($uri);
    }
    if (!$modified_file) {
      $modified_file = time();
    }

    return $this->getCryptedToken("$uri:$modified_file");
  }

  /**
   * {@inheritdoc}
   */
  public function getCryptedToken($data) {
    $private_key = $this->privateKey->get();
    $hash_salt = Settings::getHashSalt();

    // Return the first eight characters.
    return substr(Crypt::hmacBase64($data, $private_key . $hash_salt), 0, 8);
  }

  /**
   * By Passed Protocols.
   *
   * These protocols will avoid
   * \Drupal\Core\StreamWrapper\StreamWrapperInterface::getExternalUrl().
   *
   * @return array
   *   List of by passed protocols.
   *
   * @see file_create_url()
   * @see \Drupal\Core\StreamWrapper\StreamWrapperInterface::getExternalUrl()
   */
  private function getByPassedProtocols() {
    return ['http', 'https', 'data'];
  }

  /**
   * {@inheritdoc}
   */
  public function isProtocolByPassed($protocol) {
    $by_passed_protocols = $this->getByPassedProtocols();
    return in_array($protocol, $by_passed_protocols);
  }

  /**
   * {@inheritdoc}
   */
  public function getInvalidQueryParameterNames() {
    $invalid_params = ['q', 'itok', 'file'];
    $this->moduleHandler->invokeAll('file_version_invalid_params', [$invalid_params]);
    return $invalid_params;
  }

}
