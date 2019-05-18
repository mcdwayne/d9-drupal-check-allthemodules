<?php

namespace Drupal\uc_role\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\user\Entity\User;

/**
 * Form builder for role expirations.
 */
class RoleDeleteForm extends ConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'uc_role_deletion_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    $username = [
      '#theme' => 'username',
      '#account' => $account,
    ];
    return $this->t('Delete expiration of %role_name role for the user @user?', [
      '@user' => drupal_render($username),
      '%role_name' => $role_name,
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    $username = [
      '#theme' => 'username',
      '#account' => $account,
    ];
    return $this->t('Deleting the expiration will give @user privileges set by the %role_name role indefinitely unless manually removed.', [
      '@user' => drupal_render($username),
      '%role_name' => $role_name,
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Yes');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelText() {
    return $this->t('No');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return Url::fromRoute('uc_role.expiration');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $user = NULL, $role = NULL) {
    $expiration = db_query('SELECT expiration FROM {uc_roles_expirations} WHERE uid = :uid AND rid = :rid', [':uid' => $user->id(), ':rid' => $role])->fetchField();
    if ($expiration) {

      $role_name = _uc_role_get_name($role);

      $form['user'] = ['#type' => 'value', '#value' => $user->getUsername()];
      $form['uid'] = ['#type' => 'value', '#value' => $user->id()];
      $form['role'] = ['#type' => 'value', '#value' => $role_name];
      $form['rid'] = ['#type' => 'value', '#value' => $role];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    uc_role_delete(User::load($form_state->getValue('uid')), $form_state->getValue('rid'));

    $form_state->setRedirect('uc_role.expiration');
  }

}
