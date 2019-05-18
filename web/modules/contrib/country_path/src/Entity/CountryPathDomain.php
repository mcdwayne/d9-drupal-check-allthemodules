<?php

namespace Drupal\country_path\Entity;

use Drupal\Core\Config\ConfigValueException;
use Drupal\domain\Entity\Domain as Domain;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Override Domain entity preSave to remove checking for existence hostnames.
 */
class CountryPathDomain extends Domain {

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    ConfigEntityBase::preSave($storage);
    // Sets the default domain properly.
    /** @var \Drupal\domain\DomainLoaderInterface $loader */
    $loader = \Drupal::service('domain.loader');
    /** @var self $default */
    $default = $loader->loadDefaultDomain();
    if (!$default) {
      $this->is_default = 1;
    }
    elseif ($this->is_default && $default->getDomainId() != $this->getDomainId()) {
      // Swap the current default.
      $default->is_default = 0;
      $default->save();
    }
    // Ensures we have a proper domain_id.
    if ($this->isNew()) {
      $this->createDomainId();
    }

    // Prevent duplicate hostname.
    $hostname = $this->getHostname();
    $domainId = $this->getDomainId();
    // Do not use domain loader because it may change hostname.
    $existing = $storage->loadByProperties(
      [
        'hostname'  => $hostname,
        'domain_id' => $domainId,
      ]
    );
    $existing = reset($existing);
    if ($existing && $domainId != $existing->getDomainId()) {
      throw new ConfigValueException("The hostname ($hostname) is already registered.");
    }

  }

}
