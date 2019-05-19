<?php

namespace Drupal\simplenews_stats;

use Drupal\Core\Url;
use Drupal\Core\Render\Markup;
use Drupal\Core\Entity\EntityInterface;
use Drupal\simplenews\SubscriberInterface;

/**
 * Class SimplenewsStatsMail.
 */
class SimplenewsStatsMail {

  /**
   * The body of the mail.
   *
   * @var string 
   */
  protected $message;

  /**
   * The simplenews mail object.
   *
   * @var Drupal\simplenews\Mail\MailEntity
   */
  protected $simpleNewsMail;

  /**
   * The simplenews stats engine.
   *
   * @var Drupal\simplenews_stats\SimplenewsStatsEngine
   */
  protected $simplenewsStatsEngine;

  /**
   * The Allowed link manager.
   *
   * @var Drupal\simplenews_stats\SimplenewsStatsAllowedLinks
   */
  protected $simplenewsStatsAllowedLinks;

  /**
   * SimplenewsStatsMail constructor.
   *
   * @param \Drupal\simplenews_stats\SimplenewsStatsEngine $simplenewsStatsEngine
   *   The simplenews stats engine.
   * @param \Drupal\simplenews_stats\SimplenewsStatsAllowedLinks $simplenewsStatsAllowedLinks
   *   The simplenews allowed links manager.
   */
  public function __construct(SimplenewsStatsEngine $simplenewsStatsEngine, SimplenewsStatsAllowedLinks $simplenewsStatsAllowedLinks) {
    $this->simplenewsStatsEngine       = $simplenewsStatsEngine;
    $this->simplenewsStatsAllowedLinks = $simplenewsStatsAllowedLinks;
  }

  /**
   * Prepare the mail by adding to it tags and image Tracker.
   */
  public function prepareMail(&$message) {
    $this->message = &$message;

    // Store simplenews mail object.
    // @todo: use instanceof Drupal\simplenews\Mail instead of is_object
    if (!empty($message['params']['simplenews_mail']) && is_object($message['params']['simplenews_mail'])) {
      $this->simpleNewsMail = $message['params']['simplenews_mail'];
    }

    $this->addImageTracker()
      ->addTags()
      ->logHitSent();
  }

  /**
   * Get the body.
   *
   * @return Drupal\Core\Render\Markup
   *   The body markup.
   */
  protected function getBody() {
    return reset($this->message['body']);
  }

  /**
   * Return the context (Simplenews source object).
   *
   * @return SimplenewsMail
   *   The simplenews mail object used as context.
   */
  protected function getContext() {
    return $this->simpleNewsMail;
  }

  /**
   * Return the context (Simplenews source object).
   *
   * @return \Drupal\simplenews\SubscriberInterface
   *   The simplenews subscriber.
   */
  protected function getSubscriber() {
    $simpleNewsMail = $this->getContext();
    return $simpleNewsMail->getSubscriber();
  }

  /**
   * Get a tag.
   *
   * @param \Drupal\simplenews\SubscriberInterface $subscriber
   *   The simplenews subscriber.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity use as simplenews.
   *
   * @return string
   *   The tag.
   */
  protected function getTag(SubscriberInterface $subscriber, EntityInterface $entity) {
    return 'u' . $subscriber->id() . 'nl' . $entity->id();
  }

  /**
   * Return the entity from simplenews object.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The entity referenced in the simplenews mail object.
   */
  protected function getEntity() {
    $simpleNewsMail = $this->getContext();
    return $simpleNewsMail->getEntity();
  }

  /**
   * Set the Body.
   *
   * @param string $string
   *   The body string.
   */
  protected function setBody($string) {
    $this->message['body'] = [Markup::create($string)];
  }

  /**
   * Adds image tracker to the body.
   * 
   * @return static
   */
  protected function addImageTracker() {
    $simpleNewsSource = $this->getContext();
    $subscriber       = $simpleNewsSource->getSubscriber();
    $entity           = $simpleNewsSource->getEntity();

    // Dont add image if this user is not registred.
    if (!$subscriber->id()) {
      return $this;
    }

    $tag = $this->getTag($subscriber, $entity);
    $url = Url::fromRoute('simplenews_stats.hit_view')
      ->setOption('query', ['sstc' => $tag])
      ->setAbsolute();

    $image = [
      '#theme'      => 'image',
      '#attributes' => [
        'src' => $url->toString(),
      ],
    ];

    $this->setBody($this->getBody() . render($image));
    return $this;
  }

  /**
   * AddTags on every link in the mail.
   * 
   * @return static
   */
  protected function addTags() {
    $body = $this->getBody();

    // Add tags on links.
    $body = preg_replace_callback("`<a.*href=\"([a-zA-Z0-9@:%_+*~#?&=.,/;-]*[a-zA-Z0-9@:%_+*~#&?=/;-])\"`i", [$this, 'replaceLinksUrl'], $body);

    $this->setBody($body);
    return $this;
  }

  /**
   * Callback of AddTags.
   *
   * @param string $url
   *   The url to replace.
   *
   * @return string
   *   The new string.
   */
  protected function replaceLinksUrl($url) {
    $simpleNewsSource = $this->getContext();
    $subscriber       = $simpleNewsSource->getSubscriber();
    $entity           = $simpleNewsSource->getEntity();

    $external    = FALSE;
    $url_cleaned = strtolower($url[1]);

    // Escape if email is not registered.
    if (!$subscriber->id()) {
      return $url[0];
    }

    // Find Url Type.
    if (preg_match('/^https?\:\/\//', $url_cleaned)) {
      $external = TRUE;
      $url_obj  = Url::fromUri($url[1]);

      if (!$this->simplenewsStatsAllowedLinks->isLinkExist($entity, $url[1])) {
        $this->simplenewsStatsAllowedLinks->add($entity, $url[1]);
      }
      $url_obj = $this->generateExternalLink($subscriber, $entity, $url[1]);
    }
    elseif (substr($url[1], 0, 1) == '/') {
      $url_obj = Url::fromUri('internal:' . $url[1]);
    }
    else {
      $url_obj = Url::fromUri('internal:/' . $url[1]);
    }

    $tag = $this->getTag($subscriber, $entity);
    if (!$external) {
      $url_obj->setOption('query', ['sstc' => $tag]);
    }
    $url_obj->setAbsolute();

    return str_replace($url[1], $url_obj->toString(), $url[0]);
  }

  /**
   * Return a link for external link reference.
   * 
   * @param Drupal\simplenews\SubscriberInterface $subscriber
   *   The simplenews subscriber.
   * @param Drupal\Core\Entity\EntityInterface $entity
   *   The entity use as simplenews.
   * @param string $link
   *   The link to generate.
   *
   * @return string 
   *   The link.
   */
  protected function generateExternalLink(SubscriberInterface $subscriber, EntityInterface $entity, $link) {
    $params = ['tag' => $this->getTag($subscriber, $entity), 'link' => $link];

    return Url::fromRoute('simplenews_stats.hit_click', $params);
  }

  /**
   * Log sent Hit.
   */
  public function logHitSent() {
    $simpleNewsSource = $this->getContext();
    $entity           = $simpleNewsSource->getEntity();

    $this->simplenewsStatsEngine->logHitSent($this->getSubscriber(), $entity);
  }

}
