<?php namespace Drupal\storychief\Field;

use Drupal\node\Entity\Node;

class StoryChiefFilesFieldHandler implements StoryChiefFieldHandlerInterface {

	protected $config, $drupal_field_handle;

	public function __construct($drupal_field_handle) {
		$this->config = \Drupal::config('storychief.settings');
		$this->drupal_field_handle = $drupal_field_handle;
	}

	public function handle($value, Node &$node) {
		if (!is_array($value)) $value = [$value];
		$node->{$this->drupal_field_handle}->setValue([]);
		foreach ($value as $uri) {
			$file = $this->save_file($uri);
			if ($file) {
				$node->{$this->drupal_field_handle}->appendItem(['target_id' => $file->id()]);
			}
		}
	}

	protected function save_file($uri) {
		$node_type = $this->config->get('node_type');
		$field = \Drupal::entityTypeManager()->getStorage('field_config')->load('node.' . $node_type . '.' . $this->drupal_field_handle);
		$destination = $field->getSetting('uri_scheme') . '://' . $field->getSetting('file_directory');

		if (!file_prepare_directory($destination, FILE_CREATE_DIRECTORY)) {
			\Drupal::logger('storychief')->error(t('Image not added: upload path not valid?'));

			return null;
		} else {
			$file = system_retrieve_file($uri, $destination, true);
			if (isset($file) && $file->fid) {
				return $file;
			} else {
				\Drupal::logger('storychief')->error(t('Image not added: allowed extensions? File size limit?'));

				return null;
			}
		}
	}
}
