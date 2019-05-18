<?php

namespace Drupal\prod_check\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\prod_check\Entity\ProdCheckProcessor;
use Drupal\prod_check\Plugin\ProdCheckCategoryPluginManager;
use Drupal\prod_check\Plugin\ProdCheckProcessorPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Route controller fields.
 */
class StatusReportController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * The prod check processor plugin manager.
   *
   * @var \Drupal\prod_check\Plugin\ProdCheckProcessorPluginManager;
   */
  protected $processManager;

  /**
   * The prod check category plugin manager.
   *
   * @var \Drupal\prod_check\Plugin\ProdCheckCategoryPluginManager;
   */
  protected $categoryManager;

  /**
   * Constructs a \Drupal\prod_check\Controller\StatusReportController object.
   *
   * @param \Drupal\prod_check\Plugin\ProdCheckProcessorPluginManager $process_manager
   *   The prod check processor plugin manager.
   * @param \Drupal\prod_check\Plugin\ProdCheckCategoryPluginManager $category_manager
   *   The prod check processor category manager.
   */
  public function __construct(ProdCheckProcessorPluginManager $process_manager, ProdCheckCategoryPluginManager $category_manager) {
    $this->processManager = $process_manager;
    $this->categoryManager = $category_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.prod_check_processor'),
      $container->get('plugin.manager.prod_check_categories')
    );
  }

  /**
   * Builds a list of fields
   */
  public function build() {
    /** @var ProdCheckProcessor $internal_processor */
    $internal_processor = ProdCheckProcessor::load('internal');
    $requirements = $internal_processor->getPlugin()->requirements();

    // Get all categories.
    $categories = $this->categoryManager->getDefinitions();

    // Array where we keep track of the requirements per category.
    $requirements_per_category = [];

    // Prefill the requirements per category so we have the same order as the
    // categories themselves.
    foreach ($categories as $key => $category) {
      $requirements_per_category[$key] = [];
    }

    foreach ($requirements as $key => $requirement) {
      $requirements_per_category[$requirement['category']][$key] = $requirement;
    }

    return array(
      '#theme' => 'prod_check_status_report',
      '#requirements' => $requirements_per_category,
      '#categories' => $categories,
    );
  }

}
