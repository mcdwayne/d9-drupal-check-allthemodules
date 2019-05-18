<?php

namespace Drupal\deploy_individual\Plugin\Action;

/**
 * Deploy individual files.
 *
 * @Action(
 *   id = "deploy_individual_file_action",
 *   label = @Translation("Deploy selected file"),
 *   confirm_form_route_name = "deploy_individual.push_confirm_confirm",
 *   type = "file",
 *   category = @Translation("Deploy individual")
 * )
 */
class DeployIndividualFile extends DeployIndividualActionBase {
}
