<?php

/**
 * @file
 * Contains Drupal\push_notifications\Entity\Controller\PushNotificationsTokenListBuilder.
 */

namespace Drupal\push_notifications\Entity\Controller;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\Component\Utility\Unicode;

/**
 * Provides a list controller for push_notifications_token entity.
 *
 * @ingroup push_notifications_token
 */
class PushNotificationsTokenListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   *
   * Add token-specific messaging to table header.
   */
  public function render() {
    $build['description'] = array(
      '#markup' => $this->t('List of all tokens in the database.'),
    );

    $build['table'] = parent::render();
    $build['table']['table']['#empty'] = $this->t('There are no device tokens registered yet.');

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('ID');
    $header['owner'] = $this->t('Owner');
    $header['token'] = $this->t('Token');
    $header['network'] = $this->t('Network');
    $header['created'] = $this->t('Created');
    $header['langcode'] = $this->t('Language Code');

    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\push_notifications\Entity\PushNotificationsToken */
    $row['id'] = $entity->id();
    $row['owner']['data'] = array(
      '#theme' => 'username',
      '#account' => $entity->getOwner(),
    );
    // Link to canonical URL and truncate token after 80 characters.
    $row['token'] = Link::fromTextAndUrl(
      Unicode::truncate($entity->getToken(), 80, TRUE, TRUE),
      Url::fromRoute('entity.push_notifications_token.canonical', array('push_notifications_token' => $entity->id()))
    );
    $row['network'] = $entity->getNetwork();
    $row['created'] = $entity->getCreatedTime();
    $row['langcode'] = $entity->getLanguageCode();

    return $row + parent::buildRow($entity);
  }

}