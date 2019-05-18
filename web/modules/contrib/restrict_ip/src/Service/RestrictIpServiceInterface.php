<?php

namespace Drupal\restrict_ip\Service;

interface RestrictIpServiceInterface
{
	/**
	 * Test if the user is blocked
	 *
	 * @return bool
	 *   TRUE if the user is blocked
	 *   FALSE if the user is not blocked
	 */
	public function userIsBlocked();

	/**
	 * Run all tests to see if the current user should be blocked or not
	 * based on their IP address
	 *
	 * @param $runInCli bool
	 *   Indicate whether the test should be run even when the code is being run through
	 *   the command line. This will almost always be FALSE, to prevent the user from
	 *   being blocked while running Drush commands, however this needs to be set as
	 *   TRUE when running PHPUnit tests, in order to be able to run the code.
	 */
	public function testForBlock($runInCli = FALSE);

	/**
	 * Takes a string containing potential IP addresses on separate lines,
	 * strips them of any code comments, trims them, and turns them into a clean array.
	 * Note that the elements may or may not be IP addresses and if validation is necessary,
	 * the array returned from this function should be validated.
	 *
	 * @param string $input
	 *   A string containing new-line separated IP addresses. Can contain code comments
	 *
	 * @return array
	 *   An array of IP addresses parsed from the $input.
	 */
	public function cleanIpAddressInput($input);

	/**
	 * Get the IP address of the current user
	 *
	 * @return string
	 *   The IP address of the current user
	 */
	public function getCurrentUserIp();

	/**
	 * Get the current path that the user is on
	 *
	 * @return string
	 *   The current path
	 */
	public function getCurrentPath();

	/**
	 * Get an array of all IP addresses that have been whitelisted through the admin interface
	 *
	 * @return array
	 *   An array of addresses whitelisted through the admin interface.
	 */
	public function getWhitelistedIpAddresses();

	/**
	 * Save whitelisted IP addresses to the system
	 *
	 * @param array $ip_addresses
	 *   An array of IP addresses to be saved
	 * @param bool $overwriteExisting
	 *   A boolean indicating whether existing IP addresses should be deleted before saving
	 */
	public function saveWhitelistedIpAddresses(array $ipAddresses, $overwriteExisting = TRUE);

	/**
	 * Get an array of all whitelisted pages
	 *
	 * @return array
	 *   An array of paths that have been whitelisted
	 */
	public function getWhitelistedPagePaths();

	/**
	 * Save whitelisted page paths to the system
	 *
	 * @param array $whitelistedPaths
	 *   An array of paths to be saved for whitelisting.
	 * @param bool $overwriteExisting
	 *   A boolean indicating whether existing paths should be deleted before saving
	 */
	public function saveWhitelistedPagePaths(array $whitelistedPaths, $overwriteExisting = TRUE);

	/**
	 * Get an array of all blacklisted pages
	 *
	 * @return array
	 *   An array of paths that have been whitelisted
	 */
	public function getBlacklistedPagePaths();

	/**
	 * Save blacklisted page paths to the system
	 *
	 * @param array $blacklistedPaths
	 *   An array of paths to be saved for blacklisting.
	 * @param bool $overwriteExisting
	 *   A boolean indicating whether existing paths should be deleted before saving
	 */
	public function saveBlacklistedPagePaths(array $blacklistedPaths, $overwriteExisting = TRUE);
}
