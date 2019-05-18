<?php

/**
 * @file Contains Drupal\pending_user_notification\Service\PendingUserNotificationServiceInterface
 *
 * Maps database requests getting pending user notification data
 */

namespace Drupal\pending_user_notification\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Link;
use Drupal\Core\Routing\RedirectDestination;
use Drupal\Core\Url;

interface PendingUserNotificationServiceInterface
{
	public function getPendingUsers();
}
