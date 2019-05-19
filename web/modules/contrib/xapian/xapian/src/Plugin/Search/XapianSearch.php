<?php

namespace Drupal\xapian\Plugin\Search;

use Drupal\Core\Access\AccessibleInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Database\Connection;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\Entity\NodeType;
use Drupal\search\Plugin\ConfigurableSearchPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

const XAPIAN_LOCAL = 0;
const XAPIAN_REMOTE = 1;
const XAPIAN_MATCHES_BEST_ESTIMATE = 0;
const XAPIAN_MATCHES_LOWER_BOUND = 1;
const XAPIAN_MATCHES_UPPER_BOUND = 2;

/**
 * Handles searching for node entities using the Xapian module index.
 *
 * @SearchPlugin(
 *   id = "xapian_search",
 *   title = @Translation("Content (xapian)")
 * )
 */
class XapianSearch extends ConfigurableSearchPluginBase implements AccessibleInterface {

  /**
   * A database connection object.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('database'),
      $container->get('entity.manager'),
      $container->get('module_handler'),
      $container->get('config.factory')->get('search.settings'),
      $container->get('language_manager'),
      $container->get('renderer'),
      $container->get('messenger'),
      $container->get('current_user')
    );
  }

  /**
   * Constructs a \Drupal\xapian\Plugin\Search\XapianSearch object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Database\Connection $database
   *   A database connection object.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, Connection $database) {
    $this->database = $database;
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->addCacheTags(['node_list']);
  }

  /**
   * {@inheritdoc}
   */
  public function access($operation = 'view', AccountInterface $account = NULL, $return_as_object = FALSE) {
    $result = AccessResult::allowedIfHasPermission($account, 'access content');
    return $return_as_object ? $result : $result->isAllowed();
  }

  public function execute() {
    // TODO: Implement execute() method.
  }


  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return array
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    // Create an all-encompassing Xapian detail
    $form['xapian'] = [
      '#type' => 'details',
      '#title' => t('Xapian search settings'),
      '#open' => TRUE,
    ];

    // Create a database deatils
    $form['xapian']['database'] = [
      '#type' => 'details',
      '#title' => t('Database'),
      '#open' => FALSE,
    ];

    // Database type
    $database_type = XAPIAN_LOCAL; // @TODO: Get the database type.
    $form['xapian']['database']['xapian_database_type'] = [
      '#type' => 'radios',
      '#title' => t('Type'),
      '#default_value' => $database_type ? $database_type : XAPIAN_LOCAL,
      '#options' => [
        XAPIAN_LOCAL => t('Local'),
        XAPIAN_REMOTE => t('Remote'),
      ],
    ];

    // local database settings
    $form['xapian']['database']['local_database'] = [
      '#type' => 'details',
      '#title' => t('Local database options'),
      '#open' => ($database_type != XAPIAN_LOCAL),
    ];
    // @TODO: Get default value for xapian_database_path variable.
    $form['xapian']['database']['local_database']['xapian_database_path'] = [
      '#type' => 'textfield',
      '#title' => t('Path to database'),
      '#default_value' => file_default_scheme(),
      '#required' => ($database_type == XAPIAN_LOCAL),
      '#description' => t('Directory where your local Xapian database will be created.  Specify a directory writable by your web server process.'),
    ];

    // Remote database settings
    $form['xapian']['database']['remote_database'] = [
      '#type' => 'details',
      '#title' => t('Remote database options'),
      '#open' => ($database_type != XAPIAN_REMOTE),
    ];
    // @TODO: Get default value for xapian_database_hostname variable.
    $form['xapian']['database']['remote_database']['xapian_database_hostname'] = [
      '#type' => 'textfield',
      '#title' => t('Database server'),
      '#default_value' => '',
      '#required' => ($database_type == XAPIAN_REMOTE),
      '#description' => t('IP address or host name of remote server running xapian-tcpsrv.'),
    ];
    // @TODO: Get default value for xapian_database_port variable and custom field validate.
    $form['xapian']['database']['remote_database']['xapian_database_port'] = [
      '#type' => 'textfield',
      '#title' => t('Database port'),
      '#default_value' => '6431',
      '#required' => ($database_type == XAPIAN_REMOTE),
      //'#validate' => ['_xapian_validate_port' => ['xapian_database_port']],
      '#description' => t('Remote port that xapian-tcpsrv is listening on.'),
    ];

