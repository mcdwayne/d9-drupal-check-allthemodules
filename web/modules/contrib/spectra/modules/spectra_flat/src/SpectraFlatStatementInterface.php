<?php

namespace Drupal\spectra_flat;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface defining a SpectraFlatStatement entity.
 * @ingroup spectra_flat
 */
interface SpectraFlatStatementInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

}

?>