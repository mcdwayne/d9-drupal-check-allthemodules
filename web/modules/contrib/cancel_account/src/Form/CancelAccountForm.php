<?php

namespace Drupal\cancel_account\Form;

use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Password\PasswordInterface;
use Drupal\user\UserStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for the cancel user account form.
 */
class CancelAccountForm extends FormBase {

  /**
   * The Password Hasher.
   *
   * @var \Drupal\Core\Password\PasswordInterface
   */
  protected $passwordHasher;

  /**
   * A user settings config instance.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $userSettings;

  /**
   * The user storage handler.
   *
   * @var \Drupal\user\UserStorageInterface
   */
  protected $userStorage;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'cancel_account_form';
  }

  /**
   * Constructs a CancelAccountForm object.
   *
   * @param \Drupal\Core\Password\PasswordInterface $password_hasher
   *   The password hasher.
   * @param \Drupal\Core\Config\ImmutableConfig $user_settings
   *   A user settings config instance.
   * @param \Drupal\user\UserStorageInterface $user_storage
   *   The user storage handler.
   */
  public function __construct(PasswordInterface $password_hasher, ImmutableConfig $user_settings, UserStorageInterface $user_storage) {
    $this->passwordHasher = $password_hasher;
    $this->userSettings = $user_settings;
    $this->userStorage = $user_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('password'),
      $container->get('config.factory')->get('user.settings'),
      $container->get('entity_type.manager')->getStorage('user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $extra = NULL) {
    // Get user.
    if (!$extra) {
      $account = $this->currentUser();
    }
    else {
      $account = $this->userStorage->load((int) $extra);
    }

    // Get current user.
    $user = $this->currentUser();

    // Build cancel account form.
    if (!$user->isAnonymous() && $user->id() == $account->id()) {
      // Check cancel account permissions and prevent adding form to superadmin.
      if ($user->hasPermission('cancel account') && $account->id() != 1) {
        $form['advanced'] = [
          '#type' => 'details',
          '#title' => $this->t('Cancel your account'),
          '#open' => TRUE,
        ];

        $form['advanced']['user_cancel_method'] = [
          '#type' => 'radios',
          '#title' => $this->t('When cancelling your account'),
          '#access' => $user->hasPermission('select account cancellation method'),
        ];
        $form['advanced']['user_cancel_method'] += user_cancel_methods();

        $form['advanced']['cancel']['markup'] = [
          '#markup' => $this->t('This action cannot be undone.'),
          '#prefix' => '<p>',
          '#suffix' => '</p>',
        ];
        $form['advanced']['cancel']['confirm'] = [
          '#type' => 'checkbox',
          '#default_value' => 0,
          '#title' => $this->t('Confirm deleting my account.'),
        ];
        $form['advanced']['cancel']['pass'] = [
          '#type' => 'password',
          '#title' => $this->t('Current password'),
          '#attributes' => ['autocomplete' => 'off'],
          '#size' => 25,
          '#required' => TRUE,
        ];
        $form['advanced']['actions'] = ['#type' => 'actions'];
        $form['advanced']['actions']['submit'] = [
          '#type' => 'submit',
          '#value' => $this->t('Cancel account'),
          '#states' => [
            'enabled' => [
              [
                ':input[name="confirm"]' => ['checked' => TRUE],
                ':input[name="pass"]' => ['filled' => TRUE],
              ],
            ],
          ],
        ];
      }
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $pass_input = trim($form_state->getValue('pass'));
    if ($pass_input) {
      /** @var \Drupal\user\UserInterface $user */
      $user = $this->userStorage->load($this->currentUser()->id());
      // Check user password.
      if (!$this->passwordHasher->check($pass_input, $user->getPassword())) {
        $form_state->setErrorByName('pass', $this->t('The password you provided is incorrect.'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->userSettings;

    // Get account cancel settings.
    if ($this->currentUser()->hasPermission('select account cancellation method')) {
      $method = $form_state->getValue('user_cancel_method');
    }
    else {
      $method = $config->get('cancel_method');
    }
    $edit['user_cancel_notify'] = $config->get('notify.status_canceled');

    // Cancel user account finally.
    user_cancel($edit, $this->currentUser()->id(), $method);

    // Redirect user to the front page.
    $form_state->setRedirect('<front>');
  }

}
