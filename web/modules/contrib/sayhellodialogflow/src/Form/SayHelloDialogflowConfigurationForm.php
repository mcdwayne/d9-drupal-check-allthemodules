<?php

namespace Drupal\say_hello_dialogflow\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Menu\MenuParentFormSelectorInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configuration form definition for the dialogflow menu.
 */
class SayHelloDialogflowConfigurationForm extends ConfigFormBase {

  /**
   * SayHelloDialogflowConfigurationForm constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Menu\MenuParentFormSelectorInterface $menu_parent_selector
   *   The menu parent form selector service.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    MenuParentFormSelectorInterface $menu_parent_selector
  ) {
    parent::__construct($config_factory);
    $this->menuParentSelector = $menu_parent_selector;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('menu.parent_form_selector')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'say_hello_dialogflow.dialogflow_menu',
      'say_hello_dialogflow.dialogflow_config'
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'say_hello_dialogflow_configuration_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['export_modal'] = [
      '#type' => 'link',
      '#title' => $this->t('Export'),
      '#url' => Url::fromRoute('say_hello_dialogflow.modal_export'),
      '#attributes' => [
        'class' => [
          'use-ajax',
          'button',
        ],
      ],
    ];

    $config = $this->config('say_hello_dialogflow.dialogflow_menu');

    $form['dialogflow_menu'] = $this->menuParentSelector->parentSelectElement(":", "");
    $form['dialogflow_menu']['#title'] = $this->t('Dialogflow Menu Parent link');
    $form['dialogflow_menu']['#description'] = $this->t('Please provide the menu name you want to use. The maximum depth for a link and all its children is fixed. Some menu links may not be available as parents if selecting them would exceed this limit.');
    $form['dialogflow_menu']['#default_value'] = $config->get('dialogflow_menu');
    $form['dialogflow_menu']['#attributes']['class'][] = 'menu-title-select';

    $form['dialogflow_token'] = array(
      '#type' => 'textfield',
      '#description' => $this->t('Please type Dialogflow Token to your agent'),
      '#title' => $this->t('Dialogflow Token'),
      '#default_value' => $config->get('dialogflow_token'),
      '#required' => TRUE
    );

    $form['dialogflow_domain'] = array(
      '#type' => 'textfield',
      '#description' => $this->t('Please type Dialogflow API domain: https://api.dialogflow.com'),
      '#title' => $this->t('Dialogflow API domain'),
      '#default_value' => $config->get('dialogflow_domain'),
      '#required' => TRUE
    );

    $form['dialogflow_baseurl'] = array(
      '#type' => 'textfield',
      '#description' => $this->t('Please type Dialogflow API base url: https://api.dialogflow.com/v1/query?v=20150910'),
      '#title' => $this->t('Dialogflow API base url'),
      '#default_value' => $config->get('dialogflow_baseurl'),
      '#required' => TRUE
    );

    $form['dialogflow_default_intent_text'] = array(
      '#type' => 'textfield',
      '#description' => $this->t('Please type Default Intent speech'),
      '#title' => $this->t('Dialogflow Default Intent Text'),
      '#default_value' => $config->get('dialogflow_default_intent_text'),
      '#required' => TRUE
    );

    $form['dialogflow_debug'] = array(
      '#type' => 'checkbox',
      '#description' => $this->t('Enable debug under chat'),
      '#title' => $this->t('Enable debug under chat'),
      '#default_value' => $config->get('dialogflow_debug')
    );

    // Attach the library for pop-up dialogs/modals.
    $form['#attached']['library'][] = 'core/drupal.dialog.ajax';

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('say_hello_dialogflow.dialogflow_menu')
      ->set('dialogflow_menu', $form_state->getValue('dialogflow_menu'))
      ->save();

    $this->config('say_hello_dialogflow.dialogflow_menu')
      ->set('dialogflow_token', $form_state->getValue('dialogflow_token'))
      ->save();

    $this->config('say_hello_dialogflow.dialogflow_menu')
      ->set('dialogflow_domain', $form_state->getValue('dialogflow_domain'))
      ->save();

    $this->config('say_hello_dialogflow.dialogflow_menu')
      ->set('dialogflow_baseurl', $form_state->getValue('dialogflow_baseurl'))
      ->save();

    $this->config('say_hello_dialogflow.dialogflow_menu')
      ->set('dialogflow_default_intent_text', $form_state->getValue('dialogflow_default_intent_text'))
      ->save();

    $this->config('say_hello_dialogflow.dialogflow_menu')
      ->set('dialogflow_debug', $form_state->getValue('dialogflow_debug'))
      ->save();

    parent::submitForm($form, $form_state);
  }
}