<?php

namespace Drupal\third_party_services;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;

/**
 * Extension of existing field formatters.
 *
 * Methods definitions are kept the same as in FormatterInterface, but this
 * implementation is not exactly the full formatter.
 *
 * @see \Drupal\Core\Field\FormatterInterface
 */
interface FieldOptionalRenderInterface extends ContainerInjectionInterface {

  /**
   * FieldOptionalRender constructor.
   *
   * @param MediatorInterface $mediator
   *   Instance of the "MODULE.mediator" service.
   */
  public function __construct(MediatorInterface $mediator);

  /**
   * Set settings for the formatter.
   *
   * @param array $settings
   *   An associative array of formatter settings.
   *
   * @see third_party_services_field_formatter_third_party_settings_form()
   * @see third_party_services_field_formatter_settings_summary_alter()
   * @see third_party_services_preprocess_field()
   */
  public function setSettings(array $settings);

  /**
   * Returns placeholders for element containing third-party widgets.
   *
   * @param array $element
   *   Complete element to replace by placeholder.
   * @param \Drupal\Core\Field\FieldItemListInterface $items
   *   Input set of elements.
   *
   * @return \Iterator
   *   Set of elements which should not be rendered.
   */
  public function process(array &$element, FieldItemListInterface $items): \Iterator;

  /**
   * Returns a form for building formatter settings.
   *
   * @param array $form
   *   Form elements.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   A state of form.
   *
   * @return array[]
   *   Configuration form for formatter.
   */
  public function settingsForm(array $form, FormStateInterface $form_state): array;

  /**
   * Returns a short summary for the current formatter settings.
   *
   * @return string[]
   *   A short summary of the formatter settings.
   */
  public function settingsSummary(): array;

  /**
   * Returns a state availability of formatter for particular field.
   *
   * @return bool
   *   A state of availability.
   */
  public function isEnabled(): bool;

}
