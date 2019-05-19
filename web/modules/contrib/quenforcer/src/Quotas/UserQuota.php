<?php

namespace Drupal\quenforcer\Quotas;

class UserQuota extends Quota {

  const HUMAN_READABLE_NAME = 'User quota';
  const LIMIT_SETTING = 'users_max_number';
  const UNITS = 'users';

  /**
   * @see SiteAuditCheckUsersCountAll::calculateScore().
   */
  protected function calculateCurrentlyUsedAmount() {
    $sql_query  = 'SELECT COUNT(uid) FROM {users} WHERE uid != 0';
    return db_query($sql_query)->fetchField();
  }

  public function exceededMessage() {
    return t('You have reached your user quota limit of %limit users preventing you from adding more. Please ask your administrator to increase it.', [
      '%limit' => $this->limit,
    ]);
  }

  protected function getReportDetails() {
    return [];
  }
}
