<?php

/**
 * @file
 * Hooks specific to the LTI Tool Provider module.
 */

use Drupal\user\Entity\User;

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Allows modules to alter the lti context that is used to authenticate.
 *
 * @param array $context
 *   The LTI context from the launch request.
 */
function hook_lti_tool_provider_launch_alter(array &$context)
{
}

/**
 * Allows modules to act on a user entity before creation.
 *
 * @param User $user
 *   The user that has been authenticated.
 * @param array $context
 *   The LTI context from the launch request.
 */
function hook_lti_tool_provider_create_user(User $user, array $context)
{
}

/**
 * Allows modules to act on a successful LTI authentication.
 *
 * @param User $user
 *   The user that has been authenticated.
 * @param array $context
 *   The LTI context from the launch request.
 */
function hook_lti_tool_provider_authenticated(User $user, array $context)
{
}

/**
 * Allows modules to alter the redirect destination after launch and authentication is successful.
 *
 * @param string $destination
 *   The url to redirect to via RedirectResponse.
 * @param array $context
 *   The LTI context from the launch request.
 */
function hook_lti_tool_provider_launch_redirect_alter(string &$destination, array &$context)
{
}

/**
 * Allows modules to act on the LTI return event.
 *
 * @param array $context
 *   The LTI context.
 */
function hook_lti_tool_provider_return(array $context)
{
}

/**
 * @} End of "addtogroup hooks".
 */
