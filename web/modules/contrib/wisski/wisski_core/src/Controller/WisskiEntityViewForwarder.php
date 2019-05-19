<?php

namespace Drupal\wisski_core\Controller;

use Drupal\Core\Entity\ContentEntityStorageInterface;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;


class WisskiEntityViewForwarder {

  public function forward(\Drupal\wisski_core\Entity\WisskiEntity $wisski_individual = NULL) {

#    dpm($wisski_individual,__METHOD__);
    $storage = \Drupal::entityManager()->getStorage('wisski_individual');
    //let's see if the user provided us with a bundle, if not, the storage will try to guess the right one
    $match = \Drupal::request();
    $bundle_id = $match->query->get('wisski_bundle');
    if ($bundle_id) $storage->writeToCache($wisski_individual->id(),$bundle_id);

    if(empty($wisski_individual))
      $wisski_individual = $storage->load($wisski_individual);
    //dpm($entity,__FUNCTION__);
    if (empty($wisski_individual)) {
      throw new NotFoundHttpException();
    }
    $entity_type = $storage->getEntityType();
    $view_builder_class = $entity_type->getViewBuilderClass();
    $view_builder = $view_builder_class::createInstance(\Drupal::getContainer(),$entity_type);
//    dpm($view_builder);
    return $view_builder->view($wisski_individual);
  }
  
}
