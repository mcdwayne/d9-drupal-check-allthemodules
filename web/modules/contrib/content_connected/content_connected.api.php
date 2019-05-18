<?php

/**
 * @file
 * Hooks provided by the content connected module.
 */

/**
 * Alter matches array or add own module rule.
 *
 * @param int $nid
 *   The node nid.
 * @param array $matches
 *   Matches list.
 *
 * @see matchesLongTextField()
 *
 * @ingroup content_connected
 */
function hook_content_connected_alter($nid, $matches) {

  /* Matches needs to be added as array with index of field type.
   * Example $matches['TYPE OF FIELD'] = array('NODE ID' => t('NODE ID'));
   */
}
