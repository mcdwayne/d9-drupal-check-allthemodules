<?php

namespace Drupal\powertagging\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\taxonomy\Entity\Term;
use EasyRdf_Sparql_Client;

/**
 * Provides a 'PowerTaggingBlock' block plugin.
 *
 * @Block(
 *   id = "powertagging_tag_glossary_block",
 *   admin_label = @Translation("PowerTagging Tag Glossary"),
 * )
 */

class PowerTaggingTagGlossaryBlock extends BlockBase {
  /**
   * Creates a NodeBlock instance.
   *
   * @param array $configuration
   * @param string $plugin_id
   * @param array $plugin_definition
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $block = array(
      '#cache' => array(
        'max-age' => 0
      )
    );

    $entity_type = '';
    $entity_id = '';
    $current_path = \Drupal\Core\Url::fromUserInput(\Drupal::service('path.current')->getPath());
    if ($current_path->isRouted()) {
      $params = $current_path->getRouteParameters();
      foreach (array('node', 'user', 'taxonomy_term') as $current_entity_type) {
        if (isset($params[$current_entity_type])) {
          $entity_type = $current_entity_type;
          $entity_id = $params[$current_entity_type];
        }
      }
    }


    $block_html = '';
    // One of the supported entities is being displayed at the moment.
    if (!empty($entity_type)) {
      /** @var \Drupal\Core\Entity\ContentEntityBase $entity */
      $entity = \Drupal::entityTypeManager()->getStorage($entity_type)->load($entity_id);

      // Get all PowerTagging field instances of the currently viewed entity.
      $field_instances = \Drupal::entityTypeManager()
        ->getStorage('field_config')
        ->loadByProperties([
          'field_type' => 'powertagging_tags',
          'entity_type' => $entity_type,
          'bundle' => $entity->bundle(),
        ]);

