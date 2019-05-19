<?php

namespace Drupal\webform_digests;

use Drupal\webform_digests\Entity\WebformDigestInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Utility\Token;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Language\LanguageManagerInterface;

/**
 * Handler for sending out digest messages.
 */
class WebformDigestsMailHandler implements WebformDigestsMailHandlerInterface {

  /**
   * The drupal mail manager.
   *
   * @var MailManagerInterface
   */
  protected $mailManager;

  /**
   * @var Drupal\Core\Language\LanguageManagerInterface
   */
  private $languageManager;

  /**
   * The drupal token utility.
   *
   * @var Token
   */
  protected $token;

  /**
   * Get an instance of the webform digest mail handler.
   */
  public function __construct(MailManagerInterface $mail_manager, Token $token, LanguageManagerInterface $language_manager) {
    $this->mailManager = $mail_manager;
    $this->token = $token;
    $this->languageManager = $language_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function sendMessage(WebformDigestInterface $webformDigest, EntityInterface $entity) {
    $params = $message = [];

    $tokenData = [
      'webform_digest' => $webformDigest,
      $entity->getEntityTypeId() => $entity,
    ];

    $current_langcode = $this->languageManager->getCurrentLanguage()->getId();

    $params['to'] = $this->token->replace($webformDigest->getRecipient(), $tokenData);
    $params['from'] = $this->token->replace($webformDigest->getOriginator(), $tokenData);
    $message['subject'] = $this->token->replace($webformDigest->getSubject(), $tokenData);
    $message['body'] = $this->token->replace($webformDigest->getBody(), $tokenData);

    $this->mailManager->mail('webform_digests', 'digest', $params['to'], $current_langcode, $message, $params['from']);
  }

}
