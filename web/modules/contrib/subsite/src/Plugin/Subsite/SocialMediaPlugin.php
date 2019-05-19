<?php
/**
 * Created by PhpStorm.
 * User: andy
 * Date: 26/01/2016
 * Time: 00:57
 */

namespace Drupal\subsite\Plugin\Subsite;


use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\social_media_links\IconsetBase;
use Drupal\subsite\BaseSubsitePlugin;
use Drupal\subsite\SubsitePluginInterface;

/**
 * @Plugin(
 *   id = "subsite_social",
 *   label = @Translation("Social media"),
 *   block_prerender = {
 *     "social_media_links_block"
 *   }
 * )
 */
class SocialMediaPlugin extends BaseSubsitePlugin {
  use StringTranslationTrait;

  private $platformManager;

  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    $this->platformManager = \Drupal::service('plugin.manager.social_media_links.platform');
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * Gets this plugin's configuration.
   *
   * @return array
   *   An array of this plugin's configuration.
   */
  public function getConfiguration() {
    return $this->configuration;
  }

  /**
   * Sets the configuration for this plugin instance.
   *
   * @param array $configuration
   *   An associative array containing the plugin's configuration.
   */
  public function setConfiguration(array $configuration) {
    $this->configuration = $configuration;
  }

  /**
   * Gets default configuration for this plugin.
   *
   * @return array
   *   An associative array with the default configuration.
   */
  public function defaultConfiguration() {
    return array();
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
    $config = $this->getConfiguration();

    // Platforms.
    $form['platforms'] = array(
      '#type' => 'table',
      '#header' => array(
        $this->t('Platform'),
        $this->t('Platform URL'),
        $this->t('Weight'),
      ),
      '#tabledrag' => array(
        array(
          'action' => 'order',
          'relationship' => 'silbing',
          'group' => 'platform-order-weight',
        ),
      ),
    );

    $i = -11;
    foreach ($this->platformManager->getPlatforms() as $platform_id => $platform) {
      $form['platforms'][$platform_id]['#attributes']['class'][] = 'draggable';
      $form['platforms'][$platform_id]['#weight'] = isset($config['platforms'][$platform_id]['weight']) ? $config['platforms'][$platform_id]['weight'] : $i + 1;

      $form['platforms'][$platform_id]['label'] = array(
        '#markup' => '<strong>' . $platform['name']->render() . '</strong>',
      );

      $form['platforms'][$platform_id]['value'] = array(
        '#type' => 'textfield',
        '#title' => $platform['name']->render(),
        '#title_display' => 'invisible',
        '#size' => 40,
        '#default_value' => isset($config['platforms'][$platform_id]['value']) ? $config['platforms'][$platform_id]['value'] : '',
        '#field_prefix' => $platform['instance']->getUrlPrefix(),
        '#field_suffix' => $platform['instance']->getUrlSuffix(),
      );

      $form['platforms'][$platform_id]['weight'] = array(
        '#type' => 'weight',
        '#title' => t('Weight for @title', array('@title' => $platform['name']->render())),
        '#title_display' => 'invisible',
        '#default_value' => isset($config['platforms'][$platform_id]['weight']) ? $config['platforms'][$platform_id]['weight'] : $i + 1,
        '#attributes' => array('class' => array('platform-order-weight')),
      );

      $i++;
    }

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
    $plugin_form_values = $form_state->getValue($form['#parents']);

    $this->setConfiguration($plugin_form_values);
  }

  public function blockPrerender($build, $node, $subsite_node) {
    $configuration = $this->getConfiguration();

    $platformManager = \Drupal::service('plugin.manager.social_media_links.platform');
    $platforms = $platformManager->getPlatformsWithValue($configuration['platforms']);

    $config = $build['#configuration'];
    $iconset = IconsetBase::explodeStyle($config['iconset']['style']);

    $iconsetManager = \Drupal::service('plugin.manager.social_media_links.iconset');


    try {
      $iconsetInstance = $iconsetManager->createInstance($iconset['iconset']);
    }
    catch (PluginException $exception) {
      \Drupal::logger('social_media_links')->error('The selected "@iconset" iconset plugin does not exist.', array('@iconset' => $iconset['iconset']));
      return array();
    }

    foreach ($config['link_attributes'] as $key => $value) {
      if ($value === '<none>') {
        unset($config['link_attributes'][$key]);
      }
    }

    foreach ($platforms as $platform_id => $platform) {
      $platforms[$platform_id]['element'] = (array) $iconsetInstance->getIconElement($platform['instance'], $iconset['style']);
    }

    $build['content'][0]['#platforms'] = $platforms;

    return $build;
  }
}