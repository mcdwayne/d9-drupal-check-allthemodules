<?php


namespace Drupal\user_lock;


use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\Element\EntityAutocomplete;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\TypedData\Type\DateTimeInterface;
use Drupal\user\Entity\User;

/**
 * Provides a listing of User lock entities.
 */
class UserLockConfigEntityListBuilder extends ConfigEntityListBuilder {
  /**
   * {@inheritdoc}
   */
  public function buildHeader(){
    $header['label'] = $this->t('User lock');
    $header['id'] = $this->t('Machine name');
    $header['user'] = $this->t('User');
    $header['lock_period_from'] = $this->t('Lock Period From');
    $header['lock_period_to'] = $this->t('Lock Period To');
    $header['redirect_url'] = $this->t('Redirect URL');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    if($entity->get_lock_period_from()) {
      $default_lock_from = DrupalDateTime::createFromTimestamp($entity->get_lock_period_from());
    }
    else {
      $default_lock_from = 'N/A';
    }
    if($entity->get_lock_period_to()) {
      $default_lock_to = DrupalDateTime::createFromTimestamp($entity->get_lock_period_to());
    }
    else {
      $default_lock_to = 'N/A';
    }
    $user_values = $entity->get_user();
    $uids = explode(',', $user_values);
    $default_users = User::loadMultiple($uids);
    $default_value = EntityAutocomplete::getEntityLabels($default_users);
    $row['label'] = $entity->label();
    $row['id'] = $entity->id();
    $row['user'] = $default_value;
    $row['lock_period_from'] = $default_lock_from;
    $row['lock_period_to'] = $default_lock_to;
    $row['redirect_url'] = $entity->get_redirect_url();
    return $row + parent::buildRow($entity);
  }

}
