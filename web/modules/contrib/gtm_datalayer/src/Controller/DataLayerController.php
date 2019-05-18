<?php

namespace Drupal\gtm_datalayer\Controller;

use Drupal\Component\Plugin\Exception\ContextException;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Condition\ConditionAccessResolverTrait;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Plugin\Context\ContextHandlerInterface;
use Drupal\Core\Plugin\Context\ContextRepositoryInterface;
use Drupal\Core\Plugin\ContextAwarePluginInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\gtm_datalayer\Entity\DataLayerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a controller to manage GTM dataLayer.
 */
class DataLayerController extends ControllerBase implements DataLayerControllerInterface {

  use ConditionAccessResolverTrait;

  /**
   * The config object for the gtm_datalayer settings.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $configuration;

  /**
   * The plugin context handler.
   *
   * @var \Drupal\Core\Plugin\Context\ContextHandlerInterface
   */
  protected $contextHandler;

  /**
   * The context manager service.
   *
   * @var \Drupal\Core\Plugin\Context\ContextRepositoryInterface
   */
  protected $contextRepository;

  /**
   * The debug messages.
   *
   * @var array
   */
  protected $debugMessage = [];

  /**
   * Turns a render array into a HTML string.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The entity storage class.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $storage;

  /**
   * Creates an DataLayerController object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory service.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage class.
   * @param \Drupal\Core\Plugin\Context\ContextHandlerInterface $context_handler
   *   The ContextHandler for applying contexts to conditions properly.
   * @param \Drupal\Core\Plugin\Context\ContextRepositoryInterface $context_repository
   *   The lazy context repository service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   Turns a render array into a HTML string.
   */
  public function __construct(ConfigFactoryInterface $configFactory, EntityStorageInterface $storage, ContextHandlerInterface $context_handler, ContextRepositoryInterface $context_repository, RendererInterface $renderer) {
    $this->configuration = $configFactory->get('gtm_datalayer.settings');
    $this->contextHandler = $context_handler;
    $this->contextRepository = $context_repository;
    $this->renderer = $renderer;
    $this->storage = $storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager')->getStorage('gtm_datalayer'),
      $container->get('context.handler'),
      $container->get('context.repository'),
      $container->get('renderer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function isConfigured() {
    return !empty($this->configuration->get('container_id'));
  }

  /**
   * {@inheritdoc}
   */
  public function isDebugEnabled() {
    return $this->configuration->get('debug');
  }

  /**
   * {@inheritdoc}
   */
  public function isEnabled() {
    return $this->configuration->get('status');
  }

  /**
   * {@inheritdoc}
   */
  public function buildGtmNoScript(array &$page_top) {
    // If is enabled and configured.
    if ($this->isEnabled() && $this->isConfigured()) {
      // Build Google Tag Manager (noscript) code.
      $page_top['gtm_datalayer_gtm_noscript'] = [
        '#type' => 'inline_template',
        '#template' => '<noscript><iframe src="https://www.googletagmanager.com/ns.html?id={{ container_id }}" height="0" width="0" style="display:none; visibility:hidden"></iframe></noscript>',
        '#context' => [
          'container_id' => $this->configuration->get('container_id'),
        ],
        '#weight' => -100,
      ];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildGtmScripts(array &$attachments) {
    // If is enabled and configured.
    if ($this->isEnabled() && $this->isConfigured()) {
      // Build Google Tag Manager dataLayer (script) code with the rendered
      // tags.
      $data_layer = $this->renderTags();
      $attachments['#attached']['html_head'][] = [
        [
          '#type' => 'html_tag',
          '#tag' => 'script',
          '#value' => "var dataLayer = [" . Json::encode($data_layer) . "];",
          '#attributes' => ['type' => 'text/javascript'],
          '#weight' => -100.1,
        ],
        'gtm_datalayer_script',
      ];

      // Build Google Tag Manager (script) code.
      $attachments['#attached']['html_head'][] = [
        [
          '#type' => 'html_tag',
          '#tag' => 'script',
          '#value' => "(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer','" . $this->configuration->get('container_id') . "');",
          '#attributes' => ['type' => 'text/javascript'],
          '#weight' => -100,
        ],
        'gtm_datalayer_gtm_script',
      ];

      if ($this->isDebugEnabled() && count($this->buildDebugMessage())) {
        $this->addDebugMessage('---');
        $this->addDebugMessage('Rendered dataLayer:');
        $this->addDebugMessage($this->t('<pre>@datalayer</pre>', ['@datalayer' => print_r($data_layer, TRUE)]));

        $debug_message = $this->buildDebugMessage();
        drupal_set_message($this->renderer->renderPlain($debug_message), 'warning', TRUE);
      }
    }
  }

  /**
   * Build debug message.
   *
   * @return array
   *   Debug message.
   */
  protected function buildDebugMessage() {
    $build['title'] = [
      '#type' => 'html_tag',
      '#tag' => 'h2',
      '#value' => $this->t('dataLayer debug!'),
    ];

    foreach ($this->getDebugMessages() as $description) {
      if ($description == '---') {
        $build[] = ['#markup' => '<hr />'];
      }
      else {
        $build[] = [
          '#markup' => $description . '<br />',
        ];
      }
    }

    return $build;
  }

  /**
   * Evaluates the given conditions.
   *
   * @param \Drupal\gtm_datalayer\Entity\DataLayerInterface $data_layer
   *   The dataLayer that contain the conditions to be evaluated.
   *
   * @return bool
   *   TRUE if access is granted or FALSE if access is denied.
   */
  protected function evaluateConditions(DataLayerInterface $data_layer) {
    $conditions = [];
    $missing_context = FALSE;
    foreach ($data_layer->getAccessConditions() as $condition_id => $condition) {
      if ($condition instanceof ContextAwarePluginInterface) {
        try {
          $contexts = $this->contextRepository->getRuntimeContexts(array_values($condition->getContextMapping()));
          $this->contextHandler->applyContextMapping($condition, $contexts);
        }
        catch (ContextException $e) {
          $missing_context = TRUE;
        }
      }
      $conditions[$condition_id] = $condition;
    }

    if (!$missing_context && $this->resolveConditions($conditions, $data_layer->getAccessLogic()) !== FALSE) {
      $this->addDebugMessage($this->t('The conditions has been evaluated to TRUE'));

      return TRUE;
    }

    $this->addDebugMessage($this->t('Any of the conditions has been evaluated to FALSE'));

    return FALSE;
  }

  /**
   * Adds a debug message to the array of messages.
   *
   * @param string $message
   *   The message to add.
   */
  protected function addDebugMessage(string $message) {
    array_push($this->debugMessage, $message);
  }

  /**
   * Returns the array of messages.
   *
   * @return array
   *   The array of messages.
   */
  protected function getDebugMessages() {
    return $this->debugMessage;
  }

  /**
   * Loads GTM dataLayer IDs sorted by the entity weight.
   *
   * @return array
   *   An array of GTM dataLayer IDs.
   */
  protected function getEntityIds() {
    return $this->getStorage()->getQuery()
      ->sort('weight')
      ->execute();
  }

  /**
   * Loads GTM dataLayers from storage.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   *   An array of GTM dataLayers indexed by their weights. Returns an empty
   * array if no matching entities are found.
   */
  protected function loadEntities() {
    $entity_ids = $this->getEntityIds();

    return $this->getStorage()->loadMultiple($entity_ids);
  }

  /**
   * Gets the GTM dataLayer entity storage.
   *
   * @return \Drupal\Core\Entity\EntityStorageInterface
   *   The storage used.
   */
  protected function getStorage() {
    return $this->storage;
  }

  /**
   * Evaluate configured dataLayer and render the tags.
   *
   * @return array
   *   The rendered dataLayer tags.
   */
  protected function renderTags() {
    $tags = [];

    /** @var \Drupal\gtm_datalayer\Entity\DataLayerInterface $datalayer */
    foreach ($this->loadEntities() as $datalayer_id => $datalayer) {
      $this->addDebugMessage($this->t('Evaluating dataLayer: @datalayer', ['@datalayer' => $datalayer->label()]));
      $this->addDebugMessage('---');

      if ($this->evaluateConditions($datalayer)) {
        $new_tags = $datalayer->getDataLayerProcessor()->render();
        if (count($new_tags)) {
          $tags = array_merge($tags, $new_tags);
        }
      }

      $this->addDebugMessage('---');
    }

    return $tags;
  }

}
