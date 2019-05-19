<?php

namespace Drupal\colorapi\Plugin\Validation\Constraint;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\colorapi\Service\ColorapiServiceInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Validates the hexadecimal_color constraint.
 */
class HexColorConstraintValidator extends ConstraintValidator implements ContainerInjectionInterface {

  /**
   * The Color API service.
   *
   * @var \Drupal\colorapi\Service\ColorApiServiceInterface
   */
  protected $colorapiService;

  /**
   * Constructs a HexColorConstraintalidator object.
   *
   * @param \Drupal\colorapi\Service\ColorapiServiceInterface $colorapiService
   *   THe Color API service.
   */
  public function __construct(ColorapiServiceInterface $colorapiService) {
    $this->colorapiService = $colorapiService;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('colorapi.service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function validate($items, Constraint $constraint) {
    if (is_array($items)) {
      foreach ($items as $item) {
        if (!$this->isHexColorString($item)) {
          // The value is not a valid hexadecimal color string, so a violation,
          // aka error, is applied.
          $this->context->addViolation($constraint->notValidHexadecimalColorString, ['%value' => (string) $item]);
        }
      }
    }
    elseif (!$this->isHexColorString($items)) {
      $this->context->addViolation($constraint->notValidHexadecimalColorString, ['%value' => (string) $items]);
    }
  }

  /**
   * Check if a string is a valid hexadecimal color string.
   *
   * @param mixed $value
   *   The item to check as a hexadecimal color string.
   *
   * @return bool
   *   TRUE if the given value is a valid hexadecimal color string. FALSE if it
   *   is not.
   */
  private function isHexColorString($value) {
    return $this->colorapiService->isValidHexadecimalColorString($value);
  }

}
