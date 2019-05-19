<?php

namespace Drupal\spectra\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface defining a SpectraStatement entity.
 * @ingroup spectra
 */
interface SpectraStatementInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

}

?>