<?php

namespace Drupal\responsive_class_field\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\responsive_class_field\ResponsiveClassField;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure Responsive Class Field default settings.
 */
class ResponsiveClassFieldSettingsForm extends ConfigFormBase {

  /**
   * The Responsive Class Field service.
   *
   * @var \Drupal\responsive_class_field\ResponsiveClassField
   */
  protected $responsiveClassField;

  /**
   * Construct a ResponsiveClassFieldSettingsForm instance.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\responsive_class_field\ResponsiveClassField $responsive_class
   *   The Responsive Class Field service.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    ResponsiveClassField $responsive_class
  ) {
    parent::__construct($config_factory);

    $this->responsiveClassField = $responsive_class;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('responsive_class_field')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'responsive_class_field_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['responsive_class_field.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('responsive_class_field.settings');

    $form['#tree'] = TRUE;

    $form['breakpoint_defaults'] = [
      '#type' => 'details',
      '#title' => $this->t('Breakpoint defaults'),
      '#description' => $this->t("Configure the default breakpoint settings that will be used for newly created responsive class fields. These values can be altered for every field within it's field storage settings."),
      '#open' => TRUE,
    ];

    $breakpoint_group = $config->get('breakpoint_defaults.breakpoint_group');
    $form['breakpoint_defaults']['breakpoint_group'] = [
      '#type' => 'select',
      '#title' => $this->t('Breakpoint group'),
      '#options' => $this->responsiveClassField->getBreakpointGroups(),
      '#required' => TRUE,
      '#description' => $this->t('Select the breakpoint group to use by default.'),
      '#ajax' => [
        'callback' => [$this, 'breakpointMappingFormAjax'],
        'wrapper' => 'responsive-class-breakpoints-wrapper',
      ],
      '#default_value' => $breakpoint_group,
    ];

    // Add breakpoints settings subform.
    $form['breakpoint_defaults'] += $this->responsiveClassField->buildBreakpointsSettingsForm(
      [],
      SubformState::createForSubform($form['breakpoint_defaults'], $form, $form_state),
      $form_state->getValue(['breakpoint_defaults', 'breakpoint_group']) ?: $breakpoint_group,
      NULL,
      'breakpoint_defaults'
    );

    $form['attach_conditions'] = [
      '#type' => 'container',
    ];
    $form['attach_conditions']['attach_condition_tabs'] = [
      '#type' => 'vertical_tabs',
      '#title' => $this->t('Automatic CSS class conditions'),
      '#description' => $this->t('Enable the theme(s) where generated responsive CSS classes shall be attached to the entities.'),
      '#parents' => ['attach_condition_tabs'],
    ];

    $conditions = $this->responsiveClassField->getConditions();
    foreach ($conditions as $condition_id => $condition) {
      $form_state->set(['attach_conditions', $condition_id], $condition);
      $condition_form = $condition->buildConfigurationForm([], $form_state);
      $condition_form['#type'] = 'details';
      $condition_form['#title'] = $condition->getPluginDefinition()['label'];
      $condition_form['#group'] = 'attach_condition_tabs';

      if ($condition_id == 'current_theme') {
        $condition_form['#title'] = $this->t('Theme');
        $condition_form['theme']['#description'] = $this->t('The theme for which CSS classes shall be attached.');
        $form['current_theme']['negate']['#type'] = 'value';
        $form['current_theme']['negate']['#value'] = $condition_form['negate']['#default_value'];
      }

      $form['attach_conditions'][$condition_id] = $condition_form;
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    // Validate condition settings.
    foreach ($form_state->getValue(['attach_conditions']) as $condition_id => $values) {
      // All condition plugins use 'negate' as a Boolean in their schema.
      // However, certain form elements may return it as 0/1. Cast here to
      // ensure the data is in the expected type.
      if (array_key_exists('negate', $values)) {
        $form_state->setValue([
          'attach_conditions',
          $condition_id,
          'negate',
        ], (bool) $values['negate']);
      }

      // Allow the condition to validate the form.
      $condition = $form_state->get(['attach_conditions', $condition_id]);
      $condition->validateConfigurationForm($form['attach_conditions'][$condition_id], SubformState::createForSubform($form['attach_conditions'][$condition_id], $form, $form_state));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $config = $this->config('responsive_class_field.settings');

    // Remove button and internal Form API values from submitted values.
    $form_state->cleanValues();

    $values = $form_state->getValues();

    $breakpoint_defaults = $values['breakpoint_defaults'];
    $breakpoint_defaults['breakpoints'] = $this->responsiveClassField->breakpointSettingsToConfig($breakpoint_defaults['breakpoints']);
    $config->set('breakpoint_defaults', $breakpoint_defaults);

    $attach_conditions = [];
    foreach ($values['attach_conditions'] as $condition_id => $values) {
      // Allow the condition to submit the form.
      $condition = $form_state->get(['attach_conditions', $condition_id]);
      $condition->submitConfigurationForm($form['attach_conditions'][$condition_id], SubformState::createForSubform($form['attach_conditions'][$condition_id], $form, $form_state));
      $attach_conditions[$condition_id] = $condition->getConfiguration();
    }
    $config->set('attach_conditions', $attach_conditions);

    $config->save();
  }

  /**
   * Get the form for mapping breakpoints to breakpoint replacements.
   */
  public function breakpointMappingFormAjax($form, FormStateInterface $form_state) {
    return $form['breakpoint_defaults']['breakpoints'];
  }

}
