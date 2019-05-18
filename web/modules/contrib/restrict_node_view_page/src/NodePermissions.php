<?php

namespace Drupal\restrict_node_view_page;

use Drupal\Core\Routing\UrlGeneratorTrait;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\node\Entity\NodeType;

/**
 * Provides dynamic override permissions for nodes of different types.
 */
class NodePermissions {

	use StringTranslationTrait;
	use UrlGeneratorTrait;

	/**
	* Returns an array of additional permissions.
	*
	* @return array
	*   An array of permissions.
	*/
	public function nodeTypePermissions() {
		$permissions = [];
		// Generate node permissions for all node types.
    	foreach (NodeType::loadMultiple() as $type) {
			$permissions += $this->buildPermissions($type);
		}

		return $permissions;
	}

	/**
     * Returns a list of node permissions for a given node type.
     *
     * @param The node type.
     *
     * @return array
     *   An associative array of permission names and descriptions.
     */
	protected function buildPermissions(NodeType $type) {
		$type_id = $type->id();
		$type_params = ['%type_name' => $type->label()];

		return [
		  "view full node pages of $type_id" => [
		    'title' => $this->t('View full node pages of %type_name', $type_params),
		    'description' => $this->t('Access to view full node pages of %type_name', $type_params),
		  ],
		];
	}
}