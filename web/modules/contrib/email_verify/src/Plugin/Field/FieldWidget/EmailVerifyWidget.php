<?php

/**
 * @file
 * Contains \Drupal\email_verifyPlugin\Field\FieldWidget\EmailVerifyWidget.
 */

namespace Drupal\email_verify\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\EmailDefaultWidget;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\Unicode;

/**
 * Plugin implementation of the 'email_default' widget.
 *
 * @FieldWidget(
 *   id = "email_verify",
 *   label = @Translation("Email Verify"),
 *   field_types = {
 *     "email"
 *   }
 * )
 */
class EmailVerifyWidget extends EmailDefaultWidget {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    $element['#element_validate'][] = array(get_class($this), 'validateElement');

    return $element;
  }

  /**
   * Form validation handler for widget elements.
   *
   * @param array $element
   *   The form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public static function validateElement(array $element, FormStateInterface $form_state) {
    if (isset($element['value']['#value']) && !empty($element['value']['#value'])) {

      $manager = \Drupal::getContainer()->get('email_verify.manager');
      $email = $element['value']['#value'];;
      $host = Unicode::substr(strstr($email, '@'), 1);
      $manager->checkHost($host);

      // Only check full emails if the host can connect out on port 25.
      if (\Drupal::config('email_verify.settings')->get('active')) {
        $manager->checkEmail($element['value']['#value']);
      }

      if ($errors = $manager->getErrors()) {
        foreach ($errors as $error) {
          $form_state->setError($element, $error);
        }
      }
    }
  }
}
