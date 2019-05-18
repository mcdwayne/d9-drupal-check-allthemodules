<?php

namespace Drupal\ad_entity\Plugin;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslationManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Component\Plugin\PluginBase;
use Drupal\ad_entity\Entity\AdEntityInterface;

/**
 * Base class for Advertising view handlers.
 */
abstract class AdViewBase extends PluginBase implements AdViewInterface, ContainerFactoryPluginInterface {

  /**
   * The string translation service.
   *
   * @var \Drupal\Core\StringTranslation\TranslationManager
   */
  protected $stringTranslation;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('string_translation')
    );
  }

  /**
   * Constructs an AdViewBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\StringTranslation\TranslationManager $translation_manager
   *   The string translation service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, TranslationManager $translation_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->stringTranslation = $translation_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function entityConfigForm(array $form, FormStateInterface $form_state, AdEntityInterface $ad_entity) {
    return ['#markup' => $this->stringTranslation->translate("This plugin doesn't offer any configurable settings.")];
  }

  /**
   * {@inheritdoc}
   */
  public function entityConfigValidate(array &$form, FormStateInterface $form_state, AdEntityInterface $ad_entity) {}

  /**
   * {@inheritdoc}
   */
  public function entityConfigSubmit(array &$form, FormStateInterface $form_state, AdEntityInterface $ad_entity) {}

}
