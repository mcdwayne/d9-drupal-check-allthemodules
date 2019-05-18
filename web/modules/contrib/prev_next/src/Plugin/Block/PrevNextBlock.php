<?php

namespace Drupal\prev_next\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\node\NodeInterface;
use Drupal\prev_next\PrevNextHelperInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides a 'Previous Next' block.
 *
 * @Block(
 *  id = "prev_next_block",
 *  admin_label = @Translation("Prev/Next"),
 *   context = {
 *     "node" = @ContextDefinition("entity:node", label = @Translation("Node"))
 *   }
 * )
 */
class PrevNextBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Symfony\Component\HttpFoundation\RequestStack definition.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The PrevNextHelper service.
   *
   * @var \Drupal\prev_next\PrevNextHelperInterface
   */
  protected $prevnextHelper;

  /**
   * Constructs an PrevNextBlock object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param string $plugin_definition
   *   The plugin implementation definition.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The Request Stack.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\prev_next\PrevNextHelperInterface $prevnext_helper
   *   The PrevNextHelper service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, RequestStack $request_stack, ModuleHandlerInterface $module_handler, PrevNextHelperInterface $prevnext_helper) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->requestStack = $request_stack;
    $this->moduleHandler = $module_handler;
    $this->prevnextHelper = $prevnext_helper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('request_stack'),
      $container->get('module_handler'),
      $container->get('prev_next.helper')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'prev_display' => TRUE,
      'prev_text' => $this->t('«prev'),
      'next_display' => TRUE,
      'next_text' => $this->t('next»'),
    ];

  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['previous'] = [
      '#type' => 'details',
      '#title' => $this->t('Previous Node'),
      '#open' => TRUE,
      '#tree' => TRUE,
    ];
    $form['previous']['prev_display'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display'),
      '#default_value' => $this->configuration['prev_display'],
    ];
    $form['previous']['prev_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Link text'),
      '#description' => $this->t('Provide the text to customize the previous link text.'),
      '#default_value' => $this->configuration['prev_text'],
    ];
    $form['next'] = [
      '#type' => 'details',
      '#title' => $this->t('Next Node'),
      '#open' => TRUE,
      '#tree' => TRUE,
    ];
    $form['next']['next_display'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display'),
      '#default_value' => $this->configuration['next_display'],
    ];
    $form['next']['next_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Link text'),
      '#description' => $this->t('Provide the text to customize the next link text.'),
      '#default_value' => $this->configuration['next_text'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $previous_settings = $form_state->getValue('previous');
    foreach ($previous_settings as $key => $value) {
      $this->setConfigurationValue($key, $value);
    }
    $next_settings = $form_state->getValue('next');
    foreach ($next_settings as $key => $value) {
      $this->setConfigurationValue($key, $value);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [
      '#theme' => 'prev_next_block',
    ];

    /* @var $node \Drupal\node\NodeInterface */
    $node = $this->getContextValue('node');
    if ($node instanceof NodeInterface) {
      $prev_id = $this->prevnextHelper->getPrevnextId($node->id(), 'prev');
      $next_id = $this->prevnextHelper->getPrevnextId($node->id(), 'next');
      if ($next_id || $prev_id) {
        if ($prev_id && $this->configuration['prev_display'] && $this->configuration['prev_text'] != '') {
          $build += [
            '#prev_display' => $this->configuration['prev_display'],
            '#prev_text' => $this->configuration['prev_text'],
            '#prev_id' => $prev_id,
          ];
        }
        if ($next_id && $this->configuration['next_display'] && $this->configuration['next_text'] != '') {
          $build += [
            '#next_display' => $this->configuration['next_display'],
            '#next_text' => $this->configuration['next_text'],
            '#next_id' => $next_id,
          ];
        }
      }
    }
    return $build;
  }

}
