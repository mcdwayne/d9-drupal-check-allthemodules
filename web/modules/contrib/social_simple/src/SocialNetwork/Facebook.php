<?php

namespace Drupal\social_simple\SocialNetwork;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;

/**
 * The social network Facebook.
 */
class Facebook implements SocialNetworkInterface {

  use StringTranslationTrait;

  /**
   * The social network base share link.
   */
  const FACEBOOK_URL = 'https://www.facebook.com/sharer/sharer.php';

  /**
   * {@inheritdoc}
   */
  public function getId() {
    return 'facebook';
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return $this->t('Facebook');
  }

  /**
   * {@inheritdoc}
   */
  public function getShareLink($share_url, $title = '', EntityInterface $entity = NULL, array $additional_options = []) {
    $options = [
      'query' => [
        'u' => $share_url,
      ],
      'absolute' => TRUE,
      'external' => TRUE,
    ];

    if ($additional_options) {
      foreach ($additional_options as $id => $value) {
        $options['query'][$id] = $value;
      }
    }

    $url = Url::fromUri(self::FACEBOOK_URL, $options);
    $link = [
      'url' => $url,
      'title' => ['#markup' => '<i class="fa fa-facebook"></i><span class="visually-hidden">' . $this->getLabel() . '</span>'],
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

}
