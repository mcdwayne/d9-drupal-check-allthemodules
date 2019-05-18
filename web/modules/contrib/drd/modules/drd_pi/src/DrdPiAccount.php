<?php

namespace Drupal\drd_pi;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use mikehaertl\shellcommand\Command as ShellCommand;

/**
 * Provides an interface for defining Account entities.
 */
abstract class DrdPiAccount extends ConfigEntityBase implements DrdPiAccountInterface {

  /**
   * Plugin ID of the DrdPiAccount.
   *
   * @var string
   */
  protected $id;

  /**
   * Label of the DrdPiAccount.
   *
   * @var string
   */
  protected $label;

  /**
   * Loggin service for output.
   *
   * @var \Drupal\drd\Logging
   */
  protected $logging;

  /**
   * Output of the last run shell command.
   *
   * @var string
   */
  protected $lastShellOutput;

  /**
   * List of DrdPiHosts.
   *
   * @var DrdPiHost[]
   */
  protected $hosts;

  /**
   * List of DrdPiCores.
   *
   * @var DrdPiCore[]
   */
  protected $cores;

  /**
   * List of DrdPiDomains.
   *
   * @var DrdPiDomain[]
   */
  protected $domains;

  /**
   * Configuration of the acocunt plugin.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $values, $entity_type) {
    parent::__construct($values, $entity_type);
    $this->logging = \Drupal::service('drd.logging');
    $this->config = \Drupal::config($this->getConfigName());
  }

  /**
   * Decrypt and return the value of $key.
   *
   * @param string $key
   *   The name of the field for which to retrieve the value.
   *
   * @return string
   *   The decrypted value.
   */
  protected function getDecrypted($key) {
    $value = $this->get($key);
    \Drupal::service('drd.encrypt')->decrypt($value);
    return $value;
  }

  /**
   * Encrypt and set the value of $key.
   *
   * @param string $key
   *   The name of the field for which to set the value.
   * @param mixed $value
   *   The value of the field.
   *
   * @return $this
   */
  protected function setEncrypted($key, $value) {
    \Drupal::service('drd.encrypt')->encrypt($value);
    $this->set($key, $value);
    return $this;
  }

  /**
   * Add new entities and enable/disable existing ones to match imventory.
   *
   * @param DrdPiEntityInterface[] $platform
   *   List of DrdPiEntities as they exist on the platform, the inventory.
   * @param string $type
   *   Type is either core, host or domain.
   * @param DrdPiEntityInterface $parent
   *   The optional parent entity to which the list of entities are attached.
   */
  protected function syncEntities(array &$platform, $type, DrdPiEntityInterface $parent = NULL) {

    // Get all internal entities.
    $properties = [
      'pi_type' => $this->entityTypeId,
      'pi_account' => $this->id(),
    ];
    switch ($type) {
      case 'core':
        $properties['pi_id_host'] = $parent->id();
        break;

      case 'domain':
        $properties['pi_id_host'] = $parent->host()->id();
        $properties['pi_id_core'] = $parent->id();
        break;

    }
    $storage = \Drupal::entityTypeManager()->getStorage('drd_' . $type);
    /** @var \Drupal\drd\Entity\BaseInterface[] $internal */
    $internal = $storage->loadByProperties($properties);

    $ids_with_pi = [];

    // Work through all platform entities.
    foreach ($platform as &$entity) {

      $this->logging->debug('Checking @label', ['@label' => $entity->label()]);
      // Check if we already know that platform entity.
      foreach ($internal as $drd_entity) {
        if (!in_array($drd_entity->id(), $ids_with_pi) && drd_pi_get_entity_value($drd_entity, $type) == $entity->id()) {
          $this->logging->debug('- already available');
          $entity->setDrdEntity($drd_entity);
          $ids_with_pi[] = $drd_entity->id();
          break;
        }
      }

      // Create new DRD entity if don't have it yet.
      if (!$entity->hasDrdEntity()) {
        $this->logging->debug('- create');
        $entity->create();
      }

      $entity->update();
    }

    // Enable/disable DRD entities that no longer exist on the platform.
    foreach ($internal as $drd_entity) {
      $status = in_array($drd_entity->id(), $ids_with_pi);
      if ($drd_entity->isPublished() !== $status) {
        $this->logging->debug('@action @type @label', [
          '@action' => ($status ? 'Re-enable' : 'Disable'),
          '@type' => $type,
          '@label' => $drd_entity->label(),
        ]);
        $drd_entity
          ->setPublished($status)
          ->save();
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function sync() {
    $this->logging->log('info', 'Receiving hosts');
    $hosts = $this->getPlatformHosts();
    $this->logging->log('info', 'Syncing hosts');
    $this->syncEntities($hosts, 'host');

    foreach ($hosts as $host) {
      $this->logging->log('info', 'Receiving cores for host @label', ['@label' => $host->label()]);
      $cores = $this->getPlatformCores($host);
      $this->logging->log('info', 'Syncing cores');
      $this->syncEntities($cores, 'core', $host);

      foreach ($cores as $core) {
        $this->logging->log('info', 'Receiving domains for core @label', ['@label' => $core->label()]);
        $domains = $this->getPlatformDomains($core);
        $this->logging->log('info', 'Syncing domains');
        $this->syncEntities($domains, 'domain', $core);
      }
    }

  }

  /**
   * {@inheritdoc}
   */
  public function getPlatformDomains(DrdPiCore $core) {
    $this->domains = [];

    if (isset($this->cores[$core->id()])) {
      $this->domains = $this->cores[$core->id()]->getDomains();
    }
    return $this->domains;
  }

  /**
   * Execute a shell command, capture the console output and return exit code.
   *
   * @param string $cmd
   *   The command to be executed.
   *
   * @return int
   *   Exit code of the executed command.
   */
  public function shell($cmd) {
    $this->lastShellOutput = '';
    $command = new ShellCommand($cmd);
    $command->execute();
    $this->lastShellOutput = $command->getOutput();
    return $command->getExitCode();
  }

}
