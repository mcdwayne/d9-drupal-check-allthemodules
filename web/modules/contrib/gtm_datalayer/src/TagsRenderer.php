<?php

namespace Drupal\gtm_datalayer;

/**
 * Provides a base class for a GTM dataLayer Tags renderer.
 */
class TagsRenderer implements RendererInterface {

  /**
   * The values used to extract the tags.
   *
   * @var array
   */
  private $values = [];

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
    $this->setValues($values);

    $tags = [];
    foreach ($values as $term) {
      $tags[$term->id()] = $term->label();
    }

    return $tags;
  }

}
