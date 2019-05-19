<?php namespace Drupal\storychief\Entity;

use Drupal\node\Entity\Node;
use Drupal\storychief\Field\StoryChiefFilesFieldHandler;
use Drupal\storychief\Field\StoryChiefListFieldHandler;
use Drupal\storychief\Field\StoryChiefSimpleFieldHandler;
use Drupal\storychief\Field\StoryChiefTaxonomyFieldHandler;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class StoryChiefStory {

	protected $config, $payload_data, $payload_meta;

	public function __construct(array $payload) {
		$this->config = \Drupal::config('storychief.settings');
		$this->payload_data = $payload['data'];
		$this->payload_meta = $payload['meta'];
	}

	/**
	 * Handle a publish webhook call.
	 *
	 * @return array
	 */
	public function publish() {
		$node_type = $this->config->get('node_type');

		if (isset($this->payload_data['source']['data']['external_id'])) {
			$source_nid = (int)$this->payload_data['source']['data']['external_id'];
			$source_node = Node::load($source_nid);
			$node = $source_node->addTranslation($this->payload_data['language'], [
				'uid'  						 => 1,
				'title' 					 => $this->payload_data['title'],
				'content_translation_source' => $source_node->language()->getId(),
			]);
		} else {
			$node = Node::create([
				'type'     => $node_type,
				'langcode' => $this->payload_data['language'] ?: \Drupal::languageManager()->getDefaultLanguage()->getId(),
				'uid'      => 1,
				'title'    => $this->payload_data['title'],
			]);
		}

		$this->mapPayloadToNode($node);
		$node->save();

		//create path alias from seo_slug
		try {
			if ($this->payload_data['seo_slug']) {
				$system_path = '/node/' . $node->id();
				$path_alias = '/' . $this->payload_data['seo_slug'];
				$langcode = $node->language()->getId();

				\Drupal::service('path.alias_storage')->save($system_path, $path_alias, $langcode);
			}
		} catch (\Exception $e) {
			// silence is golden
		}

		$this->mapPayloadSEOToNode($node);

		return array(
			'id'        => $node->id(),
			'permalink' => $node->url('canonical', ['absolute' => true]),
		);
	}

	/**
	 * Handle an update webhook call.
	 *
	 * @return array
	 */
	public function update() {
		$nid = $this->payload_data['external_id'];
		$langcode = $this->payload_data['language'];
		$node = Node::load($nid);
		if ($node && $langcode) {
			$node = $node->getTranslation($langcode);
		}
		if (!$node) throw new NotFoundHttpException('Unable to find Story');

		$this->mapPayloadToNode($node);
		$node->save();
		$this->mapPayloadSEOToNode($node);

		return array(
			'id'        => $node->id(),
			'permalink' => $node->url('canonical', ['absolute' => true]),
		);
	}

	/**
	 * Handle a delete webhook call.
	 *
	 * @return array
	 */
	public function delete() {
		$nid = $this->payload_data['external_id'];
		$langcode = $this->payload_data['language'];
		$node = Node::load($nid);

		if ($node) {
			if (isset($this->payload_data['source']['data']['external_id'])) {
				$node->removeTranslation($langcode);
				$node->save();
			} else {
				$node->delete();
			}

			$database = \Drupal\Core\Database\Database::getConnection();
			$database->delete('storychief_meta_tags')
				->condition('nid', $nid)
				->condition('langcode', $langcode)
				->execute();
		}

		return array(
			'id'        => $nid,
			'permalink' => null,
		);
	}

	/**
	 * Handle a test webhook call.
	 *
	 * @return array
	 */
	public function test() {
		if ($this->payload_data['custom_fields']) {
			$custom_field_mapping = $this->config->get('custom_field_mapping');
			$mapping = [];
			foreach ($this->payload_data['custom_fields']['data'] as $custom_field) {
				$field_name = $custom_field['name'];
				$field_label = $custom_field['label'];
				$field_type = $custom_field['type'];
				$mapping[$field_name] = [
					'label' => $field_label,
					'type'  => $field_type,
					'field' => isset($custom_field_mapping[$field_name]['field']) ? $custom_field_mapping[$field_name]['field'] : '',
				];
			}
			\Drupal::configFactory()->getEditable('storychief.settings')->set('custom_field_mapping', $mapping)->save();
		}

		return ['status' => 'OK'];
	}

	protected function mapPayloadToNode(Node &$node) {
		$mapping = $this->config->get('mapping');

		// set the title
		$node->setTitle($this->payload_data['title']);

		// set the content
		if (!empty($mapping['field_content'])) {
			$field_handle = $mapping['field_content'];
			$node->{$field_handle}->value = $this->payload_data['content'];
			$node->{$field_handle}->format = 'full_html';
			$node->{$field_handle}->summary = $this->payload_data['excerpt'];
		}

		// set the featured image field.
		if (!empty($mapping['field_featured_image']) && isset($this->payload_data['featured_image']['data']['sizes']['large'])) {
			$image_uris = [$this->payload_data['featured_image']['data']['sizes']['large']];
			$field_handle = $mapping['field_featured_image'];
			$fieldHandler = new StoryChiefFilesFieldHandler($field_handle);
			$fieldHandler->handle($image_uris, $node);
		}

		// Set the author field.
		if (isset($this->payload_data['author']['data']['email'])) {
			$user = user_load_by_mail($this->payload_data['author']['data']['email']);
			if ($user) {
				$node->uid = $user->uid;
			}
		}

		// set the tags
		if (!empty($mapping['field_tags']) && isset($this->payload_data['tags']['data'])) {
			$field_handle = $mapping['field_tags'];
			$tags = array_column($this->payload_data['tags']['data'], 'name');

			$fieldHandler = new StoryChiefTaxonomyFieldHandler($field_handle);
			$fieldHandler->handle($tags, $node);
		}

		// set the category
		if (!empty($mapping['field_category']) && isset($this->payload_data['category']['data'])) {
			$field_handle = $mapping['field_category'];
			$category = $this->payload_data['category']['data']['name'];

			$fieldHandler = new StoryChiefTaxonomyFieldHandler($field_handle);
			$fieldHandler->handle([$category], $node);
		}

		// custom fields
		foreach ($this->payload_data['custom_fields'] as $custom_field) {
			$custom_field_key = $custom_field['key'];
			$custom_field_value = $custom_field['value'];
			$field_handle = $this->config->get('custom_field_mapping.' . $custom_field_key . '.field');

			$this->handleCustomField($node, $field_handle, $custom_field_value);
		}
	}

	protected function mapPayloadSEOToNode(Node &$node) {
		$nid = $node->id();
		$langcode = $node->language()->getId();
		$database = \Drupal\Core\Database\Database::getConnection();

		// delete previous meta info
		$database
			->delete('storychief_meta_tags')
			->condition('nid', $nid)
			->condition('langcode', $langcode)
			->execute();

		// AMP Meta tag
		if (isset($this->payload_data['amphtml']) && !empty($this->payload_data['amphtml'])) {
			$render_array = [
				'#tag'        => 'link',
				'#attributes' => [
					'rel'  => 'amphtml',
					'href' => $this->payload_data['amphtml'],
				],
			];
			$database
				->insert('storychief_meta_tags')
				->fields(
					['nid', 'langcode', 'render_key', 'render_array'],
					[$nid, $langcode, 'amphtml', json_encode($render_array)]
				)
				->execute();
		}

		// SEO Meta Title tag
		if (isset($this->payload_data['seo_title']) && !empty($this->payload_data['seo_title'])) {
			$render_array = [
				'#tag'    => 'title',
				'content' => ['#plain_text' => $this->payload_data['seo_title']],
			];
			$database
				->insert('storychief_meta_tags')
				->fields(
					['nid', 'langcode', 'render_key', 'render_array'],
					[$nid, $langcode, 'title', json_encode($render_array)]
				)
				->execute();
		}

		// SEO Meta Description tag
		if (isset($this->payload_data['seo_description']) && !empty($this->payload_data['seo_description'])) {
			$render_array = [
				'#tag'        => 'meta',
				'#attributes' => [
					'name'    => 'description',
					'content' => $this->payload_data['seo_description'],
				],
			];
			$database
				->insert('storychief_meta_tags')
				->fields(
					['nid', 'langcode', 'render_key', 'render_array'],
					[$nid, $langcode, 'description', json_encode($render_array)]
				)
				->execute();
		}
	}

	protected function handleCustomField(Node &$node, $field_handle, $value) {
		$node_type = $this->config->get('node_type');
		$field = \Drupal::entityTypeManager()->getStorage('field_config')->load('node.' . $node_type . '.' . $field_handle);
		$fieldHandler = null;
		if ($field) {
			switch ($field->getType()) {
				case 'string':
				case 'text':
				case 'text_long':
				case 'text_with_summary':
					$fieldHandler = new StoryChiefSimpleFieldHandler($field_handle);
					break;
				case 'list':
				case 'list_string':
				case 'list_float':
				case 'list_integer':
					$fieldHandler = new StoryChiefListFieldHandler($field_handle);
					break;
				case 'image':
					$fieldHandler = new StoryChiefFilesFieldHandler($field_handle);
					break;
				case 'entity_reference':
					if ($field->getSetting('target_type') === 'taxonomy_term') {
						$fieldHandler = new StoryChiefTaxonomyFieldHandler($field_handle);
					}
					break;
				default:
					break;
			}
		}

		if ($fieldHandler) {
			$fieldHandler->handle($value, $node);
		}
	}
}
