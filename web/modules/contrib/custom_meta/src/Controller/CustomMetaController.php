<?php

/**
 * @file
 * Contains \Drupal\custom_meta\Controller\CustomMetaController.
 */

namespace Drupal\custom_meta\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\custom_meta\CustomMetaStorageInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller routines for custom meta routes.
 */
class CustomMetaController extends ControllerBase {

  /**
   * The custom meta tags storage.
   *
   * @var \Drupal\custom_meta\CustomMetaStorageInterface
   */
  protected $metaStorage;

  /**
   * Constructs a new CustomMetaController.
   *
   * @param \Drupal\custom_meta\CustomMetaStorageInterface $meta_storage
   *   The custom meta tags storage.
   */
  public function __construct(CustomMetaStorageInterface $meta_storage) {
    $this->metaStorage = $meta_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('custom_meta.meta_storage')
    );
  }

  /**
   * Displays the custom meta tags administration overview page.
   *
   * @return array
   *   A render array as expected by drupal_render().
   */
  public function adminOverview() {
    // Table header
    $header = array();
    $header[] = array('data' => $this->t('Meta attribute'), 'field' => 'meta_attr', 'sort' => 'asc');
    $header[] = array('data' => $this->t('Attribute value'), 'field' => 'meta_attr_value');
    $header[] = array('data' => $this->t('Content value'), 'field' => 'meta_content');
    $header[] = $this->t('Output');
    $header[] = $this->t('Operations');

    $rows = array();
    $destination = $this->getDestinationArray();
    foreach ($this->metaStorage->getCustomMetaTagsForAdminListing($header) as $data) {
      // Table row.
      $row['data']['meta_attr'] = $data->meta_attr;
      $row['data']['meta_attr_value'] = $data->meta_attr_value;
      $row['data']['meta_content'] = $data->meta_content;
      $row['data']['output'] = '<meta ' . $data->meta_attr . '="' . $data->meta_attr_value . '" content="' . $data->meta_content . '>';

      $operations = array();
      $operations['edit'] = array(
        'title' => $this->t('Edit'),
        'url' => Url::fromRoute('custom_meta.admin_edit', ['meta_uid' => $data->meta_uid], ['query' => $destination]),
      );
      $operations['delete'] = array(
        'title' => $this->t('Delete'),
        'url' => Url::fromRoute('custom_meta.delete', ['meta_uid' => $data->meta_uid], ['query' => $destination]),
      );
      $row['data']['operations'] = array(
        'data' => array(
          '#type' => 'operations',
          '#links' => $operations,
        ),
      );

      $rows[] = $row;
    }

    $build['meta_table'] = array(
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('No custom meta tags available. <a href=":link">Add tag</a>.', array(':link' => $this->url('custom_meta.admin_add'))),
    );
    $build['meta_pager'] = array('#type' => 'pager');

    return $build;
  }

}