    // Optional write-only database (see http://drupal.org/node/282855).
    // @TODO: Get validate if xapian_write_database_hostname and xapian_write_database_port.
    // To set open status.
    $form['xapian']['database']['writeonly'] = [
      '#type' => 'details',
      '#title' => t('Optional write-only database settings'),
      '#open' => FALSE,
      '#description' => t('Leave these optional settings blank to use the above settings for both read and write database access.  If you would like to send write queries to a different database than read queries, configure the remote write-only database settings below.  Using a separate remote write-only server allows you to efficiently scale your search solution across multiple web servers, and avoids potential issues with lock contention.'),
    ];
    // @TODO: Get default value for xapian_write_database_hostname variable.
    $form['xapian']['database']['writeonly']['xapian_write_database_hostname'] = [
      '#type' => 'textfield',
      '#title' => t('Write-only database server'),
      //'#default_value' => variable_get('xapian_write_database_hostname', ''),
      '#description' => t('IP address or host name of remote server running %writable.',
        ['%writable' => t('xapian-tcpsrv --writable')]),
    ];
    // @TODO: Get default value for xapian_write_database_port variable and custom field validate.
    $form['xapian']['database']['writeonly']['xapian_write_database_port'] = [
      '#type' => 'textfield',
      '#title' => t('Write-only database port'),
      '#default_value' => '',
      //'#validate' => array('_xapian_validate_port' => array('xapian_write_database_port')),
      '#description' => t('Remote port that %writable is listening on.', ['%writable' => t('xapian-tcpsrv --writable')]),
    ];

    // Indexing settings.
    $form['xapian']['performance'] = [
      '#type' => 'details',
      '#open' => FALSE,
      '#title' => t('Performance')
    ];
    // @TODO: Get default value for xapian_index_immediately variable.
    $form['xapian']['performance']['xapian_index_immediately'] = [
      '#type' => 'checkbox',
      '#title' => t('Index immediately'),
      //'#default_value' => variable_get('xapian_index_immediately', TRUE),
      '#description' => t('Enable this option to index content immediately as it is created and updated.  Disable this option to delay indexing until cron runs.  Your should disable this option on larger websites.'),
    ];


    // Display settings
    $form['xapian']['display'] = [
      '#type'  => 'details',
      '#open' => FALSE,
      '#title' => t('Display'),
    ];
    // @TODO: Get default value for xapian_node_count_type variable.
    $form['xapian']['display']['xapian_node_count_type'] = [
      '#type' => 'radios',
      '#title' => t('Result count'),
      '#description' => t('This setting determines the value that xapian returns for the result count returned from queries (used for number of pages in pagers, etc.)'),
      // '#default_value' => variable_get('xapian_node_count_type', 0),
      '#options' => [
        XAPIAN_MATCHES_BEST_ESTIMATE => t('Best estimate'),
        XAPIAN_MATCHES_LOWER_BOUND => t('Lower bound'),
        XAPIAN_MATCHES_UPPER_BOUND => t('Upper bound'),
      ],
    ];

    // Logging options.
    $form['xapian']['diagnostic'] = [
      '#type' => 'details',
      '#open' => FALSE,
      '#title' => t('Logging')
    ];
    // @TODO: Get default value for xapian_log_queries variable.
    $form['xapian']['diagnostic']['xapian_log_queries'] = [
      '#type' => 'checkbox',
      '#title' => t('Log searches'),
      '#description' => t('Log search queries and time taken for search to the watchdog log.'),
      //'#default_value' => variable_get('xapian_log_queries', FALSE),
    ];

    // Node Type Settings
    $form['xapian']['node_types'] = array(
      '#type' => 'details',
      '#open' => FALSE,
      '#title' => t('Excluded node types'),
    );
    $form['xapian']['node_types']['info'] = array(
      '#markup' => t("<p>Select the node types to <strong>EXCLUDE</strong> from Xapian's indexing</p>"),
    );
    $nodeTypes =  NodeType::loadMultiple();
    $nodeTypeOptions = [];
    foreach ($nodeTypes as $type => $nodeType) {
      $nodeTypeOptions[$type] = $nodeType->label();
    }
    // @TODO: Get default value for xapian_excluded_nodes variable.
    $form['xapian']['node_types']['xapian_excluded_nodes'] = array(
      '#type' => 'select',
      '#title' => t('Exclude indexing on'),
      '#options' => $nodeTypeOptions,
      //'#default_value' => variable_get('xapian_excluded_nodes', array()),
      '#multiple' => TRUE,
    );

    // Algorithms
    $form['xapian']['algorithms'] = [
      '#type' => 'details',
      '#open' => FALSE,
      '#title' => t('Algorithms'),
    ];//

    $form['xapian']['algorithms']['xapian_stem_language'] = [
      '#type' => 'language_select',
      '#languages' => LanguageInterface::STATE_CONFIGURABLE,
      //'#default_value' => $vocabulary->language()->getId(),
      '#title' => t('Stemming language'),
      '#description' => t('Select the language that Xapian should use when deriving the stem of each word when building an index.'),
    ];
    return $form;
  }

  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    // TODO: Implement submitConfigurationForm() method.
  }
}
