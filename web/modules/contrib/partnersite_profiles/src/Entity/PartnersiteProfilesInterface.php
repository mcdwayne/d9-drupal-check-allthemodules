<?php

namespace Drupal\partnersite_profile\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining Partnersite profiles entities.
 */
interface PartnersiteProfilesInterface extends ConfigEntityInterface {

    public function getAuthDiv();

    public function setAuthDiv($auth_div);

    public function getAuthSecret();

    public function setAuthSecret($auth_secret);

    public function getAuthMappingHash();

    public function setAuthMappingHash($auth_mapping_hash);

		public function getAuthTimestampExpiry();

		public function setAuthTimestampExpiry( $auth_timestamp_expiry );

		public function getAuthHashLogic();

		public function setAuthHashLogic( $auth_hash_logic );

		public function getPartnerEmail();

		public function setPartnerEmail($partner_email);

}
