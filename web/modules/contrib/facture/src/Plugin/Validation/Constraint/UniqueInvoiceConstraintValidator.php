<?php


/**
 * @file
 * Contains \Drupal\entity_validation\Plugin\Validation\Constraint\UniqueInvoiceConstraintValidator.
 */
namespace Drupal\facture\Plugin\Validation\Constraint;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the UniqueInvoice constraint.
 */
class UniqueInvoiceConstraintValidator extends ConstraintValidator {

    /**
     * {@inheritdoc}
     */
    public function validate($items, Constraint $constraint) {
        foreach ($items as $item) {
//            // First check if the value is an integer.
//            if (!is_int($item->value)) {
//                // The value is not an integer, so a violation, aka error, is applied.
//                // The type of violation applied comes from the constraint description
//                // in step 1.
//                $this->context->addViolation($constraint->notInteger, ['%value' => $item->value]);
//            }

            // Next check if the value is unique.
            if (!$this->isUnique($item->value)) {
                $this->context->addViolation($constraint->notUnique, ['%value' => $item->value]);
            }

        }
    }

    /**
     * Is unique?
     *
     * @param string $value
     */
    private function isUnique($value) {
        // Here is where the check for a unique value would happen.
        $query = \Drupal::entityQuery('node');
        $query->condition('type', 'invoice');
        $query->condition('field_invoice_number', $value);
        $nb_resultats = $query->count()->execute();
        if ($nb_resultats >= 1 ) {
            return false;
        } else {
            return true;
        }
    }
}