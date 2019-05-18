<?php


namespace Drupal\partnersite_profile\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Wraps a partner profile creation event for event subscribers.
 *
 * @ingroup partnersite_profile
 */

class PartnerProfileInitializeEvent extends Event
{
    /**
     * Authentication Point
     *
     * @var string
     */
    protected $login_auth;

    /**
     * Flagged to determine user creation required or not to Authentication Point of Choice
     *
     * @var boolean
     */
    protected $init_user;

    /**
     * Profile Name Created
     *
     * @var string
     */
    protected $profile_user_handle;

	/**
	 * Profile Email to use for creation
	 *
	 * @var string
	 */
	protected $profile_user_email_handle;

	/**
     * PartnerProfileInitializeEvent constructor.
     * @param string $login_auth
     * Authentication Point
     *
     * @param boolean $init_user
     * Determine whether to initialize user
     *
     * @param string $profile_user_handle
     * Partner Profile Name for UserID generation
     */

    public function __construct($login_auth, $init_user, $profile_user_handle, $profile_user_email_handle )
    {
        $this->login_auth = $login_auth;
        $this->init_user = $init_user;
        $this->profile_user_handle = $profile_user_handle;
        $this->profile_user_email_handle = $profile_user_email_handle;

    }

    /**
     * Get the Authentication Point
     *
     * @return string
     */
    public function getLoginAuth()
    {
        return $this->login_auth;
    }

    /**
     * Get user initiation/creation required or not
     * @return bool
     */

    public function getInitUser()
    {
        return $this->init_user;
    }

    /**
     * Get user handle for partner profile
     *
     * @return string
     */

    public function getProfileUserHandle(){
        return $this->profile_user_handle;
    }

	/**
	 * Get email of partner profile
	 *
	 * @return string
	 */

	public function getProfileUserEmailHandle(){
		return $this->profile_user_email_handle;
	}



}