<?php

namespace Drupal\cancel_button\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Path\PathValidatorInterface;
use Drupal\Core\Routing\RequestContext;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure generic settings for the Cancel Button.
 */
class CancelButtonSettingsForm extends ConfigFormBase {

  /**
   * The path validator.
   *
   * @var \Drupal\Core\Path\PathValidatorInterface
   */
  protected $pathValidator;

  /**
   * The request context.
   *
   * @var \Drupal\Core\Routing\RequestContext
   */
  protected $requestContext;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a CancelButtonSettingsForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Path\PathValidatorInterface $path_validator
   *   The path validator.
   * @param \Drupal\Core\Routing\RequestContext $request_context
   *   The request context.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, PathValidatorInterface $path_validator, RequestContext $request_context, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($config_factory);

    $this->pathValidator = $path_validator;
    $this->requestContext = $request_context;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('path.validator'),
      $container->get('router.request_context'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'cancel_button_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'cancel_button.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $url_prefix = $this->requestContext->getCompleteBaseUrl();

    // Get the entities and bundles which will be listed in the settings form.
    // We only show entities that don't have wizards.
    $result = $this->getEntityTypesToDisplay();
    $entity_types = $bundles = [];
    if (!empty($result)) {
      $entity_types = $result['entity_types'];
      $bundles = $result['bundles'];
    }

    // Get existing config, if any.
    $config = $this->config('cancel_button.settings');
    $entity_type_cancel_destinations = $config->get('entity_type_cancel_destination');

    $entity_types += ['default' => NULL];

    // The form elements in this form are built based on whether the entity type
    // has bundles or not. If there are no bundles, we save the path/ display
    // corresponding form elements for the entity type, otherwise we save the
    // path /list form elements for each bundle in the entity type.
    // The setting for enabling the cancel button is stored at the entity type
    // level.
    foreach ($entity_types as $id => $entity_type) {
      // Set title and description values.
      if (!empty($entity_type)) {
        $label = $entity_type->getLabel();
        $title = $this->t('@label entity form', ['@label' => $label]);
        $description = $this->t('The default destination for the <strong>Cancel</strong> button on @label entity forms.', ['@label' => $label]);
      }
      else {
        $title = $this->t('Default entity form');
        $description = $this->t('The default destination for the <strong>Cancel</strong> button on all other entity forms not otherwise configured here.');
        $label = 'Default';
      }

      // Settings related to enabling the cancel button on the entity forms.
      if (array_key_exists($id, $entity_type_cancel_destinations)) {
        $default_enabled = isset($entity_type_cancel_destinations[$id]['enabled']) ? $entity_type_cancel_destinations[$id]['enabled'] : TRUE;
      }
      else {
        $default_enabled = isset($entity_type_cancel_destinations['default']['enabled']) ? $entity_type_cancel_destinations['default']['enabled'] : TRUE;
      }
      $checkbox = [
        '#type' => 'checkbox',
        '#title' => $this->t('Enable cancel button for forms of the @label entity type.', ['@label' => $label]),
        '#default_value' => $default_enabled,
      ];
      $enabled_checkbox = $id . '_cancel_enabled';
      // Determine states for the path textfield based on whether the cancel
      // button is enabled for the entity type.
      $states = [
        'enabled' => [
          ':input[name="' . $enabled_checkbox . '"]' => ['checked' => TRUE],
        ],
        'required' => [
          ':input[name="' . $enabled_checkbox . '"]' => ['checked' => TRUE],
        ],
      ];

      // If this entity type has bundles, only display elements for each
      // bundle.
      if (array_key_exists($id, $bundles) && count($bundles[$id]) > 0) {
        // Wrap all the bundles within one detail element.
        if (!empty($entity_type)) {
          $form['entity_type_cancel_destination'][$id . '_bundles'] = [
            '#type' => 'details',
            '#title' => $this->t('@label', ['@label' => $label]),
            '#open' => FALSE,
            '#description' => $this->t('For specifying settings for each bundle in this entity type, enter values below.'),
          ];
        }
        // Display one 'enabled' checkbox for the entity type.
        $form['entity_type_cancel_destination'][$id . '_bundles'][$id . '_cancel_enabled'] = $checkbox;

        // Build the form elements for all bundles.
        foreach ($bundles[$id] as $bundle_id => $bundle) {
          $bundle_label = $bundle->label();
          // Set title and description values.
          if (!empty($entity_type)) {
            $title = $this->t('@label Bundle form', ['@label' => $bundle_label]);
            $description = $this->t('The default destination for the <strong>Cancel</strong> button on @label bundle forms.', ['@label' => $bundle_label]);
          }
          if (array_key_exists($id . '_' . $bundle->id(), $entity_type_cancel_destinations)) {
            $default_path = $entity_type_cancel_destinations[$id . '_' . $bundle->id()]['path'];
          }
          else {
            $default_path = $entity_type_cancel_destinations['default']['path'];
          }

          $form['entity_type_cancel_destination'][$id . '_bundles'][$id . '_' . $bundle->id() . '_cancel_destination'] = [
            '#type' => 'textfield',
            '#title' => $title,
            '#default_value' => $default_path,
            '#description' => $description,
            '#field_prefix' => $url_prefix,
            '#states' => $states,
          ];
        }
      }
      // There are no bundles, so generate the default path from the
      // configuration based on the entity type id.
      else {
        if (array_key_exists($id, $entity_type_cancel_destinations)) {
          $default_path = $entity_type_cancel_destinations[$id]['path'];
        }
        else {
          $default_path = $entity_type_cancel_destinations['default']['path'];
        }

        // Wrap each element for the entity type within a details element.
        $form['entity_type_cancel_destination'][$id] = [
          '#type' => 'details',
          '#title' => $this->t('@label', ['@label' => $label]),
          '#open' => FALSE,
          '#description' => '',
        ];

        $form['entity_type_cancel_destination'][$id][$id . '_cancel_enabled'] = $checkbox;

        // Build the form element.
        $form['entity_type_cancel_destination'][$id][$id . '_cancel_destination'] = [
          '#type' => 'textfield',
          '#title' => $title,
          '#default_value' => $default_path,
          '#description' => $description,
          '#field_prefix' => $url_prefix,
          '#states' => $states,
        ];
      }
    }
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $result = $this->getEntityTypesToDisplay();
    $entity_types = $result["entity_types"];
    $bundles = $result["bundles"];
    $entity_types += ['default' => NULL];

    // Based on whether the entity type has bundles or not, the fieldsets
    // contain settings for either the entity type or the bundles.
    // Validate appropriately.
    foreach ($entity_types as $id => $type) {
      if (!(array_key_exists($id, $bundles) && count($bundles[$id]) > 0)) {
        $field = $id . '_cancel_destination';
        $value = $form_state->getValue($field);
        if (empty($value) && ($form_state->getValue($id . '_cancel_enabled'))) {
          $form_state->setErrorByName($field, $this->t('You must enter a path in this field'));
          $form['entity_type_cancel_destination'][$id]['#open'] = TRUE;
        }
        elseif (!$this->pathValidator->isValid($value)) {
          $form_state->setErrorByName($field, $this->t("The path '%path' is either invalid or you do not have access to it.", ['%path' => $value]));
          $form['entity_type_cancel_destination'][$id]['#open'] = TRUE;
        }
      }

      if (array_key_exists($id, $bundles) && count($bundles[$id]) > 0) {
        foreach ($bundles[$id] as $bundle) {
          $field = $id . '_' . $bundle->id() . '_cancel_destination';
          $value = $form_state->getValue($field);
          if (empty($value) && ($form_state->getValue($id . '_cancel_enabled'))) {
            $form_state->setErrorByName($field, $this->t('You must enter a path in this field'));
            $form['entity_type_cancel_destination'][$id][$id . '_bundles']['#open'] = TRUE;
          }
          elseif (!$this->pathValidator->isValid($value)) {
            $form_state->setErrorByName($field, $this->t("The path '%path' is either invalid or you do not have access to it.", ['%path' => $value]));
            $form['entity_type_cancel_destination'][$id][$id . '_bundles']['#open'] = TRUE;
          }
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('cancel_button.settings');
    $result = $this->getEntityTypesToDisplay();
    $entity_types = $result["entity_types"];
    $bundles = $result["bundles"];
    $entity_types += ['default' => NULL];

    // If the entity type is not fieldable (doesn't have bundles), store config
    // for entity type, otherwise store config for a combination of entity type
    // and bundle.
    foreach ($entity_types as $id => $type) {
      if (array_key_exists($id, $bundles) && count($bundles[$id]) > 0) {
        foreach ($bundles[$id] as $bundle) {
          $config->set('entity_type_cancel_destination.' . $id . '_' . $bundle->id() . '.path', $form_state->getValue($id . '_' . $bundle->id() . '_cancel_destination'));
        }
      }
      else {
        $config->set('entity_type_cancel_destination.' . $id . '.path', $form_state->getValue($id . '_cancel_destination'));
      }
      $config->set('entity_type_cancel_destination.' . $id . '.enabled', (bool) $form_state->getValue($id . '_cancel_enabled'));
    }
    $config->save();
    parent::submitForm($form, $form_state);
  }

  /**
   * Gets all the entity types and bundles if applicable for processing.
   */
  public function getEntityTypesToDisplay() {
    $entity_types = $this->entityTypeManager->getDefinitions();
    $entity_types_to_return = [];
    $bundles = [];
    foreach ($entity_types as $entity_type_id => $entity_type) {
      // Do not consider entities with wizard forms.
      if (array_key_exists('wizard', $entity_type->getHandlerClasses())) {
        continue;
      }
      if ($entity_type->hasKey('bundle')) {
        $bundle_entity_type = $entity_type->getBundleEntityType();
        if (!empty($bundle_entity_type)) {
          $bundles[$entity_type_id] = $this->entityTypeManager
            ->getStorage($bundle_entity_type)
            ->loadMultiple();
        }
      }
      $entity_types_to_return[$entity_type_id] = $entity_type;
    }
    return ['entity_types' => $entity_types_to_return, 'bundles' => $bundles];
  }

}
