<?php

namespace Drupal\username_reminder\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\user\UserStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a username reminder form.
 */
class UsernameReminderForm extends FormBase {

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The user storage.
   *
   * @var \Drupal\user\UserStorageInterface
   */
  protected $userStorage;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The mail manager.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  protected $mailManager;

  /**
   * Constructs a UserPasswordForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\user\UserStorageInterface $user_storage
   *   The user storage.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Mail\MailManagerInterface $mail_manager
   *   The mail manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, UserStorageInterface $user_storage, LanguageManagerInterface $language_manager, MailManagerInterface $mail_manager) {
    $this->configFactory = $config_factory;
    $this->userStorage = $user_storage;
    $this->languageManager = $language_manager;
    $this->mailManager = $mail_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity.manager')->getStorage('user'),
      $container->get('language_manager'),
      $container->get('plugin.manager.mail')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'username_reminder_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['email'] = [
      '#type' => 'email',
      '#title' => $this->t('Email address'),
      '#required' => TRUE,
    ];
    $form['instructions'] = [
      '#prefix' => '<p>',
      '#markup' => $this->t('If there is a username for your email address, it will be sent to your email address.'),
      '#suffix' => '</p>',
    ];
    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = ['#type' => 'submit', '#value' => $this->t('Submit')];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $email = trim($form_state->getValue('email'));
    $users = $this->userStorage->loadByProperties(['mail' => $email, 'status' => 1]);
    $account = reset($users);
    if ($account && $account->id()) {
      $form_state->setValueForElement(['#parents' => ['account']], $account);
    }
    else {
      $form_state->setErrorByName('email', $this->t('Sorry, %email is not a recognized email address.', ['%email' => $email]));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $account = $form_state->getValue('account');
    $langcode = $this->languageManager->getCurrentLanguage()->getId();
    if (!$langcode) {
      $langcode = $account->getPreferredLangcode();
    }
    $params['account'] = $account;
    // Get the custom site notification email to use as the from email address
    // if it has been set.
    $site_mail = $this->configFactory->get('system.site')->get('mail_notification');
    // If the custom site notification email has not been set, we use the site
    // default for this.
    if (empty($site_mail)) {
      $site_mail = $this->configFactory->get('system.site')->get('mail');
    }
    if (empty($site_mail)) {
      $site_mail = ini_get('sendmail_from');
    }

    // Mail username using current language.
    $mail = $this->mailManager->mail('username_reminder', 'reminder', $account->getEmail(), $langcode, $params, $site_mail);
    if (!empty($mail)) {
      $this->logger('user')->notice('Username reminder mailed to %name at %email.', ['%name' => $account->getUsername(), '%email' => $account->getEmail()]);
      drupal_set_message($this->t('Your username has been sent to your email address.'));
    }

    $form_state->setRedirect('user.page');
  }

}
