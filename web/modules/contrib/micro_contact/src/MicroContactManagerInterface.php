<?php

namespace Drupal\micro_contact;

use Drupal\micro_site\Entity\SiteInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\micro_site\SiteUsers;
use Drupal\node\NodeInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\micro_node\MicroNodeFields;

/**
 * Handles the negotiation of the active domain record.
 */
interface MicroContactManagerInterface {

  /**
   * Determines the current site id.
   *
   * @param array
   *   The current site Id or NULL if not site context found.
   */
  public static function getCurrentSiteId();

  /**
   * Get the contact form allowed for micro sites.
   *
   * @param string $type
   *   The contact form type allowed : canonical or embed.
   * @param boolean $return_entity
   *   Return an array of Contact Form entity
   * @param boolean $reset
   *   Reset the static cache.
   *
   * @return array|\Drupal\contact\ContactFormInterface[]
   *   An array of contact form id (or entity) keyed by the contact form id.
   */
  public function getContactFormAllowed($type = 'canonical', $return_entity = FALSE, $reset = FALSE);

  /**
   * Get the contact form id of a micro site.
   *
   * @param \Drupal\micro_site\Entity\SiteInterface $site
   *   The site entity.
   *
   * @return string
   *   The contact form id.
   */
  public function getSiteContactFormId(SiteInterface $site);

}
