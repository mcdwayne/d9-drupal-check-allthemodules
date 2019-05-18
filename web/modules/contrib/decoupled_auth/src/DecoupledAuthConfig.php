<?php

namespace Drupal\decoupled_auth;

/**
 * Configuration constants for Decoupled User Authentication.
 *
 * @see config/install/decoupled_auth.settings.yml
 * @see config/schema/decoupled_auth.schema.yml
 */
class DecoupledAuthConfig {

  /**
   * Unique emails mode: All users.
   *
   * All coupled and decoupled users must have unique email addresses.
   *
   * @var string
   *
   * @see Config decoupled_auth.settings.unique_emails.mode
   */
  const UNIQUE_EMAILS_MODE_ALL_USERS = 'all';

  /**
   * Unique emails mode: Decoupled users with the selected roles.
   *
   * All coupled users and decoupled users with the selected roles
   * (decoupled_auth.settings.unique_emails.roles) must have unique email
   * addresses. Decoupled users without the selected roles may re-use existing
   * email addresses and do not reserve that email address.
   *
   * @var string
   *
   * @see Config decoupled_auth.settings.unique_emails.mode
   */
  const UNIQUE_EMAILS_MODE_WITH_ROLE = 'with_role';

  /**
   * Unique emails mode: Decoupled users without the selected roles.
   *
   * All coupled users and decoupled users without the selected roles
   * (decoupled_auth.settings.unique_emails.roles) must have unique email
   * addresses. Decoupled users with the selected roles may re-use existing
   * email addresses and do not reserve that email address.
   *
   * @var string
   *
   * @see Config decoupled_auth.settings.unique_emails.mode
   */
  const UNIQUE_EMAILS_MODE_WITHOUT_ROLE = 'without_role';

  /**
   * Unique emails mode: No decoupled users.
   *
   * Only coupled users must have unique email addresses. Decoupled users may
   * re-use existing email addresses and do not reserve that email address.
   *
   * @var string
   *
   * @see Config decoupled_auth.settings.unique_emails.mode
   */
  const UNIQUE_EMAILS_MODE_COUPLED = 'none';

}
