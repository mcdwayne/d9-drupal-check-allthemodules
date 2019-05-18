<?php

namespace Drupal\paragraphs_collection_bootstrap\Plugin\paragraphs\Behavior;

use Drupal\Core\Asset\LibraryDiscoveryInterface;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityFieldManager;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Url;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\paragraphs\ParagraphInterface;
use Drupal\paragraphs\ParagraphsBehaviorBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a way to use Bootstrap tooltip.
 *
 * @ParagraphsBehavior(
 *   id = "pcb_tooltip",
 *   label = @Translation("Bootstrap tooltip"),
 *   description = @Translation("Displays text in a bootstrap tooltip."),
 *   weight = 100
 * )
 */
class ParagraphsBootstrapTooltipPlugin extends ParagraphsBehaviorBase {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Library discovery service.
   *
   * @var \Drupal\Core\Asset\LibraryDiscoveryInterface
   */
  protected $libraryDiscovery;

  /**
   * ParagraphsBootstrapTooltipPlugin constructor.
   *
   * @param array $configuration
   *   The configuration array.
   * @param string $plugin_id
   *   This plugin id.
   * @param mixed $plugin_definition
   *   Plugin definition.
   * @param \Drupal\Core\Entity\EntityFieldManager $entity_field_manager
   *   Entity field manager service.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user account.
   * @param \Drupal\Core\Asset\LibraryDiscoveryInterface $library_discovery
   *   Library discovery service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityFieldManager $entity_field_manager, AccountProxyInterface $current_user, LibraryDiscoveryInterface $library_discovery) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_field_manager);

    $this->currentUser = $current_user;
    $this->libraryDiscovery = $library_discovery;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_field.manager'),
      $container->get('current_user'),
      $container->get('library.discovery')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'content' => ['value' => '', 'format' => ''],
      'animation' => TRUE,
      'container' => '',
      'delay' => 0,
      'placement' => 'right',
      'trigger' => ['hover'],
      'offset' => '0 0',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildBehaviorForm(ParagraphInterface $paragraph, array &$form, FormStateInterface $form_state) {
    $allowed_formats = array_map(function ($format) {
      return $format->label();
    }, filter_formats($this->currentUser));

    $form['content'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Content'),
      '#description' => $this->t('Content to be used for the tooltip.'),
      '#default_value' => $paragraph->getBehaviorSetting($this->getPluginId(), 'content')['value'],
      '#format' => $paragraph->getBehaviorSetting($this->getPluginId(), 'content')['format'] ?: filter_default_format(),
      '#allowed_formats' => array_keys($allowed_formats),
      '#editor' => TRUE,
    ];

    $form['animation'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Animation'),
      '#description' => $this->t('Apples a CSS fade transition to the tooltip.'),
      '#default_value' => $paragraph->getBehaviorSetting($this->getPluginId(), 'animation', $this->configuration['animation']),
    ];

    $form['container'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Container'),
      '#description' => $this->t("Appends the tooltip to a specific element. Example: container: 'body'. This option is particularly useful in that it allows you to position the tooltip in the flow of the document near the triggering element - which will prevent the tooltip from floating away from the triggering element during a window resize."),
      '#default_value' => $paragraph->getBehaviorSetting($this->getPluginId(), 'container', $this->configuration['container']),
      '#size' => 32,
    ];

    $form['delay'] = [
      '#type' => 'number',
      '#title' => $this->t('Delay'),
      '#description' => $this->t('Delay showing and hiding the popover (ms) - does not apply to manual trigger type.'),
      '#default_value' => $paragraph->getBehaviorSetting($this->getPluginId(), 'delay', $this->configuration['delay']),
      '#min' => 0,
    ];

    $form['placement'] = [
      '#type' => 'select',
      '#title' => $this->t('Placement'),
      '#description' => $this->t('The placement of the tooltip.'),
      '#options' => [
        'top' => $this->t('Top'),
        'left' => $this->t('Left'),
        'bottom' => $this->t('Bottom'),
        'right' => $this->t('Right'),
      ],
      '#default_value' => $paragraph->getBehaviorSetting($this->getPluginId(), 'placement', $this->configuration['placement']),
    ];

    $form['trigger'] = [
      '#type' => 'select',
      '#multiple' => TRUE,
      '#title' => $this->t('Focus trigger'),
      '#description' => $this->t('Choose what trigger to use for the tooltip to appear. You may choose multiple triggers, "manual" cannot be combined with any other trigger.'),
      '#options' => [
        'click' => $this->t('Click'),
        'hover' => $this->t('Hover'),
        'focus' => $this->t('Focus'),
        'manual' => $this->t('Manual'),
      ],
      '#default_value' => $paragraph->getBehaviorSetting($this->getPluginId(), 'trigger', $this->configuration['trigger']),
    ];

    $form['offset'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Offset'),
      '#description' => $this->t("Offset of the tooltip relative to its target. For more information refer to Tether's @offset_docs.", [
        '@offset_docs' => Link::fromTextAndUrl('offset docs', Url::fromUri('http://tether.io/#offset'))
          ->toString(),
      ]),
      '#default_value' => $paragraph->getBehaviorSetting($this->getPluginId(), 'offset', $this->configuration['offset']),
      '#size' => 32,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateBehaviorForm(ParagraphInterface $paragraph, array &$form, FormStateInterface $form_state) {
    if (in_array('manual', $form_state->getValue('trigger')) && count($form_state->getValue('trigger')) > 1) {
      $form_state->setError($form['trigger'], '"Manual" cannot be combined with any other trigger in behavior settings.');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function view(array &$build, Paragraph $paragraph, EntityViewDisplayInterface $display, $view_mode) {
    $build['#attributes'] = [
      'data-toggle' => 'tooltip',
      'data-animation' => $paragraph->getBehaviorSetting($this->getPluginId(), 'animation') ? 'true' : 'false',
      'data-container' => $paragraph->getBehaviorSetting($this->getPluginId(), 'container') ?: 'false',
      'data-delay' => $paragraph->getBehaviorSetting($this->getPluginId(), 'delay'),
      'data-html' => $paragraph->getBehaviorSetting($this->getPluginId(), 'content')['format'] !== 'plain_text' ? 'true' : 'false',
      'data-placement' => $paragraph->getBehaviorSetting($this->getPluginId(), 'placement'),
      'title' => $paragraph->getBehaviorSetting($this->getPluginId(), 'content')['value'] ?: '',
      'data-trigger' => implode(' ', $paragraph->getBehaviorSetting($this->getPluginId(), 'trigger')),
      'data-offset' => $paragraph->getBehaviorSetting($this->getPluginId(), 'offset'),
    ];

    $build['#attached']['library'][] = 'bs_lib/tooltip';
    $build['#attached']['library'][] = 'paragraphs_collection_bootstrap/tooltip';
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary(Paragraph $paragraph) {
    return [
      $this->t('Content: @content, Animation: @animation, Container: @container, Delay: @delay, Placement: @placement, Trigger: @trigger, Offset: @offset', [
        '@content' => $paragraph->getBehaviorSetting($this->getPluginId(), 'content'),
        '@animation' => $paragraph->getBehaviorSetting($this->getPluginId(), 'animation') ? 'enabled' : 'disabled',
        '@container' => $paragraph->getBehaviorSetting($this->getPluginId(), 'container'),
        '@delay' => $paragraph->getBehaviorSetting($this->getPluginId(), 'delay'),
        '@placement' => $paragraph->getBehaviorSetting($this->getPluginId(), 'placement'),
        '@trigger' => current($paragraph->getBehaviorSetting($this->getPluginId(), 'trigger')),
        '@offset' => $paragraph->getBehaviorSetting($this->getPluginId(), 'offset'),
      ]),
    ];
  }

}
