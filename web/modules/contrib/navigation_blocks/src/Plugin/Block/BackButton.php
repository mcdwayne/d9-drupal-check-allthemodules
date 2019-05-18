<?php

namespace Drupal\navigation_blocks\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\Element\EntityAutocomplete;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Drupal\link\LinkItemInterface;
use Drupal\link\Plugin\Field\FieldWidget\LinkWidget;
use Drupal\navigation_blocks\BackButtonManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'GenericBackButton' block.
 *
 * @Block(
 *  id = "back_button",
 *  admin_label = @Translation("Back Button"),
 * )
 */
class BackButton extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Back Button Manager.
   *
   * @var \Drupal\navigation_blocks\BackButtonManager
   */
  protected $backButtonManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new back button.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $pluginId
   *   The plugin_id for the plugin instance.
   * @param mixed $pluginDefinition
   *   The plugin implementation definition.
   * @param \Drupal\navigation_blocks\BackButtonManagerInterface $backButtonManager
   *   The back button manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(array $configuration, $pluginId, $pluginDefinition, BackButtonManagerInterface $backButtonManager, EntityTypeManagerInterface $entityTypeManager) {
    parent::__construct($configuration, $pluginId, $pluginDefinition);
    $this->backButtonManager = $backButtonManager;
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $pluginId, $pluginDefinition): self {
    return new static(
      $configuration,
      $pluginId,
      $pluginDefinition,
      $container->get('navigation_blocks.back_button_manager'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'preferred_paths' => '',
      'link' => [
        'url' => '',
        'text' => '',
      ],
      'use_javascript' => FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function blockForm($form, FormStateInterface $formState): array {
    $form['preferred_paths'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Preferred back paths'),
      '#description' => $this->t('Please provide the back paths you&#039;d like the back button to refer to. Separate paths by a return.'),
      '#default_value' => $this->configuration['preferred_paths'],
    ];

    $form['link'] = $this->getLinkFormElements();

    $form['use_javascript'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use Javascript'),
      '#default_value' => $this->configuration['use_javascript'],
      '#description' => $this->t('Using javascript:history.back(-1)'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $formState): void {
    parent::blockSubmit($form, $formState);

    $this->setConfigurationValue('preferred_paths', $formState->getValue('preferred_paths'));
    $this->setConfigurationValue('link', $formState->getValue('link'));
    $this->setConfigurationValue('use_javascript', $formState->getValue('use_javascript'));
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    return $this->backButtonManager->getPreferredLink($this->getPreferredBackPaths(), $this->useJavascript()) ?:
      $this->backButtonManager->getLink($this->getLinkUrl(), $this->getLinkText());
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts(): array {
    return Cache::mergeContexts(parent::getCacheContexts(), [
      'route',
      'headers:referer',
    ]);
  }

  /**
   * Get the preferred paths from the configuration and split them by newline.
   *
   * @return string
   *   The preferred paths
   */
  protected function getPreferredBackPaths(): string {
    // Convert path to lowercase. This allows comparison of the same path
    // with different case. Ex: /Page, /page, /PAGE.
    return \mb_strtolower($this->configuration['preferred_paths']) ?: '';
  }

  /**
   * Get the url for the link.
   *
   * @return \Drupal\Core\Url
   *   The Url.
   */
  protected function getLinkUrl(): Url {
    return Url::fromUri($this->configuration['link']['url']);
  }

  /**
   * Get the link text.
   *
   * @return string
   *   The text.
   */
  protected function getLinkText(): string {
    return $this->configuration['link']['text'];
  }

  /**
   * Gets the URI without the 'internal:' or 'entity:' scheme.
   *
   * The following two forms of URIs are transformed:
   * - 'entity:' URIs: to entity autocomplete ("label (entity id)") strings;
   * - 'internal:' URIs: the scheme is stripped.
   *
   * This method is the inverse of ::getUriAsDisplayableString().
   *
   * @param string $uri
   *   The URI to get the displayable string for.
   *
   * @return string
   *   The URI without scheme.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *
   * @see LinkWidget::getUriAsDisplayableString()
   */
  protected function getUriAsDisplayableString(string $uri): string {
    $scheme = \parse_url($uri, PHP_URL_SCHEME);

    // By default, the displayable string is the URI.
    $displayable_string = $uri;

    // A different displayable string may be chosen in case of the 'internal:'
    // or 'entity:' built-in schemes.
    if ($scheme === 'internal') {
      $uri_reference = \explode(':', $uri, 2)[1];

      // @todo '<front>' is valid input for BC reasons, may be removed by
      //   https://www.drupal.org/node/2421941
      $path = \parse_url($uri, PHP_URL_PATH);
      if ($path === '/') {
        $uri_reference = '<front>' . \substr($uri_reference, 1);
      }

      $displayable_string = $uri_reference;
    }
    elseif ($scheme === 'entity') {
      [$entity_type, $entity_id] = \explode('/', \substr($uri, 7), 2);
      // @todo Support entity types other than 'node'. Will be fixed in
      // @todo https://www.drupal.org/node/2423093.
      if ($entity_type === 'node' && $entity = $this->entityTypeManager->getStorage($entity_type)->load($entity_id)) {
        $displayable_string = EntityAutocomplete::getEntityLabels([$entity]);
      }
    }

    return $displayable_string;
  }

  /**
   * Defines the link element of the form array as a tree.
   *
   * @return array
   *   The form.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getLinkFormElements(): array {
    return [
      '#tree' => TRUE,
      'url' => $this->getUrlElement(),
      'text' => $this->getTextFieldElement(),
    ];
  }

  /**
   * Defines the url element of the form array.
   *
   * @return array
   *   The link element of the form.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getUrlElement(): array {
    return [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('URL'),
      '#description' => $this->t('Enter the URL you want to generate a back button for.'),
      '#default_value' => $this->getUriAsDisplayableString($this->configuration['link']['url'] ?? ''),
      '#element_validate' => [[LinkWidget::class, 'validateUriElement']],
      '#required' => TRUE,
      '#link_type' => LinkItemInterface::LINK_INTERNAL,
      '#target_type' => 'node',
      '#attributes' => [
        'data-autocomplete-first-character-blacklist' => '/#?',
      ],
      '#process_default_value' => FALSE,
    ];
  }

  /**
   * Defines the url element of the form array.
   *
   * @return array
   *   The linik element of the form.
   */
  protected function getTextFieldElement(): array {
    return [
      '#type' => 'textfield',
      '#title' => $this->t('Text'),
      '#description' => $this->t('The text of the back button.'),
      '#default_value' => $this->configuration['link']['text'] ?? '',
      '#maxlength' => 255,
      '#size' => 64,
    ];
  }

  /**
   * Use javascript to render back link.
   *
   * @return bool
   *   Whether to use javascript.
   */
  protected function useJavascript(): bool {
    return $this->configuration['use_javascript'];
  }

}
