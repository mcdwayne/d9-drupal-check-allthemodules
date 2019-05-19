<?php

/**
 * @file
 * Contains \Drupal\translation_condition\Plugin\Condition\Translation.
 */

namespace Drupal\translation_condition\Plugin\Condition;

use Drupal\Core\Condition\ConditionPluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\Context\ContextDefinition;

/**
 * Provides a 'Translation' condition to
 *
 * @Condition(
 *   id = "translation",
 *   label = @Translation("Translation"),
 *   context = {
 *     "node" = @ContextDefinition("entity:node", required = TRUE , label = @Translation("node"))
 *   }
 * )
 *
 */
class Translation extends ConditionPluginBase {
  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {

    $form['translation'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Show only when a translation exists for the viewed language'),
      '#default_value' => $this->configuration['translation'],
      '#description' => $this->t('Negate this condition to show the block when a translation does not exist for the viewed language.'),
    );

    return parent::buildConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['translation'] = $form_state->getValue('translation');
    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array('translation' => '') + parent::defaultConfiguration();
  }

  /**
   * Evaluates the condition and returns TRUE or FALSE accordingly.
   *
   * @return bool
   *   TRUE if the condition has been met, FALSE otherwise.
   */
  public function evaluate() {
    // If there has been no choice and negated checkbox has not been selected.
    if (empty($this->configuration['translation']) && !$this->isNegated()) {
      return TRUE;
    }

    /** @var \Drupal\node\Entity\Node $node */
    $node = $this->getContextValue('node');
    // Get the current language.
    $current_lang = \Drupal::languageManager()->getCurrentLanguage()->getId();

    // Check if this node has a translation in this language.
    $node_langs = $node->getTranslationLanguages();
    if (!empty($node_langs[$current_lang])) {
      return TRUE;
    }

    // No condition was met.
    return FALSE;
  }

  /**
   * Provides a human readable summary of the condition's configuration.
   */
  public function summary() {
    if ($translation = $this->configuration['translate']) {
      if (!empty($this->configuration['negate'])) {
        return $this->t('The node does not have a translation in the currently viewed language.');
      }
      else {
        return $this->t('The node does have a translation in the currently viewed language.');
      }
    }
  }

}
