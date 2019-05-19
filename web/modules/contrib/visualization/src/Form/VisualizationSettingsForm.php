<?php
namespace Drupal\visualization\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Config\ConfigFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\visualization\Plugin\VisualizationHandlerManager;
use Drupal\Core\Form\FormStateInterface;

class VisualizationSettingsForm extends ConfigFormBase {

  /**
   * Implements \Drupal\Core\ControllerInterface::create().
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('plugin.manager.visualization.handler'));
  }

  public function __construct(ConfigFactory $config_factory, VisualizationHandlerManager $handler_manager) {
    parent::__construct($config_factory);
    $this->handler_manager = $handler_manager;
  }

  public function getFormID() {
    return 'visualization_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['visualization.settings'];
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $options = array();
    $type = $this->handler_manager;
    foreach ($type->getDefinitions() as $id => $plugin) {
      if ($type->createInstance($id)->available()) {
        $options[$id] = $plugin['label'];
      }
    }
    $config = $this->config('visualization.settings');
    $form['library'] = array(
      '#type' => 'select',
      '#title' => t('Preferred charting library'),
      '#options' => $options,
      '#description' => t('Your charting library of preference will be used when generating charts (as long as it offers support for the requested type). The available options are the charting libraries detected on your system, please refer to the help section for more information about enabling more libraries.'),
      '#default_value' => $config->get('library'),
    );

    return parent::buildForm($form, $form_state);
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->configFactory->getEditable('visualization.settings')
      ->set('library', $form_state->getValue('library'))
      ->save();

    parent::submitForm($form, $form_state);
  }
}
?>
