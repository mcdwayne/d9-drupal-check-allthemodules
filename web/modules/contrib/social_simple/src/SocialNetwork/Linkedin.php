<?php

namespace Drupal\social_simple\SocialNetwork;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;

/**
 * The social network Linkedin.
 */
class Linkedin implements SocialNetworkInterface {

  use StringTranslationTrait;

  /**
   * The social network base share link.
   */
  const LINKEDIN_URL = 'https://www.linkedin.com/shareArticle';

  /**
   * {@inheritdoc}
   */
  public function getId() {
    return 'linkedin';
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return $this->t('Linked in');
  }

  /**
   * {@inheritdoc}
   */
  public function getShareLink($share_url, $title = '', EntityInterface $entity = NULL, array $additional_options = []) {
    $options = [
      'query' => [
        'mini' => 'true',
        'url' => $share_url,
        'title' => $title,
      ],
      'absolute' => TRUE,
      'external' => TRUE,
    ];

    if ($additional_options) {
      foreach ($additional_options as $id => $value) {
        $options['query'][$id] = $value;
      }
    }

    $url = Url::fromUri(self::LINKEDIN_URL, $options);
    $link = [
      'url' => $url,
      'title' => ['#markup' => '<i class="fa fa-linkedin"></i><span class="visually-hidden">' . $this->getLabel() . '</span>'],
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
