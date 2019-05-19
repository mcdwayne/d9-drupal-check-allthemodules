<?php

namespace Drupal\user_homepage\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\user_homepage\UserHomepageManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a simple "Save as homepage" form.
 */
class UserHomepageSaveButtonForm extends FormBase {

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
   * Constructs a new UserHomepageSaveButtonForm.
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
    return 'user_homepage_save_button';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save as homepage'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Get the homepage path that will be stored on the database.
    $homepage_path = $this->userHomepageManager->buildHomepagePathFromCurrentRequest();

    // Create or Update entry for the user on the user_homepage table.
    if ($this->userHomepageManager->setUserHomepage($this->account->id(), $homepage_path)) {
      $this->messenger()->addMessage($this->t('Page saved successfully as homepage.'));
    }
    else {
      $this->messenger()->addError($this->t("Page could not be saved as homepage. Try again later."));
    }
  }

}
