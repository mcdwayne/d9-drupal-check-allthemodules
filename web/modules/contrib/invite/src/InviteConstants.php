<?php

namespace Drupal\invite;

/**
 * InviteConstants project's contants.
 */
class InviteConstants {

  /**
   * Flag for invite valid.
   */
  const INVITE_VALID = 1;

  /**
   * Flag for invite withdrawn.
   */
  const INVITE_WITHDRAWN = 2;

  /**
   * Flag for invite used.
   */
  const INVITE_USED = 3;

  /**
   * Flag for invite expired.
   */
  const INVITE_EXPIRED = 4;

  /**
   * Flag for invite code.
   */
  const INVITE_SESSION_CODE = 'invite_code';

}
