<?php

namespace Drupal\norwegian_id\Plugin\Validation\Constraint;

use Drupal\Component\Datetime\DateTimePlus;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the Norwegian ID constraint.
 */
class NorwegianIdConstraintValidator extends ConstraintValidator {

  use StringTranslationTrait;

  /**
   * Error constants.
   */
  const ERR_INVALID_FORMAT   = 1;
  const ERR_INVALID_BIRTH    = 2;
  const ERR_INVALID_CHECKSUM = 3;

  /**
   * Validates the field value. Callback validation.
   *
   * @param string $value
   *   The Fødselsnummer to be tested.
   * @param \Symfony\Component\Validator\Constraint $constraint
   *   Constraint context.
   */
  public function validate($value, Constraint $constraint) {
    if (!is_string($value)) {
      throw new UnexpectedTypeException($value, 'string');
    }

    $validation = $this->isValidNorwegianId($value);

    if ($validation !== TRUE) {
      switch ($validation) {
        case self::ERR_INVALID_FORMAT:
          $this->context->addViolation($constraint->invalidFormatMessage);
          break;

        case self::ERR_INVALID_BIRTH:
          $this->context->addViolation($constraint->invalidBirthMessage);
          break;

        case self::ERR_INVALID_CHECKSUM:
          $this->context->addViolation($constraint->invalidControlDigitsMessage);
          break;
      }
    }
  }

  /**
   * Verify that the norwegian id is valid.
   *
   * @param string $value
   *   The Fødselsnummer to be tested.
   *
   * @see http://www.skatteetaten.no/nn/Person/Folkeregister/Fodsel-og-namneval/Barn-fodde-i-Noreg/Fodselsnummer/
   * @see http://www.skatteetaten.no/en/person/National-Registry/Birth-and-name-selection/Children-born-in-Norway/National-ID-number/
   *
   * @todo: allow selecting an entity field to get the data and gender from
   *    example: $constraint->getRoot()->get('field_birth_date')->date;
   *    example: $constraint->getRoot()->get('field_gender')->date;
   *
   * @return true|int
   *   Any of the self::ERR_* constants or true if the validation works fine.
   */
  public function isValidNorwegianId($value) {
    // Very basic regex validation at first.
    $regex_day   = '(0[1-9]|[12][0-9]|3[01])';
    $regex_month = '(0[1-9]|1[012])';
    $regex_year  = '[0-9]{2}';
    $regex_id    = '[0-9]{5}';
    $pattern     = "/{$regex_day}{$regex_month}{$regex_year}{$regex_id}/";
    if (!preg_match($pattern, $value)) {
      return self::ERR_INVALID_FORMAT;
    }
    else {
      $date_str = substr($value, 0, 6);
      try {
        $date = DateTimePlus::createFromFormat('dmy', $date_str);

        $individualNum = substr($value, 6, 3);
        if (!$this->isValidIndividualNum($individualNum, $date->format('Y'))) {
          return self::ERR_INVALID_BIRTH;
        }

        $id_as_array = str_split($value);
        $digit1 = $this->getFirstControlDigit($id_as_array);
        $digit2 = $this->getSecondControlDigit($id_as_array, $digit1);

        if (!$this->isValidControlDigits($id_as_array, $digit1, $digit2)) {
          return self::ERR_INVALID_CHECKSUM;
        }

      }
      catch (\Exception $e) {
        // DateTimePlus::createFromFormat() throws Exception!
        return self::ERR_INVALID_BIRTH;
      }
    }

    return TRUE;
  }

  /**
   * Verifies that the "Individual Number" matches with the year of birth.
   *
   * @param int $individualNumber
   *   The 3 digits individual number.
   * @param int $birthYear
   *   Birth year in format YYYY.
   *
   * @return bool
   *   Appropriate response.
   */
  protected function isValidIndividualNum($individualNumber, $birthYear) {
    // Group from 0 to 499.
    if ($individualNumber >= 0 && $individualNumber <= 499) {
      return $birthYear >= 1900 && $birthYear <= 1999;
    }
    // Special handling for overlap.
    if ($individualNumber <= 999) {
      $from_ixx_century = $from_xx_century = $from_xxi_century = FALSE;
      // Group from 500 to 749.
      if ($individualNumber <= 749) {
        $from_ixx_century = $birthYear >= 1854 && $birthYear <= 1899;
      }
      // Group from 900 to 999 = XX Century.
      if ($individualNumber >= 900) {
        $from_xx_century = $birthYear >= 1940 && $birthYear <= 1999;
      }
      // Group from 500 to 999 = XXI Century.
      if ($individualNumber >= 500) {
        $from_xxi_century = $birthYear >= 2000 && $birthYear <= 2039;
      }

      // Can be valid if either of those is true.
      return $from_ixx_century || $from_xx_century || $from_xxi_century;
    }

    return FALSE;
  }

  /**
   * Calculate the 1st control digit based on the ID.
   *
   * @param array $n
   *   Norwegian Personal ID, split into an array.
   *
   * @return int
   *   Returns the first calculated digit;
   */
  protected function getFirstControlDigit(array $n) {
    if (count($n) !== 11) {
      throw new \UnexpectedValueException($this->t("Not a valid Norwegian Personal ID"));
    }

    $controlDigit1 = 11 - (3 * $n[0] + 7 * $n[1] + 6 * $n[2] + 1 * $n[3] + 8 * $n[4] + 9 * $n[5] + 4 * $n[6] + 5 * $n[7] + 2 * $n[8]) % 11;
    if ($controlDigit1 == 11) {
      $controlDigit1 = 0;
    }

    return $controlDigit1;
  }

  /**
   * Calculate the 2nd control digit based on the ID and the 1st control digit.
   *
   * @param array $n
   *   Norwegian Personal ID, split into an array.
   * @param int $controlDigit1
   *   First control digit.
   *
   * @return int
   *   Returns the first calculated digit;
   */
  protected function getSecondControlDigit(array $n, $controlDigit1) {
    if (count($n) !== 11) {
      throw new \UnexpectedValueException($this->t("Not a valid Norwegian Personal ID"));
    }

    $controlDigit2 = 11 - (5 * $n[0] + 4 * $n[1] + 3 * $n[2] + 2 * $n[3] + 7 * $n[4] + 6 * $n[5] + 5 * $n[6] + 4 * $n[7] + 3 * $n[8] + 2 * $controlDigit1) % 11;
    if ($controlDigit2 == 11) {
      $controlDigit2 = 0;
    }

    return $controlDigit2;
  }

  /**
   * Verify that both control digits are matching.
   *
   * @param array $n
   *   Norwegian Personal ID, split into an array.
   * @param int $digit1
   *   First calculated control digit.
   * @param int $digit2
   *   Second calculated control digit.
   *
   * @return bool
   *   Appropriate response.
   */
  protected function isValidControlDigits(array $n, $digit1, $digit2) {
    // Verify 1st digit.
    if (intval($digit1) !== intval($n[9]) && $digit1 !== 10) {
      return FALSE;
    }
    // Verify 2nd digit.
    if (intval($digit2) !== intval($n[10]) && $digit2 !== 10) {
      return FALSE;
    }

    return TRUE;
  }

}
