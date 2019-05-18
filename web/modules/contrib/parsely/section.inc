<?php

namespace Drupal\parsely;

/**
 * Attempt to determine the "section" to which a given node belongs.
 */
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\taxonomy\TermStorage;
use Drupal\taxonomy\TermStorageInterface;
use Psr\Log\InvalidArgumentException;

/**
 * @param $node Node
 * @return string
 */
function parsely_get_section($node)
{
	$section_name = 'Uncategorized';
	$section_term = NULL;
	$section_logic = \Drupal::config('parsely.settings')->get('parsely_section_logic');

	switch ($section_logic) {

		case 0:
			$section_by_node_type = \Drupal::config('parsely.settings')->get('parsely_nodes_wrap')['parsely_nodes'];
			if (\Drupal::moduleHandler()->moduleExists('node') && !is_null($section_by_node_type)) {

				if (in_array($node->getType(), $section_by_node_type)) {
					$section_name = $node->getType();
				}
			}
			break;

		case 1:
			$section_vocabulary_id = \Drupal::config('parsely.settings')->get('parsely_sections_tax_wrapper')['parsely_section_vocabulary'];
			$section_criterion = \Drupal::config('parsely.settings')->get('parsely_sections_tax_wrapper')['parsely_section_term_criterion'];

			if (\Drupal::moduleHandler()->moduleExists('taxonomy') && !is_null($section_vocabulary_id)) {
				// Find the most relevant term from the designated vocab, using the
				// criterion specified in the module configuration. See
				// parsely_admin_settings() for an explanation of the supported criteria.


				// "First term" or "Last term."
				$clean_term_name = Vocabulary::load($section_vocabulary_id)->get('vid');

				if ($section_criterion == 'highest') {
					$term_tree = \Drupal::EntityTypeManager()->getStorage('taxonomy_term')->loadTree($section_vocabulary_id);
					$section_name = $term_tree[0]->name;
					break;
				}

				try {
					$term_ids = $node->get('field_' . $clean_term_name)->getValue();
				} catch (\InvalidArgumentException $e) {
					$term_ids = NULL;
				}

				if (count($term_ids) == 1) {
					$section_name = Term::load($term_ids[0]['target_id'])->getName();
				} elseif ($section_criterion == 'first' || $section_criterion == 'last' && $term_ids) {

					asort($term_ids);

					if ($section_criterion == 'first') {
						$section_name = Term::load(array_shift($term_ids)['target_id'])->getName();
					} elseif ($section_criterion == 'last') {
						$section_name = Term::load(array_pop($term_ids)['target_id'])->getName();

					}
				}
				break;

			}
	}

			// Allow any module to alter the section name.
		if ($section_name == NULL) {
			$section_name = 'Uncategorized';
		}
		\Drupal::moduleHandler()->alter('parsely_section', $section_name, $node, $section_term);
		return $section_name;

}

