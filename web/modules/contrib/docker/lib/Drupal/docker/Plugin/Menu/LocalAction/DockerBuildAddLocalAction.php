<?php

/**
 * @file
 * Contains \Drupal\image\Plugin\Menu\DockerBuildAddLocalAction.
 */

namespace Drupal\docker\Plugin\Menu\LocalAction;

use Drupal\Core\Annotation\Menu\LocalAction;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Menu\LocalActionBase;

/**
 * @LocalAction(
 *   id = "docker_build_add_action",
 *   route_name = "docker_build_add",
 *   title = @Translation("Add Docker build"),
 *   appears_on = {"docker_build_list"}
 * )
 */
class DockerBuildAddLocalAction extends LocalActionBase {

}
