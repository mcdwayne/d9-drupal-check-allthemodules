<?php

namespace Drupal\loading_bar_library\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\loading_bar\Form\LoadingBarConfigurationForm;

/**
 * Form handler for loading bar style forms.
 */
class LoadingBarStyleForm extends EntityForm {

  /**
   * The loading bar configuration form instance.
   *
   * @var \Drupal\loading_bar\Form\LoadingBarConfigurationForm
   */
  protected $configurationForm;

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /** @var \Drupal\loading_bar_library\Entity\LoadingBarStyleInterface $entity */
    $entity = $this->entity;

    $form['#tree'] = TRUE;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $entity->label(),
      '#description' => $this->t('Label for the loading bar style.'),
      '#required' => TRUE,
    ];
    $form['id'] = [
      '#type' => 'machine_name',
      '#maxlength' => EntityTypeInterface::ID_MAX_LENGTH,
      '#description' => $this->t('A unique name for this loading bar style instance. Must be alpha-numeric and underscore separated.'),
      '#default_value' => !$entity->isNew() ? $entity->id() : NULL,
      '#machine_name' => [
        'exists' => '\Drupal\loading_bar_library\Entity\LoadingBarStyle::load',
        'replace_pattern' => '[^a-z0-9_.]+',
        'source' => ['label'],
      ],
      '#required' => TRUE,
      '#disabled' => !$entity->isNew(),
    ];
    $form['description'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Description'),
      '#default_value' => $entity->getDescription(),
    ];

    // Get loading bar configuration.
    $form['style_configuration'] = [
      '#tree' => TRUE,
      // If we use the same key as the property, ajax reload do not detect the
      // changes.
      '#parents' => ['style_configuration'],
    ];
    if (empty($this->configurationForm)) {
      $this->configurationForm = LoadingBarConfigurationForm::create(\Drupal::getContainer(), $entity->getConfiguration());
    }
    $subform_state = SubformState::createForSubform($form['style_configuration'], $form, $form_state);
    $form['style_configuration'] = $this->configurationForm->buildForm($form['style_configuration'], $subform_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\loading_bar_library\Entity\LoadingBarStyleInterface $entity */
    $entity = $this->entity;

    // The loading bar style configuration is stored in the
    // 'style_configuration'.
    $subform_state = SubformState::createForSubform($form['style_configuration'], $form, $form_state);
    $this->configurationForm->validateForm($form['style_configuration'], $subform_state);

    // Process configuration form state errors.
    $this->processConfigurationFormErrors($subform_state, $form_state);

    // Update the original form values.
    $form_state->setValue('style_configuration', $subform_state->getValues());
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // The loading bar style configuration is stored in the 'configuration'.
    $subform_state = SubformState::createForSubform($form['style_configuration'], $form, $form_state);
    $this->configurationForm->submitForm($form['style_configuration'], $subform_state);

    // Update the original form values.
    $form_state->setValue('style_configuration', $subform_state->getValues());

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\loading_bar_library\Entity\LoadingBarStyleInterface $entity */
    $entity = $this->entity;

    $entity->setConfiguration($form_state->getValue('style_configuration'));
    $status = $entity->save();

    $t_args = ['%name' => $entity->label()];
    if ($status == SAVED_UPDATED) {
      // $this->messenger()->addStatus();
      drupal_set_message($this->t('The loading bar style %name has been updated.', $t_args));
    }
    elseif ($status == SAVED_NEW) {
      // $this->messenger()->addStatus();
      drupal_set_message($this->t('The loading bar style %name has been added.', $t_args));

      $context = array_merge($t_args, [
        'link' => $entity->toLink($this->t('View'), 'collection')->toString(),
      ]);
      $this->logger('loading_bar_library')->notice('Added loading bar style %name.', $context);
    }

    $form_state->setRedirectUrl($entity->toUrl('collection'));
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);

    $actions['submit']['#value'] = t('Save loading bar style');
    $actions['delete']['#value'] = t('Delete loading bar style');

    return $actions;
  }

  /**
   * Process configuration form errors in form.
   *
   * @param \Drupal\Core\Form\FormStateInterface $configuration_state
   *   The loading bar style form state.
   * @param \Drupal\Core\Form\FormStateInterface &$form_state
   *   The form state.
   */
  protected function processConfigurationFormErrors(FormStateInterface $configuration_state, FormStateInterface &$form_state) {
    foreach ($configuration_state->getErrors() as $name => $message) {
      $form_state->setErrorByName($name, $message);
    }
  }

}
