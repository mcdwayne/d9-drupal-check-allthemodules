<?php

/**
 * Created by PhpStorm.
 * User: andy
 * Date: 15/01/2016
 * Time: 23:16
 */

namespace Drupal\subsite\Plugin\Subsite;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\subsite\BaseSubsitePlugin;
use Drupal\subsite\SubsitePluginInterface;

/**
 * @Plugin(
 *   id = "subsite_theme",
 *   label = @Translation("Theme"),
 * )
 */
class ThemeSubsitePlugin extends BaseSubsitePlugin {
  public function getTheme() {
    return !empty($this->configuration['theme']) ? $this->configuration['theme'] : FALSE;
  }

  /**
   * Gets this plugin's configuration.
   *
   * @return array
   *   An array of this plugin's configuration.
   */
  public function getConfiguration() {
    return $this->configuration;
    // TODO: Implement getConfiguration() method.
  }

  /**
   * Sets the configuration for this plugin instance.
   *
   * @param array $configuration
   *   An associative array containing the plugin's configuration.
   */
  public function setConfiguration(array $configuration) {
    $this->configuration = $configuration;
    // TODO: Implement setConfiguration() method.
  }

  /**
   * Gets default configuration for this plugin.
   *
   * @return array
   *   An associative array with the default configuration.
   */
  public function defaultConfiguration() {
    return array(
      'theme' => '',
    );
  }

  /**
   * Calculates dependencies for the configured plugin.
   *
   * Dependencies are saved in the plugin's configuration entity and are used to
   * determine configuration synchronization order. For example, if the plugin
   * integrates with specific user roles, this method should return an array of
   * dependencies listing the specified roles.
   *
   * @return array
   *   An array of dependencies grouped by type (config, content, module,
   *   theme). For example:
   * @code
   *   array(
   *     'config' => array('user.role.anonymous', 'user.role.authenticated'),
   *     'content' => array('node:article:f0a189e6-55fb-47fb-8005-5bef81c44d6d'),
   *     'module' => array('node', 'user'),
   *     'theme' => array('seven'),
   *   );
   * @endcode
   *
   * @see \Drupal\Core\Config\Entity\ConfigDependencyManager
   * @see \Drupal\Core\Entity\EntityInterface::getConfigDependencyName()
   */
  public function calculateDependencies() {
    // TODO: Implement calculateDependencies() method.
  }

  /**
   * Form constructor.
   *
   * Plugin forms are embedded in other forms. In order to know where the plugin
   * form is located in the parent form, #parents and #array_parents must be
   * known, but these are not available during the initial build phase. In order
   * to have these properties available when building the plugin form's
   * elements, let this method return a form element that has a #process
   * callback and build the rest of the form in the callback. By the time the
   * callback is executed, the element's #parents and #array_parents properties
   * will have been set by the form API. For more documentation on #parents and
   * #array_parents, see \Drupal\Core\Render\Element\FormElement.
   *
   * @param array $form
   *   An associative array containing the initial structure of the plugin form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the complete form.
   *
   * @return array
   *   The form structure.
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    /** @var ThemeHandlerInterface $theme_handler */
    $theme_handler = \Drupal::service('theme_handler');

    $installed_theme_info = $theme_handler->listInfo();
    $theme_options = array_map(function($element) {return $element->info['name'];}, $installed_theme_info);

    $form['theme'] = array(
      '#title' => t('Select the theme'),
      '#type' => 'select',
      '#options' => $theme_options,
      '#default_value' => $this->configuration['theme'],
    );

    return $form;
  }

  /**
   * Form validation handler.
   *
   * @param array $form
   *   An associative array containing the structure of the plugin form as built
   *   by static::buildConfigurationForm().
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the complete form.
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    // TODO: Implement validateConfigurationForm() method.
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the plugin form as built
   *   by static::buildConfigurationForm().
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the complete form.
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    // TODO: Implement submitConfigurationForm() method.
    // Need to get array parents here.
    $plugin_form_values = $form_state->getValue($form['#parents']);
    $this->setConfiguration($plugin_form_values);

  }
}