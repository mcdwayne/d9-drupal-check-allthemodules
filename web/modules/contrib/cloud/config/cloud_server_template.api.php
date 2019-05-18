<?php

/**
 * @file
 * Hooks related to cloud_server_template module.
 */

use Drupal\cloud\Entity\CloudServerTemplateInterface;

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Alter the route array after a template is launched.
 *
 * @param array $route
 *   Associate array with route_name, params.
 * @param \Drupal\cloud\Entity\CloudServerTemplateInterface $cloud_server_template
 *   A Cloud Server Template  object.
 */
function hook_cloud_server_template_post_launch_redirect_alter(array &$route, CloudServerTemplateInterface $cloud_server_template) {

}

/**
 * @} End of "addtogroup hooks".
 */
