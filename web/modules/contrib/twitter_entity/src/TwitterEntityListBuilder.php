<?php

namespace Drupal\twitter_entity;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Routing\LinkGeneratorTrait;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Defines a class to build a listing of Twitter entity entities.
 *
 * @ingroup twitter_entity
 */
class TwitterEntityListBuilder extends EntityListBuilder {

  use LinkGeneratorTrait;

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['date'] = $this->t('Date');
    $header['tweet_text'] = $this->t('Tweet text');
    $header['tweet_media'] = $this->t('Tweet media');
    $header['twitter_user'] = $this->t('Tweet user');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\twitter_entity\Entity\TwitterEntity */
    $row['date'] = date('Y-m-d', $entity->getCreatedTime());

    // Tweet text.
    $row['tweet_text']['data'] = ['#markup' => $entity->getTweetText()];

    // Tweet image.
    $tweetMedia = '-';
    if ($entity->getTweetMedia()) {
      $tweetMedia = [
        '#theme' => 'image',
        '#uri' => $entity->getTweetMedia(),
        '#attributes' => [
          'class' => 'tweet-image',
          'style' => 'display:block;width:100px;max-width:100%;height:auto;',
        ],
      ];
      $tweetMedia = drupal_render($tweetMedia);
    }

    $row['tweet_media'] = [
      'data' => [
        '#markup' => $tweetMedia,
      ],
      'style' => 'text-align:center;width:100px;',
    ];

    $twitterUrlAttributes = [
      'attributes' => [
        'target' => '_blank',
      ],
    ];
    $twitterUrl = Url::fromUri('http://twitter.com/' . $entity->getTwitterUser(), $twitterUrlAttributes);
    $row['twitter_user'] = Link::fromTextAndUrl('@' . $entity->getTwitterUser(), $twitterUrl);

    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityIds() {
    $query = $this->getStorage()->getQuery()
      ->sort($this->entityType->getKey('created'), 'DESC');

    // Only add the pager if a limit is specified.
    if ($this->limit) {
      $query->pager($this->limit);
    }
    return $query->execute();
  }

}
