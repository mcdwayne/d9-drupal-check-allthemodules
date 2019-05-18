<?php
/**
 * @file
 * Contains \Drupal\facture\Plugin\Validation\Constraint\UniqueInvoiceConstraint.
 */
namespace Drupal\facture\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Checks that the submitted value is a unique integer.
 *
 * @Constraint(
 *   id = "UniqueInvoice",
 *   label = @Translation("Unique Invoice", context = "Validation"),
 *   type = "string"
 * )
 */
class UniqueInvoiceConstraint extends Constraint {
//
//    // The message that will be shown if the value is not an integer.
//    public $notInteger = '%value is not an integer';

    // The message that will be shown if the value is not unique.
    public $notUnique = 'Le numéro de facture %value existe déja. Veuillez saisir un numéro unique.';

}
