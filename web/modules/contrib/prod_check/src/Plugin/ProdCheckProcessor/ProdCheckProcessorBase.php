<?php

namespace Drupal\prod_check\Plugin\ProdCheckProcessor;

use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\prod_check\Plugin\ProdCheckCategoryPluginManager;
use Drupal\prod_check\Plugin\ProdCheckInterface;
use Drupal\prod_check\Plugin\ProdCheckPluginManager;
use Drupal\prod_check\Plugin\ProdCheckProcessorInterface;
use Drupal\prod_check\ProdCheck;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for all the prod check processor plugins.
 */
abstract class ProdCheckProcessorBase extends PluginBase implements ContainerFactoryPluginInterface, ProdCheckProcessorInterface {

  /**
   * The prod check plugin manager.
   *
   * @var \Drupal\prod_check\Plugin\ProdCheckPluginManager;
   */
  protected $checkManager;

  /**
   * The prod check category plugin manager.
   *
   * @var \Drupal\prod_check\Plugin\ProdCheckCategoryPluginManager;
   */
  protected $categoryManager;

  /**
   * The query Service
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory;
   */
  protected $queryService;


  /**
   * Constructs a Drupal\Component\Plugin\PluginBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ProdCheckPluginManager $manager, ProdCheckCategoryPluginManager $category_manager, QueryFactory $query_service) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->checkManager = $manager;
    $this->categoryManager = $category_manager;
    $this->queryService = $query_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.prod_check'),
      $container->get('plugin.manager.prod_check_categories'),
      $container->get('entity.query')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function process(ProdCheckInterface $plugin) {
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function info() {
    return ProdCheck::REQUIREMENT_INFO;
  }

  /**
   * {@inheritdoc}
   */
  public function ok() {
    return ProdCheck::REQUIREMENT_OK;
  }

  /**
   * {@inheritdoc}
   */
  public function warning() {
    return ProdCheck::REQUIREMENT_WARNING;
  }

  /**
   * {@inheritdoc}
   */
  public function error() {
    return ProdCheck::REQUIREMENT_ERROR;
  }

}
