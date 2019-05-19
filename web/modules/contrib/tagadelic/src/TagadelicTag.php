<?php

namespace Drupal\tagadelic;

use Drupal\Component\Utility\SafeMarkup;

class TagadelicTag {
  private $id   = 0;         # Identifier of this tag
  private $name = "";        # A human readable name for this tag.
  private $description = ""; # A human readable piece of HTML-formatted text.
  private $count = 0.0000001;# Absolute count for the weight. Weight, i.e. tag-size will be extracted from this.
  private $weight = 0.0;

  /**
   * Initalize this tag
   * @param id Integer the identifier of this tag
   * @param name String a human readable name describing this tag
   */
  function __construct($id, $name, $count) {
    $this->id    = $id;
    $this->name  = $name;
    if($count != 0) {
      $this->count = $count;
    }
  }

  /**
   * Getter for the ID
   * @ingroup getters
   * return Integer Identifier
   **/
  public function getId() {
    return $this->id;
  }

  /**
   * Getter for the name
   * @ingroup getters
   * return String the human readable name
   **/
  public function getName() {
    return $this->name;
    return SafeMarkup::checkPlain($this->name);
  }

  /**
   * Getter for the description
   * @ingroup getters
   * return String the human readable description
   **/
  public function getDescription() {
    return SafeMarkup::checkPlain($this->description);
  }

  /**
   * Returns the weight, getter only.
   * @ingroup getters
   * return Float the weight of this tag.
   **/
  public function getWeight() {
    return $this->weight;
  }

  /**
   * Returns the count, getter only.
   * @ingroup getters
   * return Int the count as provided when Initializing the Object.
   **/
  public function getCount() {
    return $this->count;
  }

  /**
   * Sets the optional description.
   * A tag may have a description
   * @param $description String a description
   */
  public function setDescription($description) {
    $this->description = $description;
  }

  /**
   * setter for weight
   * Operates on $this
   * Returns $this
   */
  public function setWeight($weight) {
    $this->weight = $weight;
    return $this;
  }

  /**
   * Calculates a more evenly distributed value.
   */
  public function distributed() {
    return log($this->count);
  }
}
