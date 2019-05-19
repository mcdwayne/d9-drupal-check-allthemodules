<?php

/**
 * @file
 * Contains \Drupal\wisski_pipe\Form\Processor\OverviewForm.
 */

namespace Drupal\wisski_pipe\Form\Processor;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\wisski_pipe\ProcessorManager;
use Drupal\wisski_pipe\PipeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides an overview form for processors on a pipe.
 */
class OverviewForm extends FormBase {

  /**
   * The pipes to which the processors are applied to.
   *
   * @var \Drupal\wisski_pipe\PipeInterface
   */
  private $pipe;

  /**
   * The processor manager.
   *
   * @var \Drupal\wisski_pipe\ProcessorManager
   */
  protected $manager;

  /**
   * Constructs a new OverviewForm.
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
    return "wisski_pipe_processor_overview_form";
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, PipeInterface $wisski_pipe = NULL) {
    $this->pipe = $wisski_pipe;
    $form['plugins'] = [
      '#type' => 'table',
      '#header' => [
        [
          'data' => $this->t('Processor'),
          'colspan' => 2
        ],
        $this->t('Weight'),
        $this->t('Operations'),
      ],
      '#empty' => $this->t('No processors added.'),
      '#tabledrag' => [
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'plugin-order-weight',
        ],
      ],
    ];

    foreach ($this->pipe->getProcessors() as $plugin) {
      $key = $plugin->getUuid();

      $form['plugins'][$key]['#attributes']['class'][] = 'draggable';
      $form['plugins'][$key]['#weight'] = $plugin->getWeight();

      $form['plugins'][$key]['label'] = [
        '#plain_text' => (string) $plugin->getLabel(),
      ];

      $form['plugins'][$key]['summary'] = [];

      $summary = $plugin->getSummary();
      if (!empty($summary)) {
        $form['plugins'][$key]['summary'] = [
          '#type' => 'inline_template',
          '#template' => '<div class="wisski_pipe-plugin-summary">{{ summary|safe_join("<br />") }}</div>',
          '#context' => ['summary' => $summary],
        ];
      }

      $form['plugins'][$key]['weight'] = [
        '#type' => 'weight',
        '#title' => t('Weight for @title', ['@title' => (string) $plugin->getLabel()]),
        '#title_display' => 'invisible',
        '#default_value' => $plugin->getWeight(),
        '#attributes' => ['class' => ['plugin-order-weight']],
      ];

      $form['plugins'][$key]['operations'] = [
        '#type' => 'operations',
        '#links' => [],
      ];

      $is_configurable = $plugin instanceof ConfigurablePluginInterface;
      if ($is_configurable) {
        $form['plugins'][$key]['operations']['#links']['edit'] = [
          'title' => t('Edit'),
          'url' => Url::fromRoute('wisski_pipe.processor.edit', [
            'wisski_pipe' =>  $this->pipe->id(),
            'plugin_instance_id' => $key,
          ]),
        ];
      }

      $form['plugins'][$key]['operations']['#links']['delete'] = [
        'title' => t('Delete'),
        'url' => Url::fromRoute('wisski_pipe.processor.delete', [
          'wisski_pipe' =>  $this->pipe->id(),
          'plugin_instance_id' => $key,
        ]),
      ];
    }

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    foreach ($form_state->getValue('plugins') as $id => $plugin_data) {
      if ($this->pipe->getProcessors()->has($id)) {
        $this->pipe->getProcessor($id)->setWeight($plugin_data['weight']);
      }
    }
    $this->pipe->save();
  }

}
