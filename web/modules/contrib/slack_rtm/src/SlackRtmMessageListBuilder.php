<?php

namespace Drupal\slack_rtm;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Defines a class to build a listing of Slack RTM Messages.
 *
 * @ingroup slack_rtm
 */
class SlackRtmMessageListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['created'] = $this->t('Time Posted');
    $header['channel'] = $this->t('Channel');
    $header['message_author'] = $this->t('Message Author');
    $header['message'] = $this->t('Message');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\slack_rtm\Entity\SlackRtmMessage */
    // Create the date format.
    $created = $entity->getCreatedTime();
    $date = new \DateTime();
    $timestamp = $date->setTimestamp($created);

    // Set up the message to display.
    $message = $entity->getMessage();
    $msg_len = strlen($message);
    $msg = ($msg_len >= 100) ? substr($message, 0, 100) . '...' : $message;

    // Remove external links because Drupal strips them.
    if (strpos($msg, 'http') !== FALSE) {
      $grab_link = strstr($msg, '<http');
      $msg = str_replace($grab_link, '~~External Link~~', $msg);
    }

    // Build the link.
    $link = $entity->getPermaLink();
    $link_msg = $link !== NULL
      ? Link::fromTextAndUrl(t($msg), Url::fromUri($link))->toString()
      : $msg;

    // @todo port over the date formatter from manage display.

    $row['created'] = $timestamp->format('m/d/Y H:i:s');
    $row['channel'] = $entity->getChannel();
    $row['message_author'] = $entity->getMessageAuthor();
    $row['message'] = $link_msg;
    return $row + parent::buildRow($entity);
  }

}
