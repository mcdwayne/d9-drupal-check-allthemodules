<?php

namespace Drupal\gtm_datalayer\Controller;

use Drupal\gtm_datalayer\Entity\DataLayerInterface;
use Drupal\Core\Controller\ControllerBase;

/**
 * Controller routines for admin GTM dataLayer entity routes.
 */
class EntityDataLayerController extends ControllerBase {

  /**
   * Calls a method on a dataLayer and reloads the listing page.
   *
   * @param \Drupal\gtm_datalayer\Entity\DataLayerInterface $gtm_datalayer
   *   The dataLayer being acted upon.
   * @param string $op
   *   The operation to perform, e.g., 'enable' or 'disable'.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A redirect back to the collection page.
   *
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  public function performOperation(DataLayerInterface $gtm_datalayer, $op) {
    $gtm_datalayer->$op()->save();
    drupal_set_message($this->t('The dataLayer settings have been updated.'));

    return $this->redirect($this->getCollectionUrl($gtm_datalayer));
  }

  /**
   * Returns the entity collection route.
   *
   * @param \Drupal\gtm_datalayer\Entity\DataLayerInterface $gtm_datalayer
   *   The dataLayer entity.
   *
   * @return string
   *   A route name.
   *
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  protected function getCollectionUrl(DataLayerInterface $gtm_datalayer) {
    if ($gtm_datalayer->hasLinkTemplate('collection')) {
      // If available, return the collection URL.
      return $gtm_datalayer->toUrl('collection')->getRouteName();
    }
    else {
      // Otherwise fall back to the default link template.
      return $gtm_datalayer->toUrl()->getRouteName();
    }
  }

}
