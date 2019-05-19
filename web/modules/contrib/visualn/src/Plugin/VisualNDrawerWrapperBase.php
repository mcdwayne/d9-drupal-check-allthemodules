<?php

namespace Drupal\visualn\Plugin;

use Drupal\visualn\Core\DrawerBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Plugin\PluginBase;
use Drupal\visualn\Core\DrawerInterface;
use Drupal\visualn\ResourceInterface;

/**
 * Base class for VisualN Wrapper Drawer plugins.
 *
 * We extend here PluginBase only (not VisualNDrawerBase) because all VisualN plugin specific
 * methods must be delegated to the subdrawer base drawer.
 *
 * @see \Drupal\visualn\Plugin\VisualNDrawerWrapperInterface
 */
// @todo: !IMPORTANT: drawer should always be actualized with every new change to the interface and base class
//    since it must include all the methods and wrapper around them to delegate to the subdrawer_base_drawer object.
abstract class VisualNDrawerWrapperBase extends PluginBase implements DrawerInterface {

  // Contains a reference to the base drawer object.
  protected $subdrawer_base_drawer;

  // Contains a reference to the modifiers array.
  protected $modifiers;

  // Contains modifiers methods substitutions for drawer and modifier methods.
  protected $methods_modifiers_substitutions;

  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $subdrawer_base_drawer_id = $configuration['base_drawer_id'];
    $subdrawer_base_drawer_config = $configuration['base_drawer_config'];

    // @todo: add to subdrawers documentation: always provide a native wrapper as a good practice>
    // native wrappers may call parent::methodName() and wrap it with smth
    // like $wrapperHelper->doBefore('methodName', ars) and $wrapperHelper->doAfter('methodName', ars).
    // the wrapperHelper itself does something similar that does generic/default drawer wrapper class

    // @todo: we can set default configuration replacements here since we can't override it directly
    //    of course this is more a hack than a good practice
    // @todo: maybe use some kind of instantiateDrawer callback in substitutions
    $this->subdrawer_base_drawer = \Drupal::service('plugin.manager.visualn.drawer')
                                  ->createInstance($subdrawer_base_drawer_id, $subdrawer_base_drawer_config);
    $this->modifiers = $configuration['modifiers'];


    // first register modifier methods that correspond to the drawer methods to modify
    //    also modifiers can be applied before and after drawer methods
    foreach ($this->modifiers as $uuid => $modifier) {
      $methods_substitutions = $modifier->methodsSubstitutionsInfo();
      foreach ($methods_substitutions as $drawer_method_name => $methods_substitution) {
        foreach ($methods_substitution as $before_after => $substitution_name) {
          $this->methods_modifiers_substitutions[$drawer_method_name][$before_after][$uuid] = $substitution_name;
        }
      }
    }
  }

  /**
   * @inheritdoc
   *
   * @todo: add new comments here and for other docblocks regarding the role of the drawer wrapper
   */
  public function prepareBuild(array &$build, $vuid, ResourceInterface $resource) {
    return $this->subdrawer_base_drawer->prepareBuild($build, $vuid, $resource);
  }

  /**
   * @inheritdoc
   */
  public function prepareJsConfig(array &$drawer_config) {
    $this->subdrawer_base_drawer->prepareJsConfig($drawer_config);
  }

  /**
   * @inheritdoc
   */
  public function jsId() {
    return $this->subdrawer_base_drawer->jsId();
  }

  /**
   * @inheritdoc
   */
  public function dataKeys() {
    return $this->subdrawer_base_drawer->dataKeys();
  }

  /**
   * @inheritdoc
   */
  public function dataKeysStructure() {
    return $this->subdrawer_base_drawer->dataKeysStructure();
  }

  /**
   * @inheritdoc
   */
  public function extractFormValues($form, FormStateInterface $form_state) {
    return $this->subdrawer_base_drawer->extractFormValues($form, $form_state);
  }





  // @todo: indicate interfaces that groups of methods belong here

  /**
   * @inheritdoc
   */
  public function defaultConfiguration() {
    // @todo: we can't override defaultConfiguration() directly because it is used internally in the drawer.
    //    in most cases it is used in, it is ok just to set drawer configuration to override default config values
    return $this->subdrawer_base_drawer->defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return $this->subdrawer_base_drawer->getConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $this->subdrawer_base_drawer->setConfiguration($configuration);
    // return $this for chaining methods, otherwide base drawer instance would be returned
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return $this->subdrawer_base_drawer->calculateDependencies();
  }




  // @todo: most of these should be moved to a base class

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->subdrawer_base_drawer->validateConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->subdrawer_base_drawer->submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    return $this->subdrawer_base_drawer->buildConfigurationForm($form, $form_state);
  }

}
