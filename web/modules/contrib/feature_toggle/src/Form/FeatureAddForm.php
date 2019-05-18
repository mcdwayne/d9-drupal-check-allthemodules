<?php

namespace Drupal\feature_toggle\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\feature_toggle\Feature;
use Drupal\feature_toggle\FeatureManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class FeatureAddForm.
 */
class FeatureAddForm extends FormBase {

  /**
   * Drupal\Core\State\State definition.
   *
   * @var \Drupal\feature_toggle\FeatureManagerInterface
   */
  protected $featureManager;

  /**
   * Constructs a new FeatureAddFrom object.
   */
  public function __construct(FeatureManagerInterface $feature_status) {
    $this->featureManager = $feature_status;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('feature_toggle.feature_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'feature_toggle_add';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Feature Name'),
      '#default_value' => '',
      '#required' => TRUE,
    ];
    $form['name'] = [
      '#type' => 'machine_name',
      '#title' => $this->t('Machine name'),
      '#default_value' => '',
      '#maxlength' => 64,
      '#description' => $this->t('A unique name for the feature. It must only contain lowercase letters, numbers and hyphens.'),
      '#machine_name' => [
        'exists' => [$this, 'featureNameExists'],
        'source' => ['label'],
      ],
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->featureManager->addFeature(new Feature($form_state->getValue('name'), $form_state->getValue('label')));

    drupal_set_message($this->t('Feature <strong>@label</strong> saved successfully.', ['@label' => $form_state->getValue('label')]));
    $form_state->setRedirect('feature_toggle.feature_toggle_form');
  }

  /**
   * Returns whether a feature name already exists.
   *
   * @param string $value
   *   The name of the feature.
   *
   * @return bool
   *   Returns TRUE if the feature already exists, FALSE otherwise.
   */
  public function featureNameExists($value) {
    $features = $this->featureManager->featureExists($value);
    return isset($features[$value]);
  }

}
