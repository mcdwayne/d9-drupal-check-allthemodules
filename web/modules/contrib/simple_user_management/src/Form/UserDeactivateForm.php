<?php

namespace Drupal\simple_user_management\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\user\Entity\User;
use Drupal\role_delegation\DelegatableRoles;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Class UserDeactivateForm.
 *
 * @package Drupal\simple_user_management\Form
 */
class UserDeactivateForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'user_approval_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $user = FALSE) {
    $uid = $user;
    if ($user = User::load($uid)) {

      $current_user = \Drupal::currentUser();
      $delegatable_roles = new DelegatableRoles();
      $roles = $delegatable_roles->getAssignableRoles($current_user);

      // Only allow deactivation if the logged in user has permission to
      // delegate all roles that the user being checked has.
      $allow_deactivation = TRUE;
      $user_roles = $user->getRoles();
      foreach ($user_roles as $user_role) {
        if ($user_role != 'authenticated' && !in_array($user_role, array_keys($roles))) {
          $allow_deactivation = FALSE;
        }
      }

      if ($allow_deactivation) {

        // Hidden fields to match expectations of user_cancel().
        $form['user_cancel_method']  = [
          '#type'  => 'hidden',
          '#value' => 'user_cancel_block',
        ];
        $form['user_cancel_confirm'] = [
          '#type'  => 'hidden',
          '#value' => '',
        ];
        $form['user_cancel_notify']  = [
          '#type'  => 'hidden',
          '#value' => '',
        ];
        $form['uid']                 = [
          '#type'  => 'hidden',
          '#value' => $user->id(),
        ];
        $form['access']              = [
          '#type'  => 'hidden',
          '#value' => TRUE,
        ];

        // Informational fields for the logged in user.
        $form['intro']                 = [
          '#markup' => '<p>' . $this->t('Are you sure you want to deactivate the following user? This will disable the account but keep its contents.') . '</p>',
        ];
        $form['user']                  = [
          '#theme' => 'item_list',
          '#items' => [],
        ];
        $form['user']['#items'][]      = [
          '#markup' => $this->t('Username:') . ' ' . $user->getDisplayName(),
        ];
        $form['user']['#items'][]      = [
          '#markup' => $this->t('Email:') . ' ' . $user->getEmail(),
        ];
        $form['actions']               = [
          '#type' => 'container',
        ];
        $form['actions']['deactivate'] = [
          '#type'       => 'submit',
          '#value'      => t('Deactivate'),
          '#attributes' => [
            'class' => [
              'button',
              'button--primary',
            ],
          ],
        ];
        $form['uid']                   = [
          '#type'  => 'hidden',
          '#value' => $uid,
        ];
      }
      else {
        $message = $this->t('You are not allowed to deactivate this user.');
        \Drupal::messenger()->addMessage($message, 'warning');
      }
    }
    else {
      $message = $this->t('Unable to load the user details.');
      \Drupal::messenger()->addMessage($message, 'warning');
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    user_cancel(
      $form_state->getValues(),
      $form_state->getValue('uid'),
      $form_state->getValue('user_cancel_method')
    );

    $form_state->setRedirectUrl(Url::fromUserInput('/admin/people'));

    // Let the deactivator know the user has been deactivated.
    $message = $this->t('The user has been successfully deactivated.');
    \Drupal::messenger()->addMessage($message, 'status');
  }
}
