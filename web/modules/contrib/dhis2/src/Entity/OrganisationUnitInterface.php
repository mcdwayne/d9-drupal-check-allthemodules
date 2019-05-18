<?php

namespace Drupal\dhis\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Organisation unit entities.
 *
 * @ingroup dhis
 */
interface OrganisationUnitInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface
{

    // Add get/set methods for your configuration properties here.

    /**
     * Gets the Organisation unit name.
     *
     * @return string
     *   Name of the Organisation unit.
     */
    public function getName();

    /**
     * Sets the Organisation unit name.
     *
     * @param string $name
     *   The Organisation unit name.
     *
     * @return \Drupal\dhis\Entity\OrganisationUnitInterface
     *   The called Organisation unit entity.
     */
    public function setName($name);

    /**
     * Gets the Organisation unit creation timestamp.
     *
     * @return int
     *   Creation timestamp of the Organisation unit.
     */
    public function getCreatedTime();

    /**
     * Sets the Organisation unit creation timestamp.
     *
     * @param int $timestamp
     *   The Organisation unit creation timestamp.
     *
     * @return \Drupal\dhis\Entity\OrganisationUnitInterface
     *   The called Organisation unit entity.
     */
    public function setCreatedTime($timestamp);

    /**
     * Returns the Organisation unit published status indicator.
     *
     * Unpublished Organisation unit are only visible to restricted users.
     *
     * @return bool
     *   TRUE if the Organisation unit is published.
     */
    public function isPublished();

    /**
     * Sets the published status of a Organisation unit.
     *
     * @param bool $published
     *   TRUE to set this Organisation unit to published, FALSE to set it to unpublished.
     *
     * @return \Drupal\dhis\Entity\OrganisationUnitInterface
     *   The called Organisation unit entity.
     */
    public function setPublished($published);

    /**
     * Sets the organisation unit uid
     *
     * @param $orgunituid
     *
     * @return \Drupal\dhis\Entity\OrganisationUnitInterface
     *
     */
    public function setOrgunitUid($orgunituid);

    /**
     * Returns the Organisation unit uid.
     *
     *
     * @return string
     *
     *   uid of the Organisation Unit
     */
    public function getOrgunitUid();

}
