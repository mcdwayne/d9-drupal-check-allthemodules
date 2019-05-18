<?php

/**
 * @file
 * Documentation for claim_on_registration.
 */

/**
 * Define hook_claim_on_registration_node_update().
 *
 * Alter The Node object when a user "claims" the node on registration or login.
 *
 * Note: the new user_id is set and there is no need to call $node->save().
 *
 * @param Object $node
 *   The node object.
 */
function hook_claim_on_registration_node_update(Object &$node) {
}
