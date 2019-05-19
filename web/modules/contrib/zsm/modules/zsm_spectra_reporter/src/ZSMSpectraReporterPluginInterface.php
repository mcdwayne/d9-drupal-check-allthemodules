<?php

namespace Drupal\zsm_spectra_reporter;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface defining a Contact entity.
 * @ingroup zsm_spectra_reporter
 */
interface ZSMSpectraReporterPluginInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

}

?>