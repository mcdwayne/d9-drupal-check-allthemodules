<?php

namespace CleverReach\BusinessLogic\Entity;

use CleverReach\Infrastructure\Interfaces\Required\Configuration;
use CleverReach\Infrastructure\ServiceRegister;

/**
 *
 */
class Tag implements \Serializable {
  /**
   * @var string*/
  protected $type;
  /**
   * @var string*/
  protected $name;
  /**
   * @var string*/
  protected $prefix;
  /**
   * @var bool*/
  protected $isDeleted;

  /**
   * Tag is in format "Prefix-Type.Name".
   * "Prefix" is automatically appended by core if "Type" is set and should not be set in integrations.
   *
   * @param string $name
   *   Name of the tag.
   * @param string $type
   *   Type of the tag, e.g. Group, Website, Domain...
   *
   * @throws \InvalidArgumentException Name and Type parameters cannot be empty
   */
  public function __construct($name, $type) {
    $this->name = $name;
    $this->type = $type;
    $this->isDeleted = FALSE;

    $this->validate();

    // Disclaimer:
    // Core needs integration prefix in order to properly distinguish tags on CleverReach added by
    // integration from the ones added by user. It is general convention to use integration name as
    // tag prefix. The only reason why prefix is added only when type is not empty
    // (and the reason type can be empty) is backward compatibility when tag was a single string.
    // Also, because CORE requires prefix, it is added here, although accessing service from entity in this way
    // is not a good practice, but it could not be done differently because of PHP language limitations.
    if (!empty($this->type)) {
      $this->prefix = ServiceRegister::getService(Configuration::CLASS_NAME)->getIntegrationName();
    }
  }

  /**
   * Marks tag deleted so that it can be removed on remote API.
   */
  public function markDeleted() {
    $this->isDeleted = TRUE;
  }

  /**
   * Checks whether two tags are semantically equal. Does not compare object instances.
   *
   * @param \CleverReach\BusinessLogic\Entity\Tag|string $tag
   *
   * @return bool
   */
  public function isEqual($tag) {
    return (string) $this === (string) $tag;
  }

  /**
   * Gets tag as readable string in format "Type: Name".
   */
  public function getTitle() {
    $result = $this->type ? $this->type . ': ' : '';
    $result .= $this->name;

    return $result;
  }

  /**
   * Gets tag as string in format "IntegrationName-Type.Name".
   *
   * @return string
   */
  public function __toString() {
    $pattern = "/[^a-zA-Z0-9_\\p{L}]+/u";
    $name = empty($this->type) ? $this->name : preg_replace($pattern, '_', $this->name);
    $type = preg_replace($pattern, '_', $this->type);
    $prefix = preg_replace($pattern, '_', $this->prefix);

    $result = $this->isDeleted ? '-' : '';
    $result .= $prefix ? : '';

    if ($prefix && $type) {
      // Implode with - only if both exist.
      $result .= '-';
    }

    $result .= $type ? : '';
    if ($prefix || $type) {
      $result .= '.';
    }

    $result .= $name;

    return $result;
  }

  /**
   * @inheritdoc
   */
  public function serialize() {
    return serialize([
      $this->name,
      $this->type,
      $this->prefix,
      $this->isDeleted,
    ]);
  }

  /**
   * @inheritdoc
   */
  public function unserialize($serialized) {
    list($this->name, $this->type, $this->prefix, $this->isDeleted) = unserialize($serialized);
  }

  /**
   * Validates "Name" and "Type" for tag.
   *
   * @throws \InvalidArgumentException Name and Type parameters cannot be empty!
   */
  protected function validate() {
    if (empty($this->name) || empty($this->type)) {
      throw new \InvalidArgumentException('Name and Type parameters cannot be empty!');
    }
  }

}
