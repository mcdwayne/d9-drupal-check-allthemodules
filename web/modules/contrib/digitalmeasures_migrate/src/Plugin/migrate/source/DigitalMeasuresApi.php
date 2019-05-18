<?php

namespace Drupal\digitalmeasures_migrate\Plugin\migrate\source;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\digitalmeasures_migrate\DigitalMeasuresApiServiceInterface;
use Drupal\migrate_plus\Plugin\migrate\source\Url as Source_Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\migrate\Plugin\MigrationInterface;
/**
 * Provides a means to query the Digital Measures API in a migration
 *
 * Digital Measures (DM) is a REST based API with the following URL format:
 *
 * @code
 * <apiUrl>/v4/<resource>/<schema_key>/[index_key][:[entry_key]]
 * @endcode
 *
 * If successful, the result of the query is provided as an XML document.
 *
 * You can use this plugin to specify the object to fetch. Note the index_key
 * and entity_key are optional:
 *
 * @code
 * source:
 *   plugin: digitalmeasures_api
 *   resource: User
 *   schema_key: MY_SCHEMA_KEY
 *   index_key: COLLEGE
 *   entry_key: Example+College+of+dubious+things
 * @endcode
 *
 * Credentials are provided by the module configuration. If you need to force
 * a particular migration to use the testing API endpoint, you can use the beta
 * key:
 *
 * @code
 * source:
 *   plugin: digitalmeasures_api
 *   resource: User
 *   schema_key: MY_SCHEMA_KEY
 *   beta: 'yes'
 *   index_key: COLLEGE
 *   entry_key: Example+College+of+dubious+things
 *   item_selector: /Users/User
 * @endcode
 *
 * The contents of schemas depend on how you have DM configured, so this
 * plugin also requires you to specify how to uniquely identify each "row" in
 * the resulting XML document using an XPath. This Xpath can be specified using
 * the item_selector key:
 *
 * @code
 * source:
 *   plugin: digitalmeasures_api
 *   resource: User
 *   schema_key: MY_SCHEMA_KEY
 *   beta: 'yes'
 *   index_key: COLLEGE
 *   entry_key: Example+College+of+dubious+things
 *   item_selector: /Users/User
 * @endcode
 *
 * Furthermore, you need to specify what fields from each "row" in the XML
 * document to retrieve and make available to the migration:
 *
 * @code
 * fields:
 *  -
 *   name: username         # The field name in the migration.
 *   label: Username        # Used for display in: admin > structure > migrate
 *   selector: '@entryKey'  # XPath within the "row" to the row's unique ID.
 * @endcode
 *
 * And the data type of the ID field:
 *
 * @code
 * ids:
 *   username:
 *     type: string
 * @endcode
 *
 * All together, your source section will look like this:
 *
 * @code
 * source:
 *   plugin: digitalmeasures_api
 *   resource: User
 *   schema_key: MY_SCHEMA_KEY
 *   beta: 'yes'
 *   index_key: COLLEGE
 *   entry_key: Example+College+of+dubious+things
 *   item_selector: /Users/User
 *   fields:
 *    -
 *     name: username
 *     label: Username
 *     selector: '@entryKey'
 *   ids:
 *     username:
 *       type: string
 * @endcode
 *
 * @MigrateSource(
 *   id = "digitalmeasures_api",
 *   source_module = "digitalmeasures_migrate"
 * )
 */
class DigitalMeasuresApi extends Source_Url implements ContainerFactoryPluginInterface {

  /**
   * The Digital Measures API service.
   *
   * @var \Drupal\digitalmeasures_migrate\DigitalMeasuresApiServiceInterface
   */
  protected $digitalMeasuresApi;

  /**
   * DigitalMeasuresApi constructor.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition,
                              MigrationInterface $migration,
                              DigitalMeasuresApiServiceInterface $digitalMeasuresApi) {

    $this->digitalMeasuresApi = $digitalMeasuresApi;

    // Get the API url from the service.
    $configuration['urls'] = [
      $this->digitalMeasuresApi->getApiURL($configuration),
    ];

    // Set the default data fetcher and parser.
    $configuration['data_fetcher_plugin'] = 'http';
    $configuration['data_parser_plugin'] = 'xml';

    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container,
                                array $configuration,
                                $plugin_id,
                                $plugin_definition,
                                MigrationInterface $migration = NULL) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $migration,
      $container->get('digitalmeasures.api')
    );
  }
}
