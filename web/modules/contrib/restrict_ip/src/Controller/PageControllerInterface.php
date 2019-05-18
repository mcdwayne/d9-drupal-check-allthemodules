<?php

namespace Drupal\restrict_ip\Controller;

interface PageControllerInterface
{
	/**
	 * Provides the configuration page for the Restrict IP module
	 */
	public function configPage();

	/**
	 * Provides the Access Denied page for the Restrict IP module
	 */
	public function accessDeniedPage();
}
