<?php

namespace Drupal\wsdata\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\wsdata\Plugin\WSEncoderManager;
use Drupal\wsdata\Plugin\WSDecoderManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class WSCallForm.
 *
 * @package Drupal\wsdata\Form
 */
class WSCallForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function __sleep() {
    return parent::__sleep();
  }

  /**
   * {@inheritdoc}
   */
  public function __wakeup() {
    return parent::__wakeup();
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(
    WSEncoderManager $plugin_manager_wsencoder,
    WSDecoderManager $plugin_manager_wsdecoder,
    ModuleHandlerInterface $module_handler
  ) {
    $this->plugin_manager_wsencoder = $plugin_manager_wsencoder;
    $this->plugin_manager_wsdecoder = $plugin_manager_wsdecoder;
    $this->module_handler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Instantiates this form class.
    return new static(
      $container->get('plugin.manager.wsencoder'),
      $container->get('plugin.manager.wsdecoder'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $wscall_entity = $this->entity;

    if (isset($wscall_entity->needSave) and $wscall_entity->needSave) {
      drupal_set_message($this->t('You have unsaved changes.  Click save to save this entity.'), 'warning');
    }

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $wscall_entity->label(),
      '#description' => $this->t("Label for the Web Service Call."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $wscall_entity->id(),
      '#machine_name' => [
        'exists' => '\Drupal\wsdata\Entity\WSCall::load',
      ],
      '#disabled' => !$wscall_entity->isNew(),
    ];

    $servers = entity_load_multiple('wsserver');
    $options = [];
    foreach ($servers as $server) {
      $options[$server->id()] = $server->label() . ' (' . $server->getEndpoint() . ')';
    }

    $form['wsserver'] = [
      '#type' => 'select',
      '#title' => $this->t('Web Service Server'),
      '#description' => $this->t('Data source.'),
      '#options' => $options,
      '#required' => TRUE,
      '#default_value' => $wscall_entity->wsserver,
      '#ajax' => [
        'callback' => '::wsserverForm',
        'wrapper' => 'wsserver-wrapper',
      ],
    ];

    $triggering = $form_state->getTriggeringElement();

    if ($triggering['#id'] == 'wscall_new_method') {
      $values = $form_state->getValues();
      $wscall_entity->setMethod($values['add_method'], $values['new_method_name'], $values['new_method_path']);
    }
    // Setting the form state in the options so that we can see values in the get options form.
    $options['form_state'] = $form_state;

    $form['options'] = [
      '#id' => 'wsserver-wrapper',
      '#type' => 'container',
      'wsserveroptions' => $wscall_entity->getOptionsForm($form_state->getValue('wsserver'), $options),
    ];

    $options = $wscall_entity->getOptions();
    foreach ($options as $name => $option) {
      if (isset($form['options']['wsserveroptions'][$name])) {
        if (is_array($option)) {
          // Traverse down the options till we can build out the form structure.
          for ($i = 0; $i < count($option); $i++) {
            // I think this can be improved.
            foreach ($option[$i] as $options_key => $options_value) {
              $form['options']['wsserveroptions'][$name][$i][$options_key]['#default_value'] = $options_value;
            }
          }
        }
        else {
          $form['options']['wsserveroptions'][$name]['#default_value'] = $option;
        }
      }
    }

    $decoder_definitions = $this->plugin_manager_wsdecoder->getDefinitions();
    $options = ['' => $this->t('None')];
    foreach ($decoder_definitions as $key => $decoder) {
      $options[$key] = $decoder['label']->render();
    }

    $form['wsdecoder'] = [
      '#type' => 'select',
      '#title' => $this->t('Decoder'),
      '#description' => $this->t('Decoder to decode the result.'),
      '#options' => $options,
      '#required' => TRUE,
      '#default_value' => $wscall_entity->wsdecoder,
    ];

    $encoder_definitions = $this->plugin_manager_wsencoder->getDefinitions();
    $options = ['' => $this->t('None')];
    foreach ($encoder_definitions as $key => $encoder) {
      $options[$key] = $encoder['label']->render();
    }

    $form['wsencoder'] = [
      '#type' => 'select',
      '#title' => $this->t('Encoder'),
      '#description' => $this->t('Encoder to encode the data sent to the web service.'),
      '#options' => $options,
      '#required' => TRUE,
      '#default_value' => $wscall_entity->wsencoder,
    ];

    if (!$this->module_handler->moduleExists('wsdata_extras')) {
      $form['wsdecoder']['#description'] .= '  ' . $this->t('Looking for more decoder plugins?  Try enabling the <em>wsdata_extras</em> module.');
      $form['wsencoder']['#description'] .= '  ' . $this->t('Looking for more encoder plugins?  Try enabling the <em>wsdata_extras</em> module.');
    }

    return $form;
  }

  /**
   * Ajax Callback.
   */
  public function wsserverForm(array $form, FormStateInterface $form_state) {
    return $form['options'];
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $wscall_entity = $this->entity;
    $wscall_entity->setOptions($form_state->getValues());

    $status = $wscall_entity->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Web Service Call.', [
          '%label' => $wscall_entity->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Web Service Call.', [
          '%label' => $wscall_entity->label(),
        ]));
    }
    $form_state->setRedirectUrl($wscall_entity->urlInfo('collection'));
  }

}
