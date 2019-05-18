<?php

namespace Drupal\partnersite_profile\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the Partnersite profiles entity.
 *
 * @ConfigEntityType(
 *   id = "partnersite_profiles",
 *   label = @Translation("Partnersite profiles"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\partnersite_profile\PartnersiteProfilesListBuilder",
 *     "form" = {
 *       "add" = "Drupal\partnersite_profile\Form\PartnersiteProfilesForm",
 *       "edit" = "Drupal\partnersite_profile\Form\PartnersiteProfilesForm",
 *       "delete" = "Drupal\partnersite_profile\Form\PartnersiteProfilesDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\partnersite_profile\PartnersiteProfilesHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "partnersite_profiles",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/partnersite_profiles/{partnersite_profiles}",
 *     "add-form" = "/admin/structure/partnersite_profiles/add",
 *     "edit-form" = "/admin/structure/partnersite_profiles/{partnersite_profiles}/edit",
 *     "delete-form" = "/admin/structure/partnersite_profiles/{partnersite_profiles}/delete",
 *     "collection" = "/admin/structure/partnersite_profiles"
 *   }
 * )
 */
class PartnersiteProfiles extends ConfigEntityBase implements PartnersiteProfilesInterface {

  /**
   * The Partnersite profiles ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Partnersite profiles label.
   *
   * @var string
   */
  protected $label;

  /**
   * The Partnersite profiles authentication identifier
   *
   * @var string
   */

  protected $auth_div;

    /**
     * The Partnersite profiles authentication key secret
     *
     * @var string
     */

    protected $auth_secret;


    /**
     * The Partnersite profiles authentication hash map
     *
     * @var string
     */

    protected $auth_mapping_hash;

		/**
		 * The Partnersite profiles authentication expiration window
		 *
		 * @var integer
		 */

		protected $auth_timestamp_expiry;

		/**
		 * The Partnersite profiles authentication hash key logic
		 *
		 * @var string
		 */

		protected $auth_hash_logic;


	/**
	 * The Partnersite profiles contact email
	 *
	 * @var string
	 */

	protected $profile_email;

	/**
     * Fetch the Authentication Division
     *
     * @return mixed|null
     */
    public function getAuthDiv()
    {
       return $this->get('auth_div');
    }

    /**
     * Set the Authentication Division
     *
     * @param integer
     * Partnersite Profile Authentication identifier
     *
     * @return mixed|null
     * Return this class instance back
     */
    public function setAuthDiv( $auth_div )
    {
        $this->set('auth_div', $auth_div );
        return $this;
    }

    /**
     * Fetch the Authentication Secret Key
     *
     * @return mixed|null
     */
    public function getAuthSecret()
    {
        return $this->get('auth_secret');
    }

    /**
     * Set the Authentication Secret Key
     *
     * @param string $auth_secret
     * Partnersite Secret key to use for Profile
     *
     * @return mixed|null
     * Return this object instance back
     */
    public function setAuthSecret( $auth_secret )
    {
        $this->set('auth_secret', $auth_secret );
        return $this;
    }

    /**
     * Fetch the Authentication HASH map Secret
     *
     * @return mixed|null
     */
    public function getAuthMappingHash()
    {
        return $this->get('auth_mapping_hash');
    }

    /**
     * Set the Authentication Secret HASH map
     *
     * @param string $auth_mapping_hash
     * Partnersite Hash secret to use for Profile
     *
     * @return mixed|null
     * Return this object instance
     */
    public function setAuthMappingHash( $auth_mapping_hash )
    {
        $this->set('auth_mapping_hash', $auth_mapping_hash );
        return $this;
    }

		/**
		 * Fetch the link expiry for expiration check
		 *
		 * @return mixed|null
		 */
		public function getAuthTimestampExpiry()
		{
			return $this->get('auth_timestamp_expiry');
		}

		/**
		 * Set the link expiry for expiration check
		 *
		 * @param string $auth_timestamp_expiry
		 * Partnersite link expiry to use for Profile
		 *
		 * @return mixed|null
		 * Return this object instance
		 */
		public function setAuthTimestampExpiry( $auth_timestamp_expiry )
		{
			$this->set('auth_timestamp_expiry', $auth_timestamp_expiry );
			return $this;
		}

		/**
		 * Fetch the link hash generation approach
		 *
		 * @return mixed|null
		 */
		public function getAuthHashLogic()
		{
			return $this->get('auth_hash_logic');
		}

		/**
		 * Set the hash generation approach logic
		 *
		 * @param string $auth_hash_logic
		 * Partnersite link hash generation approach
		 *
		 * @return mixed|null
		 * Return this object instance
		 */
		public function setAuthHashLogic( $auth_hash_logic )
		{
			$this->set('auth_hash_logic', $auth_hash_logic );
			return $this;
		}

	/**
	 * Fetch the profile entity contact email
	 *
	 * @return mixed|null
	 */
	public function getPartnerEmail()
	{
		return $this->get('partner_email');
	}

	/**
	 * Set the email for the partner profile for contact
	 *
	 * @param string $partner_email
	 * Profile entity contact email
	 *
	 * @return mixed|null
	 * Return this object instance
	 */
	public function setPartnerEmail( $partner_email )
	{
		$this->set('partner_email', $partner_email );
		return $this;
	}

}
