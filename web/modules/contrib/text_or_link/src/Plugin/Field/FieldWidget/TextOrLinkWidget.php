<?php

namespace Drupal\text_or_link\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\link\Plugin\Field\FieldWidget\LinkWidget;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationListInterface;

/**
 * Plugin implementation of the 'text_or_link' widget.
 *
 * @FieldWidget(
 *   id = "text_or_link",
 *   label = @Translation("Text or Link"),
 *   field_types = {
 *     "text_or_link"
 *   }
 * )
 */
class TextOrLinkWidget extends LinkWidget {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    // Make URI not required and give it a weight.
    $element['uri']['#required'] = FALSE;
    $element['uri']['#weight'] = 50;

    // Make Title potentially, give it a weight, and more accurate title.
    $element['title']['#title'] = $this->t('Text');
    $element['title']['#required'] = $element['#required'];
    $element['title']['#weight'] = 0;

    // Replace the validation callback.
    foreach ($element['#element_validate'] as $key => $validation) {
      if (array_search(get_called_class(), $validation) !== FALSE) {
        $element['#element_validate'][$key] = [get_called_class(), 'validateTitleElement'];
      }
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);

    // Text overrides and weighting in a logical order.
    $elements['placeholder_title']['#title'] = $this->t('Placeholder for text');
    $elements['placeholder_title']['#weight'] = 0;
    $elements['placeholder_url']['#weight'] = 50;

    return $elements;
  }

  /**
   * {@inheritdoc}
   *
   * For each of the violations from the Link module, let's loop through
   * and determine whether it's actually a violation for this field type.
   */
  public function flagErrors(FieldItemListInterface $items, ConstraintViolationListInterface $violations, array $form, FormStateInterface $form_state) {
    $errors = $violations->getIterator();
    foreach ($errors as $error) {
      $invalid = $error->getInvalidValue();
      if (!empty($invalid)) {
        $values = $invalid->getValue();
        if ($values['uri'] === '' && $values['title'] !== '') {
          $property_path = $error->getPropertyPath();
          foreach ($violations as $offset => $violation) {
            $violation_property_path = $violation->getPropertyPath();
            if ($violation_property_path == $property_path || $violation_property_path == $property_path . '.uri') {
              $violations->remove($offset);
            }
          }
        }
      }
    }

    // Copied straight from the Link module.
    /** @var \Symfony\Component\Validator\ConstraintViolationInterface $violation */
    foreach ($violations as $offset => $violation) {
      $parameters = $violation->getParameters();
      if (isset($parameters['@uri'])) {
        $parameters['@uri'] = static::getUriAsDisplayableString($parameters['@uri']);
        $violations->set($offset, new ConstraintViolation(
          $this->t($violation->getMessageTemplate(), $parameters),
          $violation->getMessageTemplate(),
          $parameters,
          $violation->getRoot(),
          $violation->getPropertyPath(),
          $violation->getInvalidValue(),
          $violation->getPlural(),
          $violation->getCode()
        ));
      }
    }
    parent::flagErrors($items, $violations, $form, $form_state);
  }

}
