<?php

namespace Drupal\entity_counter\Plugin\Filter;

use Drupal\Component\Utility\Html as HtmlUtility;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Url;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a filter to display entity counter values.
 *
 * @Filter(
 *   id = "entity_counter",
 *   title = @Translation("Entity counter values"),
 *   description = @Translation("Uses a <code>data-entity-counter</code> attribute on tags to display entity counter values."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE
 * )
 */
class EntityCounterFilter extends FilterBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * EntityCounterFilter constructor.
   *
   * @param array $configuration
   *   The plugin configuration, i.e. an array with configuration values keyed
   *   by configuration option name. The special key 'context' may be used to
   *   initialize the defined contexts by setting it to an array of context
   *   values keyed by context names.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, RendererInterface $renderer) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->entityTypeManager = $entity_type_manager;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('renderer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    $result = new FilterProcessResult($text);

    if (stristr($text, 'data-entity-counter') !== FALSE) {
      $dom = HtmlUtility::load($text);
      $xpath = new \DOMXPath($dom);
      $entity_counters = [];
      foreach ($xpath->query('//*[@data-entity-counter]') as $node) {
        $filter_value = $altered_html = NULL;

        // Read the data-entity-counter attribute's value, then delete it.
        $entity_counter = $node->getAttribute('data-entity-counter');
        $node->removeAttribute('data-entity-counter');

        // Read the data-entity-counter-percentage attribute's value, then
        // delete it.
        $entity_counter_percentage = $node->getAttribute('data-entity-counter-percentage');
        $node->removeAttribute('data-entity-counter-percentage');

        // Read the data-entity-counter-property attribute's value, then delete
        // it.
        $entity_counter_property = $node->getAttribute('data-entity-counter-property');
        $node->removeAttribute('data-entity-counter-property');

        // Read the data-entity-counter-ratio attribute's value, then delete it.
        $entity_counter_ratio = $node->getAttribute('data-entity-counter-ratio');
        $node->removeAttribute('data-entity-counter-ratio');

        // Read the data-entity-counter-decimals attribute's value, then delete
        // it.
        $entity_counter_decimals = $node->getAttribute('data-entity-counter-decimals');
        $node->removeAttribute('data-entity-counter-decimals');

        // Read the data-entity-counter-separator attribute's value, then delete
        // it.
        $entity_counter_separator = $node->getAttribute('data-entity-counter-separator');
        $node->removeAttribute('data-entity-counter-separator');

        // Read the data-entity-counter-type-decimal attribute's value, then
        // delete it.
        $entity_counter_type_decimal = $node->getAttribute('data-entity-counter-type-decimal');
        $node->removeAttribute('data-entity-counter-type-decimal');

        // Read the data-entity-counter-round attribute's value, then delete it.
        $entity_counter_round = $node->getAttribute('data-entity-counter-round');
        $node->removeAttribute('data-entity-counter-round');

        // Read the data-entity-counter-ajax attribute's value, then delete it.
        $interval = $node->getAttribute('data-entity-counter-ajax');
        $node->removeAttribute('data-entity-counter-ajax');

        // Load the entity counter and get the property.
        $storage = $this->entityTypeManager->getStorage('entity_counter');
        /** @var \Drupal\entity_counter\Entity\EntityCounterInterface $entity_counter */
        $entity_counter = $storage->load($entity_counter);
        if ($entity_counter !== NULL && !empty($entity_counter_property)) {
          $entity_counters[] = $entity_counter;
          $filter_value = $entity_counter->get($entity_counter_property);

          // Calculate the ratio equivalence.
          if (!empty($entity_counter_ratio)) {
            $filter_value *= $entity_counter_ratio;
          }

          // Round it.
          if (!empty($entity_counter_round)) {
            $round_type = ($entity_counter_round == 'up') ? PHP_ROUND_HALF_UP : PHP_ROUND_HALF_DOWN;
            $filter_value = round($filter_value, 0, $round_type);
          }
        }
        if ($entity_counter !== NULL && !empty($entity_counter_percentage)) {
          $entity_counters[] = $entity_counter;
          $max = empty($entity_counter->getMax()) ? 100 : $entity_counter->getMax();
          $filter_value = ($entity_counter->getValue() / $max) * 100;

          // Round it.
          $round_type = ($entity_counter_percentage == 'up') ? PHP_ROUND_HALF_UP : PHP_ROUND_HALF_DOWN;
          $filter_value = round($filter_value, 0, $round_type);
        }

        if ($filter_value !== NULL) {
          // Render the new node.
          $tag = $node->tagName;
          $attributes = [];
          foreach ($node->attributes as $attr) {
            $attributes[$attr->nodeName] = $attr->nodeValue;
          }

          if ($entity_counter->isOpen() && !empty($interval)) {
            $entity_counter_filter = [
              '#type' => 'entity_counter',
              '#entity_counter' => $entity_counter->id(),
              '#renderer_plugin' => 'plain_ajax_reload',
              '#renderer_settings' => [
                'interval' => $interval,
                'ratio' => empty($entity_counter_ratio) ? 1 : $entity_counter_ratio,
                'round' => empty($entity_counter_round) ? 0 : $round_type,
                'format' => [
                  'decimals' => empty($entity_counter_decimals) ? 0 : $entity_counter_decimals,
                  'separator' => empty($entity_counter_separator) ? '.' : $entity_counter_separator,
                  'type-decimal' => empty($entity_counter_type_decimal) ? ',' : $entity_counter_type_decimal,
                ],
              ],
              '#wrapper_tag' => $tag,
              '#wrapper_attributes' => $attributes,
            ];
          }
          else {
            $entity_counter_filter = [
              '#type' => 'html_tag',
              '#tag' => $tag,
              '#value' => $filter_value,
              '#attributes' => $attributes,
            ];
          }

          $altered_html = $this->renderer->render($entity_counter_filter);

          // Load the altered HTML into a new DOMDocument and retrieve the
          // element.
          $updated_nodes = HtmlUtility::load($altered_html)->getElementsByTagName('body')
            ->item(0)
            ->childNodes;

          foreach ($updated_nodes as $updated_node) {
            // Import the updated node from the new DOMDocument into the
            // original one, importing also the child nodes of the updated node.
            $updated_node = $dom->importNode($updated_node, TRUE);
            $node->parentNode->insertBefore($updated_node, $node);
          }
          // Finally, remove the original data-caption node.
          $node->parentNode->removeChild($node);
        }
      }

      // Set the processed text and add the cache dependency to every entity
      // counter.
      $result->setProcessedText(HtmlUtility::serialize($dom));
      foreach ($entity_counters as $entity_counter) {
        $result->addCacheableDependency($entity_counter);
      }
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function tips($long = FALSE) {
    if ($long) {
      return $this->t('
        <p>You can display entity counter values. Examples:</p>
        <ul>
          <li>Entity counter property: <code>&lt;div data-entity-counter="my_counter" data-entity-counter-property="value"&gt;&lt;/div&gt;</code>
            <ul>
              <li>Available properties: id, label, description, minimum, maximum, step, initial_value and value.</li>
              <li>Only for value property you can add ajax refresh feature adding: data-entity-counter-ajax="TIME_IN_SECONDS".</li>
            </ul>
          </li>
          <li>Entity counter property equivalence: <code>&lt;div data-entity-counter="my_counter" data-entity-counter-property="value" data-entity-counter-ratio="1.5" data-entity-counter-round="down"&gt;&lt;/div&gt;</code>
            <ul>
              <li>Available round modes:</li>
                <ul>
                  <li>Up: Round val up to 0 decimal places away from zero, when it is half way there. Making 1.5 into 2 and -1.5 into -2.</li>
                  <li>Down: Round val down to 0 decimal places towards zero, when it is half way there. Making 1.5 into 1 and -1.5 into -1.</li>
                </ul>
            </ul>
          </li>
          <li>Entity counter percentage: <code>&lt;div data-entity-counter="my_counter" data-entity-counter-percentage="down"&gt;&lt;/div&gt;</code>
            <ul>
              <li>Available percentage modes:</li>
                <ul>
                  <li>Up: Round val up to 0 decimal places away from zero, when it is half way there. Making 1.5 into 2 and -1.5 into -2.</li>
                  <li>Down: Round val down to 0 decimal places towards zero, when it is half way there. Making 1.5 into 1 and -1.5 into -1.</li>
                </ul>
            </ul>
          </li>
          <li>Entity counter property decimals: <code>&lt;div data-entity-counter="my_counter" data-entity-counter-property="value" data-entity-counter-ratio="1.5" data-entity-counter-decimals="2"&gt;&lt;/div&gt;</code></li>
          <li>Entity counter property decimal type: <code>&lt;div data-entity-counter="my_counter" data-entity-counter-property="value" data-entity-counter-ratio="1.5" data-entity-counter-type-decimal=","&gt;&lt;/div&gt;</code></li>
          <li>Entity counter property thousands separator: <code>&lt;div data-entity-counter="my_counter" data-entity-counter-property="value" data-entity-counter-ratio="1.5" data-entity-counter-separator="."&gt;&lt;/div&gt;</code></li>
        </ul>
      ');
    }
    else {
      return $this->t('You can display entity counter values with (<code>data-entity-counter="my_counter"</code>).');
    }
  }

}
