<?php

/**
 * @file
 * Conatins DrawerWrapperTrait for drawer native drawers to wrap base drawer methods
 */

namespace Drupal\visualn;

use Drupal\Core\Form\FormStateInterface;

trait DrawerWrapperTrait {

  // @todo: move these properties into DrawerBase class

  // Contains a reference to the modifiers array.
  protected $modifiers;

  // Contains modifiers methods substitutions for drawer and modifier methods.
  protected $methods_modifiers_substitutions;

  // These are required methods by the drawer base abstract class,
  // all additional methods should be overridden in certain native wrapper class if required.


  /**
   * @todo: add docblock to this and other methods
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {

    // subdrawer_base_drawer_id is not needed here (it is used in generic wrapper)
    //$subdrawer_base_drawer_id = $configuration['base_drawer_id'];
    $subdrawer_base_drawer_config = $configuration['base_drawer_config'];

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

    // @todo: this should work well except teoretically possible edge case when
    //    base drawer implements create() method and uses some how $configuration there.
    //    in that case configuration should be overridden in the native wrapper create() method override
    //dsm($configuration);
    $configuration = $configuration['base_drawer_config'];
    //dsm($configuration);

    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }


  /**
   * @todo: add to the interface and drawer base class (?)
   *    then it could be also used by the default wrapper
   *
   * returns an array of references to the modifiers
   */
  protected function getMethodModifiers($method_name, $before_after_op) {
    $modifiers = [];
    if (!empty($this->methods_modifiers_substitutions[$method_name][$before_after_op])) {
      foreach ($this->methods_modifiers_substitutions[$method_name][$before_after_op] as $uuid => $substitution_name) {
        //dsm($uuid . ' => ' . $substitution_name);
        $modifiers[] = [
          'modifier' => $this->modifiers[$uuid],
          'substitution_name' => $substitution_name,
        ];
      }
    }
    return $modifiers;
  }








/*
  public function prepareBuild(array &$build, $vuid, ResourceInterface $resource) {
    return $this->subdrawer_base_drawer->prepareBuild($build, $vuid, $resource);
  }
*/

  public function prepareJsConfig(array &$drawer_config) {
    $original_drawer_config = $drawer_config;
    parent::prepareJsConfig($drawer_config);

    foreach ($this->getMethodModifiers('prepareJsConfig', 'after') as  $modifier_substitution) {
      $modifier = $modifier_substitution['modifier'];
      $substitution_name = $modifier_substitution['substitution_name'];
      $modifier->{$substitution_name}($drawer_config, $original_drawer_config);
    }
  }

/*
  public function jsId() {
    return $this->subdrawer_base_drawer->jsId();
  }

  public function dataKeys() {
    return $this->subdrawer_base_drawer->dataKeys();
  }

  public function dataKeysStructure() {
    return $this->subdrawer_base_drawer->dataKeysStructure();
  }
*/





  // @todo: indicate interfaces that groups of methods belong here

  public function defaultConfiguration() {
    $default_configuration = parent::defaultConfiguration();
    // @todo: which one should we use here?
    $configuration = $this->getConfiguration();

    foreach ($this->getMethodModifiers('defaultConfiguration', 'after') as  $modifier_substitution) {
      $modifier = $modifier_substitution['modifier'];
      $substitution_name = $modifier_substitution['substitution_name'];
      $default_configuration = $modifier->{$substitution_name}($default_configuration, $configuration);
    }

    return $default_configuration;
  }

  public function getConfiguration() {
    $configuration = parent::getConfiguration();

    foreach ($this->getMethodModifiers('getConfiguration', 'after') as  $modifier_substitution) {
      $modifier = $modifier_substitution['modifier'];
      $substitution_name = $modifier_substitution['substitution_name'];
      $configuration = $modifier->{$substitution_name}($configuration);
    }

    return $configuration;
  }

/*
  public function setConfiguration(array $configuration) {
    $this->subdrawer_base_drawer->setConfiguration($configuration);
    return $this;
  }

  public function calculateDependencies() {
    return $this->subdrawer_base_drawer->calculateDependencies();
  }





  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->subdrawer_base_drawer->validateConfigurationForm($form, $form_state);
  }

  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->subdrawer_base_drawer->submitConfigurationForm($form, $form_state);
  }
*/

  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    // @todo: since modifiers may modify getConfiguration(), which one should be here?
    // @todo: here the point with getting drawer_config needs more examination
    $drawer_config = $this->getConfiguration() + $this->defaultConfiguration();

    // Modify drawer configuration form

    foreach ($this->getMethodModifiers('buildConfigurationForm', 'after') as  $modifier_substitution) {
      $modifier = $modifier_substitution['modifier'];
      $substitution_name = $modifier_substitution['substitution_name'];
      // @todo: maybe set a reference in drawer modifier to the original base_drawer (base drawer ?)
      //    to not pass drawer_config every time into arguments
      //    though there are security concerns in case of using third-party modifiers
      $form = $modifier->{$substitution_name}($form, $form_state, $drawer_config);
    }

    return $form;
  }

}
