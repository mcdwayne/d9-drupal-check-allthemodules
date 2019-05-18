<?php

namespace Drupal\private_messages;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Routing\LinkGeneratorTrait;
use Drupal\Core\Url;

/**
 * Defines a class to build a listing of Message entities.
 *
 * @ingroup private_messages
 */
class MessageListBuilder extends EntityListBuilder {

  use LinkGeneratorTrait;

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('Message ID');
    $header['recipient'] = $this->t('Recipient');
    $header['sender'] = $this->t('Sender');
    $header['name'] = $this->t('Subject');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
//    $user = \Drupal::currentUser();
//    $permission = $user->hasPermission('administer messages');
//    if (!$permission) return false;
    /* @var $entity \Drupal\private_messages\Entity\Message */
    $row['id'] = $entity->id();
    //$row['recipient'] = $entity->getRecipientId();
    // $a = $entity->getDialog();
    //$row['user'] = $entity->getSenderId();
//    $row['name'] = $this->l(
//      $entity->label(),
//      new Url(
//        'entity.message.edit_form', [
//          'message' => $entity->id(),
//        ]
//      )
//    );
    return $row + parent::buildRow($entity);
  }

}
