<?php

namespace Drupal\visualn\Form;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\visualn\Manager\DrawerManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\visualn\Entity\VisualNStyleInterface;
use Drupal\visualn\Entity\VisualNStyle;
use Drupal\Core\Render\Element;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class VisualNStyleForm.
 *
 * @package Drupal\visualn\Form
 */
class VisualNStyleForm extends EntityForm {


  /**
   * The visualn drawer manager service.
   *
   * @var \Drupal\visualn\Manager\DrawerManager
   */
  protected $visualNDrawerManager;

  /**
   * Constructs a VisualNStyleEditForm object.
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

    $visualn_style = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $visualn_style->label(),
      '#description' => $this->t("Label for the VisualN style."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $visualn_style->id(),
      '#machine_name' => [
        'exists' => '\Drupal\visualn\Entity\VisualNStyle::load',
      ],
      '#disabled' => !$visualn_style->isNew(),
    ];

    // Get not only base drawer plugins list (base drawers) but also drawer entities list (subdrawers)
    // and add a prefix to each item (BASE_DRAWER_PREFIX or SUBDRAWER_PREFIX) so that they could be distinguished while selecting

    $drawers_list = [];

    // Get drawers list (plugins and subdrawer entities)
    $definitions = $this->visualNDrawerManager->getDefinitions();
    foreach ($definitions as $definition) {
      if ($definition['role'] == 'wrapper') {
        continue;
      }
      $drawers_list[VisualNStyleInterface::BASE_DRAWER_PREFIX . "|" . $definition['id']] = $definition['label'];
    }
    // Get drawer entities list
    foreach (visualn_subdrawer_options(FALSE) as $id => $label) {
      // exclude 'No defined subdrawers' option
      if (empty($id)) {
        continue;
      }
      $drawers_list[VisualNStyleInterface::SUB_DRAWER_PREFIX . "|" . $id] = $label . ' [' . $this->t('user-defined') . ']';
    }

    // get common_drawer_id with prefix, i.e. "DRAWER_PREFIX|{subdrawer or base drawer}_ID"
    $prefixed_id = $visualn_style->isNew() ? "" : $visualn_style->getDrawerType() . "|" . $visualn_style->getDrawerId();
    // @todo: rename key to prefixed_id
    $form['drawer_id'] = [
      '#type' => 'select',
      '#title' => $this->t('Drawer'),
      '#options' => $drawers_list,
      '#default_value' => $prefixed_id,
      '#description' => $this->t("Drawer for the VisualN style."),
      '#ajax' => [
        'callback' => [get_called_class(), 'ajaxCallback'],
        'wrapper' => 'visualn-style-drawer-config-form-ajax',
      ],
      // @todo: the empty value shouldn't be added if #default_value is set (according to FAPI documentation)
      '#empty_value' => '',
      '#required' => TRUE,
      '#weight' => 1,
    ];

    // get real drawer plugin stored configuration for visualn style
    $drawer_config = $this->entity->getDrawerConfig();


    // @todo: check entity default configuration: values should be set to "" and [] respectively

    $form['drawer_container'] = [
      '#tree' => TRUE,
      '#prefix' => '<div id="visualn-style-drawer-config-form-ajax">',
      '#suffix' => '</div>',
      '#type' => 'container',
      '#process' => [[$this, 'processBaseDrawerSubform']],
      '#weight' => 2,
    ];
    $stored_configuration = [
      'prefixed_id' => $prefixed_id,
      'drawer_config' => $drawer_config,
    ];
    $form['drawer_container']['#stored_configuration'] = $stored_configuration;

    return $form;
  }


  // @todo: what if user changed subdrawer modifiers for a subdrawer that has a style?
  //    how style configuration should behave? maybe just block such subdrawers?
  // @todo: actually the function should be renamed because it can be also a user-defined drawer
  public function processBaseDrawerSubform(array $element, FormStateInterface $form_state, $form) {
    $stored_configuration = [
      'prefixed_id' => $element['#stored_configuration']['prefixed_id'],
      'drawer_config' => $element['#stored_configuration']['drawer_config'],
    ];

    $common_drawer_element_parents = array_slice($element['#parents'], 0, -1);
    $prefixed_id = $form_state->getValue(array_merge($common_drawer_element_parents, ['drawer_id']));

    if (!$prefixed_id) {
      return $element;
    }

    $drawer_plugin = self::getDrawerByPrefixedId($prefixed_id);

    // @todo: basically this should always be non-empty here
    if (!$drawer_plugin) {
      return $element;
    }

    // So here we suppose to have just a generic drawer plugin without making any consideration whether it
    // is a base drawer or a wrapper.

    if ($prefixed_id == $stored_configuration['prefixed_id']) {
      // Make no difference whether the drawer plugin is a base plugin or a sudbdrawer plugin.
      $drawer_config = $stored_configuration['drawer_config'];
      $drawer_plugin->setConfiguration($drawer_config);
    }
    else {
      $drawer_config = $drawer_plugin->getConfiguration();
    }

    $drawer_container_key = $prefixed_id;

    // get drawer configuration form

    // @todo: allow to set custom descriptions for subdrawers and show it here
    $drawer_description = $drawer_plugin->getDescription() ?: t('<em>No description provided by the drawer plugin</em>');
    $element['drawer_description'] = [
      '#markup' => t('<strong>Description</strong><br /> @description', ['@description' => $drawer_description]),
      '#prefix' => '<div>',
      '#suffix' => '</div>',
    ];

    $element[$drawer_container_key]['drawer_config'] = [];
    $element[$drawer_container_key]['drawer_config'] += [
      '#parents' => array_merge($element['#parents'], [$drawer_container_key, 'drawer_config']),
      '#array_parents' => array_merge($element['#array_parents'], [$drawer_container_key, 'drawer_config']),
    ];

    $subform_state = SubformState::createForSubform($element[$drawer_container_key]['drawer_config'], $form, $form_state);
    // attach drawer configuration form
    $element[$drawer_container_key]['drawer_config']
              = $drawer_plugin->buildConfigurationForm($element[$drawer_container_key]['drawer_config'], $subform_state);


    // since drawer and fields onfiguration forms may be empty, do a check (then it souldn't be of details type)
    if (Element::children($element[$drawer_container_key]['drawer_config'])) {
      // show open by default
      $element[$drawer_container_key] = [
        '#type' => 'details',
        '#title' => t('VisualN Style configuration'),
        '#open' => TRUE,
      ] + $element[$drawer_container_key];
/*
      $drawer_element_array_parents = array_slice($element['#array_parents'], 0, -1);
      // check that the triggering element is visualn_style_id but not fetcher_id select (or some other element) itself
      if ($form_state->getTriggeringElement()) {
        $triggering_element = $form_state->getTriggeringElement();
        $details_open = $triggering_element['#array_parents'] === array_merge($drawer_element_array_parents, ['drawer_id']);
        $element[$drawer_container_key] = [
          '#type' => 'details',
          '#title' => t('VisualN Style configuration'),
          '#open' => $details_open,
        ] + $element[$drawer_container_key];
      }
      else {
        // show closed by default
        $element[$drawer_container_key] = [
          '#type' => 'details',
          '#title' => t('VisualN Style configuration'),
          '#open' => FALSE,
        ] + $element[$drawer_container_key];
      }
*/
    }

    // @todo: uncomment validate callback
    $element[$drawer_container_key]['#element_validate'] = [[get_called_class(), 'validateDrawerSubForm']];
    //$element[$drawer_container_key]['#element_validate'] = [[get_called_class(), 'submitDrawerSubForm']];


    return $element;
  }

  // @todo: this is based on VisualNFormsHelper::validateBaseDrawerSubForm()
  public static function validateDrawerSubForm(&$form, FormStateInterface $form_state) {
    // @todo: the code here should actually go to #element_submit, but it is not implemented at the moment in Drupal core

    $visualNDrawerManager = \Drupal::service('plugin.manager.visualn.drawer');

    // Here the full form_state (e.g. not SubformStateInterface) is supposed to be
    // since validation is done after the whole form is rendered.


    // get drawer_container_key (for selected visualn style is equal by convention to visualn_style_id,
    // see processDrawerContainerSubform() #process callback)
    $element_parents = $form['#parents'];
    // use $drawer_container_key for clarity though may get rid of array_pop() here and use end($element_parents)
    $drawer_container_key = array_pop($element_parents);

    // remove 'drawer_container' key
    $base_element_parents = array_slice($element_parents, 0, -1);


    // Call drawer_plugin submitConfigurationForm(),
    // submitting should be done before $form_state->unsetValue() after restructuring the form_state values, see below.

    // @todo: it is not correct to call submit inside a validate method (validateDrawerContainerSubForm())
    //    also see https://www.drupal.org/node/2820359 for discussion on a #element_submit property
    $full_form = $form_state->getCompleteForm();
    $subform = $form['drawer_config'];
    $sub_form_state = SubformState::createForSubform($subform, $full_form, $form_state);

    $prefixed_id  = $form_state->getValue(array_merge($base_element_parents, ['drawer_id']));

    // Get drawer (may be a wrapper)  plugin with default config (or subdrawer config).
    // There is no need in visualn style stored drawer_config here
    // since submitConfigurationForm() should fully rely on form_state values
    $drawer_plugin = self::getDrawerByPrefixedId($prefixed_id);
    $drawer_plugin->submitConfigurationForm($subform, $sub_form_state);


    // move drawer_config two levels up (remove 'drawer_container' and $drawer_container_key) in form_state values
    $drawer_config_values = $form_state->getValue(array_merge($element_parents, [$drawer_container_key, 'drawer_config']));
    if (!is_null($drawer_config_values)) {
      $form_state->setValue(array_merge($base_element_parents, ['drawer_config']), $drawer_config_values);
    }


    // remove remove 'drawer_container' key itself from form_state
    $form_state->unsetValue(array_merge($element_parents, [$drawer_container_key]));
  }


  public static function getDrawerByPrefixedId($prefixed_id) {
    $visualNDrawerManager = \Drupal::service('plugin.manager.visualn.drawer');

    $drawer_id_components = self::explodeDrawerIdIntoComponents($prefixed_id);
    $drawer_type = $drawer_id_components['drawer_type'];
    // @todo: maybe rename the variable
    $deprefixed_id = $drawer_id_components['drawer_id'];

    if (!$deprefixed_id) {
      return NULL;
    }

    // Get base drawer plugin or wrapper drawer plugin for further processing.
    switch ($drawer_type) {
      case VisualNStyleInterface::BASE_DRAWER_PREFIX :
        $base_drawer_id = $deprefixed_id;
        // For now just use empty (actually default) config. If needed, it will be changed below after comparing
        // currenlty selected prefixed_id to the one from $stored_configuration.
        $drawer_config = [];
        $drawer_plugin = $visualNDrawerManager->createInstance($base_drawer_id, $drawer_config);
        break;
      case VisualNStyleInterface::SUB_DRAWER_PREFIX :
        $subdrawer_entity_id = $deprefixed_id;
        $wrapper_plugin_components = VisualNStyle::getSubDrawerWrapperPluginArguments($subdrawer_entity_id);
        $wrapper_drawer_id = $wrapper_plugin_components['wrapper_drawer_id'];
        // wrapper config contains at least two elements: base drawer config array and drawer modifiers info
        $wrapper_config = $wrapper_plugin_components['wrapper_drawer_config'];
        $wrapper_plugin = $visualNDrawerManager->createInstance($wrapper_drawer_id, $wrapper_config);
        // For all other actions wrapper plugin should behave itself as its undelying base drawer plugin.
        // It seamlesly delegates all its methods calls to its base drawer modifying their behaviour where needed.
        // Wrapper plugins technically are drawer plugins and implement the same interface. Native wrappers
        // even are just subclasses for their base drawers which wrap base drawer methods to allow modifiers
        // to change their behaviour.
        $drawer_plugin = $wrapper_plugin;

        // @todo: maybe use just drawer_config key
        //$wrapper_config['base_drawer_config']
        break;
    }


    return $drawer_plugin;
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
    $visualn_style = $this->entity;
    $status = $visualn_style->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label VisualN style.', [
          '%label' => $visualn_style->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label VisualN style.', [
          '%label' => $visualn_style->label(),
        ]));
    }
    $form_state->setRedirectUrl($visualn_style->toUrl('collection'));
  }


  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $prefixed_id = $form_state->getValue('drawer_id');

    $drawer_id_components = $this->explodeDrawerIdIntoComponents($prefixed_id);
    // @todo: maybe rename to drawer_type_prefix (see the property in visualn_style.schema.yml)
    $drawer_type = $drawer_id_components['drawer_type'];
    $common_drawer_id = $drawer_id_components['drawer_id'];


    // Extract config values from drawer config form for saving in VisualNStyle config entity
    // and add drawer plugin id for the visualn style.
    $this->entity->set('drawer_id', $common_drawer_id);
    $this->entity->set('drawer_type', $drawer_type);
    $this->entity->set('drawer_type_prefix', $drawer_type);


    $drawer_config_values = $form_state->getValue('drawer_config') ?: [];

    $this->entity->set('drawer_config', $drawer_config_values);
  }


  /**
   * {@inheritdoc}
   */
  public function afterBuild(array $element, FormStateInterface $form_state) {
    // Rebuild the entity if #after_build is being called as part of a form
    // rebuild, i.e. if we are processing input.
    if ($form_state->isProcessingInput()) {
      // @todo: here we disable default EntityForm::afterBuild() behaviour,
      //   we always need original values in form build
      //$this->entity = $this->buildEntity($element, $form_state);
    }

    return $element;
  }


  protected static function explodeDrawerIdIntoComponents($drawer_id_prefixed) {
    // @todo: check for empty $drawer_id_prefixed value
    $drawer_plugin_id_explode = explode('|', $drawer_id_prefixed);

    $drawer_type = array_shift($drawer_plugin_id_explode);
    // implode in case drawer_plugin_id itself contains "|" symbol
    $drawer_plugin_id = implode('|', $drawer_plugin_id_explode);

    return ['drawer_type' => $drawer_type, 'drawer_id' => $drawer_plugin_id];
  }

}
