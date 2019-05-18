<?php

namespace Drupal\ckeditor_content_style\Controller;

/**
 * @file
 * Contains \Drupal\ckeditor_content_style\Controller\DefaultController.
 */

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Default controller for the ckeditor_content_style module.
 */
class DefaultController extends ControllerBase {


  protected $connection;

  /**
   * Default  Constructure.
   */
  public function __construct(Connection $connection) {
    $this->connection = $connection;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database')
    );
  }

  /**
   * Display.
   *
   * @return string
   *   Return content style data.
   */
  public function display() {

    $content = [];

    $rows = [];
    $headers = [
      'id' => $this->t('Sr No'),
      'entity' => $this->t('Entity'),
      'suggested' => $this->t('Suspected'),
      'suggestion' => $this->t('Suggestion'),
      'opt' => $this->t('operations'),
      'opt1' => $this->t('operations'),
    ];

    $query = $this->connection->select('contentstyle', 'cs');
    $query->fields('cs', ['id', 'entity', 'sugested', 'suggestion']);
    $pager = $query->extend('Drupal\Core\Database\Query\PagerSelectExtender')->limit(50);
    $results = $pager->execute()->fetchAll();
    $rows = [];
    foreach ($results as $data) {
      $delete = Url::fromUserInput('/admin/ckcs/form/delete/' . $data->id);
      $edit = Url::fromUserInput('/admin/ckcs/form/add?num=' . $data->id);
      $sugested = unserialize($data->sugested);
      $sugested = implode(", ", $sugested);
      $rows[] = [
        'id' => $data->id,
        'entity' => $data->entity,
        'suggested' => $sugested,
        'suggestion' => $data->suggestion,
        'deletekey' => Link::fromTextAndUrl($this->t('Delete'), $delete),
        'editkey' => Link::fromTextAndUrl($this->t('Edit'), $edit),
      ];
    }

    $content['summary']['#markup'] = '<a href="/admin/ckcs/form/add" class="button button-action button--primary button--small" data-drupal-link-system-path="node/add">' . $this->t("Add Entity") . '</a><br><br>';
    $content['table'] = [
      '#type' => 'table',
      '#header' => $headers,
      '#rows' => $rows,
      '#empty' => $this->t('No entries available.'),
    ];

    // Don't cache this page.
    $content['#cache']['max-age'] = 0;

    // For pagination.
    $content['pager'] = [
      '#type' => 'pager',
    ];

    return $content;

  }

}
