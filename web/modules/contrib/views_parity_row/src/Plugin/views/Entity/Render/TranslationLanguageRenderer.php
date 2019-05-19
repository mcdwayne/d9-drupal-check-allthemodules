<?php

declare(strict_types = 1);

namespace Drupal\views_parity_row\Plugin\views\Entity\Render;

use Drupal\views\Plugin\views\query\QueryPluginBase;
use Drupal\views\ResultRow;

/**
 * Renders entities in the current language.
 */
class TranslationLanguageRenderer extends RendererBase {

  /**
   * Stores the field alias of the langcode column.
   *
   * @var string
   */
  protected $langcodeAlias;

  /**
   * {@inheritdoc}
   */
  public function getLangcode(ResultRow $row) {
    return isset($row->{$this->langcodeAlias}) ?
      $row->{$this->langcodeAlias} :
      $this->languageManager->getDefaultLanguage()->getId();
  }

  /**
   * {@inheritdoc}
   */
  public function query(QueryPluginBase $query, $relationship = NULL) {
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $row) {
    $entity_id = $row->_entity->id();

    return $this->build[$entity_id];
  }

}
