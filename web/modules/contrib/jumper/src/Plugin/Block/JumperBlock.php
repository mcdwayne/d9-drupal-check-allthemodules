<?php

namespace Drupal\jumper\Plugin\Block;

use Drupal\Core\Url;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides an 'Jumper' block.
 *
 * @Block(
 *  id = "jumper_block",
 *  admin_label = @Translation("Jumper"),
 *  category = @Translation("Jumper"),
 * )
 */
class JumperBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs an AggregatorFeedBlock object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ModuleHandlerInterface $module_handler) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->moduleHandler = $module_handler;
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

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'target'     => '#header',
      'duration'   => 1000,
      'offset'     => 0,
      'color'      => 'blue',
      'no_text'    => TRUE,
      'text'       => 'Top',
      'style'      => 'round-2',
      'visibility' => 600,
      'selectors'  => '',
      'autovlm'    => FALSE,
    ];
  }

  /**
   * Overrides \Drupal\block\BlockBase::blockForm().
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $path = drupal_get_path('module', 'jumper');
    $readme = $this->moduleHandler->moduleExists('help') ? Url::fromRoute('help.page', ['name' => 'jumper'])->toString() : Url::fromUri('base:' . $path . '/README.md')->toString();

    $form['target'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Target'),
      '#default_value' => $this->configuration['target'],
      '#description'   => $this->t('Valid CSS selector as the scrolling target which must exist on your template, e.g.: #main, #top, .header, etc.'),
      '#max_length'    => 255,
    ];

    $form['duration'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Duration'),
      '#default_value' => $this->configuration['duration'],
      '#description'   => $this->t('Pass the time the `jump()` takes, in milliseconds.'),
      '#field_suffix'  => '<abbr title="Milliseconds">ms</abbr>',
      '#max_length'    => 32,
    ];

    $form['offset'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Offset'),
      '#default_value' => $this->configuration['offset'],
      '#description'   => $this->t('Offset a `jump()`, _only if to an element_, by a number of pixels. Use minus sign (-) to stop before the top of the element, else positive number to stop afterward. If having a fixed header, play around with this.'),
      '#field_suffix'  => '<abbr title="Pixel">px</abbr>',
      '#max_length'    => 32,
    ];

    $form['color'] = [
      '#type'          => 'select',
      '#title'         => $this->t('Background color'),
      '#default_value' => $this->configuration['color'],
      '#options'       => [
        'grey'   => $this->t('Grey'),
        'dark'   => $this->t('Dark'),
        'purple' => $this->t('Purple'),
        'orange' => $this->t('Orange'),
        'blue'   => $this->t('Blue'),
        'lime'   => $this->t('Lime'),
        'red'    => $this->t('Red'),
      ],
      '#empty_option'  => $this->t('- None -'),
      '#description'   => $this->t('Choose the provided background color, or leave empty to DIY.'),
    ];

    $form['no_text'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Visually hide the title text, and use the icon only. If unchecked, be sure to adjust the styling accordingly with text presence.'),
      '#default_value' => $this->configuration['no_text'],
    ];

    $form['text'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Text'),
      '#default_value' => $this->configuration['text'],
      '#description'   => $this->t('Text to display if not hidden. You can include HTML with the only allowed tag `span` for advanced positioning/ styling, e.g.: <br /><strong>&lt;span&gt;Top&lt;/span&gt;</strong><br />Further theming is required if you have more texts than just `Top`.'),
      '#max_length'    => 60,
      '#states'        => [
        'visible' => [':input[name="settings[no_text]"]' => ['checked' => FALSE]],
      ],
    ];

    $form['style'] = [
      '#type'          => 'select',
      '#title'         => $this->t('Style'),
      '#default_value' => $this->configuration['style'],
      '#options'       => [
        'round'    => $this->t('Round'),
        'round-2'  => $this->t('Round 2'),
        'round-8'  => $this->t('Round 8'),
        'round-12' => $this->t('Round 12'),
      ],
      '#empty_option'  => $this->t('Square'),
      '#description'   => $this->t('Choose the provided style, or leave it to default Square to DIY.'),
    ];

    $form['visibility'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Activation point'),
      '#default_value' => $this->configuration['visibility'],
      '#description'   => $this->t('The jumper will be visible when reaching this point. This is not accurate as it is debounced every 250ms, affected by windows scrolling speed.'),
      '#field_suffix'  => '<abbr title="Pixel">px</abbr>',
      '#max_length'    => 32,
    ];

    $form['selectors'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Additional selectors'),
      '#default_value' => $this->configuration['selectors'],
      '#description'   => $this->t('Additional selectors to act as jumpers. Use comma separated valid CSS selectors supported by <strong>querySelectorAll</strong>, e.g.:<br /> <strong>.menu-item a[href*="#"], .button--cta</strong><br />Be specific to avoid unintentional link hijacking such as with Bootstrap tabs. This block MUST be present where those selectors are. See <a href=":url">README</a> for relevant info.', [':url' => $readme]),
      '#max_length'    => 255,
    ];

    $vlm = $this->moduleHandler->moduleExists('views_load_more');
    $form['autovlm'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Auto trigger Views Load More.'),
      '#default_value' => $this->configuration['autovlm'],
      '#disabled'      => !$vlm,
      '#description'   => $this->t('Check to enable auto trigger Views Load More (VLM) wherever VLM and Jumper are both present on a page. Requires optional VLM.'),
    ];

    return $form;
  }

  /**
   * Overrides \Drupal\block\BlockBase::blockSubmit().
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    foreach ($this->defaultConfiguration() as $key => $default) {
      $this->setConfigurationValue($key, $form_state->getValue($key));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config   = $this->getConfiguration();
    $target   = trim(strip_tags($config['target']));
    $fragment = strpos($target, '#') !== FALSE ? str_replace('#', '', $target) : ' ';
    $text     = strip_tags($this->configuration['text']);

    $build = [
      '#type' => 'link',
      '#title' => [
        '#markup' => trim($text),
        '#allowed_tags' => ['span'],
      ],
      '#url' => Url::fromRoute('<current>'),
      '#options' => [
        'attributes' => [
          'class' => ['jumper', 'jumper--block'],
          'data-target' => is_numeric($target) ? (int) $target : $target,
          'id' => 'jumper',
        ],
        'fragment' => $fragment,
        'external' => TRUE,
        'html' => TRUE,
      ],
      '#attached' => [
        'library' => ['jumper/load'],
        'drupalSettings' => ['jumper' => $config],
      ],
    ];

    if (!empty($config['color'])) {
      $build['#options']['attributes']['class'][] = 'jumper--color jumper--' . strip_tags($config['color']);
    }
    if (!empty($config['style'])) {
      $build['#options']['attributes']['class'][] = 'jumper--' . strip_tags($config['style']);
    }
    if (!empty($config['no_text'])) {
      $build['#options']['attributes']['class'][] = 'jumper--no-text';
    }

    return $build;
  }

}
