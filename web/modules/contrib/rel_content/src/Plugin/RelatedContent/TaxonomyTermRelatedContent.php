<?php

namespace Drupal\rel_content\Plugin\RelatedContent;

use Drupal\plugin_type_example\SandwichBase;
use Drupal\rel_content\Annotation\RelatedContent;
use Drupal\rel_content\Plugin\views\filter\RelatedContentFilter;
use Drupal\rel_content\RelatedContentBase;
use Drupal\views\Views;

/**
 * Provides a taxonomy term related content plugin.
 *
 * @RelatedContent(
 *   id = "taxonomy_term_rel_content",
 *   description = @Translation("Related content by taxonomy terms.")
 * )
 */
class TaxonomyTermRelatedContent extends RelatedContentBase {

  /**
   * @inheritdoc
   */
  public function getOptions() {
    $options = [];

    foreach ($this->configuration['items'] as $item) {
      $field_definitions = \Drupal::entityManager()->getFieldDefinitions($item->getFieldDefinition()->getTargetEntityTypeId(), $item->getFieldDefinition()->getTargetBundle());

      foreach ($field_definitions as $definition) {
        if ('default:taxonomy_term' == $definition->getSetting('handler')) {
          $options[$definition->getName()] = 'Taxonomy: ' . $definition->getLabel();
        }
      }
    }
    return $options;
  }

  /**
   * @inheritdoc
   */
  public function viewsAlteration(RelatedContentFilter &$data) {
    $table = $data->ensureMyTable();

    $definition = array(
      'table' => 'taxonomy_index',
      'field' => 'nid',
      'left_table' => $table,
      'left_field' => 'nid',
    );

    $join = Views::pluginManager('join')->createInstance('standard', $definition);
    $data->query->addRelationship('taxonomy_index', $join, 'taxonomy_index');
    $field = $data->currentNode->get($this->configuration['id']);

    foreach ($field->getValue() as $value) {
      $data->query->addWhereExpression(0, "taxonomy_index.tid = :taxonomy_index_tid", array(':taxonomy_index_tid' => $value['target_id']));
    }
  }

}
