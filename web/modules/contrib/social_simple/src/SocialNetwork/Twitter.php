<?php

namespace Drupal\social_simple\SocialNetwork;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\node\NodeInterface;

/**
 * The social network Twitter.
 */
class Twitter implements SocialNetworkInterface {

  use StringTranslationTrait;

  /**
   * The social network base share link.
   */
  const TWITTER_URL = 'https://twitter.com/intent/tweet/';

  /**
   * {@inheritdoc}
   */
  public function getId() {
    return 'twitter';
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return $this->t('Twitter');
  }

  /**
   * {@inheritdoc}
   */
  public function getShareLink($share_url, $title = '', EntityInterface $entity = NULL, array $additional_options = []) {
    $options = [
      'query' => [
        'url' => $share_url,
        'text' => $title,
      ],
      'absolute' => TRUE,
      'external' => TRUE,
    ];

    // Get hashtags if set on the entity.
    if ($entity instanceof ContentEntityInterface) {
      $additional_options += $this->getHashtags($entity);
    }

    if ($additional_options) {
      foreach ($additional_options as $id => $value) {
        $options['query'][$id] = $value;
      }
    }

    $url = Url::fromUri(self::TWITTER_URL, $options);
    $link = [
      'url' => $url,
      'title' => ['#markup' => '<i class="fa fa-twitter"></i><span class="visually-hidden">' . $this->getLabel() . '</span>'],
      'attributes' => $this->getLinkAttributes($this->getLabel()),
    ];

    return $link;
  }

  /**
   * {@inheritdoc}
   */
  public function getLinkAttributes($network_name) {
    $attributes = [
      'data-popup-width' => 600,
      'data-popup-height' => 300,
      'data-toggle' => 'tooltip',
      'data-placement' => 'top',
      'title' => $network_name,
    ];
    return $attributes;
  }

  /**
   * {@inheritdoc}
   */
  public function getHashtags(ContentEntityInterface $entity) {
    $options = [];

    if (!$entity instanceof NodeInterface) {
      return $options;
    }
    /** @var \Drupal\Core\Config\Entity\ConfigEntityInterface $entity_type */
    $entity_type = $entity->type->entity;
    if (!$entity_type instanceof ConfigEntityInterface) {
      return $options;
    }

    $key_value = $entity_type->getThirdPartySetting('social_simple', 'hashtags', '');
    if (!empty($key_value) && $entity->hasField($key_value)) {
      $referenced_entities = $entity->get($key_value)->referencedEntities();
      $labels = [];
      /** @var \Drupal\Core\Entity\EntityInterface $referenced_entity */
      foreach ($referenced_entities as $referenced_entity) {
        $labels[] = preg_replace('/\s+/', '', $referenced_entity->label());
      }

      if ($labels) {
        $options['hashtags'] = implode(',', $labels);
      }
    }

    return $options;
  }

}
