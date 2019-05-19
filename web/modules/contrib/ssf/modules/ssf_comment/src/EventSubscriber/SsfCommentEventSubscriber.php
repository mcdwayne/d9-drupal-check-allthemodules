<?php

namespace Drupal\ssf_comment\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\ssf\Event\SsfRatingEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Render\RenderContext;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * SsfCommentEventSubscriber class.
 */
class SsfCommentEventSubscriber implements EventSubscriberInterface {

  use StringTranslationTrait;

  /**
   * The ssf_comment configuration.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * The site configuration.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $siteConfig;

  /**
   * The mail manager.
   *
   * @var \Drupal\Core\Mail\MailManager
   */
  protected $mailManager;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\Renderer
   */
  protected $renderer;

  /**
   * A current user instance.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * SsfCommentEventSubscriber constructor.
   *
   * @param ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param MailManagerInterface $mail_manager
   *   The mail manager.
   * @param RendererInterface $renderer
   *   The renderer.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    MailManagerInterface $mail_manager,
    RendererInterface $renderer,
    AccountProxyInterface $current_user,
    LanguageManagerInterface $language_manager
  ) {
    $this->config = $config_factory->get('ssf_comment.settings');
    $this->siteConfig = $config_factory->get('system.site');
    $this->mailManager = $mail_manager;
    $this->renderer = $renderer;
    $this->currentUser = $current_user;
    $this->languageManager = $language_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = [
      SsfRatingEvent::SSF_RATING => ['onRating', -100],
    ];

    return $events;
  }

  /**
   * Send a notification on rating of content.
   *
   * @param \Drupal\ssf\Event\SsfRatingEvent $event
   *   The event.
   */
  public function onRating(SsfRatingEvent $event) {
    $ham_threshold = $this->config->get('ssf_comment_ham_threshold') / 100;
    $spam_threshold = $this->config->get('ssf_comment_spam_threshold') / 100;
    $notify = $this->config->get('ssf_comment_notify');
    $to = $this->config->get('ssf_comment_mail_addresses');

    $entity = $event->getEntity();
    $type = $event->getType();
    $rating = $event->getRating();

    if (empty($to)) {
      $to = $this->siteConfig->get('mail');
    }

    // Do not send a notification when the content is not a comment or
    // the settings do not allow it.
    if (empty($to) || !$notify || $type != 'comment') {
      return;
    }

    // Do not send a notification when the comment has been classified as spam.
    if ($rating > $spam_threshold) {
       return;
    }

    // Do not send a notification that has been auto-approved.
    $has_permission = $this->currentUser->hasPermission('skip comment approval');
    if ($rating < $ham_threshold && $has_permission) {
      return;
    }

    $this->sendMail($entity, $to);
  }

  /**
   * Send the notification by mail.
   *
   * @param \Drupal\comment\Entity\Comment $comment
   *   The comment waiting for approval.
   * @param string $to
   *   E-mail addresses.
   */
  protected function sendMail($comment, $to) {
    $site = $this->siteConfig->get('name');
    $params = [
      'headers' => [
        'Content-Type' => 'text/html; charset=UTF-8',
        'Content-Transfer-Encoding' => '8bit',
      ],
      'from' => $this->siteConfig->get('mail'),
      'subject' => $this->t('A comment is waiting for approval (@title) on @site',
        ['@title' => $comment->getSubject(), '@site' => $site]),
    ];
    $build = [
      '#theme' => 'ssf_comment_approve',
      '#comment' => $comment,
      '#body' => $comment->get('comment_body')->value,
    ];
    $params['body'] = $this->renderer->executeInRenderContext(new RenderContext(),
      function () use ($build) {
        return $this->renderer->render($build);
      }
    );
    $langcode = $this->languageManager->getDefaultLanguage()->getId();
    $this->mailManager->mail('ssf_comment', 'approval', $to, $langcode, $params);
  }
}
