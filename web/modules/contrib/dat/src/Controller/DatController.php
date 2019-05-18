<?php

namespace Drupal\dat\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\dat\Entity\DatabaseConnectionInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class DatController.
 */
class DatController extends ControllerBase {

  /**
   * Route title callback.
   */
  public function getTitle(DatabaseConnectionInterface $dat_connection, string $type) {
    $variable = ['@connection' => $dat_connection->label()];

    return $type == 'adminer' ? $this->t('Adminer - @connection', $variable) : $this->t('Editor - @connection', $variable);
  }

  /**
   * Route callback for the Database Connection.
   *
   * @param \Drupal\dat\Entity\DatabaseConnectionInterface $dat_connection
   *   The Database connection.
   * @param string $type
   *   Administration Tool type.
   *
   * @return array
   *   A render array as expected by drupal_render().
   *
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  public function connectionCallback(DatabaseConnectionInterface $dat_connection, string $type) : array {
    $query = [
      'username' => $dat_connection->get('username'),
      $dat_connection->get('driver') => $dat_connection->getHostWithPort(),
      'db' => $dat_connection->get('name'),
    ];
    $config = \Drupal::config('dat.settings');
    $build['dat_iframe'] = [
      '#theme' => 'dat',
      '#attributes' => [
        'class' => ['adminer-frame'],
        'id' => 'adminer',
        'webkitallowfullscreen' => '',
        'mozallowfullscreen' => '',
        'allowfullscreen' => '',
        'frameborder' => 'no',
        'scrolling' => 'yes',
        'width' => $config->get('width') . $config->get('width_unit'),
        'height' => $config->get('height') . $config->get('height_unit'),
        'src' => $dat_connection->toUrl('frame')->setRouteParameter('type', $type)->setOption('query', $query)->toString(),
      ],
      '#attached' => [
        'library' => ['dat/main'],
      ],
    ];

    return $build;
  }

  /**
   * Route callback for a Adminer/Editor frame.
   *
   * @param \Drupal\dat\Entity\DatabaseConnectionInterface $dat_connection
   *   The Database connection.
   * @param string $type
   *   Administration Tool type.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The
   */
  public function connectionFrameCallback(DatabaseConnectionInterface $dat_connection, string $type) {
    $module_path = \Drupal::moduleHandler()->getModule('dat')->getPath();
    if ($type == 'adminer') {
      $adminer_path = $module_path . '/adminer/adminer.php';
    }
    else {
      $adminer_path = $module_path . '/adminer/editor.php';
    }
    include $adminer_path;

    return new Response();
  }

  /**
   * Provides the clone form.
   *
   * @param \Drupal\dat\Entity\DatabaseConnectionInterface $dat_connection
   *   The Database connection.
   *
   * @return array
   *   A Database connection form.
   */
  public function clonePage(DatabaseConnectionInterface $dat_connection) {
    $duplicate = $dat_connection->createDuplicate();
    $duplicate->set('label', $dat_connection->label() . ' - clone');
    $form = $this->entityFormBuilder()->getForm($duplicate, 'edit');

    return $form;
  }

}
