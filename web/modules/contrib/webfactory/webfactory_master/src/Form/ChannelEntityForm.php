<?php

namespace Drupal\webfactory_master\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\webfactory_master\Plugin\ChannelSourcePluginManager;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ChannelEntityForm.
 *
 * @package Drupal\webfactory_master\Form
 */
class ChannelEntityForm extends EntityForm {

  /**
   * The channel source plugin manager.
   *
   * @var \Drupal\webfactory_master\Plugin\ChannelSourcePluginManager
   */
  protected $channelSourceManager;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a ChannelEntityForm.
   *
   * @param \Drupal\webfactory_master\Plugin\ChannelSourcePluginManager $channel_source_manager
   *   The channel source plugin manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   */
  public function __construct(ChannelSourcePluginManager $channel_source_manager, EntityTypeManagerInterface $entity_type_manager) {
    $this->channelSourceManager = $channel_source_manager;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.webfactory_master.channel'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $channel_src_plugins = $this->channelSourceManager->getDefinitions();

    $plugin_options = [];
    foreach ($channel_src_plugins as $channel_src_plugin) {
      $plugin_options[$channel_src_plugin['id']] = $channel_src_plugin['label'];
    }

    $form['source'] = [
      '#type' => 'select',
      '#title' => $this->t('Channel source type'),
      '#options' => $plugin_options,
      '#empty_option' => $this->t('- Select a channel source type -'),
      '#required' => TRUE,
    ];

    $channel_entity = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $channel_entity->label(),
      '#description' => $this->t("Label for the Channel."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $channel_entity->id(),
      '#machine_name' => [
        'exists' => '\Drupal\webfactory_master\Entity\ChannelEntity::load',
      ],
      '#disabled' => !$channel_entity->isNew(),
    ];

    $entities = $this->entityTypeManager->getDefinitions();
    $content_entities_options = [];
    foreach ($entities as $id => $entity) {
      if ($entity->getGroup() == 'content') {
        $content_entities_options[$id] = $entity->getLabel();
      }
    }

    $form['entity_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Entities'),
      '#options' => $content_entities_options,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $channel_entity = $this->entity;

    $status = $channel_entity->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Channel.', [
          '%label' => $channel_entity->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Channel.', [
          '%label' => $channel_entity->label(),
        ]));
    }
    $form_state->setRedirectUrl($channel_entity->toUrl('edit-form'));
  }

}
