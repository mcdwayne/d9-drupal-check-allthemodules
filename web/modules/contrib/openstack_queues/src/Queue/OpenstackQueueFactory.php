<?php

namespace Drupal\openstack_queues\Queue;

use Drupal\Core\Config\ConfigFactoryInterface;
use OpenCloud\Rackspace;
use Drupal\Core\Config\ImmutableConfig;

class OpenstackQueueFactory {

  /**
   * @var Rackspace $connection
   */
  private $connection;
  /**
   * @var ConfigFactoryInterface $configFactory
   */
  private $configFactory;
  /**
   * @var ImmutableConfig $config
   */
  private $config;

  public function __construct(ConfigFactoryInterface $configFactory) {
    $this->configFactory = $configFactory;
  }

  /**
   * @param string $name
   *   The name of the collection holding key and value pairs.
   *
   * @return OpenstackQueue
   */
  public function get($name) {
    $config = $this->configFactory->get('openstack_queues.settings.' . $name);
    $this->config = ($config->get('client_id') !== NULL) ? $config : $this->configFactory->get('openstack_queues.settings.default');
    $this->connection = new Rackspace($this->config->get('auth_url'), $this->config->get('credentials'));
    return new OpenstackQueue($name, $this->connection, $this->config);
  }

}