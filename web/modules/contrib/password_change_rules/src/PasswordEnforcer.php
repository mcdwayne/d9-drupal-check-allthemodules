<?php

namespace Drupal\password_change_rules;

use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Enforce our password changing logic.
 */
class PasswordEnforcer implements ContainerInjectionInterface {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The password change rules config.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * Profile form password enforcer constructor.
   *
   * @param \Drupal\Core\Session\AccountInterface $currentUser
   *   The current user.
   * @param \Drupal\Core\Config\ImmutableConfig $config
   *   The config.
   */
  public function __construct(AccountInterface $currentUser, ImmutableConfig $config) {
    $this->currentUser = $currentUser;
    $this->config = $config;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static (
      $container->get('current_user'),
      $container->get('config.factory')->get('password_change_rules.settings')
    );
  }

  /**
   * Form alter callback.
   */
  public function alter(&$form, FormStateInterface $formState) {
    $editAccount = $formState->getFormObject()->getEntity();

    if ($this->currentUser->id() === $editAccount->id() && $editAccount->get('password_change_rules')->value) {
      // We must check if there has been any input. If there has then it could
      // be that things will be fixed and therefore we do not want to show this
      // message on the next page load.
      if (!$formState->getUserInput()) {
        drupal_set_message($this->config->get('change_password_message'), 'warning');
      }

      $form['#validate'][] = [static::class, 'validate'];
    }

    // If we have a new entity, we're already logged in (presumably an admin)
    // and it's enforced globally that all admin registered accounts must reset
    // their password upon login, then we hide this option.
    $is_new = $formState->getFormObject()->getEntity()->isNew();
    if ($is_new && $this->currentUser->isAuthenticated() && $this->config->get('admin_registered_account') && isset($form['password_change_rules'])) {
      $form['password_change_rules']['#access'] = FALSE;
    }
  }

  /**
   * Form validate callback.
   */
  public static function validate(&$form, FormStateInterface $formState) {
    $password_is_same = \Drupal::service('password')->check($formState->getValue('pass'), $formState->getFormObject()->getEntity()->pass->value);
    if ($password_is_same) {
      $formState->setErrorByName('pass', t('You must change your password to something new'));
    }
  }

  /**
   * Implements hook_user_presave().
   */
  public function userPresave(EntityInterface $entity) {
    $currentUser = \Drupal::currentUser();
    $is_owner = $entity->id() === $currentUser->id();

    // If there is no original, this is the first time a user has been saved.
    if (!$entity->original) {

      // If the person creating the account is not the owner and the global
      // settings says all admin registered accounts must reset their password
      // then we enforce it here.
      if ($this->config->get('admin_registered_account') && !$is_owner && $currentUser->isAuthenticated()) {
        $entity->password_change_rules->setValue(TRUE);
      }
      return;
    }

    // If their password hasn't changed, then there is nothing to do.
    $password_changed = $entity->original->pass->value !== $entity->pass->value;
    if (!$password_changed) {
      return;
    }

    // User has changed their own password.
    if ($is_owner) {
      $entity->password_change_rules->setValue(FALSE);
    }
    // Admin has changed their password and the config option says we should
    // update.
    elseif ($this->config->get('admin_change_password')) {
      $entity->password_change_rules->setValue(TRUE);
    }
  }

}
