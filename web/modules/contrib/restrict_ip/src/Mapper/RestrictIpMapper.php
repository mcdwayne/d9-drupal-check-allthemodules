<?php

namespace Drupal\restrict_ip\Mapper;

use Drupal\Core\Database\Connection;

class RestrictIpMapper implements RestrictIpMapperInterface
{
	/**
	 * The database connection
	 *
	 * @var Drupal\Core\Database\Connection
	 */
	protected $connection;

	public function __construct(Connection $connection)
	{
		$this->connection = $connection;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getWhitelistedIpAddresses()
	{
		return $this->connection->query('SELECT ip_address FROM {restrict_ip_whitelisted_ip_addresses} ORDER BY ip_address ASC')->fetchCol();
	}

	/**
	 * {@inheritdoc}
	 */
	public function saveWhitelistedIpAddresses(array $ip_addresses, $overwriteExisting = TRUE)
	{
		if($overwriteExisting)
		{
			$this->connection->query('DELETE FROM {restrict_ip_whitelisted_ip_addresses}');
		}

		$query = $this->connection->insert('restrict_ip_whitelisted_ip_addresses')->fields(['ip_address']);
		foreach($ip_addresses as $ip_address)
		{
			$query->values(['ip_address' => $ip_address]);
		}

		$query->execute();
	}

	/**
	 * {@inheritdoc}
	 */
	public function getWhitelistedPaths()
	{
		return $this->connection->query('SELECT path FROM {restrict_ip_paths} WHERE type = :white ORDER BY path ASC', [':white' => 'white'])->fetchCol();
	}

	/**
	 * {@inheritdoc}
	 */
	public function saveWhitelistedPaths(array $whitelistedPaths, $overwriteExisting = TRUE)
	{
		if($overwriteExisting)
		{
			$this->connection->query('DELETE FROM {restrict_ip_paths} WHERE type = :white', [':white' => 'white']);
		}

		$query = $this->connection->insert('restrict_ip_paths')->fields(['type', 'path']);
		foreach($whitelistedPaths as $whitelisted_path)
		{
			$query->values(['type' => 'white', 'path' => $whitelisted_path]);
		}

		$query->execute();
	}

	/**
	 * {@inheritdoc}
	 */
	public function getBlacklistedPaths()
	{
		return $this->connection->query('SELECT path FROM {restrict_ip_paths} WHERE type = :black ORDER BY path ASC', [':black' => 'black'])->fetchCol();
	}

	/**
	 * {@inheritdoc}
	 */
	public function saveBlacklistedPaths(array $blacklistedPaths, $overwriteExisting = TRUE)
	{
		if($overwriteExisting)
		{
			$this->connection->query('DELETE FROM {restrict_ip_paths} WHERE type = :black', [':black' => 'black']);
		}

		$query = $this->connection->insert('restrict_ip_paths')->fields(['type', 'path']);
		foreach($blacklistedPaths as $blacklisted_path)
		{
			$query->values(['type' => 'black', 'path' => $blacklisted_path]);
		}

		$query->execute();
	}
}
