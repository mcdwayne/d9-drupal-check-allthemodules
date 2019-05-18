<?php

namespace Drupal\node_delete_redirect\Validate;

use Drupal\Core\Form\FormStateInterface;

class NodeDeleteRedirectElemPathValidate
{
    /**
     * Validates given element.
     *
     * @param array $element The form element to process.
     * @param FormStateInterface $formState The form state.
     * @param array $form The complete form structure.
     */
    public static function validate(array &$element, FormStateInterface $formState, array &$form)
    {
        $parents = $element['#parents'];

        // If parent element is enabled
        if ($formState->getValue("{$parents[0]}")["{$parents[1]}"]['is_enabled']) {
            $value = $element['#value'];

            // Skip empty unique fields or arrays (aka #multiple).
            if ($value === '' || is_array($value)) {
                return;
            }

            // Check if the path is a valid Drupal internal path
            if (!\Drupal::pathValidator()->getUrlIfValid($value)) {
                if (!empty($value)) {
                    $tArgs = [
                        '%value' => $value,
                    ];

                    $formState->setError(
                        $element,
                        t('The path <b>%value</b> is either invalid or you do not have access to it.', $tArgs)
                    );
                } else {
                    $formState->setError($element);
                }
            }
        }

    }
}