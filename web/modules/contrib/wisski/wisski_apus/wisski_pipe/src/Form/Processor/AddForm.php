<?php

/**
 * @file
 * Contains \Drupal\wisski_pipe\Form\Processor\AddForm.
 */

namespace Drupal\wisski_pipe\Form\Processor;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\wisski_pipe\ProcessorManager;
use Drupal\wisski_pipe\PipeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form to apply processors to a pipe.
 */
class AddForm extends FormBase {

  /**
   * The pipes to which the processors will be applied.
   *
   * @var \Drupal\wisski_pipe\PipeInterface
   */
  protected $pipe;


  /**
   * The processor manager.
   *
   * @var \Drupal\wisski_pipe\ProcessorManager
   */
  protected $manager;

  /**
   * Constructs a new AddForm.
   *
   * @param \Drupal\wisski_pipe\ProcessorManager $manager
   *   The processor manager.
   */
  public function __construct(ProcessorManager $manager) {
    $this->manager = $manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.wisski_pipe.processor')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return "wisski_pipe_processor_add_form";
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, PipeInterface $wisski_pipe = NULL) {
    $this->pipe = $wisski_pipe;

    $form['#attached']['library'][] = 'wisski_pipe/wisski_pipe.admin';
    $header = [
      'label' => [
        'data' => $this->t('Processors'),
        'colspan' => 2
      ]
    ];

    $form['plugin'] = [
      '#type' => 'tableselect',
      '#header' => $header,
      '#options' => $this->buildRows(),
      '#empty' => $this->t('No processors available.'),
      '#multiple' => FALSE,
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save and continue'),
      '#submit' => ['::submitForm'],
      '#tableselect' => TRUE,
      '#button_type' => 'primary',
    ];

    $options = [];
    foreach ($this->manager->getDefinitions() as $id => $plugin) {
      $options[$id] = $plugin['label'];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (empty($form_state->getValue('plugin'))) {
      $form_state->setErrorByName('plugin', $this->t('No processor selected.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->cleanValues();

    /** @var \Drupal\wisski_pipe\ProcessorInterface $plugin */
    $plugin = $this->manager->createInstance($form_state->getValue('plugin'));

    $plugin_uuid = $this->pipe->addProcessor($plugin->getConfiguration());
    $this->pipe->save();

    $this->logger('wisski_pipe')->notice('Added %label processor to the @pipe pipe.', [
      '%label' => $this->pipe->getProcessor($plugin_uuid)->getLabel(),
      '@pipe' => $this->pipe->label(),
    ]);

    $is_configurable = $plugin instanceof ConfigurablePluginInterface;
    if ($is_configurable) {
      $form_state->setRedirect('wisski_pipe.processor.edit', [
        'wisski_pipe' => $this->pipe->id(),
        'plugin_instance_id' => $plugin_uuid,
      ]);
    }
    else {
      drupal_set_message($this->t('Added %label processor.', ['%label' => $plugin->getLabel()]));

      $form_state->setRedirect('wisski_pipe.processors', [
        'wisski_pipe' => $this->pipe->id(),
      ]);
    }
  }

  /**
   * Builds the table rows.
   *
   * @return array
   *   An array of table rows.
   */
  private function buildRows() {
    $rows = [];
    $all_plugins = $this->manager->getDefinitions();
    uasort($all_plugins, function ($a, $b) {
      return strnatcasecmp($a['label'], $b['label']);
    });
    foreach ($all_plugins as $definition) {
      /** @var \Drupal\wisski_pipe\ProcessorInterface $plugin */
      $plugin = $this->manager->createInstance($definition['id']);
      $row = [
        'label' => $plugin->getLabel(),
        'descrription' => $plugin->getDescription(),
      ];
      $rows[$plugin->getPluginId()] = $row;
    }

    return $rows;
  }

}
