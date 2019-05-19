<?php

namespace Drupal\siteimprove;

use Drupal\Core\Form\FormStateInterface;

/**
 * Class FormAlter.
 */
class EntityFormAlter {

  /**
   * Alter node/taxonomy term edit form.
   */
  public static function siteimprove(array $element, FormStateInterface $form_state) {
    // Get friendly url of node and include all Siteimprove js scripts.
    /** @var \Drupal\Core\Entity\Entity $entity */
    $entity = $form_state->getFormObject()->getEntity();
    $url = $entity->toUrl('canonical', ['absolute' => TRUE])->toString();
    $element['#attached']['drupalSettings']['siteimprove']['input'] = \Drupal::service('siteimprove.utils')->getSiteimproveSettings($url, 'input');
    $element['#attached']['drupalSettings']['siteimprove']['recheck'] = \Drupal::service('siteimprove.utils')->getSiteimproveSettings($url, 'recheck', FALSE);

    return $element;
  }

}
