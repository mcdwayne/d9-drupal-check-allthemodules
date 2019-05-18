<?php

namespace Drupal\entity_domain_access\Plugin\views\filter;

use Drupal\domain_access\Plugin\views\filter\DomainAccessCurrentAllFilter;
use Drupal\entity_domain_access\EntityDomainAccessMapper;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\views\Plugin\ViewsHandlerManager;
use Drupal\domain\DomainNegotiatorInterface;

/**
 * Handles matching of current domain.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("entity_domain_access_current_all_filter")
 */
class EntityDomainAccessCurrentAllFilter extends DomainAccessCurrentAllFilter {


  /**
   * Views Handler Plugin Manager.
   *
   * @var \Drupal\views\Plugin\ViewsHandlerManager
   */
  protected $joinHandler;

  /**
   * Domain negotiation.
   *
   * @var \Drupal\domain\DomainNegotiatorInterface
   */
  protected $domainNegotiator;

  /**
   * Constructs a Handler object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\views\Plugin\ViewsHandlerManager $join_handler
   *   Views Handler Plugin Manager.
   * @param \Drupal\domain\DomainNegotiatorInterface $domain_negotiator
   *   The domain negotiator.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ViewsHandlerManager $join_handler, DomainNegotiatorInterface $domain_negotiator) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->joinHandler = $join_handler;
    $this->domainNegotiator = $domain_negotiator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.views.join'),
      $container->get('domain.negotiator')
    );
  }

  /**
   * Add conditions to filter query results by current domain.
   */
  public function query() {
    $query = &$this->query;

    $this->ensureMyTable();

    // Table prefix contain entity type ID usually..
    $table_prefix = str_replace('__' . EntityDomainAccessMapper::FIELD_NAME, '', $this->table);

    // Get table of the all affiliates field.
    $all_field = EntityDomainAccessMapper::FIELD_ALL_NAME;
    $all_table = $this->query->ensureTable($table_prefix . '__' . $all_field);

    // Get  table of the all affiliates field from query if is empty.
    if (!$all_table) {
      $base = $this->query->view->storage->get('base_table');

      $left_table = $left_field = NULL;

      $table_queue = $this->view->query->getTableQueue();
      foreach ($table_queue as $alias => $table) {
        if ($table['table'] == $table_prefix . '_field_data') {
          $left_table = $alias;
          $left_field = $table['join']->field;
          break;
        }
      }

      if ($left_table && $left_field) {
        $definition = [
          'table' => $table_prefix . '__' . $all_field,
          'field' => 'entity_id',
          'left_table' => $left_table,
          'left_field' => $left_field,
        ];

        $join = $this->joinHandler->createInstance('standard', $definition);
        $this->query->addRelationship($table_prefix . '__' . EntityDomainAccessMapper::FIELD_ALL_NAME, $join, $base);

        $all_table = $this->query->ensureTable($table_prefix . '__' . $all_field);
      }
    }

    $current_domain = $this->domainNegotiator->getActiveId();
    $query->setWhereGroup('OR', EntityDomainAccessMapper::FIELD_NAME);
    $query->setWhereGroup('OR', EntityDomainAccessMapper::FIELD_ALL_NAME);
    if (empty($this->value)) {
      // 'No' conditions.
      $query->addWhere(EntityDomainAccessMapper::FIELD_NAME, "{$this->tableAlias}.{$this->realField}", $current_domain, '<>');
      $query->addWhere(EntityDomainAccessMapper::FIELD_NAME, "{$this->tableAlias}.{$this->realField}", NULL, 'IS NULL');

      if ($all_table) {
        $query->addWhere(EntityDomainAccessMapper::FIELD_ALL_NAME, "{$all_table}.{$all_field}_value", 0, '=');
        $query->addWhere(EntityDomainAccessMapper::FIELD_ALL_NAME, "{$all_table}.{$all_field}_value", NULL, 'IS NULL');
      }
    }
    elseif ($all_table) {
      // 'Yes' conditions.
      $query->addWhere(EntityDomainAccessMapper::FIELD_ALL_NAME, "{$this->tableAlias}.{$this->realField}", $current_domain, '=');
      $query->addWhere(EntityDomainAccessMapper::FIELD_ALL_NAME, "{$all_table}.{$all_field}_value", 1, '=');
    }

    // This filter causes duplicates.
    $query->options['distinct'] = TRUE;
  }

}
