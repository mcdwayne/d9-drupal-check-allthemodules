<?php

namespace Drupal\context_metadata\Plugin\ContextReaction;

use Drupal\context\ContextReactionPluginBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a content reaction that adds a Metadata.
 *
 * @ContextReaction(
 *   id = "context_metadata",
 *   label = @Translation("Context Metadata")
 * )
 */
class ContextMetadata extends ContextReactionPluginBase {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    // TODO DI metatag.manager service.
    $metatagManager = \Drupal::service('metatag.manager');

    // Get the sorted tags.
    $sortedTags = $metatagManager->sortedTags();

    $values = [];

    // Check previous values.
    foreach ($sortedTags as $tagId => $tagDefinition) {
      if (isset($this->getConfiguration()[$tagId])) {
        $values[$tagId] = $this->getConfiguration()[$tagId];
      }
    }

    // Get the base metatag form.
    $form = $metatagManager->form($values, []);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    // TODO DI metatag.manager service.
    $metatagManager = \Drupal::service('metatag.manager');
    $sortedTags = $metatagManager->sortedTags();
    $conf = [];

    foreach ($sortedTags as $tagId => $tagDefinition) {
      if ($form_state->hasValue([$tagDefinition['group'], $tagId])) {
        $conf[$tagId] = $form_state->getValue([$tagDefinition['group'], $tagId]);
      }
    }

    $this->setConfiguration($conf);
  }

  /**
   * {@inheritdoc}
   */
  public function summary() {
    return $this->getConfiguration()['context_metadata'];
  }

  /**
   * {@inheritdoc}
   */
  public function execute(array &$vars = []) {
    $config = $this->getConfiguration();
    return $config;
  }

}
