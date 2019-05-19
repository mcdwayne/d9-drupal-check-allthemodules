<?php

namespace Drupal\townsec_key;

use Drupal\Core\Entity\EntityTypeManagerInterface;

use TownsendSecurity\Akm;

class AkmFactory {

  public static function createAkm(
    EntityTypeManagerInterface $entity_type_manager
  ) {
    $storage = $entity_type_manager->getStorage('akm_server');
    $configs = $storage->loadMultiple();

    $akm = new Akm();
    foreach ($configs as $config) {
      $akm->addKeyServer($config->getKeyServer());
    }

    return $akm;
  }

}
