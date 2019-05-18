<?php

namespace Drupal\automatic_updates\Services;

use Composer\Semver\VersionParser;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Version\Constraint;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Extension\ExtensionList;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\TransferException;
use Psr\Log\LoggerInterface;

/**
 * Class AutomaticUpdatesPsa.
 */
class AutomaticUpdatesPsa implements AutomaticUpdatesPsaInterface {
  use StringTranslationTrait;
  use DependencySerializationTrait;

  /**
   * This module's configuration.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * The http client.
   *
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * The cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * The module extension list.
   *
   * @var \Drupal\Core\Extension\ExtensionList
   */
  protected $module;

  /**
   * The profile extension list.
   *
   * @var \Drupal\Core\Extension\ExtensionList
   */
  protected $profile;

  /**
   * The theme extension list.
   *
   * @var \Drupal\Core\Extension\ExtensionList
   */
  protected $theme;

  /**
   * The logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * AutomaticUpdatesPsa constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache backend.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   * @param \GuzzleHttp\Client $client
   *   The HTTP client.
   * @param \Drupal\Core\Extension\ExtensionList $module
   *   The module extension list.
   * @param \Drupal\Core\Extension\ExtensionList $profile
   *   The profile extension list.
   * @param \Drupal\Core\Extension\ExtensionList $theme
   *   The theme extension list.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger.
   */
  public function __construct(ConfigFactoryInterface $config_factory, CacheBackendInterface $cache, TimeInterface $time, Client $client, ExtensionList $module, ExtensionList $profile, ExtensionList $theme, LoggerInterface $logger) {
    $this->config = $config_factory->get('automatic_updates.settings');
    $this->cache = $cache;
    $this->time = $time;
    $this->httpClient = $client;
    $this->module = $module;
    $this->profile = $profile;
    $this->theme = $theme;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public function getPublicServiceMessages() {
    $messages = [];

    if (!$this->config->get('enable_psa')) {
      return $messages;
    }

    if ($cache = $this->cache->get('automatic_updates_psa')) {
      $response = $cache->data;
    }
    else {
      $psa_endpoint = $this->config->get('psa_endpoint');
      try {
        $response = $this->httpClient->get($psa_endpoint)
          ->getBody()
          ->getContents();
        $this->cache->set('automatic_updates_psa', $response, $this->time->getCurrentTime() + $this->config->get('check_frequency'));
      }
      catch (TransferException $exception) {
        $this->logger->error($exception->getMessage());
        return [$this->t('Drupal PSA endpoint :url is unreachable.', [':url' => $psa_endpoint])];
      }
    }

    try {
      $json_payload = json_decode($response);
      if ($json_payload) {
        foreach ($json_payload as $json) {
          if ($json->project === 'core') {
            $this->coreParser($messages, $json);
          }
          else {
            $this->contribParser($messages, $json);
          }
        }
      }
      else {
        $this->logger->error('Drupal PSA JSON is malformed: @response', ['@response' => $response]);
        $messages[] = $this->t('Drupal PSA JSON is malformed.');
      }

    }
    catch (\UnexpectedValueException $exception) {
      $this->logger->error($exception->getMessage());
      $messages[] = $this->t('Drupal PSA endpoint service is malformed.');
    }

    return $messages;
  }

  /**
   * Parse core project JSON version strings.
   *
   * @param array $messages
   *   The messages array.
   * @param object $json
   *   The JSON object.
   */
  protected function coreParser(array &$messages, $json) {
    $parser = new VersionParser();
    array_walk($json->secure_versions, function (&$version) {
      $version = '<' . $version;
    });
    $version_string = implode('||', $json->secure_versions);
    $psa_constraint = $parser->parseConstraints($version_string);
    $core_constraint = $parser->parseConstraints(\Drupal::VERSION);
    if ($psa_constraint->matches($core_constraint)) {
      $messages[] = new FormattableMarkup('<a href=":url">:message</a>', [
        ':message' => $json->title,
        ':url' => $json->link,
      ]);
    }
  }

  /**
   * Parse contrib project JSON version strings.
   *
   * @param array $messages
   *   The messages array.
   * @param object $json
   *   The JSON object.
   */
  protected function contribParser(array &$messages, $json) {
    $extension_list = $json->type;
    if (!property_exists($this, $extension_list)) {
      $this->logger->error('Extension list of type "%extension" does not exist.', ['%extension' => $extension_list]);
      return;
    }
    array_walk($json->secure_versions, function (&$version) {
      if (substr($version, 0, 4) === \Drupal::CORE_COMPATIBILITY . '-') {
        $version = substr($version, 4);
      }
    });
    foreach ($json->extensions as $extension_name) {
      if ($this->{$extension_list}->exists($extension_name)) {
        $extension = $this->{$extension_list}->getAllAvailableInfo()[$extension_name];
        if (empty($extension['version'])) {
          continue;
        }
        $this->contribMessage($messages, $json, $extension['version']);
      }
    }
  }

  /**
   * Add a contrib message PSA, if appropriate.
   *
   * @param array $messages
   *   The messages array.
   * @param object $json
   *   The JSON object.
   * @param string $extension_version
   *   The extension version.
   */
  protected function contribMessage(array &$messages, $json, $extension_version) {
    $version_string = implode('||', $json->secure_versions);
    $constraint = new Constraint("<=$extension_version", \Drupal::CORE_COMPATIBILITY);
    if (!$constraint->isCompatible($version_string)) {
      $messages[] = new FormattableMarkup('<a href=":url">:message</a>', [
        ':message' => $json->title,
        ':url' => $json->link,
      ]);
    }
  }

}
