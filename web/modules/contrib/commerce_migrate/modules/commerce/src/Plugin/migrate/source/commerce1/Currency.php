<?php

namespace Drupal\commerce_migrate_commerce\Plugin\migrate\source\commerce1;

use Drupal\commerce_price\CurrencyImporter;
use Drupal\migrate\Row;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate_drupal\Plugin\migrate\source\Variable;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\State\StateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Gets the Commerce 1 currency data.
 *
 * @MigrateSource(
 *   id = "commerce1_currency",
 *   source_module = "commerce_price"
 * )
 */
class Currency extends Variable {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The variable names to fetch.
   *
   * @var array
   */
  protected $variables;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration, StateInterface $state, EntityManagerInterface $entity_manager, LanguageManagerInterface $language_manager, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration, $state, $entity_manager);
    $this->languageManager = $language_manager;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration = NULL) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $migration,
      $container->get('state'),
      $container->get('entity.manager'),
      $container->get('language_manager'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return parent::fields() +
      [
        'currency_name' => $this->t('Currency name'),
        'numeric_code' => $this->t('Currency code numeric code'),
      ];
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    // Get the currency name and the country numeric code by using the
    // destination's currency importer. These values are not available from
    // the ubercart source.
    // @todo find a better to get the currency name and the country numeric code
    // without peeking into the destination.
    // Use Commerce 1 default if commerce_default_currency was unset.
    if (empty($row->getSourceProperty('commerce_default_currency'))) {
      $row->setSourceProperty('commerce_default_currency', 'USD');
    }

    $currencyImporter = new CurrencyImporter($this->entityTypeManager, $this->languageManager);
    $currency = $currencyImporter->import(($row->getSourceProperty('commerce_default_currency')));
    $name = $currency->getName();
    $numeric_code = $currency->getNumericCode();
    $row->setSourceProperty('currency_name', $name);
    $row->setSourceProperty('numeric_code', $numeric_code);
    return parent::prepareRow($row);
  }

}
