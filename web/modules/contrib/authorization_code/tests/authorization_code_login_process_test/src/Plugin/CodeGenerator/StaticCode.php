<?php

namespace Drupal\authorization_code_login_process_test\Plugin\CodeGenerator;

use Drupal\authorization_code\CodeGeneratorInterface;
use Drupal\authorization_code\Plugin\AuthorizationCodePluginBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Code Generator plugin implementation that uses a static code.
 *
 * @CodeGenerator(
 *   id = "static_code",
 *   title = @Translation("Static code")
 * )
 */
class StaticCode extends AuthorizationCodePluginBase implements CodeGeneratorInterface {

  /**
   * The code.
   *
   * @var string
   */
  private $code;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, string $plugin_id, array $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    assert(isset($configuration['settings']['code']));
    $this->code = $configuration['settings']['code'];
  }

  /**
   * {@inheritdoc}
   */
  public function generate(): string {
    return $this->code;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form['code'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Code'),
      '#required' => TRUE,
    ];
    return $form;
  }

}
