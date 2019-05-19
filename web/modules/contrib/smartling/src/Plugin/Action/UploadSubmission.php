<?php

/**
 * @file
 * Contains \Drupal\smartling\Plugin\Action\TranslateNode.
 */

namespace Drupal\smartling\Plugin\Action;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Action\ActionBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Queue\QueueInterface;
use Drupal\Component\Render\FormattableMarkup;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Session\AccountInterface;


/**
 * Translate entity.
 *
 * @Action(
 *   id = "smartling_upload_submission_action",
 *   label = @Translation("Upload submission to Smartling"),
 *   type = "smartling_submission",
 *   confirm_form_route_name = "smartling.upload_submissions_approve"
 * )
 */
class UploadSubmission extends SmartlingBaseTranslationAction {
  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    //** @var \Drupal\node\NodeInterface $object */
    // @todo Move the check to own service.
    $result = content_translation_translate_access($object);

    //return $return_as_object ? $result : $result->isAllowed();
    return TRUE;
  }

}
