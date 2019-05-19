<?php

namespace Drupal\webform_composite\Plugin\WebformElement;

use Drupal\webform\WebformSubmissionInterface;
use Drupal\webform\Plugin\WebformElement\WebformCompositeBase;

/**
 * Provides an Global Composite base.
 *
 * @WebformElement(
 *   id = "webform_composite",
 *   label = @Translation("Composite"),
 *   description = @Translation("Provides composite elements."),
 *   category = @Translation("Composite elements"),
 *   multiline = TRUE,
 *   composite = TRUE,
 *   states_wrapper = TRUE,
 *   deriver = "Drupal\webform_composite\Plugin\Derivative\WebformCompositeDeriver"
 * )
 */
class WebformComposite extends WebformCompositeBase {

  /**
   * Global Composite element configuration.
   *
   * @var \Drupal\webform\WebformCompositeInterface
   */
  private $compositeDefinition;

  /**
   * {@inheritdoc}
   */
  public function initializeCompositeElements(array &$element) {
    // @see \Drupal\webform\Plugin\WebformElement\WebformCompositeBase::getInitializedCompositeElement
    $class = $this->getFormElementClassDefinition();
    $element['#webform_composite_elements'] = $class::initializeCompositeElements($element);
  }

  /**
   * Load instance of element source configuration.
   *
   * @return \Drupal\webform\WebformCompositeInterface
   *   Instance of global composite used to derive plugin instance.
   */
  public function getCompositeDefinition() {
    if (!isset($this->compositeDefinition)) {
      $this->compositeDefinition = $this->entityTypeManager->getStorage($this->getBaseId())->load($this->getDerivativeId());
    }
    return $this->compositeDefinition;
  }

  /**
   * {@inheritdoc}
   */
  public function preview() {
    return [
      '#type' => $this->getBaseId(),
      '#webform_composite' => $this->getPluginId(),
      '#title' => $this->getPluginLabel(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function finalize(array &$element, WebformSubmissionInterface $webform_submission = NULL) {
    parent::finalize($element, $webform_submission);
    // Strip the derivative of elements that are not multi valued.
    if ($element['#type'] === $this->getPluginId()) {
      $base_id = $this->getBaseId();
      $element['#type'] = $base_id;
      $element['#' . $base_id] = $this->getPluginId();
    }
    elseif ($element['#type'] === 'webform_multiple' && empty($element['#multiple__header'])) {
      if (isset($element['#element']['#type']) && $element['#element']['#type'] === $this->getPluginId()) {
        $base_id = $this->getBaseId();
        $element['#element']['#type'] = $base_id;
        $element['#element']['#' . $base_id] = $this->getPluginId();
      }
    }
  }

  /**
   * Get composite element.
   *
   * @return array
   *   An array of composite sub-elements.
   */
  public function getCompositeElements() {
    return $this->getCompositeDefinition()->getElementsDecoded();
  }

}
