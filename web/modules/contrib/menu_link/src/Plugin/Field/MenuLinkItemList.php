<?php

namespace Drupal\menu_link\Plugin\Field;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Field\FieldItemList;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines a custom menu link item list for better access checking.
 */
class MenuLinkItemList extends FieldItemList {

  /**
   * {@inheritdoc}
   */
  public function defaultValuesForm(array &$form, FormStateInterface $form_state) {
    // We don't want to use any default values form.
  }

  /**
   * {@inheritdoc}
   */
  public function defaultAccess($operation = 'view', AccountInterface $account = NULL) {
    if ($operation === 'edit') {
      return AccessResult::allowedIfHasPermission($account, 'administer menu');
    }
    return parent::defaultAccess($operation, $account);
  }

}
