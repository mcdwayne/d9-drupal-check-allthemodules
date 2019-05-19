<?php

namespace Drupal\social_share;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\BubbleableMetadata;

/**
 * Trait for helping with social share link configuration.
 *
 * @internal
 */
trait SocialShareLinkConfigurationTrait {

  /**
   * Required method, usually provided by PlaceholderResolverTrait.
   */
  abstract function getPlaceholderResolver();

  /**
   * Required method, usually provided by SocialShareLinkManagerTrait.
   */
  abstract function getSocialShareLinkManager();

  /**
   * Required method, usually provided TypedDataTrait;
   */
  abstract function getTypedDataManager();

  /**
   * Prepares building the social link for the given plugin.
   *
   * @param array $configuration
   *   The array of configuration values, containing the context configuration.
   * @param $pluginId
   *   The ID of link to render.
   * @param \Drupal\Core\Render\BubbleableMetadata $bubbleable_metadata
   *   The bubbleable metadata used for collection render metadata.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   If given, an entity for which to add in placeholder tokens.
   *
   * @return \Drupal\social_share\SocialShareLinkInterface
   *   The social share link, ready for rendering.
   */
  protected function prepareLinkBuild(array $configuration, $pluginId, BubbleableMetadata $bubbleable_metadata, EntityInterface $entity = NULL) {
    $link_manager = $this->getSocialShareLinkManager();
    $share_link = $link_manager->createInstance($pluginId, []);

    // Set the context on the plugin.
    foreach ($share_link->getContextDefinitions() as $name => $definition) {
      // Process the context value.
      // @todo: Improve rules context API to make it better re-usable and
      // re-use it here.
      if (is_scalar($configuration['context_values'][$name])) {
        $value =& $configuration['context_values'][$name];
        $data = isset($entity) ? [
          $entity->getEntityTypeId() => $entity->getTypedData(),
        ] : [];
        $value = $this->getPlaceholderResolver()->replacePlaceholders($value, $data, $bubbleable_metadata, ['clear' => TRUE]);
      }
      $share_link->setContextValue($name, $configuration['context_values'][$name]);
    }
    return $share_link;
  }

  /**
   * Builds the configuration form for the used context.
   *
   * @param array $form
   *   The form to attach to.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param array $configuration
   *   The array of configuration values, containing the context configuration.
   * @param array $used_context
   *   The merged context definitions, as returned from
   *   \Drupal\social_share\SocialShareLinkManagerInterface::getMergedContextDefinitions().
   * @param $used_by_plugins
   *   The array mapping the context definitions names to plugin IDs as provided
   *   by \Drupal\social_share\SocialShareLinkManagerInterface::getMergedContextDefinitions().
   *
   * @return array
   *   The modified form array.
   */
  protected function buildContextConfigurationForm(array $form, FormStateInterface $form_state, array $configuration, array $used_context, array $used_by_plugins) {
    // @todo: Use context configuration traits for configuring this.
    $form['context_values']['#tree'] = TRUE;
    foreach ($used_context as $name => $context_definition) {
      $help = $this->t('Used by: %plugins', ['%plugins' => implode(', ', $used_by_plugins[$name])]);
      $form['context_values'][$name] = [
        // For now, special case textarea here.
        // @todo: Fix this properly by using widgets.
        '#type' => $name == 'mail_body' ? 'textarea' : 'textfield',
        '#title' => $context_definition->getLabel(),
        '#description' => $context_definition->getDescription() . ' ' . $help,
        '#default_value' => isset($configuration['context_values'][$name]) ? $configuration['context_values'][$name] : $context_definition->getDefaultValue(),
        '#required' => $context_definition->isRequired(),
        '#maxlength' => 1024,
      ];
    }
    return $form;
  }


}
