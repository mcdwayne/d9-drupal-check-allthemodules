<?php

namespace Drupal\restrict_ip\Access;

interface RestrictIpPermissionsInterface
{
	/**
	 * Creates an array of dynamic permissions for the Restrict IP module.
	 *
	 * @return array
	 *   An array of permissions with a minimum of a title and description key for
	 *   each permission.
	 */
	public function permissions();
}
