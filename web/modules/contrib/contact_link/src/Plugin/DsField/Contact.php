<?php

namespace Drupal\contact_link\Plugin\DsField;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Url;
use Drupal\ds\Plugin\DsField\Link;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin that renders a contact form link.
 *
 * @DsField(
 *   id = "contact_link",
 *   title = @Translation("Personal Contact Form Link"),
 *   entity_type = "user",
 *   provider = "user"
 * )
 */
class Contact extends Link implements ContainerFactoryPluginInterface {

  /**
   * Module Handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * {@inheritdoc}
   */
  public function __construct($configuration,
                              $plugin_id,
                              $plugin_definition,
                              ModuleHandlerInterface $module_handler,
                              TranslationInterface $string_translation) {
    $this->moduleHandler = $module_handler;
    $this->stringTranslation = $string_translation;
    return parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('module_handler'),
      $container->get('string_translation')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $url = Url::fromRoute('entity.user.contact_form', [
      'user' => $this->entity()->id(),
    ]);

    if (!$url->access()) {
      return [];
    }

    $output = [
      '#type' => 'link',
      '#title' => $this->configuration['link text'],
      '#url' => $url,
    ];

    // Wrapper and class.
    if (!empty($this->configuration['wrapper'])) {
      return [
        '#type' => 'html_tag',
        '#tag' => $this->configuration['wrapper'],
        '#attributes' => [
          'class' => explode(' ', $this->configuration['class']),
        ],
        '#value' => $output,
      ];
    }
    else {
      return $output;
    }

  }

  /**
   * {@inheritdoc}
   */
  public function isAllowed() {
    if ($this->moduleHandler->moduleExists('contact')) {
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $configuration = parent::defaultConfiguration();
    $configuration['link text'] = $this->t('Contact');

    return $configuration;
  }

}
