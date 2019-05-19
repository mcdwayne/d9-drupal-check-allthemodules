<?php
/**
 * contains Drupal\wisski_core\Controller\WisskiEntityListController
 */

namespace Drupal\wisski_core\Controller;
 
use Drupal\Core\Entity\Controller\EntityListController;
 
class WisskiEntityListController extends EntityListController {

  public function listing($wisski_bundle=NULL,$wisski_individual=NULL) {
#    dpm($this->getDestinationArray());
#    dpm(func_get_args(), "yay");

    if (is_null($wisski_bundle)) {
      if(strpos($this->getDestinationArray()['destination'], 'create') !== FALSE)
        return $this->entityManager()->getListBuilder('wisski_bundle')->render(WisskiBundleListBuilder::CREATE);
      return $this->entityManager()->getListBuilder('wisski_bundle')->render(WisskiBundleListBuilder::NAVIGATE);
    }
    return $this->entityManager()->getListBuilder('wisski_individual')->render($wisski_bundle,$wisski_individual);
  }
}
