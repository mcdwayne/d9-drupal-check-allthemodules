<?php

namespace Drupal\drd\Plugin\Action;

use Drupal\drd\Entity\BaseInterface as RemoteEntityInterface;
use Drupal\drd\Entity\CoreInterface;

/**
 * Base class for DRD Remote Core Action plugins.
 */
abstract class BaseCoreRemote extends BaseEntityRemote {

  /**
   * {@inheritdoc}
   */
  public function executeAction(RemoteEntityInterface $core) {
    if (empty($core)) {
      return FALSE;
    }
    if ($core instanceof CoreInterface) {
      $domain = $core->getFirstActiveDomain();
    }
    if (empty($domain)) {
      return FALSE;
    }

    return parent::executeAction($domain);
  }

}
