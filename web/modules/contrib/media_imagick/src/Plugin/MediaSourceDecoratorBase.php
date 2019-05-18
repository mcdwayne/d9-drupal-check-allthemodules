<?php

namespace Drupal\media_imagick\Plugin;

use Drupal\Core\Entity\Display\EntityFormDisplayInterface;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\media\MediaInterface;
use Drupal\media\MediaSourceInterface;
use Drupal\media\MediaTypeInterface;

/**
 * Abstract base class to decorate MediaSource plugins.
 *
 * This voluntarily decorates all MediaSourceInterface methods, but not any
 * methods from PluginBase, because the decorator is a different plugin.
 */
abstract class MediaSourceDecoratorBase extends PluginBase implements MediaSourceInterface {

  /**
   * The decorated object.
   *
   * @var \Drupal\media\MediaSourceInterface
   */
  protected $decorated;

  /**
   * Constructs a MediaSourceInterface decorator.
   *
   * @param array $configuration
   *   The configuration.
   * @param string $id
   *   The plugin id.
   * @param mixed $definition
   *   The definition.
   *
   * @see \Drupal\plugindecorator\PluginManagerDecorator::decorate
   */
  public function __construct(array $configuration, $id, $definition) {
    parent::__construct($configuration, $id, $definition);
    $this->decorated = $configuration['decorated'];
  }

  /**
   * {@inheritdoc}
   */
  public function getMetadataAttributes() {
    return $this->decorated->getMetadataAttributes();
  }

  /**
   * {@inheritdoc}
   */
  public function getMetadata(MediaInterface $media, $attribute_name) {
    return $this->decorated->getMetadata($media, $attribute_name);
  }

  /**
   * {@inheritdoc}
   */
  public function createSourceField(MediaTypeInterface $type) {
    return $this->decorated->createSourceField($type);
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $this->decorated->setConfiguration($configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return $this->decorated->getConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return $this->decorated->defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return $this->decorated->calculateDependencies();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    return $this->decorated->buildConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->decorated->validateConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->decorated->submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function getSourceFieldDefinition(MediaTypeInterface $type) {
    return $this->decorated->getSourceFieldDefinition($type);
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginId() {
    return $this->decorated->getPluginId();
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginDefinition() {
    return $this->decorated->getPluginDefinition();
  }

  /**
   * @inheritDoc
   */
  public function prepareViewDisplay(MediaTypeInterface $type, EntityViewDisplayInterface $display) {
    $this->decorated->prepareViewDisplay($type, $display);
  }

  /**
   * @inheritDoc
   */
  public function prepareFormDisplay(MediaTypeInterface $type, EntityFormDisplayInterface $display) {
    $this->decorated->prepareFormDisplay($type, $display);
  }

  /**
   * @inheritDoc
   */
  public function getSourceFieldValue(MediaInterface $media) {
    return $this->decorated->getSourceFieldValue($media);
  }

}
