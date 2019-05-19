<?php

/**
 * @file
 * Contains \Drupal\smartling\Plugin\Action\TranslateNode.
 */

namespace Drupal\smartling\Plugin\Action;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Translate entity.
 *
 * @Action(
 *   id = "smartling_translate_taxonomy_term_action",
 *   label = @Translation("Translate taxonomy term with Smartling"),
 *   type = "taxonomy_term",
 *   confirm_form_route_name = "smartling.upload_taxonomy_term_approve"
 * )
 */
class TranslateTaxonomyTerm extends SmartlingBaseTranslationAction {
  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    /** @var \Drupal\node\NodeInterface $object */
    // @todo Move the check to own service.
    $result = content_translation_translate_access($object);

    return TRUE;// $return_as_object ? $result : $result->isAllowed();
  }
}
