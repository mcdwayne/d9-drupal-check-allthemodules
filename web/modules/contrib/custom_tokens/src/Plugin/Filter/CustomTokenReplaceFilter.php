<?php

namespace Drupal\custom_tokens\Plugin\Filter;

use Drupal\custom_tokens\Entity\TokenEntity;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;

/**
 * Filter that replaces custom token values in the WYSWIYG.
 *
 * @Filter(
 *   title = @Translation("Custom Token Replace Filter"),
 *   id = "custom_tokens",
 *   description = @Translation("Replace custom tokens."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_MARKUP_LANGUAGE
 * )
 */
class CustomTokenReplaceFilter extends FilterBase {

  /**
   * An array of all token entities.
   *
   * @var \Drupal\custom_tokens\Entity\TokenEntity[]
   */
  protected $tokens;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->tokens = TokenEntity::loadMultiple();
  }

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    $replacements = [];
    foreach ($this->tokens as $token) {
      $replacements[sprintf('[%s]', $token->getTokenName())] = $token->getTokenValue();
    }
    $response = new FilterProcessResult($text);
    $response->setProcessedText(strtr($text, $replacements));
    return $response->addCacheTags(['token_list']);
  }

}
