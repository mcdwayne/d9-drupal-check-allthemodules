<?php

namespace Drupal\recipe\Plugin\migrate\source\recipe713;

use Drupal\Core\Database\Query\SelectInterface;
use Drupal\migrate\Row;
use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;

/**
 * Drupal 7 recipe source from database.
 *
 * @MigrateSource(
 *   id = "recipe713_recipe",
 *   source_module = "recipe"
 * )
 */
class Recipe extends DrupalSqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('recipe', 'r')
      ->fields('r')
      ->orderBy('r.nid');
    $query->join('node', 'n','r.nid = n.nid');
    $query->fields('n', ['tnid', 'language']);
    $this->handleTranslations($query);
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    // Select the ingredient reference data and add it to the row.
    $query = $this->select('recipe_node_ingredient', 'i')
      ->fields('i')
      ->condition('nid', $row->getSourceProperty('nid'))
      ->orderBy('weight', 'ASC');
    $results = $query->execute();
    $ingredients = [];
    foreach ($results as $result) {
      $ingredients[] = $result;
    }
    $row->setSourceProperty('ingredients', $ingredients);

    // Make sure we always have a translation set.
    if ($row->getSourceProperty('tnid') == 0) {
      $row->setSourceProperty('tnid', $row->getSourceProperty('nid'));
    }

    return parent::prepareRow($row);
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'nid' => $this->t('Recipe node ID'),
      'tnid' => $this->t('The translation node ID'),
      'source' => $this->t('Recipe source'),
      'yield' => $this->t('Recipe yield amount'),
      'yield_unit' => $this->t('Units of the recipe yield'),
      'description' => $this->t('Recipe description'),
      'instructions' => $this->t('Recipe instructions'),
      'notes' => $this->t('Recipe notes'),
      'preptime' => $this->t('Recipe preparation time'),
      'cooktime' => $this->t('Recipe cook time'),
      'ingredients' => $this->t('Recipe ingredients, measures, and notes'),
      'language' => $this->t('Node language'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return ['nid' => ['type' => 'integer', 'alias' => 'r']];
  }

  /**
   * Adapt our query for translations.
   *
   * @param \Drupal\Core\Database\Query\SelectInterface $query
   *   The generated query.
   */
  protected function handleTranslations(SelectInterface $query) {
    // Check whether or not we want translations.
    if (empty($this->configuration['translations'])) {
      // No translations: Yield untranslated nodes, or default translations.
      $query->where('n.tnid = 0 OR n.tnid = n.nid');
    }
    else {
      // Translations: Yield only non-default translations.
      $query->where('n.tnid <> 0 AND n.tnid <> n.nid');
    }
  }

}
