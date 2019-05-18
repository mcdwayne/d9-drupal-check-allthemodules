<?php

namespace Drupal\freelinking\Plugin\freelinking;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Drupal\freelinking\Plugin\FreelinkingPluginBase;
use Drupal\freelinking\Plugin\freelinking\GoogleSearch;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Freelinking search plugin.
 *
 * @Freelinking(
 *   id = "search",
 *   title = @Translation("Search"),
 *   weight = 0,
 *   hidden = false,
 *   settings = {
 *     "failover" = "error"
 *   }
 * )
 */
class Search extends FreelinkingPluginBase implements ContainerFactoryPluginInterface {

  /**
   * Module handler interface.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Initialize method.
   *
   * @param array $configuration
   *   The configuration array.
   * @param string $plugin_id
   *   The plugin ID.
   * @param array $plugin_definition
   *   The plugin definition array.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler interface.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ModuleHandlerInterface $moduleHandler) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->moduleHandler = $moduleHandler;
  }

  /**
   * {@inheritdoc}
   */
  public function getIndicator() {
    return '/^search$/';
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return parent::defaultConfiguration() +
      ['settings' => ['failover' => 'error']];
  }

  /**
   * {@inheritdoc}
   */
  public function getTip() {
    return $this->t('Search this site for content.');
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element['failover'] = [
      '#type' => 'select',
      '#title' => $this->t('Failover Option'),
      '#description' => $this->t('If Search is disabled or inaccessible do something else.'),
      '#options' => [
        'error' => $this->t('Error Message'),
        'google' => $this->t('Google'),
      ],
    ];
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function buildLink(array $target) {
    $failover = $this->getConfiguration()['settings']['failover'];
    $searchString = str_replace(' ', '+', $target['dest']);

    if ($this->moduleHandler->moduleExists('search')) {
      return [
        '#type' => 'link',
        '#title' => $target['text'] ? $target['text'] : $this->t('Search: “@text”', ['@text' => $target['dest']]),
        '#url' => Url::fromUserInput(
          '/search/node',
          ['query' => ['keys' => $searchString], 'language' => $target['language']]
        ),
        '#attributes' => [
          'title' => $this->getTip(),
        ],
      ];
    }
    elseif ($failover === 'google') {
      return GoogleSearch::createRenderArray($searchString, $target['text'], $target['language'], $this->getTip());
    }

    return [
      '#theme' => 'freelink_error',
      '#plugin' => 'search',
      '#message' => $this->t('Search unavailable'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('module_handler')
    );
  }

}
