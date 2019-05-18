<?php

/**
 * @file
 * Hooks provided by the Cancel Button module.
 */

use Drupal\Core\Entity\EntityMalformedException;
use Drupal\Core\Url;

/**
 * @addtogroup hooks
 * @{
 */
/**
 * Allow other modules to alter the cancel button destination.
 *
 * @param \Drupal\Core\Url $destination
 *   The destination Url object to alter.
 * @param array $context
 *   An array of contextual data about the form where the cancel button appears.
 *   Keys include:
 *   - 'settings': The module's configured default (fallback) destination
 *     settings for the cancel button.
 *   - 'request': The Symfony Request object for the form where the
 *     button appears.
 *   - 'route_match': The RouteMatch object for the form where the button
 *     appears.
 *   - 'entity_type': The entity type definition for the entity on the form
 *     where the button appears.
 *   - 'form_state': The FormState object.
 */
function hook_cancel_button_destination_alter(Url &$destination, array $context) {
  if ($context['entity_type']->id() === 'node') {
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $context['form_state']->getFormObject()->getEntity();
    // If this is node 1, set the cancel button destination to the collection
    // URL rather than to the canonical URL.
    if ($entity->id() == 1) {
      try {
        $destination = $entity->toUrl('collection');
      }
      catch (EntityMalformedException $exception) {
        // If the entity does not have a collection URL, abort without changing
        // destination.
        return;
      }
    }
  }
}

/**
 * @} End of "addtogroup hooks".
 */
