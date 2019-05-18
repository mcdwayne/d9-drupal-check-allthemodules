<?php

namespace Drupal\deploy_individual\Plugin\Action;

/**
 * Deploy individual nodes.
 *
 * @Action(
 *   id = "deploy_individual_node_action",
 *   label = @Translation("Deploy selected content"),
 *   confirm_form_route_name = "deploy_individual.push_confirm_confirm",
 *   type = "node",
 *   category = @Translation("Deploy individual")
 * )
 */
class DeployIndividualNode extends DeployIndividualActionBase {
}
