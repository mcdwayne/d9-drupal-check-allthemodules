<?php

namespace Drupal\client_config_care;

use Drupal\client_config_care\Entity\ConfigBlockerEntity;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines a class to build a listing of Config blocker entity entities.
 *
 * @ingroup client_config_care
 */
class ConfigBlockerEntityListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['name'] = $this->t('Name');
    $header['id'] = $this->t('Entity ID');
    $header['user_operation'] = $this->t('User operation');
    $header['created_by'] = $this->t('Created by');
    $header['changed_by'] = $this->t('Changed by');
    $header['changed'] = $this->t('Changed date');
    $header['created'] = $this->t('Created date');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity ConfigBlockerEntity */
    $row['name'] = $entity->label();
    $row['id'] = $entity->id();
    $row['user_operation'] = $entity->getUserOperation();
    $row['created_by'] = ($entity->getOwner() instanceof AccountInterface) ? $entity->getOwner()->getDisplayName() : null;
    $row['changed_by'] = ($entity->getRevisionUser() instanceof AccountInterface) ? $entity->getRevisionUser()->getDisplayName() : null;
    $row['changed'] = \Drupal::service('date.formatter')->format($entity->getRevisionCreationTime(), 'long');
    $row['created'] = \Drupal::service('date.formatter')->format($entity->getCreatedTime(), 'long');
    return $row + parent::buildRow($entity);
  }

	/**
	 * {@inheritdoc}
	 */
	protected function getDefaultOperations(EntityInterface $entity) {
		$operations = [];

		if ($entity->access('delete') && $entity->hasLinkTemplate('delete-form')) {
      $operations['delete'] = [
        'title'  => $this->t('Delete'),
        'weight' => 1,
        'url'    => $this->ensureDestination($entity->toUrl('delete-form')),
      ];
		}

		if ($entity->hasLinkTemplate('revision')) {
			$operations['revision'] = [
				'title'  => $this->t('Revision'),
				'weight' => 2,
				'url'    => $this->ensureDestination($entity->toUrl('version-history')),
			];
		}

		return $operations;
	}

}
