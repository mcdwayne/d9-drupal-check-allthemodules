<?php

namespace Drupal\expiring_roles\ExpiringRoles;

use Psr\Log\LoggerInterface;
use Drupal\Core\Database\Connection;
use \Drupal\user\Entity\User;
use \Datetime;
use \DateInterval;

/**
 * ExpiringRolesManager.
 */
class ExpiringRolesManager {

  /**
   * Constructs a ExpiringRolesManager object.
   */
  public function __construct(LoggerInterface $logger, Connection $connection) {
    $this->logger = $logger;
    $this->connection = $connection;
  }

  /**
   * Grant a role and save a record of the expiring role.
   *
   * @param int $uid
   *   The user id.
   * @param string $rid
   *   The role id.
   * @param DateTime $created
   *   Time when the expiring role starts.
   * @param DateInterval $duration
   *   Duration of the expiring role.
   *
   * @return mixed
   *   Return the row id OR return FALSE if unsuccessful.
   */
  public function saveExpiringRole($uid, $rid, DateTime $created, DateInterval $duration) {
    // Check if an expiring role for this user and role combination
    // already exist.
    $user = User::load($uid);
    $existing_xid = $this->getLatestExpiringRole($uid, $rid);

    // Grant the role.
    $saved = $this->grantRole($user, $rid, $existing_xid == FALSE ? FALSE : TRUE);

    // Update the start time to start at the end of the existing expiring role.
    if ($existing_xid !== FALSE) {
      // Add 1 second so no overlap.
      $created->setTimestamp($existing_xid->expiry + 1);
    }
    if ($saved == SAVED_NEW || $saved == SAVED_UPDATED) {
      try {
        $start = $created->getTimestamp();
        $created->add($duration);
        $end = $created->getTimestamp();
        // Add the new expiring role.
        $result_insert = $this->connection->insert('expiring_roles')
          ->fields([
            'uid' => $uid,
            'rid' => $rid,
            'created' => $start,
            'expiry' => $end,
            'status' => 1,
          ])
          ->execute();
        // Update the continue_xid column on the existing role.
        if ($existing_xid && $result_insert) {
          $result_update = $this->connection->update('expiring_roles')
            ->fields(['continue_xid' => $result_insert])
            ->condition('xid', $existing_xid->xid)
            ->execute();
        }
        // die();
        // Log the insert.
        if ($result_insert) {
          $this->logger->info('Expiring role record created: UID (@uid), Role (@rid), created (@created), Expiry (@expiry), Status (@status).', [
            '@uid' => $uid,
            '@rid' => $uid,
            '@created' => $start,
            '@expiry' => $end,
            '@status' => 1,
          ]);
        }
        else {
          $this->logger->error('There was a problem saving expiring role: @e.', ['@e' => $result_insert]);
        }
        // Log the update.
        if (isset($result_update)) {
          if ($result_update) {
            $this->logger->info('Existing expiring role updated continue_xid: @xid.', ['@xid' => $existing_xid->xid]);
          }
          else {
            $this->logger->error('There was a problem updating existing expiring role: @e.', ['@e' => $result_update]);
          }
        }
        return $result_insert;
      }
      catch (\Exception $e) {
        $this->logger->error($e->getMessage());
      }
    }
    return FALSE;
  }

  /**
   * Get the latest expiring role for the uid and rid.
   *
   * This returns the expiring role with the latest expiry date
   * and has no value in the continue_xid column.
   *
   * @param int $uid
   *   The user id.
   * @param string $rid
   *   The role id.
   *
   * @return int
   *   The result.
   */
  public function getLatestExpiringRole($uid, $rid) {
    try {
      $expiring = $this->connection->select('expiring_roles', 'e')
        ->fields('e', ['xid', 'expiry'])
        ->condition('e.uid', $uid)
        ->condition('e.rid', $rid)
        ->condition('e.continue_xid', '0')
        ->condition('e.expiry', time(), '>')
        ->condition('e.status', 1)
        ->orderBy('e.expiry', 'DESC')
        ->execute()->fetch();

      return $expiring;
    }
    catch (\Exception $e) {
      $this->logger->error($e->getMessage());
    }
  }

