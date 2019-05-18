<?php

namespace Drupal\authorization_code\Plugin\CodeGenerator;

use Drupal\authorization_code\CodeGeneratorInterface;
use Drupal\authorization_code\Plugin\AuthorizationCodePluginBase;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;

/**
 * Generates codes using mt_rand.
 *
 * @CodeGenerator(
 *   id = "simple_rng",
 *   title = @Translation("Simple RNG (random number generator)")
 * )
 */
class SimpleRng extends AuthorizationCodePluginBase implements CodeGeneratorInterface {

  /**
   * The maximum limit for the generated code.
   *
   * @var int
   */
  private $maxGeneratedNumber;

  /**
   * SimpleRng constructor.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin id.
   * @param array $plugin_definition
   *   The plugin definition.
   */
  public function __construct(array $configuration, string $plugin_id, array $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->maxGeneratedNumber = pow(10, $this->codeLength()) - 1;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return NestedArray::mergeDeep(parent::defaultConfiguration(), [
      'settings' => [
        'code_length' => CodeGeneratorInterface::DEFAULT_CODE_LENGTH,
      ],
    ]);
  }

  /**
   * The code length.
   *
   * @return int
   *   The code length.
   */
  private function codeLength(): int {
    return $this->configuration['settings']['code_length'];
  }

  /**
   * {@inheritdoc}
   */
  public function generate(): string {
    return str_pad(mt_rand(0, $this->maxGeneratedNumber), $this->codeLength(), '0', STR_PAD_LEFT);
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form['code_length'] = [
      '#type' => 'number',
      '#title' => $this->t('Code length'),
      '#default_value' => $this->codeLength(),
      '#min' => '1',
      '#description' => $this->t('The number of integers in the generated code'),
    ];

    return $form;
  }

}
