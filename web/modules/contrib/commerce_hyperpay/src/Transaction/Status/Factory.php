<?php

namespace Drupal\commerce_hyperpay\Transaction\Status;

/**
 * Factory class, instantiating Status objects based on given code.
 */
class Factory {

  /**
   * Constructs a new Status object, based on its result code.
   *
   * @param string $code
   *   The result code.
   * @param string $description
   *   The status description.
   *
   * @return \Drupal\commerce_hyperpay\Transaction\Status\AbstractStatus|null
   *   The status instance.
   *
   * @throws \InvalidArgumentException
   *   Thrown, if an invalid code is provided.
   */
  public static function newInstance($code, $description) {
    if (self::isSuccess($code)) {
      return new Success($code, $description);
    }
    if (self::isSuccessNeedingReview($code)) {
      return new SuccessNeedingReview($code, $description);
    }
    if (self::isPending($code)) {
      return new Pending($code, $description);
    }
    if (self::isRejected3dsecureIntercard($code)) {
      return new Rejected3dsecureIntercard($code, $description);
    }
    if (self::isRejectedExternal($code)) {
      return new RejectedExternal($code, $description);
    }
    if (self::isRejectedCommunicationError($code)) {
      return new RejectedCommunicationError($code, $description);
    }
    if (self::isRejectedSystemError($code)) {
      return new RejectedSystemError($code, $description);
    }
    if (self::isRejectedAsyncError($code)) {
      return new RejectedAsyncError($code, $description);
    }
    if (self::isRejectedRiskExternal($code)) {
      return new RejectedRiskExternal($code, $description);
    }
    if (self::isRejectedRiskAddress($code)) {
      return new RejectedRiskAddress($code, $description);
    }
    if (self::isRejectedRisk3dsecure($code)) {
      return new RejectedRisk3dsecure($code, $description);
    }
    if (self::isRejectedRiskBlacklist($code)) {
      return new RejectedRiskBlacklist($code, $description);
    }
    if (self::isRejectedRiskValidation($code)) {
      return new RejectedRiskValidation($code, $description);
    }
    if (self::isRejectedValidationConfiguration($code)) {
      return new RejectedValidationConfiguration($code, $description);
    }
    if (self::isRejectedValidationRegistration($code)) {
      return new RejectedValidationRegistration($code, $description);
    }
    if (self::isRejectedValidationJob($code)) {
      return new RejectedValidationJob($code, $description);
    }
    if (self::isRejectedValidationReference($code)) {
      return new RejectedValidationReference($code, $description);
    }
    if (self::isRejectedValidationFormat($code)) {
      return new RejectedValidationFormat($code, $description);
    }
    if (self::isRejectedValidationAddress($code)) {
      return new RejectedValidationAddress($code, $description);
    }
    if (self::isRejectedValidationContact($code)) {
      return new RejectedValidationContact($code, $description);
    }
    if (self::isRejectedValidationAccount($code)) {
      return new RejectedValidationAccount($code, $description);
    }
    if (self::isRejectedValidationAmount($code)) {
      return new RejectedValidationAmount($code, $description);
    }
    if (self::isRejectedValidationRisk($code)) {
      return new RejectedValidationRisk($code, $description);
    }
    if (self::isChargeback($code)) {
      return new Chargeback($code, $description);
    }
    throw new \InvalidArgumentException('Invalid result code provided: ' . $code);
  }

  /**
   * Get whether the result code translates to a successful transaction.
   *
   * @param string $code
   *   The result code.
   *
   * @return bool
   *   Whether the result code translates to a successful transaction.
   */
  protected static function isSuccess($code) {
    $regex = '/^(000\.000\.|000\.100\.1|000\.[36])/';
    return preg_match($regex, $code) ? TRUE : FALSE;
  }

  /**
   * Checks, if the result code translates to success but needing manual review.
   *
   * @param string $code
   *   The result code.
   *
   * @return bool
   *   Whether the result code translates to a successful transaction that
   *   should be manually reviewed.
   */
  protected static function isSuccessNeedingReview($code) {
    $regex = '/^(000\.400\.0|000\.400\.100)/';
    return preg_match($regex, $code) ? TRUE : FALSE;
  }

  /**
   * Get whether the result code translates to a pending transaction.
   *
   * @param string $code
   *   The result code.
   *
   * @return bool
   *   Whether the result code translates to a pending transaction.
   */
  protected static function isPending($code) {
    $regex = '/^(000\.200)/';
    return preg_match($regex, $code) ? TRUE : FALSE;
  }

