<?php

namespace Drupal\chatbot;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Routing\LinkGeneratorTrait;
use Drupal\Core\Url;

/**
 * Defines a class to build a listing of Message entities.
 *
 * @ingroup chatbot
 */
class MessageListBuilder extends EntityListBuilder {

  use LinkGeneratorTrait;

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('Message ID');
    $header['name'] = $this->t('Name');
    $header['type'] = $this->t('Message Type');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\chatbot\Entity\Message */
    $row['id'] = $entity->id();
    $row['name'] = $this->l(
      $entity->label(),
      new Url(
        'entity.chatbot_message.edit_form', [
          'chatbot_message' => $entity->id(),
        ]
      )
    );
    $row['type'] = $entity->getType();
    return $row + parent::buildRow($entity);
  }

}
