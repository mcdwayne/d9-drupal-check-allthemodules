<?php

namespace Drupal\api_tokens\Plugin\Filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Drupal\api_tokens\ApiTokenManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the API tokens filter.
 *
 * @Filter(
 *   id = "api_tokens",
 *   title = @Translation("API Tokens"),
 *   description = @Translation("Render API tokens."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE,
 *   settings = {
 *     "unregistered_tokens_behavior" = "cutout"
 *   }
 * )
 */
class ApiTokensFilter extends FilterBase implements ContainerFactoryPluginInterface {

 /**
  * The API token pattern.
  */
  const PATTERN = '@\[api:([0-9a-z_-]+)(\[.*?\])?/]@';

  /**
   * The API token manager.
   *
   * @var \Drupal\api_tokens\ApiTokenManagerInterface
   */
  protected $apiTokenManager;

  /**
   * Constructs an ApiTokens object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\api_tokens\ApiTokenManagerInterface $api_token_manager
   *   The API token manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ApiTokenManagerInterface $api_token_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->apiTokenManager = $api_token_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.api_token')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form['unregistered_tokens_behavior'] = [
      '#type' => 'radios',
      '#title' => $this->t('Unregistered API tokens process behavior'),
      '#default_value' => $this->settings['unregistered_tokens_behavior'],
      '#options' => [
        'cutout' => $this->t('Cut out'),
        'ignore' => $this->t('Ignore'),
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    $result = new FilterProcessResult($text);
    if (preg_match_all(self::PATTERN, $text, $matches)) {
      list($tokens, $ids, $args) = $matches;
      $replacements = $tokens;
      foreach ($ids as $index => $id) {
        if ($this->apiTokenManager->hasDefinition($id)) {
          $placeholder = $this->apiTokenManager->createInstance($id, [
            'params' => $args[$index],
          ])->placeholder();
          $result->addAttachments([
            'placeholders' => [$tokens[$index] => $placeholder],
          ]);
        }
        elseif ('cutout' == $this->settings['unregistered_tokens_behavior']) {
          $replacements[$index] = '';
        }
      }
      $text = str_replace($tokens, $replacements, $text);
      $result->setProcessedText($text);
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function tips($long = FALSE) {
    return $this->t('Replace API tokens.');
  }

}
