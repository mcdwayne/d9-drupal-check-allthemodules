<?php

namespace Drupal\gtm_datalayer;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Utility\Token;

/**
 * Provides a base class for a GTM dataLayer Metatags renderer.
 */
class MetatagsRenderer implements RendererInterface {

  /**
   * The entity object that owns the tags.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  private $entity;

  /**
   * The token service.
   *
   * @var \Drupal\Core\Utility\Token
   */
  private $token;

  /**
   * The values used to extract the tags.
   *
   * @var array
   */
  private $values = [];

  /**
   * Constructs a MetatagsRenderer object.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity object that owns the tags.
   * @param \Drupal\Core\Utility\Token $token
   *   The token service.
   */
  function __construct(EntityInterface $entity, Token $token) {
    $this->entity = $entity;
    $this->token = $token;
  }

  /**
   * {@inheritdoc}
   */
  public function getValues() {
    return $this->values;
  }

  /**
   * {@inheritdoc}
   */
  public function setValues(array $values) {
    $this->values = $values;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function render(array $values) {
    $tags = [];
    foreach ($values as $key => $value) {
      // Replace tokens.
      $values[$key] = $this->token->replace($value, [$this->entity->getEntityTypeId() => $this->entity]);

      // Check if 'comma-separated list' value.
      // @todo Try to do this magically.
      if (in_array($key, ['article_tag', 'fb_app_id', 'fb_admins', 'keywords', 'robots', 'news_keywords'])) {
        $values[$key] = array_map('trim', explode(',', $values[$key]));
      }

      $tags[$key] = $values[$key];
    }

    $this->setValues($values);

    return $tags;
  }

}
