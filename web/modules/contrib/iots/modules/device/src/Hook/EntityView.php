<?php

namespace Drupal\iots_device\Hook;

/**
 * @file
 * Contains \Drupal\iots_device\Hook\EntityView.
 */

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;

/**
 * Controller EntityView.
 */
class EntityView extends ControllerBase {

  /**
   * Hook.
   */
  public static function hook(&$build, $entity, $view_mode) {
    $types = [
      'iots_device',
    ];
    if (self::checkType($entity, $types)) {
      if ($view_mode == 'full') {
        if ($channels = self::getChannels($entity)) {
          $vtype = 'iots_channel';
          $vmode = 'teaser';
          $vbuilder = \Drupal::entityTypeManager()->getViewBuilder($vtype);
          $build['channel'] = [
            'header' => [
              '#markup' => "<h2>" . t('Channels') . "</h3>",
            ],
            '#weight' => 100,
          ];
          foreach ($channels as $cid => $channel) {
            $vbuild = $vbuilder->view($channel, $vmode);
            $build['channel']["channel-$cid"] = [
              'title' => [
                '#markup' => "<h3>" . Link::createFromRoute(
                  $channel->label(),
                  'entity.iots_channel.canonical',
                  ['iots_channel' => $channel->id()]
                )->toString() . "</h3>",
              ],
              'entity' => $vbuild,
            ];
          }
        }
        else {
          if (FALSE) {
            $form = 'Drupal\eexamples\Form\ChangeStatus';
            $build['channel'] = [
              'from' => \Drupal::formBuilder()->getForm($form, $node),
            ];
          }
          else {
            $msg = t('Device has no channels.');
            drupal_set_message($msg, 'warning');
          }
        }
      }
    }
  }

  /**
   * Check Entity Type Id.
   */
  public static function getChannels($device) {
    $entities = [];
    $entity_type = 'iots_channel';
    $storage = \Drupal::entityManager()->getStorage($entity_type);
    $query = \Drupal::entityQuery($entity_type)
      ->condition('status', 1)
      ->sort('created', 'ASC')
      ->condition('device', $device->id());
    $ids = $query->execute();
    if (!empty($ids)) {
      foreach ($storage->loadMultiple($ids) as $id => $entity) {
        $entities[$id] = $entity;
      }
    }
    return $entities;
  }

  /**
   * Check Entity Type Id.
   */
  public static function checkType($entity, $types) {
    $result = FALSE;
    if (method_exists($entity, 'getEntityTypeId')) {
      $type = $entity->getEntityTypeId();
      if (in_array($type, $types)) {
        $result = $type;
      }
    }
    return $result;
  }

}
