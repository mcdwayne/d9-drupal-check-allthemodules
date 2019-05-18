<?php

namespace Drupal\role_toggle;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\Entity\Role;
use Drupal\user\Entity\User;

class RoleToggleForm implements FormInterface {

  /**
   * { @inheritDoc }
   */
  public function getFormId() {
    return 'role_toggle_form';
  }

  /**
   * Checks access for a specific request.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(AccountInterface $account) {
    return AccessResult::allowedIf(RoleToggle::canToggleAny($account));
  }

  /**
   * { @inheritDoc }
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // This form is cacheable. See https://www.drupal.org/project/drupal/issues/2578855
    $form['#cache']['max-age'] = Cache::PERMANENT;

    foreach (RoleToggle::togglableRoles() as $rid => $role) {
      $enabled = RoleToggle::isEnabledRole($role);
      $form['role_toggle'][$rid] = array(
        '#type' => 'checkbox',
        '#default_value' => (int)$enabled,
        '#title' => $role->label(),
      );
    }
    if(isset($form['role_toggle'])) {
      $form['role_toggle']['#tree'] = TRUE;
      $form['#attached']['library'] = [
        'role_toggle/form',
      ];
      $form['actions']['submit'] = [
        '#type' => 'submit',
        '#value' => t('Submit'),
      ];
    }
    return $form;
  }

  /**
   * { @inheritDoc }
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * { @inheritDoc }
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $rolesEnabled = $form_state->getValue('role_toggle');
    foreach ($rolesEnabled as $rid => $enabled) {
      $role = Role::load($rid);

      // we are paranoid and check access again.
      if (!RoleToggle::userAccess($role)) {
        \Drupal::messenger()->addError(t('Role toggle for role %rid not permitted.', array('%rid' => $role->label())));
        continue;
      }

      $currentUser = User::load(\Drupal::currentUser()->id());
      if (!RoleToggle::isEnabledRole($role)) {
        if ($enabled) {
          $currentUser->addRole($rid);
          \Drupal::messenger()->addStatus(t('Role %role set active.', array('%role' => $role->label())));
        }
      }
      else {
        if (!$enabled) {
          $currentUser->removeRole($rid);
          \Drupal::messenger()->addStatus(t('Role %role set inactive.', array('%role' => $role->label())));
        }
      }
    }
    $currentUser->save();

    $query = \Drupal::request()->query->all();
    $query = RoleToggle::createQueryCode($currentUser) + $query;
    $form_state->setRedirect('<current>', [], ['query' => $query, 'absolute' => TRUE,]);
  }

}
