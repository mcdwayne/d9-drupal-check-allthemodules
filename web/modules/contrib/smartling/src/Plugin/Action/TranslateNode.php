<?php

/**
 * @file
 * Contains \Drupal\smartling\Plugin\Action\TranslateNode.
 */

namespace Drupal\smartling\Plugin\Action;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Translate entity.
 *
 * @Action(
 *   id = "smartling_translate_node_action",
 *   label = @Translation("Translate node with Smartling"),
 *   type = "node",
 *   confirm_form_route_name = "smartling.send_multiple_confirm"
 * )
 */
class TranslateNode extends SmartlingBaseTranslationAction {

}
