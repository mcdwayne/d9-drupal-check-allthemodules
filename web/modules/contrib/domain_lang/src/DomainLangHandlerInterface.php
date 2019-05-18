<?php

namespace Drupal\domain_lang;

use Drupal\domain\DomainInterface;

/**
 * Interface for Domain lang service.
 */
interface DomainLangHandlerInterface {

  /**
   * Get configuration name for this hostname.
   *
   * @param string $config_name
   *   The name of the config object.
   * @param \Drupal\domain\DomainInterface $domain
   *   Domain object.
   *
   * @return string
   *   The domain-specific config name.
   */
  public function getDomainConfigName($config_name, DomainInterface $domain = NULL);

  /**
   * Return domain object from URL.
   *
   * @return \Drupal\domain\DomainInterface
   *   Loaded domain object.
   *
   * @throws \Drupal\domain_lang\Exception\DomainLangDomainNotFoundException
   *   In case if domain not found.
   */
  public function getDomainFromUrl();

  /**
   * Updates the configuration based on the given language types.
   *
   * Stores the list of the language types along with information about their
   * configurable state. Stores the default settings if the language type is
   * not configurable.
   *
   * @param string[] $types
   *   An array of configurable language types.
   */
  public function updateConfiguration(array $types);

  /**
   * Stores language types configuration.
   *
   * @param array $values
   *   An indexed array with the following keys_
   *   - configurable: an array of configurable language type names.
   *   - all: an array of all the defined language type names.
   */
  public function saveLanguageTypesConfiguration(array $values);

  /**
   * Saves a list of language negotiation methods for a language type.
   *
   * @param string $type
   *   The language type.
   * @param int[] $enabled_methods
   *   An array of language negotiation method weights keyed by method ID.
   */
  public function saveConfiguration($type, array $enabled_methods);

  /**
   * Returns the language negotiation methods enabled for a language type.
   *
   * @param string $type
   *   (optional) The language type. If no type is specified all the method
   *   definitions are returned.
   *
   * @return array[]
   *   An array of language negotiation method definitions keyed by method id.
   */
  public function getNegotiationMethods($type = NULL);

}
