<?php

namespace Drupal\social_simple\SocialNetwork;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;

/**
 * The social network Google+.
 */
class GooglePlus implements SocialNetworkInterface {

  use StringTranslationTrait;

  /**
   * The social network base share link.
   */
  const GOOGLEPLUS_URL = 'https://plus.google.com/share';

  /**
   * {@inheritdoc}
   */
  public function getId() {
    return 'googleplus';
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return $this->t('Google+');
  }

  /**
   * {@inheritdoc}
   */
  public function getShareLink($share_url, $title = '', EntityInterface $entity = NULL, array $additional_options = []) {
    $options = [
      'query' => [
        'url' => $share_url,
      ],
      'absolute' => TRUE,
      'external' => TRUE,
    ];

    if ($additional_options) {
      foreach ($additional_options as $id => $value) {
        $options['query'][$id] = $value;
      }
    }

    $url = Url::fromUri(self::GOOGLEPLUS_URL, $options);
    $link = [
      'url' => $url,
      'title' => ['#markup' => '<i class="fa fa-google-plus"></i><span class="visually-hidden">' . $this->getLabel() . '</span>'],
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
