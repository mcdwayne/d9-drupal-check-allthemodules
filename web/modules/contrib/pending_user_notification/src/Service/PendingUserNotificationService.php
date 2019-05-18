<?php

/**
 * @file Contains Drupal\pending_user_notification\Service\PendingUserNotificationService
 *
 * Maps database requests getting pending user notification data
 */

namespace Drupal\pending_user_notification\Service;

use Drupal\Core\Database\Connection;
use Drupal\pending_user_notification\Service\PendingUserNotificationServiceInterface;

class PendingUserNotificationService implements PendingUserNotificationServiceInterface
{
	/**
	 * The database connection
	 *
	 * @var \Drupal\Core\Database\Connection
	 */
	protected $connection;

	/**
	 * Constructs a ForcePasswordChangeService object.
	 *
	 * @param \Drupal\Core\Database\Connection $connection
	 *   The database connection
	 */
	public function __construct(Connection $connection)
	{
	  $this->connection = $connection;
	}

	public function getPendingUsers($count = FALSE)
	{
		return $this->getPendingUserNotificationData($count);
	}

	private function getPendingUserNotificationData($count)
	{
		if($count)
		{
			$query = $this->connection->select('users_field_data', 'u')->extend('Drupal\Core\Database\Query\PagerSelectExtender')->limit($count);
		}
		else
		{
			$query = $this->connection->select('users_field_data', 'u')
				->range(0, 5);
		}

		$uids = $query
			->fields('u', ['uid'])
			->condition('status', 0)
			->condition('created', 0, '>')
			->condition('login', 0)
			->condition('uid', 0, '>')
			->orderBy('created', 'asc')
			->execute()
			->fetchCol();

		return user_load_multiple($uids);
	}
}
