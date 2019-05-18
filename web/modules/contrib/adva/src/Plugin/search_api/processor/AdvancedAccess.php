<?php

namespace Drupal\adva\Plugin\search_api\processor;

use Drupal\adva\Plugin\adva\Manager\AccessConsumerManagerInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api\LoggerTrait;
use Drupal\search_api\Plugin\search_api\datasource\ContentEntity;
use Drupal\search_api\Processor\ProcessorPluginBase;
use Drupal\search_api\Processor\ProcessorProperty;
use Drupal\search_api\Query\QueryInterface;
use Drupal\search_api\SearchApiException;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Adds advanced access checks to entities.
 *
 * @SearchApiProcessor(
 *   id = "advanced_access",
 *   label = @Translation("Advanced access control"),
 *   description = @Translation("Adds advanced access checks to entities."),
 *   stages = {
 *     "add_properties" = 0,
 *     "pre_index_save" = -10,
 *     "preprocess_query" = -30,
 *   },
 * )
 */
class AdvancedAccess extends ProcessorPluginBase {

  use LoggerTrait;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection|null
   */
  protected $database;

  /**
   * The current_user service used by this plugin.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface|null
   */
  protected $currentUser;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    /** @var static $processor */
    $processor = parent::create($container, $configuration, $plugin_id, $plugin_definition);

    $processor->setLogger($container->get('logger.channel.search_api'));
    $processor->setDatabase($container->get('database'));
    $processor->setCurrentUser($container->get('current_user'));
    $processor->setAccessConsumerManager($container->get('plugin.manager.adva.consumer'));

    return $processor;
  }

  /**
   * Retrieves the database connection.
   *
   * @return \Drupal\Core\Database\Connection
   *   The database connection.
   */
  public function getDatabase() {
    return $this->database ?: \Drupal::database();
  }

  /**
   * Sets the database connection.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The new database connection.
   *
   * @return $this
   */
  public function setDatabase(Connection $database) {
    $this->database = $database;
    return $this;
  }

  /**
   * Retrieves the current user.
   *
   * @return \Drupal\Core\Session\AccountProxyInterface
   *   The current user.
   */
  public function getCurrentUser() {
    return $this->currentUser ?: \Drupal::currentUser();
  }

  /**
   * Sets the current user.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user.
   *
   * @return $this
   */
  public function setCurrentUser(AccountProxyInterface $current_user) {
    $this->currentUser = $current_user;
    return $this;
  }

  /**
   * Sets the access consumer manager service.
   *
   * @param \Drupal\Core\Session\AccessConsumerManagerInterface $manager
   *   The current service instance.
   */
  public function setAccessConsumerManager(AccessConsumerManagerInterface $manager) {
    $this->consumerManager = $manager;
  }

  /**
   * Retrieves the Access consumer manager service.
   *
   * @return \Drupal\Core\Session\AccessConsumerManagerInterface
   *   The current service instance.
   */
  public function getAccessConsumerManager() {
    return $this->consumerManager;
  }

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions(DatasourceInterface $datasource = NULL) {
    $properties = [];

    if (!$datasource) {
      $definition = [
        'label' => $this->t('Entity access information'),
        'description' => $this->t('Data needed to apply entity access via Advanced Access.'),
        'type' => 'string',
        'processor_id' => $this->getPluginId(),
        'hidden' => TRUE,
        'is_list' => TRUE,
      ];
      $properties['search_api_adva_grants'] = new ProcessorProperty($definition);
    }

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function addFieldValues(ItemInterface $item) {

    $entity = $item->getOriginalObject()->getValue();

    if (!($entity instanceof EntityInterface)) {
      // We don't process items which are not entites.
      return;
    }

    $consumer = $this->getAccessConsumerManager()->getConsumerForEntityType($entity->getEntityType());
    if (!$consumer) {
      // There is no supported adva consumer. Nothing to do for this item.
      return;
    }

    $all_grants = $consumer->getAccessRecords($entity);
    $view_grants = [];
    foreach ($all_grants as $grant) {
      if ($grant["grant_view"] === 1) {
        $view_grants[] = $consumer->getPluginId() . ":" . $grant["realm"] . ":" . $grant["gid"];
      }
    }

    $fields = $item->getFields();
    $fields = $this->getFieldsHelper()
      ->filterForPropertyPath($fields, NULL, 'search_api_adva_grants');
    foreach ($fields as $field) {
      // Append the grant list to each grant field.
      foreach ($view_grants as $grant) {
        $field->addValue($grant);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function preIndexSave() {
    $field = $this->ensureField(NULL, 'search_api_adva_grants', 'string');
    $field->setHidden();
  }

  /**
   * Get a valid user instance for a given query.
   *
   * If the query specifies a user id use that id, other wise, use the currently
   * logged in user.
   *
   * @param \Drupal\search_api\Query\QueryInterface $query
   *   Search API Query instance.
   *
   * @return \Drupal\Core\Session\AccountInterface|null
   *   Valid account instance if one exists.
   */
  public function validateUser(QueryInterface $query) {
    if (!$query->getOption('search_api_bypass_access')) {
      $account = $query->getOption('search_api_access_account', $this->getCurrentUser());
      if (is_numeric($account)) {
        $account = User::load($account);
      }
      if ($account instanceof AccountInterface) {
        return $account;
      }
      else {
        $account = $query->getOption('search_api_access_account', $this->getCurrentUser());
        if ($account instanceof AccountInterface) {
          $account = $account->id();
        }
        if (!is_scalar($account)) {
          $account = var_export($account, TRUE);
        }
        $this->getLogger()->warning('An illegal user UID was given for node access: @uid.', ['@uid' => $account]);
        // We want to user the current user instance, to prevent invalid access.
        return $this->currentUser();
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function preprocessSearchQuery(QueryInterface $query) {
    $account = $this->validateUser($query);
    // If a invalid user is provided, no filtering will happen.
    if ($account) {
      try {
        $this->addAccessCondition($query, $account);
      }
      catch (SearchApiException $e) {
        $this->logException($e);
      }
    }
  }

  /**
   * Adds access filters to a search query, if applicable.
   *
   * @param \Drupal\search_api\Query\QueryInterface $query
   *   The query to which a node access filter should be added, if applicable.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user for whom the search is executed.
   *
   * @throws \Drupal\search_api\SearchApiException
   *   Thrown if not all necessary fields are indexed on the index.
   */
  protected function addAccessCondition(QueryInterface $query, AccountInterface $account) {
    $field = $this->findField(NULL, 'search_api_adva_grants', 'string');

    if (!$field) {
      return;
    }

    $access_conditions = $query->createConditionGroup('OR', ['advanced_access']);
    $access_conditions->addCondition($field->getFieldIdentifier(), NULL);
    $query->addConditionGroup($access_conditions);

    // Foreach datasource, get and set the relevant advanced_access grant.
    foreach ($query->getIndex()->getDatasources() as $datasource) {
      if ($datasource instanceof ContentEntity) {
        $entityTypeId = $datasource->getEntityTypeId();
        $consumer = $this->getAccessConsumerManager()->getConsumerForEntityTypeId($entityTypeId);
        if ($consumer) {
          $grants = $consumer->getAccessGrants('view', $account);
          foreach ($grants as $realm => $gids) {
            foreach ($gids as $gid) {
              $grant_value = $consumer->getPluginId() . ":" . $realm . ":" . $gid;
              $access_conditions->addCondition($field->getFieldIdentifier(), $grant_value);
            }
          }
        }
      }
    }
  }

}
