<?php

namespace Drupal\scriptjunkie\Controller;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\scriptjunkie\ScriptJunkieStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller routines for path routes.
 */
class ScriptJunkieController extends ControllerBase {

  /**
   * The Script Junkie storage service.
   *
   * @var \Drupal\scriptjunkie\ScriptJunkieStorageInterface
   */
  protected $scriptJunkieStorage;

  /**
   * Constructs a new PathController.
   *
   * @param \Drupal\scriptjunkie\ScriptJunkieStorageInterface $scriptjunkie_storage
   *   The path alias storage.
   */
  public function __construct(ScriptJunkieStorageInterface $scriptjunkie_storage) {
    $this->scriptJunkieStorage = $scriptjunkie_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('scriptjunkie.scriptjunkie_storage')
    );
  }

  /**
   * Displays the path administration overview page.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return array
   *   A render array as expected by drupal_render().
   */
  public function adminOverview(Request $request) {

    $header = array();
    $header[] = array(
      'data' => $this->t('Script Title'),
      'field' => 'general',
      'sort' => 'asc',
    );
    $header[] = array(
      'data' => $this->t('Namespace'),
      'field' => 'name',
      'sort' => 'asc',
    );
    $header[] = array(
      'data' => $this->t('Description'),
      'field' => 'general',
    );
    $header[] = $this->t('Operations');

    $rows = array();
    $destination = $this->getDestinationArray();
    foreach ($this->scriptJunkieStorage->getScriptsForAdminListing($header) as $data) {
      $general = unserialize($data->general);
      $row = array();
      $row['data']['general'] = Unicode::truncate($general['title'], 50, FALSE, TRUE);
      $row['data']['name'] = Unicode::truncate($data->name, 50, FALSE, TRUE);
      $row['data']['description'] = Unicode::truncate($general['description'], 120, FALSE, TRUE);

      $operations = array();
      $operations['edit'] = array(
        'title' => $this->t('Edit'),
        'url' => Url::fromRoute('scriptjunkie.settings.edit', ['sid' => $data->sid], ['query' => $destination]),
      );
      $operations['delete'] = array(
        'title' => $this->t('Delete'),
        'url' => Url::fromRoute('scriptjunkie.settings.delete', ['sid' => $data->sid], ['query' => $destination]),
      );
      $row['data']['operations'] = array(
        'data' => array(
          '#type' => 'operations',
          '#links' => $operations,
        ),
      );

      $rows[] = $row;
    }

    $build['path_table'] = array(
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('No Scripts available. <a href=":link">Add Scripts</a>.', array(':link' => $this->url('scriptjunkie.settings.add'))),
    );
    $build['path_pager'] = array('#type' => 'pager');

    return $build;
  }

}
