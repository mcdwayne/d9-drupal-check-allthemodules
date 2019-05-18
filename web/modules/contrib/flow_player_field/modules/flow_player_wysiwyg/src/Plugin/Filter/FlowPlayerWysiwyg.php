<?php

namespace Drupal\flow_player_wysiwyg\Plugin\Filter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Drupal\flow_player_field\ProviderManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal;

/**
 * The filter to turn tokens inserted into the WYSIWYG into videos.
 *
 * @Filter(
 *   title = @Translation("Flowplayer WYSIWYG"),
 *   id = "flow_player_wysiwyg",
 *   description = @Translation("Enables the use of Flowplayer in CKEditor."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_MARKUP_LANGUAGE
 * )
 */
class FlowPlayerWysiwyg extends FilterBase implements ContainerFactoryPluginInterface {

  /**
   * The video provider manager.
   *
   * @var \Drupal\flow_player_field\ProviderManagerInterface
   */
  protected $providerManager;

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
   * FlowPlayerWysiwyg constructor.
   *
   * @param array $configuration
   *   Plugin configuration.
   * @param string $plugin_id
   *   Plugin ID.
   * @param mixed $plugin_definition
   *   Plugin definition.
   * @param \Drupal\flow_player_field\ProviderManagerInterface $provider_manager
   *   The video provider manager.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ProviderManagerInterface $provider_manager, RendererInterface $renderer, AccountProxyInterface $current_user) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->providerManager = $provider_manager;
    $this->renderer = $renderer;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition, $container->get('flow_player_field.provider_manager'), $container->get('renderer'), $container->get('current_user'));
  }

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    $response = new FilterProcessResult($text);

    foreach ($this->getValidMatches($text) as $source_text => $embed_data) {
      $provider = $this->providerManager->loadProvider('flowplayer', $embed_data);
      $embed_code = $provider->renderEmbedHtml($this->getEmbedHtml($embed_data));

      $embed_code = [
        '#type' => 'container',
        '#attributes' => [
          'class' => [
            'flow-player-embed-container',
            Html::cleanCssIdentifier(sprintf('flow-player-field-provider-%s', $embed_data['video_id'])),
          ],
        ],
        'children' => $embed_code,
      ];

      // Replace the JSON settings with a video.
      $text = str_replace($source_text, $this->renderer->render($embed_code), $text);

      // Add the required responsive video library only when at least one match
      // is present.
      $response->setAttachments(['library' => ['flow_player_field/responsive-video']]);
      $response->setCacheContexts(['user.permissions']);
    }

    $response->setProcessedText($text);
    return $response;
  }

  /**
   * Get the HTML from the configuration.
   *
   * @param array $embed_data
   *   The data to process.
   *
   * @return mixed
   *   The html that should be embed.
   */
  public function getEmbedHtml(array $embed_data) {
    $config = Drupal::config('flow_player_field.settings');
    $flowplayerHtml = $config->get('flowplayer_html');

    $flowplayerHtml = str_replace('[VIDEOID]', $embed_data['video_id'], $flowplayerHtml);
    $flowplayerHtml = str_replace('[PLAYERID]', $embed_data['player_id'], $flowplayerHtml);

    return $flowplayerHtml;
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
    if (!preg_match_all('/(<p>)?(?<json>{(?=.*preview_thumbnail\b)(?=.*provider\b)(?=.*video_id\b)(?=.*player_id)(?=.*video)(.*)})(<\/p>)?/', $text, $matches)) {
      return [];
    }

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