      // Tag fields are available for this entity.
      if (!empty($field_instances)) {
        $tag_ids = array();
        /** @var \Drupal\field\Entity\FieldConfig $field_instance */
        foreach ($field_instances as $field_instance) {
          $field_name = $field_instance->getName();
          if ($field_instance->getSetting('include_in_tag_glossary') && $entity->hasField($field_name)) {
            foreach ($entity->get($field_name)->getValue() as $tid_value) {
              $tag_ids[] = $tid_value['target_id'];
            }
          }
        }

        // Tags are available for this entity.
        if (!empty($tag_ids)) {
          $global_config = \Drupal::config('powertagging.settings');
          $max_items = $global_config->get('tag_glossary_items_max');
          $term_counts = array();
          foreach ($field_instances as $field_instance) {
            $field_name = $field_instance->getName();
            if ($field_instance->getSetting('include_in_tag_glossary')) {
              // Get the most frequent tags.
              $term_count_query = \Drupal::database()->select($field_instance->getTargetEntityTypeId() . '__' . $field_name, 'd');
              $term_count_query->fields('d', array($field_name . '_target_id'));
              $term_count_query->condition('d.' . $field_name . '_target_id', $tag_ids, 'IN');
              $term_count_query->orderBy('count', 'DESC');

              // Terms need to have an URI --> no free terms.
              $term_count_query->join('taxonomy_term__field_uri', 'u', 'd.' . $field_name . '_target_id = u.entity_id');

              // The term needs to have a description.
              //$term_count_query->join('taxonomy_term_data', 'ttd', 'd.' . $field_name . '_tid = ttd.tid');
              //$term_count_query->condition('ttd.description', '', '<>');

              $term_count_query->groupBy('d.' . $field_name . '_target_id')
                ->addExpression('count(\'' . $field_name . '_target_id\')', 'count');

              $term_counts += $term_count_query //->range(0, $max_items)
              ->execute()
                ->fetchAllKeyed();
            }
          }
          arsort($term_counts);

          $terms = Term::loadMultiple(array_keys($term_counts));
          $potential_terms = array();
          $dbpedia_check_terms = array();
          /** @var Term $term */
          foreach ($terms as $term) {
            if (!empty($term->getDescription())) {
              $potential_terms[$term->id()] = $term;
            }
            elseif ($term->hasField('field_exact_match')) {
              foreach ($term->get('field_exact_match')->getValue() as $exact_match_value) {
                if (strpos($exact_match_value['uri'], 'http://dbpedia.org') !== FALSE) {
                  // For correct sorting (by score) it is required to add all
                  // possible terms here and remove the ones without a description
                  // in the later process.
                  $potential_terms[$term->id()] = $term;
                  $dbpedia_check_terms[trim($exact_match_value['uri'])] = $term->id();
                  break;
                }
              }
            }
          }

          // Get missing definitions from DBpedia if possible.
          if ($global_config->get('tag_glossary_use_dbpedia_definition') && !empty($dbpedia_check_terms)) {
            $dbpedia_store = new EasyRdf_Sparql_Client('http://dbpedia.org/sparql');

            // Define the SPARQL query.
            $query = "
    PREFIX onto:<http://dbpedia.org/ontology/>

    SELECT ?uri, ?definition
    WHERE {
      ?uri onto:abstract ?definition FILTER (lang(?definition) = 'en').
      VALUES ?uri { <" . implode('> <', array_keys($dbpedia_check_terms)) . "> }
    }";

            // Fetch the DBpedia definitions and update the terms.
            try {
              $rows = $dbpedia_store->query($query);

              if ($rows->numRows()) {
                foreach ($rows as $row) {
                  $dbpedia_definition = $row->definition->getValue();
                  if (!empty($dbpedia_definition)) {
                    $potential_terms[$dbpedia_check_terms[$row->uri->getURI()]]->setDescription($dbpedia_definition);
                  }
                }
              }
            }
            catch (\Exception $e) {
              \Drupal::logger('powertagging')->log(\Drupal\Core\Logger\RfcLogLevel::ERROR, 'Error during fetching definitions from DBpedia in the PowerTagging tag glossary block: <pre>%errors</pre>', array('%errors' => $e->getMessage()));
            }
          }

          // Limit the terms to the maximum number of terms and remove terms
          // without a definition.
          $final_terms_counts = array();
          $final_terms = array();
          $displayed_tags_count = 0;
          /** @var Term $term */
          foreach ($potential_terms as $tid => $term) {
            if (!empty($term->getDescription())) {
              $final_terms_counts[$tid] = $term_counts[$tid];
              $final_terms[$tid] = $term;
              $displayed_tags_count++;
              if ($displayed_tags_count >= $max_items) {
                break;
              }
            }
          }

          // Theme the terms.
          if (!empty($final_terms)) {
            // Offer a hook for customizing the block output.
            $custom_content = \Drupal::moduleHandler()->invokeAll('powertagging_tag_glossary_output', array($final_terms, $final_terms_counts));

            // Build the block content.
            if (empty($custom_content)) {
              $block_html .= '<div id="powertagging_glossary_terms">';
              foreach (array_keys($final_terms_counts) as $tid) {
                if (isset($final_terms[$tid])) {
                  /** @var Term $term */
                  $term = $final_terms[$tid];
                  $block_html .= '<div class="powertagging_glossary_terms_term">';
                  $block_html .= '<h3>' . $term->getName() . '</h3>';
                  if (!empty($term->getDescription())) {
                    $max_characters = $global_config->get('tag_glossary_definition_max_characters');
                    $block_html .= '<p>' . ($max_characters ? \Drupal\Component\Utility\Unicode::truncate($term->getDescription(), $max_characters, TRUE, TRUE) : $term->getDescription()) . '</p>';
                  }
                  $block_html .= '</div>';
                }
              }
              $block_html .= '</div>';
            }
            // Custom tag glossary block content (hooked).
            else {
              $block_html .= reset($custom_content);
            }
          }
        }
      }

      // Create the block output
      if (!empty($block_html)) {
        $block = array(
          'content' => array(
            '#type' => 'markup',
            '#markup' => $block_html,
          ),
          '#cache' => array(
            'max-age' => 0
          )
        );
      }
    }

    return $block;
  }
}
