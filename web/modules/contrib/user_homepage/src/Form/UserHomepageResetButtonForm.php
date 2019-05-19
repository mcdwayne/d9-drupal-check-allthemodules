<?php

namespace Drupal\user_homepage\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\user_homepage\UserHomepageManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a simple "Reset homepage" form.
 */
class UserHomepageResetButtonForm extends FormBase {

  /**
   * The user homepage manager to use when saving the user homepage.
   *
   * @var \Drupal\user_homepage\UserHomepageManagerInterface
   */
  private $userHomepageManager;

  /**
   * The account for which the form is being rendered.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  private $account;

  /**
   * Constructs a new UserHomepageResetButtonForm.
   *
   * @param \Drupal\user_homepage\UserHomepageManagerInterface $userHomepageManager
   *   A user homepage manager.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   A user account interface.
   */
  public function __construct(UserHomepageManagerInterface $userHomepageManager, AccountInterface $account) {
    $this->userHomepageManager = $userHomepageManager;
    $this->account = $account;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('user_homepage.manager'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'user_homepage_reset_button';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Unset configured homepage'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Set the custom path for the user to none on the user_homepage table.
    if ($this->userHomepageManager->unsetUserHomepage($this->account->id())) {
      $this->messenger()->addMessage($this->t('Your homepage was unset successfully.'));
    }
    else {
      $this->messenger()->addError($this->t('Your homepage could not be unset. Try again later.'));
    }
  }

}
