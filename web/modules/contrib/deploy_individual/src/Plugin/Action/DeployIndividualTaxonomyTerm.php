<?php

namespace Drupal\deploy_individual\Plugin\Action;

/**
 * Deploy individual taxonomy terms.
 *
 * @Action(
 *   id = "deploy_individual_taxonomy_term_action",
 *   label = @Translation("Deploy selected content"),
 *   confirm_form_route_name = "deploy_individual.push_confirm_confirm",
 *   type = "taxonomy_term",
 *   category = @Translation("Deploy individual")
 * )
 */
class DeployIndividualTaxonomyTerm extends DeployIndividualActionBase {
}