  /**
   * Check if the result code means rejected due to 3Dsecure or Intercard check.
   *
   * @param string $code
   *   The result code.
   *
   * @return bool
   *   Whether the result code translates to a rejected transaction, due to
   *   3Dsecure and Intercard risk checks.
   */
  protected static function isRejected3dsecureIntercard($code) {
    $regex = '/^(000\.400\.[1][0-9][1-9]|000\.400\.2)/';
    return preg_match($regex, $code) ? TRUE : FALSE;
  }

  /**
   * Check if the result code means rejected by external bank system.
   *
   * @param string $code
   *   The result code.
   *
   * @return bool
   *   Whether the result code translates to a rejected transaction by the
   *   external bank or similar payment system.
   */
  protected static function isRejectedExternal($code) {
    $regex = '/^(800\.[17]00|800\.800\.[123])/';
    return preg_match($regex, $code) ? TRUE : FALSE;
  }

  /**
   * Check if the result code means rejected due to communication errors.
   *
   * @param string $code
   *   The result code.
   *
   * @return bool
   *   Whether the result code translates to a rejected transaction due to
   *   communication errors.
   */
  protected static function isRejectedCommunicationError($code) {
    $regex = '/^(900\.[1234]00)/';
    return preg_match($regex, $code) ? TRUE : FALSE;
  }

  /**
   * Check if the result code means rejected due to system errors.
   *
   * @param string $code
   *   The result code.
   *
   * @return bool
   *   Whether the result code translates to a rejected transaction due to
   *   system errors.
   */
  protected static function isRejectedSystemError($code) {
    $regex = '/^(800\.5|999\.|600\.1|800\.800\.8)/';
    return preg_match($regex, $code) ? TRUE : FALSE;
  }

  /**
   * Check if the result code means rejected due to async workflow error.
   *
   * @param string $code
   *   The result code.
   *
   * @return bool
   *   Whether the result code translates to a rejected transaction due to error
   *   in asynchronous workflow.
   */
  protected static function isRejectedAsyncError($code) {
    $regex = '/^(100\.39[765])/';
    return preg_match($regex, $code) ? TRUE : FALSE;
  }

  /**
   * Check if the result code means rejected by external risk systems.
   *
   * @param string $code
   *   The result code.
   *
   * @return bool
   *   Whether the result code translates to a rejected transaction due
   *   to checks by external risk systems.
   */
  protected static function isRejectedRiskExternal($code) {
    $regex = '/^(100\.400|100\.38|100\.370\.100|100\.370\.11])/';
    return preg_match($regex, $code) ? TRUE : FALSE;
  }

  /**
   * Check if the result code means rejected due to address validation.
   *
   * @param string $code
   *   The result code.
   *
   * @return bool
   *   Whether the result code translates to a rejected transaction due to
   *   address validation.
   */
  protected static function isRejectedRiskAddress($code) {
    $regex = '/^(800\.400\.1)/';
    return preg_match($regex, $code) ? TRUE : FALSE;
  }

  /**
   * Check if the result code means rejected due to 3Dsecure check.
   *
   * @param string $code
   *   The result code.
   *
   * @return bool
   *   Whether the result code translates to a rejected transaction, due to
   *   3Dsecure check.
   */
  protected static function isRejectedRisk3dsecure($code) {
    $regex = '/^(800\.400\.2|100\.380\.4|100\.390)/';
    return preg_match($regex, $code) ? TRUE : FALSE;
  }

  /**
   * Check if the result code means rejected due to blacklist validation.
   *
   * @param string $code
   *   The result code.
   *
   * @return bool
   *   Whether the result code translates to a rejected transaction, due to
   *   blacklist validation.
   */
  protected static function isRejectedRiskBlacklist($code) {
    $regex = '/^(100\.100\.701|800\.[32])/';
    return preg_match($regex, $code) ? TRUE : FALSE;
  }

  /**
   * Check if the result code means rejected due to risk validation.
   *
   * @param string $code
   *   The result code.
   *
   * @return bool
   *   Whether the result code translates to a rejected transaction, due to
   *   risk validation.
   */
  protected static function isRejectedRiskValidation($code) {
    $regex = '/^(800\.1[123456]0)/';
    return preg_match($regex, $code) ? TRUE : FALSE;
  }

