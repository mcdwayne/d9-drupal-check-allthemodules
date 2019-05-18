<?php

namespace Drupal\inmail_collect\Plugin\inmail\Handler;

use Drupal\collect\Entity\Container;
use Drupal\Core\Url;
use Drupal\inmail\MIME\MimeMessageInterface;
use Drupal\inmail\Plugin\inmail\Handler\HandlerBase;
use Drupal\inmail\ProcessorResultInterface;

/**
 * Stores messages in Collect containers.
 *
 * The inmail collect schema is considered unstable.
 *
 * @ingroup handler
 *
 * @Handler(
 *   id = "collect",
 *   label = @Translation("Collect messages")
 * )
 */
class CollectHandler extends HandlerBase {

  /**
   * {@inheritdoc}
   */
  public function help() {
    return array(
      '#markup' => $this->t('The Collect handler stores all messages.'),
    );
  }

  /**
   * The URI defining the Inmail message schema.
   */
  const SCHEMA_URI = 'https://www.drupal.org/project/inmail/schema/message';

  /**
   * URI base for the origin URI.
   */
  const ORIGIN_URI_BASE = 'base:inmail/message';

  /**
   * {@inheritdoc}
   */
  public function invoke(MimeMessageInterface $message, ProcessorResultInterface $processor_result) {
    // For successful processing, a message needs to follow the standards.
    // Some aspects are critical. Check them and cancel otherwise and log.
    if (!$message->getReceivedDate() || !$message->getFrom() || $message->getSubject() === NULL) {
      \Drupal::logger('inmail')->info('Not creating container from message missing necessary header fields.');
      return;
    }

    // By RFC 5322 (and its predecessors), the uniqueness of the Message-Id
    // field is guaranteed by the host that generates it. While uuid offers
    // more robust uniqueness, Message-Id is preferred because it is defined
    // also outside the domains of Inmail and Collect.
    // Remove brackets from RFC822 message-id format "<" addr-spec ">"
    $message_id = trim($message->getMessageId(), '<>');

    if (!empty($message_id)) {
      // @todo Formally document this uri pattern.
      $origin_uri = Url::fromUri(static::ORIGIN_URI_BASE . '/message-id/'
        . $message_id, ['absolute' => TRUE])->toString();
    }
    else {
      // @todo Formally document this uri pattern.
      $origin_uri = Url::fromUri(static::ORIGIN_URI_BASE . '/uuid/'
        . \Drupal::service('uuid')->generate(), ['absolute' => TRUE])->toString();
    }

    // Decode "@", "=", "+" characters as they are allowed in URLs.
    $origin_uri = str_replace(['%40', '%3D', '%2B'], ['@', '=', '+'], $origin_uri);

    // The data to store. Includes the whole message string for completeness,
    // and a few regular and useful header fields.
    $data = array(
      // Note the Subject field is optional by RFC882.
      'header-subject' => $message->getSubject(),
      'header-to' => $message->getTo()[0],
      'header-from' => $message->getFrom(),
      'header-message-id' => $message->getMessageId(),
      'deliverer' => $processor_result->getDeliverer()->id(),
      'raw' => $message->toString(),
    );

    // Handling json encoding with binary data/invalid UTF-8.
    // If the json_encode fails abort operation, otherwise continue.
    $json = json_encode($data);
    if (!$json) {
      $processor_result->log('CollectHandler', 'Failed json encode with given data');
      return;
    }

    Container::create(array(
      'origin_uri' => $origin_uri,
      // @todo Formally document this schema with present fields.
      'schema_uri' => static::SCHEMA_URI,
      'type' => 'application/json',
      'data' => $json,
      'date' => $message->getReceivedDate()->getTimestamp(),
    ))->save();
  }
}
