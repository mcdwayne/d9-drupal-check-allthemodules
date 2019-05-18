<?php

namespace Drupal\commerce_paymetric\lib;

use Exception;
/**
 *
 */
class BaseCore {
  protected $PropertyList;

  //
  // Expect subclass to override this method.
  /**
   * .
   */
  protected function &GetPropertyList() {
    // Pure virtual.
    return $this->PropertyList;
  }

  //
  // Give sub classes a chance to validate array properties
  // should throw exceptions when validation fails.
  
/**
   *
   */
  protected function ValidateArrayProperty($key, $value) {
    // Valid.
    return TRUE;
  }

  //
  // IsValid will be used to see if an object has all required properties.
  
/**
   * .
   */
  public function IsValid() {
    // Base class will always say yes I am valid!
    return TRUE;
  }

  //
  // Call virtual method to get the subclass property list.
  
/**
   * .
   */
  public function __construct() {
    $this->PropertyList = $this->GetPropertyList();
  }

  //
  // Magic functions _get and __set.
  
/**
   * .
   */
  public function __get($key) {
    if (!empty($this->PropertyList[$key])) {
      return $this->$key;
    }
    else {
      throw new Exception("BaseCore::Get - Property $key not exists!");
    }
  }

  /**
   *
   */
  public function __set($key, $value) {
    if (empty($this->PropertyList[$key])) {
      throw new Exception("BaseCore::Set - Property $key not defined!");
    }

    // Enforce the type constraints.
    $typeMatch = 0;
    $typeCheck = "\$typeMatch = " . $this->PropertyList[$key] . ";";

    eval($typeCheck);

    if ($typeMatch <> 1) {
      throw new Exception("BaseCore::Set - Value $key not match its intended type. Evaluation of $typeCheck failed.");
    }

    if (is_array($value)) {
      $this->ValidateArrayProperty($key, $value);
    }

    $this->$key = $value;
  }

}
