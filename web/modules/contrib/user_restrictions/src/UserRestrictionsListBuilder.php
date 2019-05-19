<?php

namespace Drupal\user_restrictions;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Url;
use Drupal\Core\Entity\EntityInterface;
use Drupal\user_restrictions\Entity\UserRestrictions;

/**
 * Defines a class to build a listing of image style entities.
 *
 * @see \Drupal\user_restrictions\Entity\UserRestrictions
 */
class UserRestrictionsListBuilder extends ConfigEntityListBuilder {

  /**
   * The user restriction type manager.
   *
   * @var \Drupal\user_restrictions\UserRestrictionTypeManagerInterface
   */
  protected $typeManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage) {
    parent::__construct($entity_type, $storage);
    $this->typeManager = \Drupal::service('user_restrictions.type_manager');
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('User restriction');
    $header['rule_type'] = $this->t('Rule type');
    $header['pattern'] = $this->t('Pattern');
    $header['access_type'] = $this->t('Access type');
    $header['expiry'] = $this->t('Expiry');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['label'] = $entity->label();
    $row['type'] = $this->typeManager->getType($entity->getRuleType())->getLabel();
    $row['pattern'] = $entity->getPattern();
    $row['access_type'] = $entity->getAccessType() ? $this->t('Whitelisted') : $this->t('Blacklisted');
    $row['expiry'] = $entity->getExpiry() == UserRestrictions::NO_EXPIRY ? $this->t('Never') : date('Y-m-d H:i:s', $entity->getExpiry());
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build = parent::render();
    $build['table']['#empty'] = $this->t('There are currently no user restrictions. <a href=":url">Add a new one</a>.', [
      ':url' => Url::fromRoute('user_restrictions.add')->toString(),
    ]);
    return $build;
  }

}
