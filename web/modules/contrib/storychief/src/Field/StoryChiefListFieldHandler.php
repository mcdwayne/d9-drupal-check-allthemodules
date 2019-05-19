<?php namespace Drupal\storychief\Field;

use Drupal\node\Entity\Node;

class StoryChiefListFieldHandler implements StoryChiefFieldHandlerInterface {

	protected $drupal_field_handle;

	public function __construct($drupal_field_handle) {
		$this->drupal_field_handle = $drupal_field_handle;
	}

	public function handle($value, Node &$node) {
		$values = explode(',', $value);
		$node->{$this->drupal_field_handle}->setValue([]);
		foreach ($values as $v) {
			$node->{$this->drupal_field_handle}->appendItem($v);
		}
	}
}
