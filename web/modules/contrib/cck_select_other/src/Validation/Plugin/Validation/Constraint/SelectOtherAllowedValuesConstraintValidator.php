<?php

namespace Drupal\cck_select_other\Validation\Plugin\Validation\Constraint;

use Drupal\cck_select_other\EntityDisplayTrait;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\TypedData\ComplexDataInterface;
use Drupal\Core\TypedData\OptionsProviderInterface;
use Drupal\Core\TypedData\Validation\TypedDataAwareValidatorTrait;
use Drupal\options\Plugin\Field\FieldType\ListItemBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\ChoiceValidator;

/**
 * Bypass AllowedValuesConstraintValidator by rewriting it.
 *
 * This is not the "right way", but there is no other method to allow a widget
 * to modify the allowed values of a list field in Drupal 8 thanks to
 * "decoupling".Instead this class re-couples the dependency even when the
 * widget is not in-use for a field instance. DrupalWTF.
 *
 * Instead Drupal core fields should provide a means to override validation or
 * provide non-widget based validation for web services.
 */
class SelectOtherAllowedValuesConstraintValidator extends ChoiceValidator implements ContainerInjectionInterface {

  use TypedDataAwareValidatorTrait;
  use EntityDisplayTrait;

  /**
   * The current user account session.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_user'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * Constructs a new SelectOtherAllowedValuesConstraintValidator.
   *
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user. Used for fallback mode.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity_type.manager service.
   */
  public function __construct(AccountInterface $current_user, EntityTypeManagerInterface $entityTypeManager) {
    $this->currentUser = $current_user;
    $this->setEntityTypeManager($entityTypeManager);
  }

  /**
   * {@inheritdoc}
   */
  public function validate($value, Constraint $constraint) {
    $typed_data = $this->getTypedData();

    // Only bypass validation for ListItemBase.
    if ($typed_data instanceof ListItemBase) {
      // Get the field instance definition.
      $constraint->choices = [];

      /** @var \Drupal\Core\Field\FieldDefinitionInterface $instance */
      $instance = $typed_data->getFieldDefinition();
      $value = $typed_data->getValue();

      if ($this->hasSelectOtherWidget($instance) && !in_array($value, $constraint->choices)) {
        // Add the other value to the constraint choices.
        $constraint->choices[] = $value;
      }
    }

    if (empty($constraint->choices)) {
      $this->validateFallback($value, $constraint);
      return;
    }

    // The parent implementation ignores values that are not set, but makes
    // sure some choices are available firstly. However, we want to support
    // empty choices for undefined values, e.g. if a term reference field
    // points to an empty vocabulary.
    if (!isset($value)) {
      return;
    }

    parent::validate($value, $constraint);
  }

  /**
   * Fallback to what core does.
   *
   * @param mixed $value
   *   The value to check.
   * @param \Symfony\Component\Validator\Constraint $constraint
   *   Constraint object.
   *
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   * @throws \LogicException
   */
  public function validateFallback($value, Constraint $constraint) {
    $typed_data = $this->getTypedData();

    if ($typed_data instanceof OptionsProviderInterface) {
      $allowed_values = $typed_data->getSettableValues($this->currentUser);
      $constraint->choices = $allowed_values;

      // If the data is complex, we have to validate its main property.
      if ($typed_data instanceof ComplexDataInterface) {
        $name = $typed_data->getDataDefinition()->getMainPropertyName();
        if (!isset($name)) {
          throw new \LogicException('Cannot validate allowed values for complex data without a main property.');
        }
        $value = $typed_data->get($name)->getValue();
      }
    }

    // The parent implementation ignores values that are not set, but makes
    // sure some choices are available firstly. However, we want to support
    // empty choices for undefined values, e.g. if a term reference field
    // points to an empty vocabulary.
    if (!isset($value)) {
      return;
    }

    parent::validate($value, $constraint);
  }

}
