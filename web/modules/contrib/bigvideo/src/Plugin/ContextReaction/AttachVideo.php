<?php

namespace Drupal\bigvideo\Plugin\ContextReaction;

use Drupal\bigvideo\Entity\BigvideoPageInterface;
use Drupal\context\ContextReactionPluginBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a content reaction that will let you add a background video to page.
 *
 * @ContextReaction(
 *   id = "bigvideo",
 *   label = @Translation("BigVideo background")
 * )
 */
class AttachVideo extends ContextReactionPluginBase implements ContainerFactoryPluginInterface {

  /**
   * BigVideo Source entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $bigvideoSourceStorage;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->bigvideoSourceStorage = $entity_type_manager->getStorage('bigvideo_source');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $defaults = parent::defaultConfiguration();
    $defaults['selector'] = '';
    return $defaults;
  }

  /**
   * {@inheritdoc}
   */
  public function summary() {
    return $this->t('Lets you add a background video.');
  }

  /**
   * {@inheritdoc}
   */
  public function execute() {
    /** @var \Drupal\bigvideo\Entity\BigvideoSourceInterface $source */
    $source = $this->bigvideoSourceStorage->load($this->configuration['source']);

    return [
      'links' => $source->createVideoLinks(),
      'selector' => $this->configuration['selector'] ?: BigvideoPageInterface::DEFAULT_SELECTOR,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $sources = $this->bigvideoSourceStorage->loadMultiple();
    $options = [];
    /** @var \Drupal\bigvideo\Entity\BigvideoSource $source */
    foreach ($sources as $source) {
      $options[$source->id()] = $source->label();
    }
    $form['source'] = [
      '#type' => 'select',
      '#title' => $this->t('Source'),
      '#options' => $options,
      '#required' => TRUE,
      '#default_value' => $this->configuration['source'],
    ];
    $form['selector'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Selector'),
      '#description' => t('BigVideo will be applied to this selector instead of "body".'),
      '#attributes' => [
        'placeholder' => 'body',
      ],
      '#default_value' => $this->configuration['selector'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['source'] = $form_state->getValue('source');
    $this->configuration['selector'] = $form_state->getValue('selector');
  }

}
