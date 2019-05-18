<?php

namespace Drupal\easy_email\Service;


use Drupal\Core\Render\Markup;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Utility\Token;
use Drupal\easy_email\Entity\EasyEmailInterface;
use Drupal\easy_email\Event\EasyEmailEvent;
use Drupal\easy_email\Event\EasyEmailEvents;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class EmailTokenEvaluator implements EmailTokenEvaluatorInterface {

  /**
   * @var \Drupal\Core\Utility\Token
   */
  protected $token;

  /**
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * Constructs the EmailTokenEvaluator
   *
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $eventDispatcher
   * @param \Drupal\Core\Utility\Token $token
   */
  public function __construct(EventDispatcherInterface $eventDispatcher, Token $token) {
    $this->token = $token;
    $this->eventDispatcher = $eventDispatcher;
  }

  /**
   * @inheritDoc
   */
  public function evaluateTokens(EasyEmailInterface $email) {
    $this->eventDispatcher->dispatch(EasyEmailEvents::EMAIL_PRETOKENEVAL, new EasyEmailEvent($email));

    if ($email->hasField('key')) {
      $email->setKey($this->replaceTokens($email, $email->getKey()));
    }
    $email->setRecipientAddresses($this->replaceTokens($email, $email->getRecipientAddresses(), TRUE));
    $email->setCCAddresses($this->replaceTokens($email, $email->getCCAddresses(), TRUE));
    $email->setBCCAddresses($this->replaceTokens($email, $email->getBCCAddresses(), TRUE));
    $email->setFromName($this->replaceTokens($email, $email->getFromName()));
    $email->setFromAddress($this->replaceTokens($email, $email->getFromAddress()));
    $email->setReplyToAddress($this->replaceTokens($email, $email->getReplyToAddress()));
    $email->setSubject($this->replaceTokens($email, $email->getSubject()));
    if ($email->hasField('body_html')) {
      $html_body = $email->getHtmlBody();
      $email->setHtmlBody($this->replaceTokens($email, $html_body['value']), $html_body['format']);
    }
    if ($email->hasField('body_plain')) {
      $email->setPlainBody($this->replaceTokens($email, $email->getPlainBody()));
    }
    if ($email->hasField('inbox_preview')) {
      $email->setInboxPreview($this->replaceTokens($email, $email->getInboxPreview()));
    }
    if ($email->hasField('attachment_path')) {
      $email->setAttachmentPaths($this->replaceTokens($email, $email->getAttachmentPaths()));
    }

    $this->eventDispatcher->dispatch(EasyEmailEvents::EMAIL_TOKENEVAL, new EasyEmailEvent($email));
  }

  public function containsUnsafeTokens(EasyEmailInterface $email) {
    $tokens = [];
    if ($email->hasField('body_html')) {
      $html_body = $email->getHtmlBody();
      $body_tokens = $this->token->scan($html_body['value']);
      if (!empty($body_tokens['easy_email'])) {
        $tokens = array_merge($tokens, $body_tokens['easy_email']);
      }
    }
    if ($email->hasField('body_plain')) {
      $body_tokens = $this->token->scan($email->getPlainBody());
      if (!empty($body_tokens['easy_email'])) {
        $tokens = array_merge($tokens, $body_tokens['easy_email']);
      }
    }
    foreach($tokens as $token) {
      if (preg_match('/:one-time-login-url\]$/', $token) || preg_match('/:cancel-url\]$/', $token)) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * @return array
   */
  protected function unsafeTokens() {
    return [
      'one-time-login-url',
      'cancel-url'
    ];
  }

  /**
   * @inheritDoc
   */
  public function replaceTokens(EasyEmailInterface $email, $values, $unique = FALSE) {
    if (is_array($values)) {
      $replaced = [];
      foreach ($values as $key => $value) {
        $replaced[$key] = $this->token->replace($value, ['easy_email' => $email]);
      }
      if ($unique) {
        $replaced = array_unique($replaced);
      }
      return $replaced;
    }
    return $this->token->replace($values, ['easy_email' => $email]);
  }


  /**
   * @inheritDoc
   */
  public function replaceUnsafeTokens($text, AccountInterface $recipient) {
    $unsafe_tokens = $this->unsafeTokens();
    $tokens = $this->token->scan($text);
    if (!empty($tokens['easy_email'])) {
      foreach ($tokens['easy_email'] as $token => $full_token) {
        $token_parts = explode(':', $token);
        $final_token = array_pop($token_parts);
        if (in_array($final_token, $unsafe_tokens)) {
          $text = str_replace($full_token, '[user:' . $final_token . ']', $text);
        }
      }
    }
    return $this->token->replace($text, ['user' => $recipient], ['callback' => 'user_mail_tokens']);
  }

}