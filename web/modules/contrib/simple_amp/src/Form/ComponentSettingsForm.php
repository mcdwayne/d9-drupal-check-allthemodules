<?php

namespace Drupal\simple_amp\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityDisplayRepository;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure AMP Components settings.
 */
class ComponentSettingsForm extends ConfigFormBase {

  protected $component_manager;

  /**
   * {@inheritDoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    parent::__construct($config_factory);
    $this->component_manager = \Drupal::service('plugin.manager.simple_amp_component');
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('config.factory'));
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'simple_amp_components_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['simple_amp.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('simple_amp.settings');

    $form['info'] = [
      '#markup' => '<p>' . $this->t('Enable additional components. Additional components can be created via custom code.') . '</p>',
    ];

    $form['components'] = [
      '#type'   => 'table',
      '#header' => [
        $this->t('Component'),
        $this->t('description'),
        $this->t('ID'),
      ],
      '#tableselect' => FALSE,
      '#tabledrag'   => FALSE,
    ];

    $manager = $this->component_manager;
    $plugins = $manager->getDefinitions();
    foreach ($plugins as $plugin) {
      $id = $plugin['id'];
      $plugin = $manager->createInstance($id);
      $form['components'][$id]['enable'] = [
        '#type'          => 'checkbox',
        '#title'         => $plugin->getName(),
        '#default_value' => !empty($config->get('component_' . $id . '_enable')) ? $config->get('component_' . $id . '_enable') : '',
      ];
      $form['components'][$id]['description'] = [
        '#markup' => $plugin->getDescription(),
      ];
      $form['components'][$id]['id'] = [
        '#markup' => $id,
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('simple_amp.settings');
    $form_state->cleanValues();
    foreach ($form_state->getValues() as $key => $value) {
      if ($key == 'components') {
        foreach ($value as $id => $data) {
          $config->set('component_' . $id . '_enable', $data['enable']);
        }
      }
      else {
        $config->set($key, $value);
      }
    }
    $config->save();
    parent::submitForm($form, $form_state);
  }

}
