<?php

namespace Drupal\entity_embed_extras\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\entity_embed_extras\DialogEntityDisplay\DialogEntityDisplayManager;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Form\SubformState;

/**
 * Dialog customizations.
 */
class EntityEmbedConfigAlter implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The Dialog Entity Display Plugin manager.
   *
   * @var \Drupal\entity_embed\DialogEntityDisplay\DialogEntityDisplayManager
   */
  protected $DialogEntityDisplayManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.entity_embed_extras.dialog_entity_display')
    );
  }

  /**
   * Constructs a EntityEmbedDialogAlter object.
   *
   * @param \Drupal\entity_embed\DialogEntityDisplay\DialogEntityDisplayManager $dialog_entity_display_manager
   *   The Dialog Entity Display Plugin manager.
   */
  public function __construct(DialogEntityDisplayManager $dialog_entity_display_manager) {
    $this->DialogEntityDisplayManager = $dialog_entity_display_manager;
  }

  /**
   * Alter the EntityEmbedDialog form for gallery button.
   */
  public function formAlter(array &$form, FormStateInterface $form_state) {

    /** @var \Drupal\embed\Entity\EmbedButton $embedButton */
    $embedButton = $form_state->getFormObject()->getEntity();

    $definitions = $this->DialogEntityDisplayManager->getDefinitions();

    $options = array_map(function ($definition) {
      return (string) $definition['label'];
    }, $definitions);

    $form['title_settings'] = [
      '#type' => 'container',
      '#attributes' => [
        'id' => 'entity-embed-title-settings-wrapper',
      ],
    ];

    $form['select_step_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Dialog Select Step Title'),
      '#default_value' => $embedButton->getThirdPartySetting('entity_embed_extras', 'select_step_title', 'Select entity to embed'),
      '#required' => TRUE,
      '#description' => $this->t('The text to display during the selection step.'),
    ];

    $form['embed_step_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Dialog Embed Step Title'),
      '#default_value' => $embedButton->getThirdPartySetting('entity_embed_extras', 'embed_step_title', 'Edit entity embed'),
      '#required' => TRUE,
      '#description' => $this->t('The text to display during the embed step.'),
    ];

    $form['embed_back_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Return to Selection Step Button Label'),
      '#default_value' => $embedButton->getThirdPartySetting('entity_embed_extras', 'embed_back_title', 'Select a different entity'),
      '#required' => TRUE,
      '#description' => $this->t('The text on the button to return to the selection step.'),
    ];

    $form['dialog_entity_display'] = [
      '#type' => 'select',
      '#title' => $this->t('Dialog Entity Display Plugin'),
      '#default_value' => $embedButton->getThirdPartySetting('entity_embed_extras', 'dialog_entity_display', 'label'),
      '#options' => $options,
      '#required' => TRUE,
      '#ajax' => [
        'callback' => [get_class($this), 'updateDialogEntityDisplaySettings'],
        'effect' => 'fade',
      ],
      '#limit_validation_errors' => [],
    ];

    $form['dialog_entity_display_wrapper'] = [
      '#type' => 'container',
      '#attributes' => [
        'id' => 'embed-dialog-review-display-settings-wrapper',
      ],
    ];

    $pluginId = $embedButton->getThirdPartySetting('entity_embed_extras', 'dialog_entity_display', 'label');
    $settings = $embedButton->getThirdPartySetting('entity_embed_extras', 'dialog_entity_display_settings', []);

    $dialog_entity_display_plugin = $this->DialogEntityDisplayManager
      ->createInstance($pluginId, $settings);

    if ($dialog_entity_display_plugin->isConfigurable()) {

      $form['dialog_entity_display_wrapper']['dialog_entity_display_settings'] = [
        '#type' => 'details',
        '#title' => $this->t('Dialog Entity Display Plugin settings'),
        '#open' => TRUE,
        '#tree' => TRUE,
      ];

      $pluginForm = $dialog_entity_display_plugin->buildConfigurationForm($form, $form_state);

      $form['dialog_entity_display_wrapper']['dialog_entity_display_settings'] = array_merge($pluginForm, $form['dialog_entity_display_wrapper']['dialog_entity_display_settings']);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {

    /** @var \Drupal\embed\Entity\EmbedButton $embedButton */
    $embedButton = $form_state->getFormObject()->getEntity();

    $pluginId = $embedButton->getThirdPartySetting('entity_embed_extras', 'dialog_entity_display', 'label');
    $settings = $embedButton->getThirdPartySetting('entity_embed_extras', 'dialog_entity_display_settings', []);

    if ($input = $form_state->getUserInput()) {
      if (!empty($input['dialog_entity_display'])) {
        $pluginId = $input['dialog_entity_display'];
        $embedButton->setThirdPartySetting('entity_embed_extras', 'dialog_entity_display', $pluginId);
      }
      if (!empty($input['dialog_entity_display_settings'])) {
        $settings = $input['dialog_entity_display_settings'];
      }
      if (!empty($input['select_step_title'])) {
        $embedButton->setThirdPartySetting('entity_embed_extras', 'select_step_title', $input['select_step_title']);
      }
      if (!empty($input['embed_step_title'])) {
        $embedButton->setThirdPartySetting('entity_embed_extras', 'embed_step_title', $input['embed_step_title']);
      }
      if (!empty($input['embed_back_title'])) {
        $embedButton->setThirdPartySetting('entity_embed_extras', 'embed_back_title', $input['embed_back_title']);
      }
    }

    if (!empty($form['dialog_entity_display_wrapper']['dialog_entity_display_settings'])) {
      $dialog_entity_display_plugin = $this->DialogEntityDisplayManager
        ->createInstance($pluginId, $settings);
      $subFormState = SubformState::createForSubform($form['dialog_entity_display_wrapper']['dialog_entity_display_settings'], $form, $form_state);
      $dialog_entity_display_plugin->submitConfigurationForm($form, $subFormState);
      $form_state->setValue('dialog_entity_display_settings', $dialog_entity_display_plugin->getConfiguration());
      $embedButton->setThirdPartySetting('entity_embed_extras', 'dialog_entity_display_settings', $dialog_entity_display_plugin->getConfiguration());

    }
    else {
      $form_state->setValue('dialog_entity_display_settings', []);
      $embedButton->setThirdPartySetting('entity_embed_extras', 'dialog_entity_display_settings', []);
    }

    $form_state->unsetValue('dialog_entity_display_wrapper');
  }


  /**
   * Ajax callback to update the form fields which depend on embed type.
   *
   * @param array $form
   *   The build form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   Ajax response with updated options for the embed type.
   */
  public function updateDialogEntityDisplaySettings(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();

    // Update options for entity type bundles.
    $response->addCommand(new ReplaceCommand(
      '#embed-dialog-review-display-settings-wrapper',
      $form['dialog_entity_display_wrapper']
    ));

    return $response;
  }

}
