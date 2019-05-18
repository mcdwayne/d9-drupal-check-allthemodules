<?php

namespace Drupal\module_builder\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\module_builder\ExceptionHandler;
use DrupalCodeBuilder\Exception\SanityException;

/**
 * Replacement form class to use when DCB is not ready.
 *
 * This is switched by ComponentFormBase::__construct().
 */
class ComponentBrokenForm extends EntityForm {

  /**
   * The exception thrown by DCB.
   *
   * @var \DrupalCodeBuilder\Exception\SanityException
   */
  protected $exception;

  /**
   * Construct a new form object
   *
   * @param \DrupalCodeBuilder\Exception\SanityException $exception
   *   The exception thrown by DCB.
   */
  function __construct(SanityException $exception) {
    $this->exception = $exception;
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    // Pass the DCB exception to the handler, which outputs the error message.
    ExceptionHandler::handleSanityException($this->exception);

    return [];
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    return [];
  }

}
