<?php

namespace Drupal\measuremail\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\measuremail\MeasuremailInterface;
use Drupal\measuremail\Plugin\MeasuremailElementsManager;
use Drupal\measuremail\MeasuremailElementsInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides an add form for measuremail elements.
 *
 * @internal
 */
class MeasuremailElementAddForm extends MeasuremailElementFormBase {

  /**
   * The measuremail element manager.
   *
   * @var \Drupal\measuremail\Plugin\MeasuremailElementsManager
   */
  protected $elementManager;

  /**
   * Constructs a new MeasuremailElementAddForm.
   *
   * @param \Drupal\measuremail\Plugin\MeasuremailElementsManager $element_manager
   *   The measuremail element manager.
   */
  public function __construct(MeasuremailElementsManager $element_manager) {
    $this->elementManager = $element_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.measuremail.elements')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, MeasuremailInterface $measuremail = NULL, $measuremail_element = NULL) {
    $form = parent::buildForm($form, $form_state, $measuremail, $measuremail_element);

    $form['#title'] = $this->t('Add %label element', ['%label' => $this->measuremailElement->label()]);
    $form['actions']['submit']['#value'] = $this->t('Add element');

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareMeasuremailElement($measuremail_element) {
    $measuremail_element = $this->elementManager->createInstance($measuremail_element);
    // Set the initial weight so this element comes last.
    $measuremail_element->setWeight(count($this->measuremail->getElements()));
    return $measuremail_element;
  }
}
