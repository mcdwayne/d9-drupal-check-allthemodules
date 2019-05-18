<?php

namespace Drupal\dhis\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Data element entities.
 *
 * @ingroup dhis
 */
interface DataElementInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface
{

    // Add get/set methods for your configuration properties here.

    /**
     * Gets the Data element name.
     *
     * @return string
     *   Name of the Data element.
     */
    public function getName();

    /**
     * Sets the Data element name.
     *
     * @param string $name
     *   The Data element name.
     *
     * @return \Drupal\dhis\Entity\DataElementInterface
     *   The called Data element entity.
     */
    public function setName($name);

    /**
     * Gets the Data element creation timestamp.
     *
     * @return int
     *   Creation timestamp of the Data element.
     */
    public function getCreatedTime();

    /**
     * Sets the Data element creation timestamp.
     *
     * @param int $timestamp
     *   The Data element creation timestamp.
     *
     * @return \Drupal\dhis\Entity\DataElementInterface
     *   The called Data element entity.
     */
    public function setCreatedTime($timestamp);

    /**
     * Returns the Data element published status indicator.
     *
     * Unpublished Data element are only visible to restricted users.
     *
     * @return bool
     *   TRUE if the Data element is published.
     */
    public function isPublished();

    /**
     * Sets the published status of a Data element.
     *
     * @param bool $published
     *   TRUE to set this Data element to published, FALSE to set it to unpublished.
     *
     * @return \Drupal\dhis\Entity\DataElementInterface
     *   The called Data element entity.
     */
    public function setPublished($published);

    /**
     * Sets the Data element uid.
     *
     * @param string $deuid
     *   The Data element uid.
     *
     * @return \Drupal\dhis\Entity\DataElementInterface
     *   The called Data element entity.
     */
    public function setDataElementUid($deuid);

    /**
     * Gets the Data element uid.
     *
     * @return string
     *   uid of the Data element.
     */
    public function getDataElementUid();

    /**
     * Sets the Data element code.
     *
     * @param string $decode
     *   The Data element code.
     *
     * @return \Drupal\dhis\Entity\DataElementInterface
     *   The called Data element entity.
     */
    public function setDataElementCode($decode);

    /**
     * Gets the Data element code.
     *
     * @return string
     *   code of the Data element.
     */
    public function getDataElementCode();

}
