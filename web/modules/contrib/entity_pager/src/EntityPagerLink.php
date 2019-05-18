<?php

namespace Drupal\entity_pager;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\TypedData\TranslatableInterface;

/**
 * A class representing a single Entity Pager link.
 */
class EntityPagerLink implements EntityPagerLinkInterface {

  use StringTranslationTrait;

  /**
   * @var \Drupal\Core\Entity\EntityInterface|NULL
   */
  var $entity;

  /**
   * @var string
   */
  var $text;

  /**
   * EntityPagerLink constructor.
   *
   * @param string $text
   *   The text of the link
   * @param \Drupal\Core\Entity\EntityInterface|NULL $entity
   *   The result row in the view to link to.
   */
  public function __construct($text, EntityInterface $entity = NULL) {
    $this->text = $text;
    $this->entity = $entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getLink() {
    if (empty($this->entity)) {
      return $this->noResult();
    }

    $langcode = \Drupal::languageManager()->getCurrentLanguage()->getId();
    $entity = $this->entity;
    if ($entity instanceof TranslatableInterface && $entity->hasTranslation($langcode)) {
      $entity = $entity->getTranslation($langcode);
    }

    return [
      '#type' => 'link',
      '#title' => ['#markup' => $this->text],
      '#url' => $entity->toUrl('canonical'),
    ];
  }

  /**
   * Returns a render array for an entity pager link with no results.
   *
   * @return array
   *   The render array for the link with no results.
   */
  protected function noResult() {
    return [
      '#type' => 'markup',
      '#markup' => '<span class="inactive">' . $this->text . '</span>',
    ];
  }
}
