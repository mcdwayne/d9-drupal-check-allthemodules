<?php

/**
 * @file
 * Contains \Drupal\feadmin\Form\FeAdminSettingsForm.
 *
 * Sponsored by: www.freelance-drupal.com
 */

namespace Drupal\feadmin\Form;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\feadmin\FeAdminTool\FeAdminToolManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure site information settings for this site.
 */
class FeAdminSettingsForm extends ConfigFormBase {

  /**
   * The FeAdminTool manager.
   *
   * @var \Drupal\feadmin\FeAdminTool\FeAdminToolManager $feAdminToolManager
   */
  protected $feAdminToolManager;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'feadmin_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['feadmin.settings'];
  }

  /**
   * Constructs a SiteInformationForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\feadmin\FeAdminTool\FeAdminToolManagerInterface $feadmintool_manager
   *   The admin tool manager service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, FeAdminToolManagerInterface $feadmintool_manager) {
    parent::__construct($config_factory);
    $this->feAdminToolManager = $feadmintool_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('plugin.manager.feadmin.tool')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $settings = $this->config('feadmin.settings')->get('tools');
    $form['#attached'] = array(
      'library' => array(
        'feadmin/feadmin.admin',
      ),
    );

    $form['feadmin'] = array(
      '#type' => 'table',
      '#header' => array(
        'draggable' => array(
          'data' => '',
        ),
        'enabled' => array(
          'data' => $this->t('Enabeld'),
        ),
        'label' => array(
          'data' => $this->t('Tool name'),
        ),
        'weight' => array(
          'data' => $this->t('Weigth'),
          'class' => array(RESPONSIVE_PRIORITY_LOW),
        ),
        'description' => array(
          'data' => $this->t('Configuration'),
        ),
      ),
      '#empty' => t('There are no tools available yet.'),
      '#tabledrag' => array(
        array(
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'feadmin-order-weight',
        ),
      ),
      '#attributes' => array(
        'id' => 'feadmin-table',
      ),
    );

    $feadmin_tools = $this->feAdminToolManager->getDefinitions();
    foreach($feadmin_tools as  $id => $feadmin_tool) {
      $tool_settings = isset($settings[$id]) ? $settings[$id] : array(
        'weight' => NULL,
        'enabled' => FALSE,
      );
      $form['feadmin'][$id] = array(
        '#weight' => $tool_settings['weight'],
        '#attributes' => array (
          'class' => array('draggable'),
        ),
        'draggable' => array(
          '#wrapper_attributes' => array(
            'class' => array('draggable'),
          ),
        ),
        'enabled' => array(
          'data' => array(
            '#type' => 'checkbox',
            '#default_value' => $tool_settings['enabled'],
            '#theme_wrapper' => array(),
          ),
          '#wrapper_attributes' => array (
            'class' => array('checkbox'),
          ),
        ),
        'title' => array(
          'data' => array(
            '#markup' => $feadmin_tool['label'],
          ),
          '#wrapper_attributes' => array(
            'class' => array('label'),
          ),
        ),
        'weight' => array(
          'data' => array(
            '#type' => 'weight',
            '#title' => t('Weight for @title', array('@title' => $feadmin_tool['label'])),
            '#title_display' => 'invisible',
            '#default_value' => !empty($tool_settings) ? $tool_settings['weight'] : NULL,
            '#attributes' => array('class' => array('feadmin-order-weight')),
          )
        ),
      );

      $config = $this->feAdminToolManager->createInstance($id)->buildConfigurationForm(array(), $form_state);
      if (empty($config)) {
        $form['feadmin'][$id]['description'] = array(
          'data' => array(
            '#markup' => $feadmin_tool['description'],
          ),
        );
      } else {
        $form['feadmin'][$id]['description'] = array(
          'data' => array(
            '#type' => 'details',
            '#title' => $feadmin_tool['description'],
            '#open' => FALSE,
            '#tree' => FALSE,
            '#name' => $id,
          )
        );
        $form['feadmin'][$id]['description']['#wrapper_attributes'] = array (
          'class' => array('description'),
        );

        // Add custom parents to config elements
        foreach (Element::children($config) as $element_id) {
          $config[$element_id]['#parents'] = array($id, $element_id);
        }
        $form['feadmin'][$id]['description']['data'] += $config;
      }
    }

    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Save changes'),
    );
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

    // Validate table tools data.
    foreach (Element::children($form['feadmin']) as $id) {
      // Get this particular plugin values.
      $plugin_values = $form_state->getValue($id);

      // If not empty: validate.
      if (!empty($plugin_values)) {
        $this->feAdminToolManager->createInstance($id)
          ->validateConfigurationForm($form, $form_state);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $settings = $this->config('feadmin.settings');

    // Retrieve the general tools configuration in the table.
    $tools = $form_state->getValue('feadmin');

    $results = array();

    // Retrieve and save all custom plugin configurations.
    foreach (Element::children($form['feadmin']) as $id) {

      // Set the settings for this tool.
      $results[$id] = array(
        'enabled' => (bool) $tools[$id]['enabled']['data'],
        'weight' => (integer) $tools[$id]['weight']['data'],
      );

      // Get this specific tool values.
      $tool_values = $form_state->getValue($id);

      // If not empty: validate.
      if (!empty($tool_values)) {
        $this->feAdminToolManager->createInstance($id)
          ->submitConfigurationForm($form, $form_state);
      }
    }
    $settings->set('tools', $results);
    $settings->save();

    Cache::invalidateTags(['rendered']);
    parent::submitForm($form, $form_state);
  }

}
