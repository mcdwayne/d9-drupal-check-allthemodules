<?php

namespace Drupal\webfactory_master\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\webfactory_master\Plugin\ChannelSourcePluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ChannelEntityEditForm.
 *
 * @package Drupal\webfactory_master\Form
 */
class ChannelEntityEditForm extends EntityForm {

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
   * Constructs a ChannelEntityEditForm.
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

    $plugin_options = array();
    foreach ($channel_src_plugins as $channel_src_plugin) {
      $plugin_options[$channel_src_plugin['id']] = $channel_src_plugin['label'];
    }

    $entities = $this->entityTypeManager->getDefinitions();
    $content_entities_options = [];
    foreach ($entities as $id => $entity) {
      if ($entity->getGroup() == 'content') {
        $content_entities_options[$id] = $entity->getLabel();
      }
    }

    /** @var \Drupal\webfactory_master\Entity\ChannelEntity $channel_entity */
    $channel_entity = $this->entity;

    $form['source'] = array(
      '#type' => 'select',
      '#title' => $this->t('Channel source type'),
      '#options' => $plugin_options,
      '#default_value' => $channel_entity->get('source'),
      '#disabled' => !$channel_entity->isNew(),
    );

    $form['label'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $channel_entity->label(),
      '#description' => $this->t("Label for the Channel."),
      '#required' => TRUE,
    );

    $form['id'] = array(
      '#type' => 'machine_name',
      '#default_value' => $channel_entity->id(),
      '#machine_name' => array(
        'exists' => '\Drupal\webfactory_master\Entity\ChannelEntity::load',
      ),
      '#disabled' => !$channel_entity->isNew(),
    );

    $form['entity_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Entities'),
      '#options' => $content_entities_options,
      '#default_value' => $channel_entity->get('entity_type'),
    ];

    $form['settings'] = [
      '#type' => 'details',
      '#title' => $this->t("Settings"),
      '#collapsible' => TRUE,
    ];

    $channel_type = $channel_entity->get('source');
    $channel = $this->channelSourceManager->createInstance($channel_type);

    $settings = $channel_entity->get('settings') != NULL ? $channel_entity->get('settings') : [];
    $channel->setConfiguration($channel_entity, $settings);
    $form['settings'] += $channel->getSettingsForm($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\webfactory_master\Entity\ChannelEntity $channel_entity */
    $channel_entity = $this->entity;
    $channel_type = $channel_entity->get('source');
    $channel = $this->channelSourceManager->createInstance($channel_type);

    $settings = $channel->getSettings($form, $form_state);
    $channel_entity->set('settings', $settings);

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

    $form_state->setRedirectUrl($channel_entity->urlInfo('collection'));
  }

}
