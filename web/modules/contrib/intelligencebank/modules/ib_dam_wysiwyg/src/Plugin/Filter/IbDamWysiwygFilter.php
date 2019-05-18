<?php

namespace Drupal\ib_dam_wysiwyg\Plugin\Filter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Drupal\ib_dam\Asset\Asset;
use Drupal\ib_dam\AssetFormatter\AssetFormatterManager;
use Drupal\ib_dam_wysiwyg\AssetStorage\TextFilterStorage;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Render\RendererInterface;

/**
 * The filter to turn tokens inserted into the WYSIWYG into assets.
 *
 * @Filter(
 *   title = @Translation("IntelligenceBank DAM WYSIWYG"),
 *   id = "ib_dam_wysiwyg",
 *   description = @Translation("Enables the use of IntelligenceBank DAM WYSIWYG."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_MARKUP_LANGUAGE
 * )
 */
class IbDamWysiwygFilter extends FilterBase implements ContainerFactoryPluginInterface {

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * IbDamWysiwygFilter constructor.
   *
   * @param array $configuration
   *   Plugin configuration.
   * @param string $plugin_id
   *   Plugin ID.
   * @param mixed $plugin_definition
   *   Plugin definition.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, RendererInterface $renderer, AccountProxyInterface $current_user) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->renderer = $renderer;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition, $container->get('renderer'), $container->get('current_user'));
  }

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    $response = new FilterProcessResult($text);

    foreach ($this->getValidMatches($text) as $source_text => $embed_data) {
      $embed_data['has_preview'] = FALSE;

      // @todo: use typed data for validation?
      $asset  = Asset::createFromValues($embed_data);
      $formatter = AssetFormatterManager::create($asset, $embed_data['display_settings']);
      $elements = $formatter->format();

      $output = $this->renderer->render($elements);

      $classes = [
        'ib-dam-asset-item',
        Html::cleanCssIdentifier('ib-dam-asset--' . $asset->getType()),
        Html::cleanCssIdentifier('ib-dam-asset--' . $asset->getSourceType()),
      ];

      $embed_code = [
        '#type' => 'container',
        '#attributes' => ['class' => $classes],
        'item' => ['#markup' => $output],
      ];

      // Replace the JSON settings with a asset item.
      $text = str_replace($source_text, $this->renderer->render($embed_code), $text);
    }

    $response->setCacheContexts(['user.permissions']);
    $response->setProcessedText($text);
    return $response;
  }

  /**
   * Get all valid matches in the WYSIWYG.
   *
   * @param string $text
   *   The text to check for WYSIWYG matches.
   *
   * @return array
   *   An array of data from the text keyed by the text content.
   */
  protected function getValidMatches($text) {
    // Use a look ahead to match the capture groups in any order.
    if (!preg_match_all(TextFilterStorage::VALUES_PATTERN, $text, $matches)) {
      return [];
    }
    $valid_matches = [];
    foreach ($matches['json'] as $delta => $match) {
      // Ensure the JSON string is valid.
      $embed_data = json_decode($match, TRUE);
      if (!$embed_data || !is_array($embed_data)) {
        continue;
      }
      $valid_matches[$matches[0][$delta]] = $embed_data;
    }
    return $valid_matches;
  }

}
