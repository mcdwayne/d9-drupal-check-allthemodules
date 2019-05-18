<?php

namespace Drupal\mattermost_integration;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;

/**
 * Builds a listing for the Outgoing Webhook entities.
 *
 * @package Drupal\mattermost_integration
 */
class OutgoingWebhookListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Label');
    $header['channel_id'] = $this->t('Channel ID');
    $header['webhook_token'] = $this->t('Webhook Token');
    $header['content_type'] = $this->t('Content type');
    $header['comment_type'] = $this->t('Comment type');
    $header['comment_field'] = $this->t('Comment field');

    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var $entity \Drupal\mattermost_integration\Entity\OutgoingWebhook */
    $row['label'] = $entity->label();
    $row['channel_id'] = $entity->getChannelId();
    $row['webhook_token'] = $entity->getWebhookToken();
    $row['content_type'] = $entity->getContentType();
    $row['comment_type'] = $entity->getCommentType();
    $row['comment_field'] = $entity->getCommentField();

    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build = parent::render();

    $build['table']['#empty'] = $this->t('No Outgoing Webhooks available. <a href="@outgoing-webhook-add-form">Add an Outgoing Webhook</a>', [
      '@outgoing-webhook-add-form' => Url::fromRoute('entity.outgoing_webhook.add_form')->toString(),
    ]);

    return $build;
  }

}