  /**
   * Add role to user.
   *
   * @param User $user
   *   The user.
   * @param string $rid
   *   The role id.
   * @param bool $check
   *   If we need to check the role exists.
   *
   * @return int
   *   The result.
   */
  public function grantRole(User $user, $rid, $check) {
    try {
      if ($check && $user->hasRole($rid)) {
        // Log it.
        $this->logger->info('USER @user (@user_id) already has Role (@rid).', [
          '@user' => $user->getAccountName(),
          '@user_id' => $user->id(),
          '@rid' => $rid,
        ]);
        return TRUE;
      }
      else {
        // Grant user role.
        // The addRole method makes sure the roles are unique.
        $user->addRole($rid);
        $result = $user->save();

        // Log it.
        $this->logger->info('Role (@rid) granted to USER @user (@user_id).', [
          '@rid' => $rid,
          '@user' => $user->getAccountName(),
          '@user_id' => $user->id(),
        ]);
        return $result;
      }
    }
    catch (\Exception $e) {
      $this->logger->error($e->getMessage());
    }
  }

  /**
   * Expire the roles that have gone beyond their expiry.
   */
  public function updateRoles() {
    try {
      // Get the expired roles that are still active with no continue xid.
      $expiring = $this->connection->select('expiring_roles', 'e')
        ->fields('e', ['xid', 'uid', 'rid'])
        ->condition('e.expiry', time(), '<')
        ->condition('e.continue_xid', '0')
        ->condition('e.status', 1)
        ->execute()->fetchAll();

      // Revoke the roles.
      $xids = [];
      foreach ($expiring as $exp) {
        $xids[] = $exp->xid;
        $user = User::load($exp->uid);
        $user->removeRole($exp->rid);
        $result = $user->save();

        $log_info = [
          '@rid' => $exp->rid,
          '@user' => $user->getAccountName(),
          '@user_id' => $user->id(),
          '@xid' => $exp->xid,
        ];

        if ($result == SAVED_NEW || $result == SAVED_UPDATED) {
          // Log it.
          $this->logger->info('Role (@rid) removed from USER @user (@user_id), xid (@xid).', $log_info);
        }
        else {
          $this->logger->error('There was a problem removing Role (@rid) from USER @user (@user_id), xid (@xid).', $log_info);
        }
      }

      if (count($xids) > 0) {
        // Set expiring status to 0.
        $num_updated = $this->connection->update('expiring_roles')
          ->fields(['status' => 0])
          ->condition('xid', $xids, 'IN')
          ->condition('status', 1)
          ->execute();

        // Log it.
        $this->logger->info('@count roles rows have expired. xids: @xids.', [
          '@count' => $num_updated,
          '@xids' => implode(', ', $xids),
        ]);
      }
    }
    catch (\Exception $e) {
      $this->logger->error($e->getMessage());
    }
  }

  /**
   * Get the expiring roles related to the user.
   *
   * @param int $uid
   *   The user id.
   *
   * @return array
   *   The roles keyed by xid.
   */
  public function getActiveExpiringRolesFromUserId($uid) {
    try {
      // Get the expired roles that are still active.
      $eroles = $this->connection->select('expiring_roles', 'e')
        ->fields('e')
        ->condition('e.uid', $uid)
        ->condition('e.status', 1)
        ->orderBy('xid')
        ->execute()->fetchAllAssoc('xid');

      return $eroles;
    }
    catch (\Exception $e) {
      $this->logger->error($e->getMessage());
    }
  }

  /**
   * Get expiring roles that will end in the timespan.
   *
   * These are active expiring roles that have no continue_xid and
   * have an expiry between the specified times.
   * Ordered by user id.
   *
   * @param DateTime $start_time
   *   The start time.
   * @param DateTime $end_time
   *   The end time.
   *
   * @return int
   *   The result.
   */
  public function getExpiringRoles(DateTime $start_time, DateTime $end_time) {
    try {
      $expiring = $this->connection->select('expiring_roles', 'e')
        ->fields('e')
        ->condition('e.continue_xid', '0')
        ->condition('e.expiry', $start_time->getTimestamp(), '>')
        ->condition('e.expiry', $end_time->getTimestamp(), '<=')
        ->condition('e.status', 1)
        ->orderBy('e.uid')
        ->execute()->fetchAll();

      return $expiring;
    }
    catch (\Exception $e) {
      $this->logger->error($e->getMessage());
    }
  }

}
