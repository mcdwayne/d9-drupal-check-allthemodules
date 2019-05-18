<?php

namespace Drupal\arb_token\Plugin\arb_token;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\BubbleableMetadata;

/**
 * Provides tokens for literal values.
 *
 * @ArbitraryToken(
 *   id = "literal",
 *   label = @Translation("Literal value"),
 * )
 */
class LiteralToken extends ArbitraryTokenBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return parent::defaultConfiguration() + [
      'value' => NULL,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $build['value'] = [
      '#type' => 'textfield',
      '#title' => t('Value'),
      '#required' => TRUE,
      '#default_value' => $this->configuration['value'],
    ];

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function tokenInfo() {
    return [
      'name' => t("Value"),
      'description' => t('Literal value "@value".', [
        '@value' => $this->configuration['value'],
      ]),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function tokens($tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
    $replacements = [];
    foreach ($tokens as $name => $original) {
      if ($name == $this->token->id()) {
        $replacements[$original] = $this->configuration['value'];
        $bubbleable_metadata->addCacheTags(['arb_token:' . $this->token->id()]);
      }
    }
    return $replacements;
  }

}
