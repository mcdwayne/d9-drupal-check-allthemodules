<?php

namespace Drupal\social_share\Plugin\Block;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\social_share\SocialShareLinkConfigurationTrait;
use Drupal\social_share\SocialShareLinkManagerTrait;
use Drupal\typed_data\PlaceholderResolverTrait;

/**
 * Provides a 'Social share links' block.
 *
 * @Block(
 *   id = "social_share_links",
 *   admin_label = @Translation("Social share links"),
 *   category = @Translation("Social"),
 *   context = {
 *     "entity" = @ContextDefinition("entity:node",
 *       label = @Translation("Content entity"),
 *       description = @Translation("An optional content entity which may be used as source for replacement tokens, accessible under the name 'entity'."),
 *       required = false,
 *     )
 *   }
 * )
 *
 * // @todo: Define the entity as type 'entity' and have proper context mapping.
 */
class SocialShareBlock extends BlockBase {

  use PlaceholderResolverTrait;
  use SocialShareLinkManagerTrait;
  use SocialShareLinkConfigurationTrait;

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return parent::defaultConfiguration() + [
      'allowed_plugins' => [],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['context_mapping']['entity']['#description'] = $this->t("An optional content entity which may be used as source for replacement tokens, accessible under the name 'entity'.");


    // Initialize empty defaults. We cannot use defaultConfiguration() for that
    // as it merges in array values all the time.
    if (empty($this->configuration['allowed_plugins'])) {
      $this->configuration['allowed_plugins'] = array_keys($this->getSocialShareLinkManager()->getDefinitions());
    }

    $form['allowed_plugins'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Allowed plugins'),
      '#description' => $this->t('Allows restricting and ordering the allowed plugins. List one plugin ID per line.'),
      '#default_value' => implode("\r\n", $this->configuration['allowed_plugins']),
      '#required' => TRUE,
    ];

    $form['reload_button'] = [
      '#type' => 'button',
      '#value' => $this->t('Reload plugin configuration form'),
      '#limit_validation_errors' => [
        ['allowed_plugins'],
      ],
      '#ajax' => array(
        'callback' => [get_called_class(), 'reloadContextConfigurationForm'],
        'wrapper' => 'context-configuration-form',
        'progress' => array(
          'type' => 'throbber',
          'message' => "Reloading",
        ),
      ),
    ];

    $form['context_config']['#process'] = [[$this, 'updateContextConfigurationForm']];
    $form['context_config']['#type'] = 'container';
    $form['context_config']['#attributes'] = ['id' => 'context-configuration-form'];
    return $form;
  }

  /**
   * FAPI #process callback for updating the context configuration form.
   */
  public function updateContextConfigurationForm($form_element, FormStateInterface $form_state, &$form) {
    if ($values = $form_state->getValues()) {
      // This callback breaks out of the subform, so be sure to respect the
      // array parents.
      $parents = $form_element['#parents'];
      // Remove 'context_values' and append 'allowed_plugins' instead.
      array_pop($parents);
      $parents[] = 'allowed_plugins';
      $value = NestedArray::getValue($values, $parents);
      // Sometimes delimiters end up with \n instead of \r\n.
      $this->configuration['allowed_plugins'] = explode("\n", str_replace("\r\n", "\n", $value));

      list($used_context, $used_by_plugins) = $this->getSocialShareLinkManager()
        ->getMergedContextDefinitions($this->configuration['allowed_plugins']);
    }

    $form_element = $this->buildContextConfigurationForm($form_element, $form_state, $this->configuration, $used_context, $used_by_plugins);
    $form_element['context_values']['#type'] = 'fieldset';
    $form_element['context_values']['#title'] = $this->t('Social link plugin configuration');

    return $form_element;
  }

  /**
   * FAPI #ajax callback for updating the form.
   */
  public static function reloadContextConfigurationForm($form, FormStateInterface $form_state) {
    // Note that the ajax callback breaks out of the subform, so we have to
    // pre-prend the array-parents.
    $array_parents = $form_state->getTriggeringElement()['#array_parents'];
    // Remove the button from the parents and add 'context_values' instead.
    array_pop($array_parents);
    $array_parents[] = 'context_config';
    return NestedArray::getValue($form , $array_parents);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    if (!$form_state->getErrors()) {
      $value = $form_state->getValue('context_config');
      $this->configuration['context_values'] = $value['context_values'];
      // Sometimes delimiters end up with \n instead of \r\n.
      $this->configuration['allowed_plugins'] = explode("\n", str_replace("\r\n", "\n", $form_state->getValue('allowed_plugins')));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $elements = [];
    $bubbleable_metadata = new BubbleableMetadata();
    $entity = $this->getContextValue('entity');
    $template_suffix = '__block__' . $this->getMachineNameSuggestion();

    foreach ($this->configuration['allowed_plugins'] as $plugin_id) {
      try {
        $share_link = $this->prepareLinkBuild($this->configuration, $plugin_id, $bubbleable_metadata, $entity);
        $elements[] = $share_link->build($template_suffix, [
          'entity' => $entity,
          'block' => $this,
        ]);
      }
      catch (PluginException $e) {
        // Silently ignore possibly outdated data values of not existing share
        // links.
      }
    }
    $bubbleable_metadata->applyTo($elements);
    return $elements;
  }

}
