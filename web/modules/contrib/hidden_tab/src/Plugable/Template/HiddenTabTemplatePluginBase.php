<?php

namespace Drupal\hidden_tab\Plugable\Template;

use Drupal\hidden_tab\Plugable\HiddenTabPluginBase;

/**
 * Base class for hidden_tab_template plugins.
 */
abstract class HiddenTabTemplatePluginBase extends HiddenTabPluginBase implements HiddenTabTemplateInterface {

  /**
   * See regions().
   *
   * @var array
   *
   * @see \Drupal\hidden_tab\Plugable\Template\HiddenTabTemplateInterface::regions()
   */
  protected $regions;

  /**
   * See imageUri().
   *
   * @see \Drupal\hidden_tab\Plugable\Template\HiddenTabTemplateInterface::imageUri()
   */
  protected $imageUri;

  /**
   * See templateFile().
   *
   * @var string
   *
   * @see \Drupal\hidden_tab\Plugable\Template\HiddenTabTemplateInterface::templateFile()
   */
  protected $templateFile;

  /**
   * See templateFilePath().
   *
   * @var string
   *
   * @see \Drupal\hidden_tab\Plugable\Template\HiddenTabTemplateInterface::templateFilePath()
   */
  protected $templateFilePath;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, string $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->imageUri = drupal_get_path('module', 'hidden_tab') . '/asset/image/preview.png';
    $this->templateFilePath = drupal_get_path('module', 'hidden_tab') . '/templates';
  }

  /**
   * {@inheritdoc}
   */
  public function regions(): array {
    return $this->regions;
  }

  /**
   * {@inheritdoc}
   */
  public function imageUri(): ?string {
    return $this->imageUri;
  }

  /**
   * {@inheritdoc}
   */
  public function templateFile(): ?string {
    return $this->templateFile;
  }

  /**
   * {@inheritdoc}
   */
  public function templateFilePath(): ?string {
    return $this->templateFilePath;
  }

  /**
   * {@inheritdoc}
   */
  public function attachLibrary(): array {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function themeVariables(): array {
    return [
      'current_user' => \Drupal::currentUser(),
      'regions' => [],
    ];
  }

}
