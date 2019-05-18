<?php

/**
 * @file
 * Hooks provided by the Haystack module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Allow / disallow haystack indexing.
 *
 * @param \Drupal\node\Entity\Node $node
 *   The node that is being indexed.
 *
 * @return bool
 *   Only FALSE value has effects (trumps all TRUE values from other calls).
 */
function hook_haystack_allow_indexing(\Drupal\node\Entity\Node $node) {
  if ($node->isPublished()) {
    return TRUE;
  }
  else {
    return FALSE;
  }
}

/**
 * Alter haystack index package.
 *
 * @param \Drupal\node\Entity\Node $node
 *   The node that is being indexed.
 *
 * @return array
 *   Additional fields that will be added to the package sent to Haystack.
 */
function hook_haystack_get_fields(\Drupal\node\Entity\Node $node) {
  return [
    'owner_id' => $node->getOwnerId(),
  ];
}

/**
 * @} End of "addtogroup hooks".
 */
