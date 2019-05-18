<?php

namespace Drupal\ansible;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Defines a class to build a listing of Ansible entity entities.
 *
 * @ingroup ansible
 */
class AnsibleEntityListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('Ansible entity ID');
    $header['name'] = $this->t('Name');
    $header['playbook'] = $this->t('playbook');
    $header['tags'] = $this->t('tags');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\ansible\Entity\AnsibleEntity */
    $row['id'] = $entity->id();
    $row['name'] = Link::createFromRoute(
      $entity->label(),
      'entity.ansible_entity.edit_form',
      ['ansible_entity' => $entity->id()]
    );
    $row['playbook'] = \Drupal::entityTypeManager()->getStorage('ansible_entity')->load($entity->id())->playbook->value;
    $row['tags'] = \Drupal::entityTypeManager()->getStorage('ansible_entity')->load($entity->id())->tags->value;
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOperations(EntityInterface $entity) {
    $operations = [];

    $operations['edit'] = [
      'title' => $this->t('Edit'),
      'weight' => 10,
      'url' => $entity->toUrl('edit-form'),
    ];

    $operations['delete'] = [
      'title' => $this->t('Delete'),
      'weight' => 100,
      'url' => $entity->toUrl('delete-form'),
    ];
    $operations['run'] = [
      'title' => $this->t('Run playbook'),
      'weight' => 100,
      'url' => Url::fromRoute("ansible.Render", ['id' => $entity->id()]),
    ];

    return $operations;
  }

}
