<?php

namespace Drupal\flexiform\FormComponent;

use Drupal\Core\Form\FormStateInterface;
use Drupal\flexiform\FlexiformEntityFormDisplay;
use Drupal\flexiform\FormEntity\FlexiformFormEntityManager;

/**
 * Provides the base form component plugin.
 */
abstract class FormComponentBase implements FormComponentInterface {

  /**
   * The name of this component.
   *
   * @var string
   */
  protected $name = '';

  /**
   * The options supplied for this component.
   *
   * @var array
   */
  protected $options = [];

  /**
   * The form entity manager.
   *
   * @var \Drupal\flexiform\FormEntity\FlexiformFormEntityManager
   */
  protected $formEntityManager = NULL;

  /**
   * The form display.
   *
   * @var \Drupal\flexiform\FlexiformEntityFormDisplay
   */
  protected $formDisplay = NULL;

  /**
   * @return \Drupal\flexiform\FormEntity\FlexiformFormEntityManager
   */
  public function getFormEntityManager() {
    return $this->formEntityManager;
  }

  /**
   * @param FlexiformFormEntityManager $manager
   */
  public function setFormEntityManager(FlexiformFormEntityManager $manager) {
    $this->formEntityManager = $manager;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setFormDisplay(FlexiformEntityFormDisplay $form_display) {
    $this->formDisplay = $form_display;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormDisplay() {
    return $this->formDisplay;
  }

  /**
   * {@inheritdoc}
   */
  public function getAdminLabel() {
    if ($namespace = $this->getEntityNamespace()) {
      return $this->options['label'] . ' [' . $this->getFormEntityManager()->getFormEntity($namespace)->getFormEntityContextDefinition()->getLabel() . ']';
    }
    return $this->options['label'];
  }

  /**
   * Get the entity namespace from the component name.
   */
  protected function getEntityNamespace() {
    if (strpos($this->name, ':')) {
      list($namespace, $component_id) = explode(':', $this->name, 2);
      return $namespace;
    }

    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function __construct($name, array $options, FlexiformEntityFormDisplay $form_display) {
    $this->name = $name;
    $this->options = $options;
    $this->setFormDisplay($form_display);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $settings_form['settings'] = [
      '#type' => 'container',
      'admin_label' => [
        '#type' => 'textfield',
        '#title' => t('Admin Label'),
        '#default_value' => $this->options['admin_label'],
      ],
    ];

    return $settings_form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
  }

  /**
   * {@inheritdoc}
   */
  public function settingsFormSubmit($values, array $form, FormStateInterface $form_state) {
    return [
      'settings' => [
        'label' => $values['settings']['label'],
      ],
    ];
  }

}
