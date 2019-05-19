<?php namespace Drupal\storychief\Field;

use Drupal\node\Entity\Node;

class StoryChiefSimpleFieldHandler implements StoryChiefFieldHandlerInterface {

	protected $drupal_field_handle;

	public function __construct($drupal_field_handle) {
		$this->drupal_field_handle = $drupal_field_handle;
	}

	public function handle($value, Node &$node) {
		$node->{$this->drupal_field_handle}->value = $value;
	}
}