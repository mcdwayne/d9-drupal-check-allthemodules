<?php

namespace Drupal\form_alter_service;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Base form alter.
 *
 * @see \Drupal\form_alter_service\FormAlterCompilerPass::process()
 *
 * @ingroup form_api
 */
abstract class FormAlterBase {

  use StringTranslationTrait;

  /**
   * The form ID or base form ID.
   *
   * @var string
   */
  protected $locator;

  /**
   * The list of handlers of the service.
   *
   * @var array[][][]
   */
  private $handlers = [];

  /**
   * FormAlterBase constructor.
   *
   * @param string $locator
   *   The form ID or base form ID.
   */
  public function __construct(string $locator) {
    $this->locator = $locator;
  }

  /**
   * Form alteration handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  abstract public function alterForm(array &$form, FormStateInterface $form_state);

  /**
   * Checks that form is matched to specific conditions.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param string $form_id
   *   The unique string identifying the form.
   *
   * @return bool
   *   A state of check.
   *
   * @todo Add return type ": bool". Currently missing due to incompatibility with PHPUnit 4.
   */
  public function hasMatch(array $form, FormStateInterface $form_state, string $form_id) {
    return TRUE;
  }

  /**
   * Sets list of handlers of the service.
   *
   * @param array[][][] $handlers
   *   An array, keyed by the type of handler, containing an array keyed by
   *   the handler's strategy and containing an array of arrays with handler
   *   priority and name.
   *
   * @see \Drupal\form_alter_service\Annotation\FormSubmit
   * @see \Drupal\form_alter_service\Annotation\FormValidate
   * @see \Drupal\form_alter_service\FormAlterCompilerPass::getServiceHandlers()
   */
  final public function setHandlers(array $handlers) {
    $this->handlers = $handlers;
  }

  /**
   * Returns list of handlers of the service.
   *
   * @return array[][][]
   *   The list of handlers of the service.
   */
  final public function getHandlers(): array {
    return $this->handlers;
  }

}
