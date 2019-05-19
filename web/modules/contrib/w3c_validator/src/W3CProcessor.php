<?php

namespace Drupal\w3c_validator;

use Drupal\Core\Access\AccessManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AnonymousUserSession;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\w3c_validator\Validator\W3cValidatorApi;
use GuzzleHttp\Psr7\Request;
use HtmlValidator\Validator;
use Psr\Log\LoggerInterface;

/**
 * Processor for page validation.
 */
class W3CProcessor {
  use StringTranslationTrait;
  use DependencySerializationTrait;

  /**
   * A W3cTokenManager instance
   *
   * @var \Drupal\w3c_validator\W3CTokenManager
   */
  protected $w3cTokenManager;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The configuration factory.
   * @var  \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The access manager.
   *
   * @param \Drupal\Core\Access\AccessManagerInterface
   */
  protected $accessManager;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The module configurations.
   *
   * @var array
   */
  protected $moduleSettings;

  /**
   * Constructs a W3CSubscriber object.
   *
   * @param \Drupal\w3c_validator\W3CTokenManager $w3c_token_manager
   *   The form builder service.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param \Drupal\Core\Access\AccessManagerInterface $access_manager
   *   The access manager interface.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   */
  public function __construct(W3CTokenManager $w3c_token_manager, Connection $connection, ConfigFactoryInterface $config_factory, AccessManagerInterface $access_manager, AccountInterface $current_user, LoggerInterface $logger) {
    $this->w3cTokenManager = $w3c_token_manager;
    $this->connection = $connection;
    $this->configFactory = $config_factory;
    $this->accessManager = $access_manager;
    $this->currentUser = $current_user;
    $this->logger = $logger;
  }

  /**
   * Find all pages URL to validate in the site.
   *
   * Currently, this method returns :
   * 	- frontpage
   *  - nodes
   * @todo: return other pages.
   *
   * @return array
   *   List of pages to validate.
   */
  public function findAllPages() {
    $all_site_pages = [];

    // Add frontpage to list.
    $site_frontpage = $this->configFactory->get('system.site')->get('page.front');
    $all_site_pages[$site_frontpage] = ['route' => '<front>', 'url' => $site_frontpage, 'title' => $this->t('Frontpage')];

    // Add all nodes.
    $query = $this->connection->select('node_field_data', 'n');
    $query->fields('n', ['nid', 'title']);
    $query->addExpression("CONCAT('entity.node.canonical', '')", 'route');
    $query->addExpression("CONCAT('node/', n.nid)", 'url');
    $nodes = $query->execute()->fetchAllAssoc('url', \PDO::FETCH_ASSOC);
    $all_site_pages = array_merge($all_site_pages, $nodes);

    // All route names.
    if ($this->moduleSettings()->get('admin_pages')) {
      $query = $this->connection->select('router', 'r');
      $query->addField('r', 'pattern_outline', 'url');
      $query->addField('r', 'name', 'title');
      $query->addField('r', 'name', 'route');
      $query->condition('pattern_outline', '%\%%', 'NOT LIKE');
      $query->condition('pattern_outline', '%<%', 'NOT LIKE');
      $paths = $query->execute()->fetchAllAssoc('url', \PDO::FETCH_ASSOC);
      $all_site_pages = array_merge($all_site_pages, $paths);
    }

    return $all_site_pages;
  }

  /**
   * Find all already validated pages and their validation result.
   *
   * @return Array
   *   The result of page validation.
   */
  public function findAllValidatedPages() {
    $db_result = $this->connection->select('w3c_validator', 'w')
    ->fields('w')
    ->execute();
    return $db_result->fetchAllAssoc('uri', \PDO::FETCH_BOTH);
  }

