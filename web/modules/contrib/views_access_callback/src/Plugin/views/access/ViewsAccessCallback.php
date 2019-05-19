<?php

namespace Drupal\views_access_callback\Plugin\views\access;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Component\Utility\Html;
use Drupal\views\Plugin\views\access\AccessPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Route;

/**
 * Access plugin that provides permission-based access control.
 *
 * @ingroup views_access_plugins
 *
 * @ViewsAccess(
 *   id = "views_access_callback",
 *   title = @Translation("Callback function"),
 *   help = @Translation("Access will be granted to users based on result returned by callback function.")
 * )
 */
class ViewsAccessCallback extends AccessPluginBase {

  /**
   * {@inheritdoc}
   */
  protected $usesOptions = TRUE;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a Permission object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ModuleHandlerInterface $module_handler) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function access(AccountInterface $account) {
    return views_access_callback_access_callback($this->view->storage->id(), $this->view->current_display, $this->options['views_access_callback']);
  }

  /**
   * {@inheritdoc}
   */
  public function alterRouteDefinition(Route $route) {
    $bool = (views_access_callback_access_callback($this->view->storage->id(), $this->view->current_display, $this->options['views_access_callback'])) ? 'TRUE' : 'FALSE';
    $route->setRequirement('_access', $bool);
  }

  /**
   * {@inheritdoc}
   */
  public function summaryTitle() {
    return $this->t('Callback function') . ': ' . $this->options['views_access_callback'];
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['views_access_callback'] = array('default' => 'TRUE');
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    $callbacks = array();
    $module_info = system_get_info('module');

    foreach ($this->moduleHandler->getImplementations('views_access_callbacks') as $module) {
      $functions = $this->moduleHandler->invoke($module, 'views_access_callbacks');
      foreach ($functions as $function => $name) {
        $callbacks[$module_info[$module]['name']][$function] = Html::escape($name);
      }
    }

    ksort($callbacks);

    $form['views_access_callback'] = array(
      '#type' => 'select',
      '#options' => $callbacks,
      '#title' => $this->t('Callbacks'),
      '#default_value' => $this->options['views_access_callback'],
      '#description' => $this->t('Only users for which selected callback function returns TRUE will be able to access this display.'),
    );

  }
}
