<?php

namespace Drupal\content_entity_builder\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\content_entity_builder\ConfigurableBaseFieldConfigInterface;
use Drupal\content_entity_builder\ContentTypeInterface;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Provides a base form for base field.
 */
abstract class BaseFieldConfigFormBase extends FormBase {

  /**
   * The content entity type.
   *
   * @var \Drupal\content_entity_builder\ContentTypeInterface
   */
  protected $contentType;

  /**
   * The base field.
   *
   * @var \Drupal\content_entity_builder\BaseFieldConfigInterface
   */
  protected $baseField;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'base_field_form';
  }

  /**
   * {@inheritdoc}
   *
   * @param \Drupal\content_entity_builder\ContentTypeInterface $content_type
   *   The content_type.
   * @param string $base_field
   *   The base_field ID.
   *
   * @return array
   *   The form structure.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   */
  public function buildForm(array $form, FormStateInterface $form_state, ContentTypeInterface $content_type = NULL, $base_field = NULL) {
    $this->contentType = $content_type;
    try {
      $this->baseField = $this->prepareBaseField($base_field);
    }
    catch (PluginNotFoundException $e) {
      throw new NotFoundHttpException("Invalid base field id: '$base_field'.");
    }
    $request = $this->getRequest();

    if (!($this->baseField instanceof ConfigurableBaseFieldConfigInterface)) {
      throw new NotFoundHttpException();
    }

    $form['#attached']['library'][] = 'content_entity_builder/admin';

    $form['id'] = [
      '#type' => 'hidden',
      '#value' => $this->baseField->getPluginId(),
    ];

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#default_value' => $this->baseField->getLabel(),
    ];

    $form['description'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Description'),
      '#default_value' => $this->baseField->getDescription(),
    ];

    $form['required'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Required'),
      '#default_value' => $this->baseField->isRequired(),
    ];
	
    $form['index'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Index'),
      '#description' => $this->t('Check it when you need add db index for this base field.'),
      '#default_value' => $this->baseField->hasIndex(),
    ];	

    $form['default_value'] = [
      '#type' => 'details',
      '#title' => $this->t('Default value'),
      '#open' => TRUE,
    ];

    // Add handling for default value.
    if ($element = $this->baseField->buildDefaultValueForm([], $form_state)) {
      $element = array_merge($element, [
        '#type' => 'details',
        '#title' => $this->t('Default value'),
        '#open' => TRUE,
        '#tree' => TRUE,
        '#description' => $this->t('The default value for this field, used when creating new content.'),
      ]);

      $form['default_value'] = $element;
    }

    $form_state->setValue('has_data', $this->contentType->hasData());
    $form_state->setValue('applied', $this->baseField->isApplied());
    $form['settings'] = $this->baseField->buildConfigurationForm([], $form_state);
    $form['settings']['#tree'] = TRUE;

    $form['weight'] = [
      '#type' => 'hidden',
      '#value' => $request->query->has('weight') ? (int) $request->query->get('weight') : $this->baseField->getWeight(),
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#button_type' => 'primary',
    ];
    $form['actions']['cancel'] = [
      '#type' => 'link',
      '#title' => $this->t('Cancel'),
      '#url' => $this->contentType->urlInfo('edit-form'),
      '#attributes' => ['class' => ['button']],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // The base field configuration is stored in the 'settings' key in the form,
    // pass that through for validation.
    $settings = $form_state->getValue('settings');
    if (!empty($settings)) {
      $this->baseField->validateConfigurationForm($form['settings'], SubformState::createForSubform($form['settings'], $form, $form_state));
    }

    $default_value = $form_state->getValue('default_value');
    if (!empty($default_value)) {

    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->cleanValues();

    // The base field configuration is stored in the 'settings' key in the form,
    // pass that through for submission.
    $settings = $form_state->getValue('settings');
    if (!empty($settings)) {
      $base_field_settings = (new FormState())->setValues($settings);
      $this->baseField->submitConfigurationForm($form, $base_field_settings);
      // Update the original form values.
      $form_state->setValue('settings', $base_field_settings->getValues());

      $this->baseField->setSettings($base_field_settings->getValues());
    }

    $default_value = $form_state->getValue('default_value');
    if (!empty($default_value)) {
      $base_field_default_value = (new FormState())->setValues($default_value);
      $this->baseField->submitDefaultValueForm($form, $base_field_default_value);
    }
    $this->baseField->setLabel($form_state->getValue('label'));
    $this->baseField->setRequired($form_state->getValue('required'));
    $this->baseField->setDescription($form_state->getValue('description'));
    $this->baseField->setIndex($form_state->getValue('index'));
    $this->contentType->save();

    $form_state->setRedirectUrl($this->contentType->urlInfo('edit-form'));
  }

  /**
   * Converts a base_field ID into an object.
   *
   * @param string $base_field
   *   The base_field ID.
   *
   * @return \Drupal\content_entity_builder\BaseFieldConfigInterface
   *   The base_field object.
   */
  abstract protected function prepareBaseField($base_field);

}
