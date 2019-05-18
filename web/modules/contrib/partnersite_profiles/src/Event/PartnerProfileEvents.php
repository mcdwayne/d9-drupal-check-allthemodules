<?php


namespace Drupal\partnersite_profile\Event;

/**
 * Class PartnerProfileEvents
 * @package Drupal\partnersite_profile\Event
 *
 * Defines following events:
 * partnersite_profile.newpartner: This event will be dispatched by the
 * form controller \Drupal\partnersite_profile\Form\PartnersiteProfilesForm
 * whenever a new partner is registered to the site.
 *
 *
 * @ingroup partnersite_profile
 *
 */

final class PartnerProfileEvents
{
	/**
	 * This event allows modules to perform an action whenever a new
	 * partner profiles is created via the partner profile form.
	 *
	 * @Event
	 *
	 * @var string
	 */
	const NEW_PARTNER = 'partnersite_profile.new_partner_report';

	/**
	 * This event allows modules to perform an action whenever a new
	 * partner profiles is updated via the partner profile form.
	 *
	 * @Event
	 *
	 * @var string
	 */
	const UPD_PARTNER = 'partnersite_profile.upd_partner_report';


	/**
	 * This event allows modules to perform an action whenever a new
	 * partner profiles is deleted via the partner profile form.
	 *
	 * @Event
	 *
	 * @var string
	 */
	const DEL_PARTNER = 'partnersite_profile.del_partner_report';

}