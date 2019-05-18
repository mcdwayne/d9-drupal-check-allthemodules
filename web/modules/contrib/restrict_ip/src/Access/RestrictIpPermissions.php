<?php

namespace Drupal\restrict_ip\Access;

class RestrictIpPermissions implements RestrictIpPermissionsInterface
{
	/**
	 * {@inheritdoc}
	 */
	public function permissions()
	{
		$permissions = [];

		if(\Drupal::config('restrict_ip.settings')->get('allow_role_bypass'))
		{
			$permissions['bypass ip restriction'] = [
				'title' => 'Bypass IP Restriction',
				'description' => 'Allows the user to access the site even if not in the IP whitelist',
			];
		}

		return $permissions;
	}
}
