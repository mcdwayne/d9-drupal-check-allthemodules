<?php

namespace Drupal\deploy_individual\Plugin\Action;

/**
 * Deploy individual medias.
 *
 * @Action(
 *   id = "deploy_individual_media_action",
 *   label = @Translation("Deploy selected media"),
 *   confirm_form_route_name = "deploy_individual.push_confirm_confirm",
 *   type = "media",
 *   category = @Translation("Deploy individual")
 * )
 */
class DeployIndividualMedia extends DeployIndividualActionBase {
}
