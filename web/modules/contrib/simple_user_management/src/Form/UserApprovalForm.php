<?php

namespace Drupal\simple_user_management\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\Entity\User;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Class UserApprovalForm.
 *
 * @package Drupal\simple_user_management\Form
 */
class UserApprovalForm extends FormBase {

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
      $form['intro'] = [
        '#markup' => '<p>' . $this->t('Are you sure you want to approve the following user and activate their account?') . '</p>',
      ];

      $form['user'] = [
        '#theme' => 'item_list',
        '#items' => [],
      ];
      $form['user']['#items'][] = [
        '#markup' => $this->t('Username:') . ' ' . $user->getDisplayName(),
      ];
      $form['user']['#items'][] = [
        '#markup' => $this->t('Email:') . ' ' . $user->getEmail(),
      ];

      $form['actions'] = [
        '#type' => 'container',
      ];

      $form['actions']['approve'] = [
        '#type' => 'submit',
        '#value' => t('Approve'),
        '#attributes' => [
          'class' => [
            'button',
            'button--primary',
          ],
        ],
      ];

      $form['uid'] = [
        '#type' => 'hidden',
        '#value' => $uid,
      ];
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

    // Activate the user.
    $user = User::load($form_state->getValue('uid'));
    $user->activate();
    $user->save();

    // Let the activator know the user has been activated.
    $message = $this->t('The user has been successfully activated.');
    \Drupal::messenger()->addMessage($message, 'status');

    // Redirect back to admin people.
    $response = new RedirectResponse('/admin/people');
    $response->send();
  }

}
