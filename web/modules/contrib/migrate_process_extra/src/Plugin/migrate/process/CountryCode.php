<?php

namespace Drupal\migrate_process_extra\Plugin\migrate\process;

use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\MigrateException;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;
use CommerceGuys\Addressing\Country\CountryRepositoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Gets the ISO ISO 3166-1 alpha-2 country code from a string.
 *
 * @todo documentation
 *
 * @MigrateProcessPlugin(
 *   id = "country_code"
 * )
 */
class CountryCode extends ProcessPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The country repository.
   *
   * @var \CommerceGuys\Addressing\Country\CountryRepositoryInterface
   */
  protected $countryRepository;

  /**
   * Constructs a new CountryCode object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\migrate\Plugin\MigrationInterface $migration
   *   The migration entity.
   * @param \CommerceGuys\Addressing\Country\CountryRepositoryInterface $country_repository
   *   The country repository.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration, CountryRepositoryInterface $country_repository) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration);
    $this->countryRepository = $country_repository;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration = NULL) {
    return new static($configuration, $plugin_id, $plugin_definition, $migration, $container->get('address.country_repository'));
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (is_string($value)) {
      // Get optional locale from configuration.
      $locale = NULL;
      if (isset($this->configuration['locale'])) {
        $locale = $this->configuration['locale'];
      }
      $countries = $this->countryRepository->getList($locale);
      // Get key from value and compare from lower case.
      $search_array = array_combine(array_map('strtolower', $countries), $countries);
      $needle = $search_array[strtolower($value)];
      $key = array_search($needle, $countries);
      if ($key) {
        return $key;
      }
      else {
        throw new MigrateException(sprintf('%s is not a valid country name', var_export($value, TRUE)));
      }
    }
    else {
      throw new MigrateException(sprintf('%s is not a string', var_export($value, TRUE)));
    }
  }

}
