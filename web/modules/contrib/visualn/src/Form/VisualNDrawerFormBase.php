<?php

namespace Drupal\visualn\Form;

use Drupal\Component\Utility\NestedArray;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\visualn\Manager\DrawerManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Render\Element;
use Drupal\visualn\Helpers\VisualNFormsHelper;

/**
 * Class VisualNDrawerFormBase.
 */
class VisualNDrawerFormBase extends EntityForm {

  /**
   * The visualn drawer manager service.
   *
   * @var \Drupal\visualn\Manager\DrawerManager
   */
  protected $visualNDrawerManager;

  /**
   * Constructs an VisualNDrawerFormBase object
   *
   * @param \Drupal\visualn\Manager\DrawerManager $visualn_drawer_manager
   *   The visualn drawer manager service.
   */
  public function __construct(DrawerManager $visualn_drawer_manager) {
    $this->visualNDrawerManager = $visualn_drawer_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
    $container->get('plugin.manager.visualn.drawer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    // here original base drawer form is rendered (drawer wrappers are not used here, obvious)

    // do not mix this drawer and the drawer in drawer_plugin (which is for Base Drawer)
    $visualn_drawer = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $visualn_drawer->label(),
      '#description' => $this->t("Label for the VisualN Drawer."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $visualn_drawer->id(),
      '#machine_name' => [
        'exists' => '\Drupal\visualn\Entity\VisualNDrawer::load',
      ],
      '#disabled' => !$visualn_drawer->isNew(),
    ];

    // @todo: this (almost) a copy-paste from VisualNStyleForm
    // Get drawer plugins list
    $definitions = $this->visualNDrawerManager->getDefinitions();
    // @todo: is it really needed to include empty element here
    $drawers_list = [];
    //$drawers_list = ['' => $this->t('- Select -')];
    foreach ($definitions as $definition) {
      if ($definition['role'] == 'wrapper') {
        continue;
      }
      $drawers_list[$definition['id']] = $definition['label'];
    }
    $default_drawer = $visualn_drawer->isNew() ? '' : $visualn_drawer->getBaseDrawerId();
    // @todo: add a checkbox to filter out drawers without native drawers and enable it by default
    $form['drawer_plugin_id'] = [
      '#type' => 'select',
      '#title' => $this->t('Base Drawer'),
      '#options' => $drawers_list,
      '#default_value' => $default_drawer,
      // @todo: check terminology (for user drawer). maybe derived drawers or smth else
      '#description' => $this->t("Base Drawer for the VisualN User Drawer."),
      '#ajax' => [
        'callback' => [get_called_class(), 'ajaxCallback'],
        'wrapper' => 'visualn-subdrawer-config-form-ajax',
      ],
      '#empty_value' => '',
      '#required' => TRUE,
    ];



    // @todo: check entity default configuration: values should be set to "" and [] respectively

    $form['drawer_container'] = [
      '#tree' => TRUE,
      '#prefix' => '<div id="visualn-subdrawer-config-form-ajax">',
      '#suffix' => '</div>',
      '#type' => 'container',
      '#process' => [[$this, 'processBaseDrawerSubform']],
    ];
    $stored_configuration = [
      'drawer_plugin_id' => $visualn_drawer->getBaseDrawerId(),
      'drawer_config' => $visualn_drawer->getDrawerConfig(),
    ];
    $form['drawer_container']['#stored_configuration'] = $stored_configuration;


    return $form;
  }


  // @todo: maybe this should be static
  public function processBaseDrawerSubform(array $element, FormStateInterface $form_state, $form) {
    $stored_configuration = $element['#stored_configuration'];
    $configuration = [
      'drawer_plugin_id' => $stored_configuration['drawer_plugin_id'],
      'drawer_config' => $stored_configuration['drawer_config'],
    ];
    $element = VisualNFormsHelper::doProcessBaseDrawerSubform($element, $form_state, $form, $configuration);
    return $element;
  }


  /**
   * Return Drawer configuration form via ajax at Base Drawer select change
   */
  public static function ajaxCallback(array $form, FormStateInterface $form_state, Request $request) {
    $triggering_element = $form_state->getTriggeringElement();
    $triggering_element_parents = array_slice($triggering_element['#array_parents'], 0, -1);
    $element = NestedArray::getValue($form, $triggering_element_parents);

    return $element['drawer_container'];
  }


  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $visualn_drawer = $this->entity;
    $status = $visualn_drawer->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label VisualN Drawer.', [
          '%label' => $visualn_drawer->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label VisualN Drawer.', [
          '%label' => $visualn_drawer->label(),
        ]));
    }
    $form_state->setRedirectUrl($visualn_drawer->toUrl('edit-form'));
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $drawer_plugin_id = $form_state->getValue('drawer_plugin_id');
    $drawer_config_values = $form_state->getValue('drawer_config') ?: [];

    // Extract config values from drawer config form for saving in VisualNStyle config entity
    // and add drawer plugin id for the user-defined drawer.
    $this->entity->set('base_drawer_id', $drawer_plugin_id);
    $this->entity->set('drawer_config', $drawer_config_values);
  }

}
