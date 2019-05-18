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
use libphonenumber\PhoneNumberUtil;
use libphonenumber\PhoneNumberFormat;

/**
 * Formats a phone number from a string (...).
 *
 * @todo documentation
 *
 * @MigrateProcessPlugin(
 *   id = "format_phone"
 * )
 */
class FormatPhone extends ProcessPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The country repository.
   *
   * @var \CommerceGuys\Addressing\Country\CountryRepositoryInterface
   */
  protected $countryRepository;

  /**
   * Constructs a new FormatPhone object.
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
      $locale = NULL;
      // Set value by default.
      $newValue = $value;
      $phoneNumberUtil = PhoneNumberUtil::getInstance();
      // Get region from configuration.
      if (isset($this->configuration['region'])) {
        $region = $this->configuration['region'];
        // @todo validate region
        $phoneNumberObject = $phoneNumberUtil->parse($value, $region);
        $newValue = $phoneNumberUtil->format($phoneNumberObject, PhoneNumberFormat::E164);
      }
      return $newValue;
    }
    else {
      throw new MigrateException(sprintf('%s is not a string', var_export($value, TRUE)));
    }
  }

}
