<?php

namespace Drupal\views_raw_sql\Plugin\views\sort;

use Drupal\Core\Session\AccountProxyInterface;
use Drupal\views\Plugin\views\sort\SortPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Default implementation of a raw SQL sort plugin.
 *
 * @ingroup views_sort_handlers
 *
 * @ViewsSort("sort_views_raw_sql")
 */
class RawSQLSort extends SortPluginBase {

  /**
   * Provides current_user service.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $account;

  /**
   * Class constructor.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, AccountProxyInterface $account) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->account = $account;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    // Instantiates this form class.
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_user')
    );
  }

}
