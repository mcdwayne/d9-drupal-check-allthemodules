<?php

/**
 * @file
 *
 * Conatins DefaultDrawerWrapper used for subdrawers. Its primary purpose is to allow modifiers
 * to modify base drawer behaviour.
 */

namespace Drupal\visualn\Plugin\VisualN\Drawer;

use Drupal\visualn\Plugin\VisualNDrawerWrapperBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\visualn\ResourceInterface;

/**
 * Provides a 'Default Drawer Wrapper' VisualN drawer.
 *
 * @VisualNDrawer(
 *  id = "visualn_default_drawer_wrapper",
 *  label = @Translation("Default Drawer Wrapper"),
 *  role = "wrapper"
 * )
 */
// @todo: !IMPORTANT: drawer should always be actualized with every new change to the interface and base class
//    since it must include all the methods and wrapper around them to delegate to the subdrawer_base_drawer object.
class DefaultDrawerWrapper extends VisualNDrawerWrapperBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $default_values = $this->subdrawer_base_drawer->defaultConfiguration();
    // @todo: which one should we use here?
    $configuration = $this->subdrawer_base_drawer->getConfiguration();

    // Modify drawer default configuration

    if (!empty($this->methods_modifiers_substitutions['defaultConfiguration']['after'])) {
      foreach ($this->methods_modifiers_substitutions['defaultConfiguration']['after'] as $uuid => $substitution_name) {
        //dsm($uuid . ' => ' . $substitution_name);
        $default_values = $this->modifiers[$uuid]->{$substitution_name}($default_values, $configuration);
      }
    }

    return $default_values;
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    // @todo: the method may be used internally in drawer plugins methods
    $configuration = $this->subdrawer_base_drawer->getConfiguration();

    if (!empty($this->methods_modifiers_substitutions['getConfiguration']['after'])) {
      foreach ($this->methods_modifiers_substitutions['getConfiguration']['after'] as $uuid => $substitution_name) {
        //dsm($uuid . ' => ' . $substitution_name);
        $configuration = $this->modifiers[$uuid]->{$substitution_name}($configuration);
      }
    }

    return $configuration;
  }

  /**
   * @inheritdoc
   */
  public function prepareBuild(array &$build, $vuid, ResourceInterface $resource) {
    $resource = $this->subdrawer_base_drawer->prepareBuild($build, $vuid, $resource);


    // @todo: do override here for getConfiguration() because in other places it is used internally

    // @todo: use getConfiguration() instead?
    $drawer_config =  $this->subdrawer_base_drawer->configuration + $this->subdrawer_base_drawer->defaultConfiguration();

    // @todo: we can't override prepareConfig directly since it is used internally inside the prepareBuild() method
    $this->prepareJsConfig($drawer_config);


    //$this->subdrawer_base_drawer->prepareJsConfig($drawer_config);
    $build['#attached']['drupalSettings']['visualn']['drawings'][$vuid]['drawer']['config'] = $drawer_config;

    return $resource;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareJsConfig(array &$drawer_config) {
    $original_drawer_config = $drawer_config;
    $this->subdrawer_base_drawer->prepareJsConfig($drawer_config);

    if (!empty($this->methods_modifiers_substitutions['prepareJsConfig']['after'])) {
      foreach ($this->methods_modifiers_substitutions['prepareJsConfig']['after'] as $uuid => $substitution_name) {
        //dsm($uuid . ' => ' . $substitution_name);
        $this->modifiers[$uuid]->{$substitution_name}($drawer_config, $original_drawer_config);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = $this->subdrawer_base_drawer->buildConfigurationForm($form, $form_state);

    // @todo: since modifiers may modify getConfiguration(), which one should be here?
    //$drawer_config = $this->subdrawer_base_drawer->getConfiguration();
    // the logic is as follows: modifier for buildConfiguration() already needs modified default configuration
    //    also it would need modified getConfiguration() but since getConfiguration() may be also used internally,
    //    we don't use it here, though original getConfiguration() should return what we need any way
    //    because in most cases submitConfigurationForm() just saves form_state values which are then used by configuration
    $drawer_config = $this->subdrawer_base_drawer->getConfiguration() + $this->defaultConfiguration();

    // Modify drawer configuration form

    if (!empty($this->methods_modifiers_substitutions['buildConfigurationForm']['after'])) {
      foreach ($this->methods_modifiers_substitutions['buildConfigurationForm']['after'] as $uuid => $substitution_name) {
        // @todo: maybe set a reference in drawer modifier to the original base_drawer (base drawer ?)
        //    to not pass drawer_config every time into arguments
        //    though there are security concerns in case of using third-party modifiers
        //    ! that is not possible though for native wrappers because there is no reference to the base drawer -
        //      native drawer is base drawer itself (it extends base drawer class)
        //dsm($uuid . ' => ' . $substitution_name);
        $form = $this->modifiers[$uuid]->{$substitution_name}($form, $form_state, $drawer_config);
      }
    }

    return $form;
  }

}
