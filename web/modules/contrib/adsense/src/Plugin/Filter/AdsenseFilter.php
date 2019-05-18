<?php

namespace Drupal\adsense\Plugin\Filter;

use Drupal\adsense\AdBlockInterface;
use Drupal\adsense\AdsenseAdBase;
use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a filter for AdSense input tags.
 *
 * @Filter(
 *   id = "filter_adsense",
 *   title = @Translation("AdSense tag"),
 *   description = @Translation("Substitutes an AdSense special tag with an ad. Add this below 'Limit allowed HTML tags'."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_REVERSIBLE
 * )
 */
class AdsenseFilter extends FilterBase implements ContainerFactoryPluginInterface {

  /**
   * The block storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $blockStorage;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Configuration.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * Creates a new AdsenseAdBase instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $entity_storage
   *   The block storage.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   * @param \Drupal\Core\Config\ImmutableConfig $config
   *   The configuration.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityStorageInterface $entity_storage, RendererInterface $renderer, ImmutableConfig $config) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->blockStorage = $entity_storage;
    $this->renderer = $renderer;
    $this->config = $config;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')->getStorage('block'),
      $container->get('renderer'),
      $container->get('config.factory')->get('adsense.settings')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    $patterns = [
      'block'   => '/\[adsense:block:([^\]]+)\]/x',
      'oldtag'  => '/\[adsense:([^:]+):(\d*):(\d*):?(\w*)\]/x',
      'tag'     => '/\[adsense:([^:]+):([^:\]]+)\]/x',
    ];
    $modified = FALSE;

    foreach ($patterns as $mode => $pattern) {
      if (preg_match_all($pattern, $text, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $match) {
          $ad = NULL;
          switch ($mode) {
            case 'block':
              // adsense:block:name.
              // Get the block with the same machine name as the tag.
              try {
                $module_blocks = $this->blockStorage->loadByProperties(['id' => $match[1]]);
              }
              catch (\Exception $e) {
                $module_blocks = [];
              }

              /** @var \Drupal\block\Entity\Block $block */
              foreach ($module_blocks as $block) {
                if ($block->getPlugin() instanceof AdBlockInterface) {
                  $ad = $block->getPlugin()->createAd();
                }
              }
              break;

            case 'oldtag':
              // adsense:format:group:channel:slot.
              try {
                $ad = AdsenseAdBase::createAd([
                  'format' => $match[1],
                  'group' => $match[2],
                  'channel' => $match[3],
                  'slot' => $match[4],
                ]);
              }
              catch (PluginException $e) {
                // Do nothing.
              }
              break;

            case 'tag':
              // adsense:format:slot.
              try {
                $ad = AdsenseAdBase::createAd([
                  'format' => $match[1],
                  'slot' => $match[2],
                ]);
              }
              catch (PluginException $e) {
                // Do nothing.
              }
              break;
          }
          // Replace the first occurrence of the tag, in case we have the same
          // tag more than once.
          if (isset($ad)) {
            $modified = TRUE;
            $ad_array = $ad->display();
            try {
              $ad_text = $this->renderer->render($ad_array);
              $text = preg_replace('/\\' . $match[0] . '/', $ad_text, $text);
            }
            catch (\Exception $e) {
              // Do nothing.
            }
          }
        }
      }
    }

    $result = new FilterProcessResult($text);

    if ($modified) {
      $result->addAttachments(['library' => ['adsense/adsense.css']]);
      if ($this->config->get('adsense_unblock_ads')) {
        $result->addAttachments(['library' => ['adsense/adsense.unblock']]);
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
        <p>Use tags to define AdSense ads. Examples:</p>
        <ul>
          <li><code>[adsense:<em>format</em>:<em>slot</em>]</code></li>
          <li><code>[adsense:<em>format</em>:<em>[group]</em>:<em>[channel]</em><em>[:slot]</em>]</code></li>
          <li><code>[adsense:block:<em>location</em>]</code></li>
        </ul>');
    }
    else {
      return $this->t('Use the special tag [adsense:<em>format</em>:<em>slot</em>] to display Google AdSense ads.');
    }
  }

}
