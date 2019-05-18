<?php

/**
 * @file
 * Contains \Drupal\saudi_identity_field\Plugin\Validation\Constraint\SaudiIdentityCheckConstraintValidator.
 */

namespace Drupal\saudi_identity_field\Plugin\Validation\Constraint;

use Drupal\Core\Field\FieldItemListInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

/**
 * Validates the SaudiIdentityCheckConstraint.
 */
class SaudiIdentityCheckConstraintValidator extends ConstraintValidator {

  /**
   * Validator 2.5 and upwards compatible execution context.
   *
   * @var \Symfony\Component\Validator\Context\ExecutionContextInterface
   */
  protected $context;

  /**
   * {@inheritdoc}
   */
    public function validate($data, Constraint $constraint) {
      $violation = $this->validateSaudiIdentity($data);
      if ($violation instanceof ConstraintViolationBuilderInterface) {
        $violation->atPath($data->getName())->addViolation();
      }
      return;
    }

  function validateSaudiIdentity($data){
    $values = $data->getValue();
    if(!$values){
      return;
    }
//    echo '<pre>';    print_r($values); exit;
    $saudi_identity_type = $data->getSetting('saudi_identity_type');
    $check = $this->SaudiIdentitycheck($values[0]['value'], $type);
    if($check[0] === FALSE){
      return $this->context->addViolation($check[1]);
    }
  }

  /**
   * This algorithm validation was designed by:Eng.Abdul-Aziz Al-Oraij @top7up.
   */
  function SaudiIdentitycheck($saudi_identity, $id_type) {
    if (strlen($saudi_identity) !== 10) {
      return array(FALSE, t('Saudi ID numbers must be exactly 10 digits long'));
    }
    $sum = 0;
    $type = substr($saudi_identity, 0, 1);
    if ($type != 2 && $type != 1) {
      return array(FALSE, t('Invalid Saudi identity number'));
    }
    if ($type != 1 && $id_type == 'saudi') {
      return array(FALSE, t('Invalid Saudi ID number'));
    }
    if ($type != 2 && $id_type == 'iqama') {
      return array(FALSE, t('Invalid Iqama number'));
    }

    for ($i = 0; $i < 10; $i ++ ) {
      if ($i % 2 == 0) {
        $zfodd = str_pad((substr($saudi_identity, $i, 1) * 2), 2, "0", STR_PAD_LEFT);
        $sum += substr($zfodd, 0, 1) + substr($zfodd, 1, 1);
      } else {
        $sum += substr($saudi_identity, $i, 1);
      }
    }
    return ($sum % 10) ? array(FALSE, t('Invalid Saudi identity number')) : array(TRUE, $type);
  }

}
