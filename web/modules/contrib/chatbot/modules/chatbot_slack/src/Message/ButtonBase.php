<?php

namespace Drupal\chatbot_slack\Message;

use Doctrine\Common\Proxy\Exception\InvalidArgumentException;

/**
 * Base class for a button object.
 */
abstract class ButtonBase {

  const VALID_TYPES = ['button'];
  protected $type;
  protected $text;
  protected $name;
  protected $value;

  /**
   * ButtonBase constructor.
   *
   * @param string $type
   *   The button type.
   * @param string $text
   *   The button's text.
   * @param string $name
   *   The string correlating to the name attribute set in the originating action
   * @param string $value
   *   The string correlating to the value attribute set in the originating action
   */
  public function __construct($type, $text, $name, $value) {
    self::assertValidType($type);
    $this->type = $type;
    $this->text = $text;
    $this->name = $name;
    $this->value = $value;
  }

  /**
   * Assert a valid button type.
   *
   * @param string $type
   *   The type to be examined.
   *
   * @throws InvalidArgumentException
   *   Thrown if the type supplied is not one of the allowed types.
   */
  public static function assertValidType($type) {
    if (!in_array($type, self::VALID_TYPES)) {
      throw new InvalidArgumentException("Type {$type} is not a valid button type.");
    }
  }

  /**
   * Returns a object of the button's properties and values.
   *
   * @return object
   *   An object with all properties and values
   */
  public function toObject() {
    $props = new \stdClass();
    foreach ($this as $var => $value) {
      $props->{$var} = $value;
    }
    return $props;
  }

}
