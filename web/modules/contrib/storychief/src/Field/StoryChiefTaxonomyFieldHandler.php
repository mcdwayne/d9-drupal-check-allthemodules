<?php namespace Drupal\storychief\Field;

use Drupal\node\Entity\Node;

class StoryChiefTaxonomyFieldHandler implements StoryChiefFieldHandlerInterface {

	protected $config, $drupal_field_handle;

	public function __construct($drupal_field_handle) {
		$this->config = \Drupal::config('storychief.settings');
		$this->drupal_field_handle = $drupal_field_handle;
	}

	public function handle($value, Node &$node) {
		if (!is_array($value)) $value = [$value];
		$node->{$this->drupal_field_handle}->setValue([]);
		foreach ($value as $v) {
			$term = $this->getOrCreateTaxonomyTermByName($v, $node->language()->getId());
			if ($term) {
				$node->{$this->drupal_field_handle}->appendItem(['target_id' => $term->id()]);
			}
		}
	}

	protected function getOrCreateTaxonomyTermByName($term_name, $langcode) {
		$node_type = $this->config->get('node_type');
		$field = \Drupal::entityTypeManager()->getStorage('field_config')->load('node.' . $node_type . '.' . $this->drupal_field_handle);
		$field_handler_settings = $field->getSetting('handler_settings');
		$vocabularies = $field_handler_settings['target_bundles'];

		$terms = \Drupal::entityTypeManager()
			->getStorage('taxonomy_term')
			->loadByProperties([
				'vid'      => array_values($vocabularies),
				'name'     => $term_name,
				'langcode' => $langcode,
			]);


		// A term was found
		if (!empty($terms)) return array_values($terms)[0];

		// We are not allowed to create terms
		if (!$field_handler_settings['auto_create']) return null;

		// Create a new term
		$defaultVocabulary = $field_handler_settings['auto_create_bundle'];
		$term = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->create([
			'name'     => $term_name,
			'vid'      => $defaultVocabulary,
			'langcode' => $langcode,
		]);

		$term->save();

		return $term;
	}
}