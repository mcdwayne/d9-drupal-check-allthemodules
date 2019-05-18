<?php /**
 * @file
 * Contains \Drupal\leaflet_views_ajax_popup\Controller\DefaultController.
 */

namespace Drupal\leaflet_views_ajax_popup\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Response;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;

/**
 * Default controller for the leaflet_views_ajax_popup module.
 */
class DefaultController extends ControllerBase {


  public function accessCheck(EntityInterface $entity) {
    return AccessResult::allowedIf($entity->access('view'));
  }

  public function callback(EntityInterface $entity, $view_mode) {
    $build = entity_view($entity, $view_mode);
    return new Response(drupal_render($build));
  }
}
