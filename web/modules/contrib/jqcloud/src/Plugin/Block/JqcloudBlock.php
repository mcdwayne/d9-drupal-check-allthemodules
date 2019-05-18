<?php

namespace Drupal\jqcloud\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\CacheTagsInvalidator;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\jqcloud\TermServiceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * Provides a template for blocks based of each vocabulary.
 *
 * @Block(
 *   id = "jqcloud_block",
 *   admin_label = @Translation("jQCloud terms"),
 *   category = @Translation("jQCloud"),
 *   deriver = "Drupal\jqcloud\Plugin\Derivative\JqcloudBlockDeriver"
 * )
 *
 * @see \Drupal\jqcloud\Plugin\Derivative\JqcloudBlockDeriver
 */
class JqcloudBlock extends BlockBase implements
    ContainerFactoryPluginInterface {

  /**
   * Drupal\jqcloud\TermServiceInterface definition.
   *
   * @var \Drupal\jqcloud\TermServiceInterface
   */
  protected $term;

  /**
   * Drupal\Core\Cache\CacheTagsInvalidator definition.
   *
   * @var \Drupal\Core\Cache\CacheTagsInvalidator
   */
  protected $cacheInvalidator;

  /**
   * Constructs an LanguageBlock object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\jqcloud\TermServiceInterface $term
   *   Term service.
   * @param \Drupal\Core\Cache\CacheTagsInvalidator $cache_tags_invalidator
   *   Cache tags invalidator service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    TermServiceInterface $term,
    CacheTagsInvalidator $cache_tags_invalidator
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->term = $term;
    $this->cacheInvalidator = $cache_tags_invalidator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition
  ) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('jqcloud.term'),
      $container->get('cache_tags.invalidator')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'terms_count' => TermServiceInterface::DEFAULT_SIZE,
      'height' => 250,
      'link_to_term' => 0,
      'style' => 'default',
      'colors' => $this->getDefaultColors(),
      'auto_resize' => 1,
      'shape' => 'elliptic',
      'delay' => 0,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    $tags = parent::getCacheTags();
    $tags[] = $this->getBlockCacheTag();
    $tags[] = 'taxonomy_term_list';

    return $tags;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    $contexts = parent::getCacheContexts();
    $contexts[] = 'theme';
    $contexts[] = 'user.permissions';
    $contexts[] = 'languages:language_interface';

    return $contexts;
  }

  /**
   * Returns block cache tag.
   *
   * @return string
   *   Cache tag.
   */
  public function getBlockCacheTag() {
    return "jqcloud:{$this->pluginDefinition['vocabulary']->id()}";
  }

  /**
   * Returns list of default colors for the Block edit form.
   *
   * @return array
   *   Colors in simple array.
   */
  public function getDefaultColors() {
    $list = [
      '#aab5f0',
      '#99ccee',
      '#a0ddff',
      '#90c5f0',
      '#90a0dd',
      '#90c5f0',
      '#3399dd',
      '#00ccff',
      '#00ccff',
      '#00ccff',
    ];

    $colors = [];

    foreach ($list as $key => $color) {
      $colors["weight_{$key}"] = $color;
    }

    return $colors;
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['jqcloud'] = [
      '#type' => 'details',
      '#title' => $this->t('jQCloud settings'),
      '#open' => TRUE,
    ];

    $form['jqcloud']['terms_count'] = [
      '#type' => 'number',
      '#title' => $this->t('Number of terms to display'),
      '#required' => TRUE,
      '#min' => -1,
      '#default_value' => $this->configuration['terms_count'],
      '#description' => $this->t('Set "-1" value for display unlimited terms.'),
    ];

    $form['jqcloud']['height'] = [
      '#type' => 'number',
      '#title' => $this->t('Terms block height'),
      '#required' => TRUE,
      '#min' => 0,
      '#default_value' => $this->configuration['height'],
    ];

    $form['jqcloud']['link_to_term'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Link to the term page'),
      '#default_value' => $this->configuration['link_to_term'],
    ];

    $form['jqcloud']['style'] = [
      '#type' => 'select',
      '#title' => $this->t('Style'),
      '#options' => [
        'default' => $this->t('Default jQCloud styles'),
        'none' => $this->t('Without styling'),
        'custom_colors' => $this->t('With custom colors'),
      ],
      '#default_value' => $this->configuration['style'],
    ];

    // Custom colors settings.
    $form['jqcloud']['custom_colors'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Custom colors'),
      '#open' => TRUE,
      '#states' => [
        'visible' => [
          ':input[name="settings[jqcloud][style]"]' => [
            'value' => 'custom_colors',
          ],
        ],
      ],
    ];

    for ($i = 0; $i <= 9; $i++) {
      $form['jqcloud']['custom_colors']['colors']["weight_{$i}"] = [
        '#type' => 'color',
        '#title' => $this->t('Color for weight @n', ['@n' => $i + 1]),
        '#default_value' => $this->configuration['colors']["weight_{$i}"],
      ];
    }

    // Other settings.
    $form['jqcloud']['other_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Other settings'),
      '#open' => FALSE,
    ];

    $form['jqcloud']['other_settings']['auto_resize'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Auto resize'),
      '#default_value' => $this->configuration['auto_resize'],
    ];

    $form['jqcloud']['other_settings']['shape'] = [
      '#type' => 'select',
      '#title' => $this->t('Shape'),
      '#options' => [
        'elliptic' => $this->t('Elliptic'),
        'rectangular' => $this->t('Rectangular'),
      ],
      '#default_value' => $this->configuration['shape'],
    ];

    $form['jqcloud']['other_settings']['delay'] = [
      '#type' => 'number',
      '#title' => $this->t('Delay'),
      '#min' => 0,
      '#default_value' => $this->configuration['delay'],
      '#description' => $this->t(
        'Display terms in the jQCloud block with delay in milliseconds.'
      ),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $values = $form_state->getValue('jqcloud');
    $this->configuration['terms_count'] = $values['terms_count'];
    $this->configuration['height'] = $values['height'];
    $this->configuration['link_to_term'] = $values['link_to_term'];
    $this->configuration['style'] = $values['style'];

    // Custom colors.
    $this->configuration['colors'] = $values['custom_colors']['colors'];

    // Other settings.
    $this->configuration['auto_resize'] =
    $values['other_settings']['auto_resize'];
    $this->configuration['shape'] = $values['other_settings']['shape'];
    $this->configuration['delay'] = $values['other_settings']['delay'];

    // Invalidate block cache tag.
    $this->cacheInvalidator->invalidateTags([$this->getBlockCacheTag()]);
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $content = [];

    /** @var \Drupal\taxonomy\VocabularyInterface $vocabulary */
    $vocabulary = $this->pluginDefinition['vocabulary'];
    $id = "jqcloud-{$vocabulary->id()}";
    $content['#attributes']['class'][] = $id;

    $data = [];
    if ($this->configuration['terms_count'] == -1) {
      $terms_count = NULL;
    }
    else {
      $terms_count = $this->configuration['terms_count'];
    }

    $terms = $this->term->getTerms($vocabulary, $terms_count);

    if (!empty($terms)) {
      foreach ($terms as $key => $term) {
        $data[$key] = [
          'text' => $term->getName(),
          'weight' => $term->getWeight(),
        ];

        if (!empty($this->configuration['link_to_term'])) {
          $data[$key]['link'] = Url::fromRoute(
            'entity.taxonomy_term.canonical',
            ['taxonomy_term' => $term->id()]
          )->toString();
        }
      }
    }

    // Set terms list into drupalSettings.
    $content['#attached']['drupalSettings']['jQCloud'][$id]['terms'] = $data;
    // Height configuration.
    $content['#attached']['drupalSettings']['jQCloud'][$id]['height'] =
    $this->configuration['height'];

    // Attach jQCloud library.
    $content['#attached']['library'][] = 'jqcloud/jqcloud';
    // Attach module js library.
    $content['#attached']['library'][] = 'jqcloud/default';

    // Set default style.
    if ($this->configuration['style'] == 'default') {
      $content['#attached']['library'][] = 'jqcloud/jqcloud-styles';
    }
    // Set custom colors.
    if ($this->configuration['style'] == 'custom_colors') {
      $content['#attached']['drupalSettings']['jQCloud'][$id]['colors'] =
      array_values($this->configuration['colors']);
    }

    // Set other settings.
    $content['#attached']['drupalSettings']['jQCloud'][$id]['auto_resize'] =
      $this->configuration['auto_resize'];
    $content['#attached']['drupalSettings']['jQCloud'][$id]['shape'] =
      $this->configuration['shape'];
    $content['#attached']['drupalSettings']['jQCloud'][$id]['delay'] =
      $this->configuration['delay'];

    $content['wrapper'] = [
      '#type' => 'container',
      '#attributes' => ['class' => [$id . ' jqcloud-contents']],
    ];

    $content['wrapper']['hidden'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#value' => $this->t('jQCloud contents'),
      '#attributes' => [
        'class' => ['visually-hidden'],
      ],
    ];

    return $content;
  }

}
