<?php

namespace Drupal\mail;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of Mail message entities.
 */
class MailMessageListBuilder extends ConfigEntityListBuilder {

  /**
   * The group name to filter the listing by.
   */
  protected $group;

  /**
   * A redirect array to set on operations' urls.
   *
   * This allows instances of this builder that are used with
   * MailMessageGroupListController to redirect to themselves.
   */
  protected $redirect;

  /**
   * Set the group to limit this list by.
   *
   * @param string $message_group_name
   *  A message group name. This is an arbitrary string, and will filter the
   *  entities shown by their 'group' property.
   */
  public function setGroup($message_group_name) {
    $this->group = $message_group_name;
  }

  /**
   * Set the redirect query to use for the operations.
   *
   * @param $redirect
   *  A redirect array, as given by getDestinationArray().
   */
  public function setRedirect($redirect) {
    $this->redirect = $redirect;
  }

  /**
   * Loads entity IDs using a pager sorted by the entity id.
   *
   * @return array
   *   An array of entity IDs.
   */
  protected function getEntityIds() {
    $query = $this->getStorage()->getQuery()
      ->sort($this->entityType->getKey('id'));

    if (isset($this->group)) {
      $query->condition('group', $this->group);
    }

    // Only add the pager if a limit is specified.
    if ($this->limit) {
      $query->pager($this->limit);
    }
    return $query->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Mail message');
    $header['id'] = $this->t('Machine name');

    if (empty($this->group)) {
      $header['group'] = $this->t('Group');
    }

    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['label'] = $entity->label();
    $row['id'] = $entity->id();

    if (empty($this->group)) {
      $row['group'] = $entity->group;
    }

    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function getOperations(EntityInterface $entity) {
    $operations = parent::getOperations($entity);

    if (!empty($this->redirect)) {
      foreach ($operations as $operation) {
        $operation['url']->setOption('query', $this->redirect);
      }
    }

    return $operations;
  }

}