  /**
   * Check if the result code means rejected due to configuration validation.
   *
   * @param string $code
   *   The result code.
   *
   * @return bool
   *   Whether the result code translates to a rejected transaction, due to
   *   configuration validation.
   */
  protected static function isRejectedValidationConfiguration($code) {
    $regex = '/^(600\.2|500\.[12]|800\.121)/';
    return preg_match($regex, $code) ? TRUE : FALSE;
  }

  /**
   * Check if the result code means rejected due to validation registration.
   *
   * @param string $code
   *   The result code.
   *
   * @return bool
   *   Whether the result code translates to a rejected transaction, due
   *   to registration validation.
   */
  protected static function isRejectedValidationRegistration($code) {
    $regex = '/^(100\.[13]50)/';
    return preg_match($regex, $code) ? TRUE : FALSE;
  }

  /**
   * Check if the result code means rejected due to job validation.
   *
   * @param string $code
   *   The result code.
   *
   * @return bool
   *   Whether the result code translates to a rejected transaction, due to job
   *   validation.
   */
  protected static function isRejectedValidationJob($code) {
    $regex = '/^(100\.250|100\.360)/';
    return preg_match($regex, $code) ? TRUE : FALSE;
  }

  /**
   * Check if the result code means rejected due to reference validation.
   *
   * @param string $code
   *   The result code.
   *
   * @return bool
   *   Whether the result code translates to a rejected transaction, due to
   *   reference validation.
   */
  protected static function isRejectedValidationReference($code) {
    $regex = '/^(700\.[1345][05]0)/';
    return preg_match($regex, $code) ? TRUE : FALSE;
  }

  /**
   * Check if the result code means rejected due to format validation.
   *
   * @param string $code
   *   The result code.
   *
   * @return bool
   *   Whether the result code translates to a rejected transaction, due to
   *   format validation.
   */
  protected static function isRejectedValidationFormat($code) {
    $regex = '/^(200\.[123]|100\.[53][07]|800\.900|100\.[69]00\.500)/';
    return preg_match($regex, $code) ? TRUE : FALSE;
  }

  /**
   * Check if the result code means rejected due to address validation.
   *
   * @param string $code
   *   The result code.
   *
   * @return bool
   *   Whether the result code translates to a rejected transaction, due to
   *   address validation.
   */
  protected static function isRejectedValidationAddress($code) {
    $regex = '/^(100\.800)/';
    return preg_match($regex, $code) ? TRUE : FALSE;
  }

  /**
   * Check if the result code means rejected due to contact validation.
   *
   * @param string $code
   *   The result code.
   *
   * @return bool
   *   Whether the result code translates to a rejected transaction, due to
   *   contact validation.
   */
  protected static function isRejectedValidationContact($code) {
    $regex = '/^(100\.[97]00)/';
    return preg_match($regex, $code) ? TRUE : FALSE;
  }

  /**
   * Check if the result code means rejected due to account validation.
   *
   * @param string $code
   *   The result code.
   *
   * @return bool
   *   Whether the result code translates to a rejected transaction, due to
   *   account validation.
   */
  protected static function isRejectedValidationAccount($code) {
    $regex = '/^(100\.100|100.2[01])/';
    return preg_match($regex, $code) ? TRUE : FALSE;
  }

  /**
   * Check if the result code means rejected due to amount validation.
   *
   * @param string $code
   *   The result code.
   *
   * @return bool
   *   Whether the result code translates to a rejected transaction, due to
   *   amount validation.
   */
  protected static function isRejectedValidationAmount($code) {
    $regex = '/^(100\.55)/';
    return preg_match($regex, $code) ? TRUE : FALSE;
  }

  /**
   * Check if the result code means rejected due to risk management.
   *
   * @param string $code
   *   The result code.
   *
   * @return bool
   *   Whether the result code translates to a rejected transaction, due to risk
   *   management.
   */
  protected static function isRejectedValidationRisk($code) {
    $regex = '/^(100\.380\.[23]|100\.380\.101)/';
    return preg_match($regex, $code) ? TRUE : FALSE;
  }

  /**
   * Check if the result code translates to a chargeback related transaction.
   *
   * @param string $code
   *   The result code.
   *
   * @return bool
   *   Whether the result code translates to a chargeback related transaction.
   */
  protected static function isChargeback($code) {
    $regex = '/^(000\.100\.2)/';
    return preg_match($regex, $code) ? TRUE : FALSE;
  }

}
