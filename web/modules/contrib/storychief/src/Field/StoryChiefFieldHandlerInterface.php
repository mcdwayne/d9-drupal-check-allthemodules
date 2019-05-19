<?php namespace Drupal\storychief\Field;

use Drupal\node\Entity\Node;

interface StoryChiefFieldHandlerInterface {
	public function __construct($drupal_field_handle);

	public function handle($value, Node &$node);
}