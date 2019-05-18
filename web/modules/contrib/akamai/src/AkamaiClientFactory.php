<?php

namespace Drupal\akamai;

/**
 * Provides a factory for Akamai client objects.
 */
class AkamaiClientFactory {

  /**
   * The akamai client plugin manager.
   *
   * @var \Drupal\akamai\AkamaiClientManager
   */
  protected $clientManager;

  /**
   * The client version to use for this factory.
   *
   * @var string
   */
  protected $clientVersion;

  /**
   * Constructs a new AkamaiClientFactory object.
   *
   * @param \Drupal\akamai\AkamaiClientManager $client_manager
   *   The akamai client plugin manager.
   */
  public function __construct(AkamaiClientManager $client_manager) {
    $this->clientManager = $client_manager;
    $this->clientVersion = $this->clientManager->getDefaultClientVersion();
  }

  /**
   * Sets the version of the akamai client.
   *
   * @param string $client_version
   *   The version of the akamai client to use for this client factory.
   *
   * @return $this
   */
  public function setClientVersion($client_version) {
    $this->clientVersion = $client_version;
    return $this;
  }

  /**
   * Gets the version of the akamai client currently in use.
   *
   * @return string
   *   The version of the akamai client in use by the client factory.
   */
  public function getClientVersion() {
    return $this->clientVersion;
  }

  /**
   * Constructs a new AkamaiClient object.
   *
   * Normally, the version set as the default in the admin UI is used by the
   * factory to create new AkamaiClient objects. This can be overridden through
   * \Drupal\akamai\AkamaiClientInterface::setclientVersion() so that any new
   * AkamaiClient object created will use the new version specified. Finally,
   * a single AkamaiClient object can be created using a specific version,
   * regardless of the current factory settings, by passing its plugin ID in
   * the $client_version argument.
   *
   * @param string|null $client_version
   *   (optional) The version of the akamai client to use for this object,
   *   or NULL to use the current version.
   *
   * @return \Drupal\akamai\AkamaiClientInterface
   *   An Akamai Client object.
   *
   * @see AkamaiClientFactory::setclientVersion()
   */
  public function get($client_version = NULL) {
    $client_version = $client_version ?: $this->clientVersion;
    return $this->clientManager->createInstance($client_version);
  }

  /**
   * Returns the supported Akamai client versions.
   *
   * @param string|null $client_version
   *   (optional) The version of the client to use, or NULL to use
   *   the current version.
   *
   * @return array
   *   An array of supported client CCU versions (e.g. v2/ v3).
   *
   * @see \Drupal\akamai\AkamaiClientInterface::getSupportedExtensions()
   */
  public function getSupportedVersions($client_version = NULL) {
    $client_version = $client_version ?: $this->clientVersion;
    $definition = $this->clientManager->getDefinition($client_version);
    return call_user_func($definition['class'] . '::getSupportedVersions');
  }

}
