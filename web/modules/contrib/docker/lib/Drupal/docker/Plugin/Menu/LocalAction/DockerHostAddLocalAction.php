<?php

/**
 * @file
 * Contains \Drupal\image\Plugin\Menu\DockerHostAddLocalAction.
 */

namespace Drupal\docker\Plugin\Menu\LocalAction;

use Drupal\Core\Annotation\Menu\LocalAction;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Menu\LocalActionBase;

/**
 * @LocalAction(
 *   id = "docker_host_add_action",
 *   route_name = "docker_host_add",
 *   title = @Translation("Add Docker host"),
 *   appears_on = {"docker_host_list"}
 * )
 */
class DockerHostAddLocalAction extends LocalActionBase {

}
