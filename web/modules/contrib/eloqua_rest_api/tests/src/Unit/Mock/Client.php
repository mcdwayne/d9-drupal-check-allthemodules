<?php

/**
 * @file
 * Contains \Eloqua\Client
 */

namespace Eloqua;

/**
 * Represents a mock instance of the Elomentary Client. Used because the bots on
 * qa.drupal.org do not support composer dependencies in contrib modules...
 *
 * @see Drupal\Tests\eloqua_rest_api\Unit\Factory\ClientFactoryTest::setUp()
 */
class Client {

  /**
   * @var array
   */
  private $options = array(
    'base_url' => 'https://secure.eloqua.com/API/REST',
    'version' => '2.0',
    'user_agent' => 'Elomentary (http://github.com/tableau-mkt/elomentary)',
    'timeout' => 10,
    'count' => 100,
  );

  /**
   * Authenticate a user for all subsequent requests.
   *
   * @param string $site
   *   Eloqua site name for the instance against which requests should be made.
   *
   * @param string $login
   *   Eloqua user name with which requests should be made.
   *
   * @param string $password
   *   Password associated with the aforementioned Eloqua user.
   *
   * @param string $baseUrl
   *   Endpoint associated with the aforementioned Eloqua user.
   *
   * @param string $version
   *   API version to use.
   *
   * @throws InvalidArgumentException if any arguments are not specified.
   */
  public function authenticate($site, $login, $password, $baseUrl = null, $version = null) {
    if (isset($baseUrl)) {
      $this->setOption('base_url', $baseUrl);
    }
    if (isset($version)) {
      $this->setOption('version', $version);
    }
  }

  /**
   * Returns a named option.
   *
   * @param string $name
   *
   * @return mixed
   *
   * @throws InvalidArgumentException
   */
  public function getOption($name) {
    if (!array_key_exists($name, $this->options)) {
      throw new \Exception(sprintf('Undefined option: "%s"', $name));
    }
    return $this->options[$name];
  }

  /**
   * Sets a named option.
   *
   * @param string $name
   * @param mixed  $value
   *
   * @throws InvalidArgumentException
   */
  public function setOption($name, $value) {
    if (!array_key_exists($name, $this->options)) {
      throw new \Exception(sprintf('Undefined option: "%s"', $name));
    }
    $this->options[$name] = $value;
  }

}
