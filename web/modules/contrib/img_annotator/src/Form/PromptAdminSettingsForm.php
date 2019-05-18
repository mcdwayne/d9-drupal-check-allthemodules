<?php

namespace Drupal\img_annotator\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Config\ConfigFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure basic settings for this site.
 */
class PromptAdminSettingsForm extends ConfigFormBase {

  protected $entity_type_manager;
  protected $config_factory;

  public function __construct(EntityTypeManagerInterface $entity_type_manager, ConfigFactory $config_factory) {
    $this->entity_type_manager = $entity_type_manager;
    $this->config_factory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
        $container->get('entity_type.manager'),
        $container->get('config.factory')
        );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'img_annotator_prompt_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['img_annotator.prompt_settings', 'img_annotator.prompt_message'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $msgOptions = [
        'addSuccess' => 'Annotation has been added successfully.',
        'addFailed' => 'Annotation cannot be added.',
        'addNotAllowed' => 'Sorry, you do not have permission to annotate on this image.',
        'removeSuccess' => 'Annotation has been removed successfully.',
        'removeFailed' => 'Annotation cannot be removed.',
        'updateSuccess' => 'Annotation has been successfully updated.',
        'updateFailed' => 'Annotation cannot be updated.',
    ];

    $msgLabel = [
        'addSuccess' => 'On Add [Success]',
        'addFailed' => 'On Add [Failed]',
        'addNotAllowed' => 'On Add [Not Allowed]',
        'removeSuccess' => 'On Remove [Success]',
        'removeFailed' => 'On Remove [Failed]',
        'updateSuccess' => 'On Update [Success]',
        'updateFailed' => 'On Update [Failed]',
    ];

    // Prompt Style.
    $configStyle = $this->config('img_annotator.prompt_settings');
    $defaultStyle = $configStyle->get();

    $styleOptions = [
        '0' => 'None',
        'js' => 'Show Javascript Alerts',
    ];

    $form['prompt'] = [
        '#type' => 'details',
        '#title' => 'Prompt Style',
        '#open' => TRUE,
    ];
    $form['prompt']['show_alert'] = [
        '#type' => 'radios',
        '#options' => $styleOptions,
        '#title' => '',
        '#default_value' => isset($defaultStyle['prompt']) ? $defaultStyle['prompt'] : '0',
    ];

    // Prompt Messages.
    $configMessages = $this->config('img_annotator.prompt_message');
    $defaultMessages = $configMessages->get();

    $form['message'] = [
        '#type' => 'details',
        '#title' => 'Prompt Messages',
        '#tree' => TRUE,
        '#open' => TRUE,
    ];

    foreach ($msgLabel as $key => $label) {
      $form['message'][$key] = array(
          '#type' => 'textfield',
          '#title' => $label,
          '#default_value' => isset($defaultMessages[$key]) ? $defaultMessages[$key] : $msgOptions[$key],
      );
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $configStyle = $this->config_factory->getEditable('img_annotator.prompt_settings');
    $configMessages = $this->config_factory->getEditable('img_annotator.prompt_message');

    $submitted = $form_state->getValues();

    $configStyle->set('prompt', $submitted['show_alert'])->save();


    foreach ($submitted['message'] as $key => $msg) {
      $configMessages->set($key, $msg)->save();
    }

    parent::submitForm($form, $form_state);
  }

}
