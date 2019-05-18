<?php

namespace Drupal\micro_site;

use Drupal\Core\Database\TransactionNameNonUniqueException;
use Drupal\micro_site\Entity\SiteInterface;

/**
 * Handles the negotiation of the active domain record.
 */
interface SiteNegotiatorInterface {

  /**
   * Determines the active site request.
   *
   * The negotiator is passed an httpHost value, which is checked against site
   * records for a match.
   *
   * @param string $httpHost
   *   A string representing the hostname of the request (e.g. example.com).
   * @param bool $reset
   *   Indicates whether to reset the internal cache.
   */
  public function setRequestSite($httpHost, $reset = FALSE);

  /**
   * Sets the active domain.
   *
   * @param \Drupal\micro_site\Entity\SiteInterface $site
   *   Sets the domain record as active for the duration of that request.
   */
  public function setActiveSite(SiteInterface $site);

  /**
   * Stores the inbound httpHost request.
   *
   * @param string $httpHost
   *   A string representing the hostname of the request (e.g. example.com).
   */
  public function setHttpHost($httpHost);

  /**
   * Load a site object by the the hostname.
   *
   * @param string $httpHost
   *   A string representing the hostname of the request (e.g. example.com).
   */
  public function loadByHostname($httpHost);

  /**
   * Load a site object by id.
   *
   * @param integer $id
   *   The site id..
   */
  public function loadById($id);

  /**
   * Load a site object from the current request.
   */
  public function loadFromRequest();

  /**
   * Gets the inbound httpHost request.
   *
   * @return string
   *   A string representing the hostname of the request (e.g. example.com).
   */
  public function getHttpHost();

  /**
   * Gets the id of the active site.
   *
   * @return string
   *   The id of the active site.
   */
  public function getActiveId();

  /**
   * Sets the hostname of the active request.
   *
   * This method is an internal method for use by the public getActiveDomain()
   * call. It is responsible for determining the active hostname of the request
   * and then passing that data to the negotiator.
   *
   * @return string
   *   The hostname, without the "www" if applicable.
   */
  public function negotiateActiveHostname();

  /**
   * Gets the active site.
   *
   * This method should be called by external classes using the negotiator
   * service.
   *
   * @param bool $reset
   *   Reset the internal cache of the active site.
   *
   * @return \Drupal\micro_site\Entity\SiteInterface
   *   The active site object.
   */
  public function getActiveSite($reset = FALSE);

  /**
   * Gets the active site from the hostname or the request as a fallback..
   *
   * This method should be called by external classes using the negotiator
   * service.
   *
   * @param bool $reset
   *   Reset the internal cache of the active site.
   *
   * @return \Drupal\micro_site\Entity\SiteInterface
   *   The active site object.
   */
  public function getSite($reset = FALSE);

  /**
   * Get the host base url configured in the module settings.
   *
   * @return string
   *   The host base url.
   */
  public function getHostUrl();

  /**
   * Is the hostname is the host base url.
   *
   * @return boolean
   *   TRUE is the hostname is the host base url. Otherwise FALSE.
   */
  public function isHostUrl();

  /**
   * Load all the site entities.
   *
   * @return array
   *   An array of site label keyed by site id.
   */
  public function loadOptionsList();

}
