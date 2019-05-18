<?php

namespace Drupal\monster_menus\Plugin\Validation\Constraint;

use Drupal\Core\Form\FormState;
use Drupal\monster_menus\Constants;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Checks certain MM-specific node fields.
 */
class MMNodeValidationConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($node, Constraint $constraint) {
    $form_state = new FormState();
    $form = [];
    mm_node_all_nodes_hook('validate', $node, $form, $form_state);

    if (!empty($node->mm_catlist) && is_array($node->mm_catlist) && count($node->mm_catlist)) {
      if (is_array($node->mm_catlist_restricted)) {
        $node->mm_catlist = array_diff_key($node->mm_catlist, array_flip($node->mm_catlist_restricted));
      }
      foreach ($node->mm_catlist as $mmtid => $name) {
        if (!$mmtid || !mm_content_user_can($mmtid, Constants::MM_PERMS_APPLY)) {
          $form_state->setErrorByName('mm_catlist', t('You are not allowed to assign content to the page %cat.', array('%cat' => $name)));
        }
        elseif (mm_content_is_archive($mmtid)) {
          $form_state->setErrorByName('mm_catlist', t('The page %cat is an archive of another page. Assign the content to the main page, and the archive will be updated automatically.', array('%cat' => $name)));
        }
      }
    }
    elseif (empty($node->mm_catlist_restricted)) {
      $form_state->setErrorByName('mm_catlist', t('You must assign this content to at least one page.'));
    }

    if (isset($node->owner) && \Drupal::currentUser()->hasPermission('administer all menus')) {
      _mm_ui_verify_userlist($form_state, $node->getOwnerId(), 'owner');
    }

    if (empty($node->mm_skip_perms)) {
      if (isset($node->groups_w) && is_array($node->groups_w)) {
        foreach ($node->groups_w as $gid => $name) {
          if ($gid && !mm_content_user_can($gid, Constants::MM_PERMS_APPLY)) {
            $form_state->setErrorByName('groups_w', t('You do not have permission to use the group %grp.', array('%grp' => $name)));
          }
        }
      }

      if (isset($node->users_w) && is_array($node->users_w)) {
        _mm_ui_verify_userlist($form_state, $node->users_w, 'users_w');
      }
    }

    if (!empty($node->publish_on) && !empty($node->unpublish_on) && !is_array($node->publish_on) && !is_array($node->unpublish_on)) {
      $publish_on = strtotime($node->publish_on);
      $unpublish_on = strtotime($node->unpublish_on);
      if ($unpublish_on > 0 && $unpublish_on < $publish_on) {
        $form_state->setErrorByName('unpublish_on', t('You have chosen an unpublish date earlier than the publish date.'));
      }
    }

    foreach ($form_state->getErrors() as $error) {
      $this->context->addViolation($error);
    }
  }

}
