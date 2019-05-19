<?php

namespace Drupal\social_simple\SocialNetwork;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;

/**
 * The Mail button.
 */
class Mail implements SocialNetworkInterface {

  use StringTranslationTrait;

  /**
   * The social network base share link.
   */
  const MAIL = 'mailto:';

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  public function __construct(ModuleHandlerInterface $moduleHandler) {
    $this->moduleHandler = $moduleHandler;
  }

  /**
   * {@inheritdoc}
   */
  public function getId() {
    return 'mail';
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return $this->t('Mail');
  }

  /**
   * {@inheritdoc}
   */
  public function getShareLink($share_url, $title = '', EntityInterface $entity = NULL, array $additional_options = []) {
    $options = [
      'query' => [
        'body' => PHP_EOL . $title . PHP_EOL . $share_url,
        'subject' => $title,
      ],
      'absolute' => TRUE,
      'external' => TRUE,
    ];

    if ($additional_options) {
      foreach ($additional_options as $id => $value) {
        $options['query'][$id] = $value;
      }
    }

    if ($entity && $this->checkForwardIntegration($entity)) {
      $url = Url::fromRoute('forward.form', ['entity_type' => $entity->getEntityTypeId(), 'entity' => $entity->id()]);
    }
    else {
      $url = Url::fromUri(self::MAIL, $options);
    }

    $link = [
      'url' => $url,
      'title' => ['#markup' => '<i class="fa fa-envelope"></i><span class="visually-hidden">' . $this->getLabel() . '</span>'],
      'attributes' => $this->getLinkAttributes($this->getLabel()),
    ];

    return $link;
  }

  /**
   * {@inheritdoc}
   */
  public function getLinkAttributes($network_name) {
    $attributes = [
      'title' => $network_name,
      'data-popup-open' => 'false',
    ];
    return $attributes;
  }

  /**
   * Check if the mail button should use the forward module.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity which will be shared.
   *
   * @return boolean
   *   True if the Mail button should use the Forward module.
   */
  protected function checkForwardIntegration(EntityInterface $entity) {
    if (!$this->moduleHandler->moduleExists('forward')) {
      return FALSE;
    }
    /** @var \Drupal\Core\Config\Entity\ConfigEntityInterface $entity_type */
    $entity_type = $entity->type->entity;
    if (!$entity_type instanceof ConfigEntityInterface) {
      return FALSE;
    }
    return $entity_type->getThirdPartySetting('social_simple', 'forward_integration', FALSE);
  }

}
