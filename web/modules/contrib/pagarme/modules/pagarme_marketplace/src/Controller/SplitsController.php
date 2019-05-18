<?php

namespace Drupal\pagarme_marketplace\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Drupal\commerce_product\Entity\ProductVariation;

/**
 * Class SplitsController.
 *
 * @package Drupal\pagarme_marketplace\Controller
 */
class SplitsController extends ControllerBase {

  /**
   * The database object.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  protected $entity_type_manager;

  protected $route_match;

  public function __construct(Connection $database, EntityTypeManager $entity_type_manager, CurrentRouteMatch $route_match) {
    $this->database = $database;
    $this->entity_type_manager = $entity_type_manager;
    $this->route_match = $route_match;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('entity_type.manager'),
      $container->get('current_route_match')
    );
  }

  /**
   * Public Render Method split list.
   *
   * @return Return an array for markup render. Example: ['#markup' => $yourMarkup]
   */
  public function splitList() {
    $destination = $this->getDestinationArray();
    $company = $this->route_match->getParameter('company');
    $config = \Drupal::config('pagarme_marketplace.settings');
    $num_per_page = $config->get('number_items_per_page');

    $query = $this->database->select('pagarme_splits', 'splits')->extend('Drupal\Core\Database\Query\PagerSelectExtender');
    $query->fields('splits');
    $query->limit($num_per_page);
    $query->orderBy('changed', 'DESC');
    $splits = $query->execute()->fetchAll();

    $header = [
      'product_title' => $this->t('Product'),
      'product_sku' => $this->t('SKU'),
      'split_type' => $this->t('Rule type'),
      'status' => $this->t('Status'),
      'operations' => $this->t('Operations')
    ];


    $variation_ids = array_map(function($split) {
      return $split->product_variation_id;
    }, $splits);

    $product_variations = $this->entity_type_manager->getStorage('commerce_product_variation')
      ->loadMultiple($variation_ids);

    $rows = array();
    if (!empty($splits) && !empty($product_variations)) {
      foreach ($splits as $split) {
        $variation_id = $split->product_variation_id;
        $variation = $product_variations[$variation_id];
        $rows[$split->split_id] = [
          'product_title' => $variation->getTitle(),
          'product_sku' => $variation->getSku(),
          'split_type' => ($split->split_type == 'percentage') ? $this->t('In percent') : $this->t('In cents'),
          'status' => (empty($split->status)) ? $this->t('Inactive') : $this->t('Active'),
        ];

        $links = [];

        $links['edit'] = [
          'title' => t('Edit'),
          'url' => Url::fromRoute(
              'pagarme_marketplace.company_split_rules_edit', 
              [
                'op' => 'edit',
                'company' => $company,
                'product_variation_id' => $variation->id(),
                'split_id' => $split->split_id
              ]
          ),
          'query' => $destination,
        ];

        $links['delete'] = array(
          'title' => t('Delete'),
          'url' => Url::fromRoute(
              'pagarme_marketplace.company_split_rules_delete', 
              [
                'company' => $company,
                'split_id' => $split->split_id
              ]
          ),
          'query' => $destination,
        );

        $operations = [
          '#theme' => 'links',
          '#links' => $links,
          '#attributes' => array('class' => array('links', 'inline', 'nowrap')),
        ];
        $rows[$split->split_id]['operations'] = render($operations);
      }
    }

    $build['splits'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => t('There are no registered recipients.'),
    ];

    $build['pager'] = ['#type' => 'pager'];

    return $build;
  }
}