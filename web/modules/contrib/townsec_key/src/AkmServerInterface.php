<?php

namespace Drupal\townsec_key;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

interface AkmServerInterface extends ConfigEntityInterface {

  /**
   * @return TownsendSecurity\KeyServer
   */
  public function getKeyServer();

}
