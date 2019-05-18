<?php

/**
 * @file
 * Hooks specific to the LTI Tool Provider Provision module.
 */

use Drupal\Core\Entity\EntityInterface;

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Allows modules to alter the entity before it's provisioned.
 *
 * @param EntityInterface $entity
 *   The entity that will be created.
 * @param array $context
 *   The LTI context from the launch request.
 */
function hook_lti_tool_provider_provision_alter(EntityInterface &$entity, array &$context)
{
}

/**
 * Allows modules to act on a successful entity provision.
 *
 * @param EntityInterface $entity
 * @param array $context
 *   The LTI context from the launch request.
 */
function hook_lti_tool_provider_provision_finished(EntityInterface $entity, array &$context)
{
}

/**
 * @} End of "addtogroup hooks".
 */
