<?php

namespace Drupal\contactlist;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

class ContactGroupListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    return [
      'name' => $this->t('Name'),
      'description' => $this->t('Description'),
      'contacts' => $this->t('Contacts'),
    ] + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $group) {
    return [
      'name' => $group->getName(),
      'description' => $group->getDescription(),
      'contacts' => $this->formatPlural(count($group->getContacts()), '@count contact', '@count contacts'),
    ] + parent::buildRow($group);
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOperations(EntityInterface $entity) {
    $operations = parent::getDefaultOperations($entity);
    if (isset($operations['edit']) && $entity->hasLinkTemplate('collection')) {
      $operations['edit']['query']['destination'] = $entity->toUrl('collection')->toString();
    }
    return $operations;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityIds() {
    // Limit to only contacts of the current user.
    $query = $this->getStorage()->getQuery()
      ->sort($this->entityType->getKey('id'))
      ->condition('owner', \Drupal::currentUser()->id());

    // Only add the pager if a limit is specified.
    if ($this->limit) {
      $query->pager($this->limit);
    }

    return  $query->execute();
  }

}