  /**
   * This methods validates all pages that needs validation in the limit of the
   * number given.
   */
  public function validateAllPages(&$context) {
    $token = NULL;
    $user = NULL;
    $query_options = [];

    // Retrieve all pages to validate.
    $pages_to_validate = $this->findAllPages();
    $context['message'] = $this->t('Validating all pages ...');
    $context['sandbox']['max'] = count($pages_to_validate);
    $context['sandbox']['progress'] = 0;
    $context['results']['failures'] = 0;
    $context['results']['current_id'] = 0;
    $context['results']['processed'] = 0;

    // If we are using the "validate as user" option.
    if ($this->moduleSettings()->get('use_token')) {
      // Retrieve token.
      $token = $this->w3cTokenManager->createAccessToken($this->currentUser);
      // Add it to query options.
      $query_options['query'] = ['HTTP_W3C_VALIDATOR_TOKEN' => $token];
      // Get current user.
      $user = $this->currentUser;
    }
    else {
      $user = new AnonymousUserSession();
    }

    // Validate each page one by one.
    foreach ($pages_to_validate as $page) {

      // Set validation message.
      $context['message'] = $this->t('Validation for page %title...', ['%title' => $page['title']]);

      // Check if validation user will be able to validate the page.
      if (!$this->accessManager->checkNamedRoute($page['route'], ['node' => isset($page['nid']) ? $page['nid'] : ''], $user)) {
        $this->logger->debug($page['title'] . ' at ' . $page['url'] . ' is not accessible for validation.');
        // Update operation data.
        $context['results']['failures']++;
      } else {
        // Valdiate the page using the specified $query_options.
        $this->validatePage($page, $query_options);
      }

      // Update operation data.
      $context['sandbox']['progress']++;
      $context['results']['current_id']++;
      $context['results']['processed']++;
    }

    // Rewoke token.
    $this->w3cTokenManager->rewokeAccessToken($token);

    $context['finished'] = 1;
  }

  /**
   * This method is responsible for the validation of a single page.
   *
   * @param array $page
   *   This represents a page to validate.
   * @param array $query_options
   *   This is a custom array of options for the query.
   */
  public function validatePage($page, $query_options = []) {
    // Build page query options.
    $query_options['absolute'] = TRUE;
    // Retrieve absolute URL.
    $uri = Url::fromUri('base:' . $page['url'], $query_options)->toString();

    $validator = new Validator();
    $result = $validator->validateUrl($uri);
    // Save result.
    $this->saveResult($result, $page['url']);
  }

  /**
   * Save a validation result in the database.
   *
   * @param \HtmlValidator\Response $result
   *   The validation result to store in DB.
   * @param string $key
   *   The unique key used for storage in DB. This is basically the page
   *   relative URL. pages to validate.
   */
  public function saveResult($result, $key) {

    // Only if result is defined.
    if (isset($result) && isset($key)) {
      // Merge the result with eventual previous result for the same URI.
      $this->connection->merge("w3c_validator")
        ->key('uri', rtrim($key, "/"))
        ->fields([
          'uri'             => rtrim($key, "/"),
          'error_count'     => count($result->getErrors()),
          'errors'		      => serialize($result->getErrors()),
          'warning_count'   => count($result->getWarnings()),
          'warnings'	      => serialize($result->getWarnings()),
          'info_count'		  => count($result->getMessages()),
          'infos'	          => serialize($result->getMessages()),
          'need_validation' => 0,
          'doctype'			=> '',    // @todo: make it better
          'validity'		=> $result->hasErrors() ? 0 : 1,
          'charset'			=> '',    // @todo: make it better
          ])
          ->execute();
  	}
  	else {
  	  // Merge the result with eventual previous result for the same URI.
      $this->connection->merge("w3c_validator")
        ->key('uri', rtrim($key, "/"))
  	  ->fields([
	      'need_validation'		=> TRUE,
  	  ])
  	  ->execute();
  	}
  }

  /**
   * @return array
   *   Returns the module configuration settings.
   */
  protected function moduleSettings() {
    if (!isset($this->moduleSettings)) {
      $this->moduleSettings = $this->configFactory->get('w3c_validator.settings');
    }
    return $this->moduleSettings;
  }
}
