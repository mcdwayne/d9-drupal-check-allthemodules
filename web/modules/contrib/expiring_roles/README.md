# Expiring Roles

This module was conceived to grant roles with a set expiry date. A cron job handles updating the roles past their expiry date.

The initial use case was to grant roles upon successful purchase of a subscription in Drupal Commerce. This is why the continue_xid column exists - for additional subscriptions purchased.

# Usage

```
  // First retrieve the manager service.
  $er_manager = \Drupal::service('expiring_roles.expiring_roles_manager');
  // Then save an expiring role
  // with saveExpiringRole($uid, $rid, $created, $expiry).
  $xid = $er_manager->saveExpiringRole(10, 'account_admin', $created_timestamp, $duration);
  // If another expiring role is created with the same $uid and $rid, the expiring roles manager will get the expiry date of the existing xid, add the duration of the new xid, and this will be the expiry of the new xid.
  $xid2 = $er_manager->saveExpiringRole(10, 'account_admin', $created_timestamp2, $duration2);
```

Set cron to run regularly. The cron job will take care of revoking the role.
git remote set-url origin japo32@git.drupal.org:project/commerce_dps_pxpay.git
