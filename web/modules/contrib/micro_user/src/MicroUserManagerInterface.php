<?php

namespace Drupal\micro_user;

use Drupal\user\UserInterface;

/**
 * Provides an interface defining a micro user entity.
 *
 * @ingroup user_api
 */
interface MicroUserManagerInterface  {

  /**
   * Only administrators can create user accounts.
   */
  const REGISTER_SITE_ADMINISTRATORS_ONLY = 'site_admin_only';

  /**
   * Visitors can create their own accounts.
   */
  const REGISTER_SITE_VISITORS = 'site_visitors';

  /**
   * Visitors can create accounts that only become active with admin approval.
   */
  const REGISTER_SITE_VISITORS_ADMINISTRATIVE_APPROVAL = 'site_visitors_admin_approval';

}
