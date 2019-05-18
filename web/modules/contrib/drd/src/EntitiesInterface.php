<?php

namespace Drupal\drd;

/**
 * Interface for DRD entities queries service.
 */
interface EntitiesInterface {

  /**
   * Retrievs a list of set selection criteria for this service.
   *
   * @return array
   *   An array containing all the set selection criteria.
   */
  public function getSelectionCriteria();

  /**
   * Set the tag name to search for.
   *
   * @param string $name
   *   The tag name.
   *
   * @return $this
   */
  public function setTag($name);

  /**
   * Set the host name to search for.
   *
   * @param string $name
   *   The host name.
   *
   * @return $this
   */
  public function setHost($name);

  /**
   * Set the host ID to search for.
   *
   * @param int $id
   *   The host id.
   *
   * @return $this
   */
  public function setHostId($id);

  /**
   * Set the core name to search for.
   *
   * @param string $name
   *   The core name.
   *
   * @return $this
   */
  public function setCore($name);

  /**
   * Set the core ID to search for.
   *
   * @param int $id
   *   The core id.
   *
   * @return $this
   */
  public function setCoreId($id);

  /**
   * Set the domain to search for.
   *
   * @param string $domain
   *   The domain.
   *
   * @return $this
   */
  public function setDomain($domain);

  /**
   * Set the domain ID to search for.
   *
   * @param int $id
   *   The domain id.
   *
   * @return $this
   */
  public function setDomainId($id);

  /**
   * Get selected hosts.
   *
   * @return \Drupal\drd\Entity\HostInterface[]
   *   The selected hosts.
   */
  public function hosts();

  /**
   * Get selected cores.
   *
   * @return \Drupal\drd\Entity\CoreInterface[]
   *   The selected cores.
   */
  public function cores();

  /**
   * Get selected domains.
   *
   * @return \Drupal\drd\Entity\DomainInterface[]
   *   The selected domains.
   */
  public function domains();

}
