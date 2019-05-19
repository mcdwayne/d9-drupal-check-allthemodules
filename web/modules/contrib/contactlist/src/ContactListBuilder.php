<?php

namespace Drupal\contactlist;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

class ContactListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    return [
      'name' => $this->t('Full name'),
      'email' => $this->t('Email'),
      'telephone' => $this->t('Telephone'),
      'group' => $this->t('Groups'),
    ] + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $contact) {
    return [
      'name' => $contact->getContactName(),
      'email' => $contact->getEmail(),
      'telephone' => $contact->getPhoneNumber(),
      'groups' => ContactGroupHelper::viewAsTags($contact->getGroups()),
    ] + parent::buildRow($contact);
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOperations(EntityInterface $entity) {
    $operations = parent::getDefaultOperations($entity);
    if (isset($operations['edit']) && $entity->hasLinkTemplate('collection')) {
      $operations['edit']['query']['destination'] = $entity->toUrl('collection')->toString();
    }
    if ($entity->access('view') && $entity->hasLinkTemplate('canonical')) {
      $operations['view'] = array(
        'title' => $this->t('View'),
        'weight' => 1,
        'url' => $entity->toUrl('canonical'),
      );
    }
    return $operations;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityIds() {
    // Limit to only contacts of the current user.
    $query = $this->getStorage()->getQuery();
    $keys = $this->entityType->getKeys();
    return $query
      ->sort($keys['id'])
      ->condition('owner', \Drupal::currentUser()->id())
      ->pager($this->limit)
      ->execute();
  }

}
