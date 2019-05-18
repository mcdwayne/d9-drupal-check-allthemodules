<?php

namespace Drupal\acquia_contenthub\EventSubscriber\LoadLocalEntity;

use Drupal\acquia_contenthub\AcquiaContentHubEvents;
use Drupal\acquia_contenthub\Event\LoadLocalEntityEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class LoadUserByNameOrMail.
 *
 * Loads a local user entity by name/mail.
 *
 * @package Drupal\acquia_contenthub\EventSubscriber\LoadUserByNameOrMail
 */
class LoadUserByNameOrMail implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[AcquiaContentHubEvents::LOAD_LOCAL_ENTITY][] = ['onLoadLocalEntity', 5];
    return $events;
  }

  /**
   * Loads local users for editing by username/mail or just mail.
   *
   * When no user of the same uuid is locally found, this method will attempt
   * to identify local existing users as candidates for matching to the
   * importing user. User are first checked by username/email match and failing
   * that will revert to just email.
   *
   * @param \Drupal\acquia_contenthub\Event\LoadLocalEntityEvent $event
   *   The local entity loading event.
   *
   * @throws \Exception
   */
  public function onLoadLocalEntity(LoadLocalEntityEvent $event) {
    $cdf = $event->getCdf();
    if ($cdf->getType() !== 'drupal8_content_entity') {
      return;
    }
    $attribute = $cdf->getAttribute('entity_type');
    // We only care about user entities.
    if (!$attribute || $attribute->getValue()['und'] !== 'user') {
      return;
    }
    // Don't do anything with anonymous users.
    if ($anonymous = $event->getCdf()->getAttribute('is_anonymous')) {
      return;
    }
    $mail_attribute = $cdf->getAttribute('mail');
    if (!$mail_attribute) {
      return;
    }
    $mail = $mail_attribute->getValue()['und'];
    /** @var \Drupal\user\UserInterface $account */
    $account = user_load_by_mail($mail);
    if ($account) {
      $event->setEntity($account);
      $event->stopPropagation();
      return;
    }
  }

}
