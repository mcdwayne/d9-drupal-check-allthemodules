<?php

namespace Drupal\agreement\Form;

use Drupal\agreement\AgreementHandlerInterface;
use Drupal\agreement\Entity\Agreement;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Agreement page form.
 */
class AgreementForm implements FormInterface, ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * Agreement handler.
   *
   * @var \Drupal\agreement\AgreementHandlerInterface
   */
  protected $agreementHandler;

  /**
   * Route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $account;

  /**
   * Messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Mail manager.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  protected $mailManager;

  /**
   * Initialize method.
   *
   * @param \Drupal\agreement\AgreementHandlerInterface $agreementHandler
   *   The agreement handler interface.
   * @param \Drupal\Core\Routing\RouteMatchInterface $routeMatch
   *   The current route match.
   * @param \Drupal\Core\Language\LanguageManagerInterface $languageManager
   *   The language manager.
   * @param \Drupal\Core\Session\AccountProxyInterface $account
   *   The current user account.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   * @param \Drupal\Core\Mail\MailManagerInterface $mailManager
   *   The mail service.
   */
  public function __construct(AgreementHandlerInterface $agreementHandler, RouteMatchInterface $routeMatch, LanguageManagerInterface $languageManager, AccountProxyInterface $account, MessengerInterface $messenger, MailManagerInterface $mailManager) {
    $this->agreementHandler = $agreementHandler;
    $this->routeMatch = $routeMatch;
    $this->languageManager = $languageManager;
    $this->account = $account;
    $this->messenger = $messenger;
    $this->mailManager = $mailManager;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    /* @var \Drupal\agreement\Entity\Agreement $agreement */
    $agreement = $this->routeMatch->getParameter('agreement');
    return 'agreement_' . $agreement->id() . '_form';
  }

  /**
   * Get the page title.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $routeMatch
   *   The route match object. Ignored.
   * @param \Drupal\agreement\Entity\Agreement $agreement
   *   The agreement entity.
   *
   * @return string
   *   The filtered title.
   */
  public function title(RouteMatchInterface $routeMatch, Agreement $agreement) {
    $settings = $agreement->getSettings();
    return $this->t('@title', ['@title' => $settings['title']]);
  }

  /**
   * Build the agreement page.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param \Drupal\agreement\Entity\Agreement|null $agreement
   *   The agreement entity.
   *
   * @return array
   *   The form array.
   */
  public function buildForm(array $form, FormStateInterface $form_state, Agreement $agreement = NULL) {
    $settings = $agreement->getSettings();

    $agreed = $this->agreementHandler->hasAgreed($agreement, $this->account);
    $canAgree = $this->agreementHandler->canAgree($agreement, $this->account);
    $canRevoke = $this->account->hasPermission('revoke own agreement');

    $form_state->setStorage([
      'agreement' => $agreement,
      'agreed' => $agreed,
    ]);

    $form['agreement'] = [
      '#type' => 'container',
      '#tree' => FALSE,
      'text' => [
        '#type' => 'processed_text',
        '#text' => $agreement->get('agreement'),
        '#format' => $settings['format'],
        '#language' => $this->languageManager->getCurrentLanguage(LanguageInterface::TYPE_CONTENT)->getId(),
      ],
      'agree' => [
        '#type' => 'checkbox',
        '#title' => $this->t('@agree', ['@agree' => $settings['checkbox']]),
        '#default_value' => $agreed,
        '#access' => (!$agreed || $canRevoke) && $canAgree,
      ],
      'actions' => [
        '#type' => 'actions',
        'submit' => [
          '#type' => 'submit',
          '#name' => 'submit',
          '#value' => $this->t('@agree_submit', ['@agree_submit' => $settings['submit']]),
          '#access' => (!$agreed || $canRevoke) && $canAgree,
        ],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $storage = $form_state->getStorage();
    if (!$form_state->getValue('agree') && !$storage['agreed']) {
      $settings = $storage['agreement']->getSettings();
      $form_state->setErrorByName('agree', $this->t('@agree_error', ['@agree_error' => $settings['failure']]));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $storage = $form_state->getStorage();
    /* @var $agreement \Drupal\agreement\Entity\Agreement */
    $agreement = $storage['agreement'];
    $settings = $agreement->getSettings();
    $destination = '/';
    $agree = $form_state->getValue('agree');

    if ($settings['destination']) {
      $destination = $settings['destination'];
    }
    elseif (isset($_SESSION['agreement_destination'])) {
      $destination = $_SESSION['agreement_destination'];
    }

    if ($this->agreementHandler->agree($agreement, $this->account, $agree)) {
      $form_state->setRedirectUrl(Url::fromUserInput($destination));
      $message = $agree ? $settings['success'] : $settings['revoked'];
      $this->messenger->addStatus($this->t('@success', ['@success' => $message]));

      if ($settings['recipient'] && (!$storage['agreed'] || !$agree)) {
        // Only spam the email recipient if its the users first acceptance of
        // the agreement. If this is desired, please file an issue to discuss.
        $mail_key = $agree ? 'notice' : 'revoked';
        $this->notify($agreement, $this->account, $settings['recipient'], $mail_key);
      }
    }
    elseif (!$storage['agreed']) {
      $form_state->setErrorByName('agree', $this->t('An error occurred accepting the agreement. Please try again.'));
    }
  }

  /**
   * Notify email recipient if provided.
   *
   * @param \Drupal\agreement\Entity\Agreement $agreement
   *   The agreement entity.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account.
   * @param string $mail
   *   The email recipient.
   * @param string $key
   *   The mail key to use.
   */
  protected function notify(Agreement $agreement, AccountInterface $account, $mail = '', $key = 'notice') {
    if ($mail) {
      $params = [
        'context' => ['agreement' => $agreement],
        'account' => $account,
      ];
      $this->mailManager->mail('agreement', $key, $mail, $account->getPreferredAdminLangcode(), $params);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('agreement.handler'),
      $container->get('current_route_match'),
      $container->get('language_manager'),
      $container->get('current_user'),
      $container->get('messenger'),
      $container->get('plugin.manager.mail')
    );
  }

}
