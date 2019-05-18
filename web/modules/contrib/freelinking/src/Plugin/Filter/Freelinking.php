<?php

namespace Drupal\freelinking\Plugin\Filter;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Drupal\freelinking\FreelinkingManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Freelinking input filter plugin.
 *
 * @Filter(
 *   id = "freelinking",
 *   title = @Translation("Freelinking"),
 *   description = @Translation("Allows for a flexible format for linking content."),
 *   type = \Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE,
 *   provider = "freelinking",
 *   status = false,
 *   settings = {
 *     "default": "nodetitle",
 *     "global_options": {
 *       "ignore_upi": false
 *     },
 *     "plugins": {},
 *   },
 *   weight = "0"
 * )
 */
class Freelinking extends FilterBase implements ContainerFactoryPluginInterface {

  /**
   * Freelinking plugin manager.
   *
   * @var \Drupal\freelinking\FreelinkingManagerInterface
   */
  protected $freelinkingManager;

  /**
   * Current user account.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Initialize method.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin ID.
   * @param array $plugin_definition
   *   The plugin definition array.
   * @param \Drupal\freelinking\FreelinkingManagerInterface $freelinkingManager
   *   The Freelinking plugin manager.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user account.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, FreelinkingManagerInterface $freelinkingManager, AccountProxyInterface $current_user) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->freelinkingManager = $freelinkingManager;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('freelinking.manager'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $plugins = $this->freelinkingManager->getDefinitions();

    $form['default'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default plugin'),
      '#description' => $this->t('Default plugin to use when no indicator is specified. “Nodetitle” mimics previous versions of Freelinking.'),
      '#default_value' => $this->settings['default'],
      '#required' => TRUE,
    ];

    $form['global_options'] = [
      '#tree' => TRUE,
      'ignore_upi' => [
        '#type' => 'select',
        '#title' => $this->t('Ignore Unknown Plugin Indicators (UPI)'),
        '#description' => $this->t('Choose whether to ignore markup that may look like freelinking or does not have a valid plugin.'),
        '#options' => [
          0 => $this->t('Display an error'),
          1 => $this->t('Ignore indicators'),
        ],
        '#required' => TRUE,
        '#default_value' => $this->settings['global_options']['ignore_upi'],
      ],
    ];

    $form['plugins'] = [
      '#tree' => TRUE,
    ];

    foreach ($plugins as $plugin_name => $plugin_definition) {
      $config = $this->extractPluginSettings($plugin_name, $this->settings['plugins']);
      $plugin_settings = isset($config['settings']) ? $config['settings'] : [];
      $plugin = $this->freelinkingManager->createInstance(
        $plugin_name,
        ['settings' => $plugin_settings]
      );

      $form['plugins'][$plugin_name] = [
        '#tree' => TRUE,
        '#type' => 'fieldset',
        '#collapsible' => FALSE,
        '#title' => $plugin_definition['title'],
        'plugin' => [
          '#type' => 'value',
          '#value' => $plugin_name,
        ],
        'enabled' => [
          '#type' => 'checkbox',
          '#title' => $this->t('Enable'),
          '#default_value' => isset($config['enabled']) ? $config['enabled'] : FALSE,
        ],
        'settings' => [
          '#tree' => TRUE,
          '#type' => 'container',
        ] + $plugin->settingsForm($form, $form_state),
      ];

      // Hide the enabled checkbox if the plugin is always enabled.
      if ($plugin->isHidden()) {
        $form['plugins'][$plugin_name]['enabled']['#disabled'] = TRUE;
        $form['plugins'][$plugin_name]['enabled']['#default_value'] = TRUE;
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function tips($long = FALSE) {
    $text = $this->t('Freelinking helps you easily create HTML links. Links take the form of <code>[[indicator:target|Title]].</code>');

    if (!$long) {
      if (isset($this->settings['default']) &&
          'NONE' !== $this->settings['default']) {
        $plugin = $this->freelinkingManager->createInstance($this->settings['default']);
        $text .= ' ' . $plugin->getTip();
      }
      return $text;
    }

    $content = <<<EOF
<p>Freelinking helps you easily create HTML links. Links take the form of <code>[[indicator:target|Title]].</code><br />
Below is a list of available types of freelinks you may use, organized as <strong>Plugin Name</strong>: [<em>indicator</em>].</p>
<ul>
EOF;

    // Assemble tips for each allowed plugin.
    $allowed_plugins = $this->extractAllowedPlugins($this->settings['plugins']);
    foreach ($allowed_plugins as $plugin_name => $plugin_info) {
      $settings = isset($plugin_info['settings']) ? $plugin_info['settings'] : [];
      $plugin = $this->freelinkingManager->createInstance($plugin_info['plugin'], $settings);
      $content .= '<li><strong>' . $plugin->getPluginDefinition()['title'] . '</strong> [<em>' . $plugin->getIndicator() . '</em>]: ' . $plugin->getTip() . '</li>';
    }
    $content .= '</ul>';

    // Ignore phpcs warning because tips are dynamically generated based on
    // plugins. This follows the pattern in core.
    // @see \Drupal\filter\Plugin\Filter\FilterAlign::tips().
    return $this->t($content);
  }

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    $defaultplugin = $this->settings['default'];
    // Ignore the node language if the current user's preferred language is
    // different so that links can be generated in the user's native language.
    $user_langcode = $langcode <> $this->currentUser->getPreferredLangcode() ? $this->currentUser->getPreferredLangcode() : $langcode;

    $result = new FilterProcessResult($text);

    // $pattern = '/(\[\[([a-z0-9\_]+):([^\]]+)\]\])/';.
    // @todo why not go back to preg_match_all for double square bracket syntax?
    // @see https://www.drupal.org/node/647940
    $start = 0;
    $remain = $text;
    $newtext = '';

    // Begin an indefinite loop until it is manually broken.
    while (TRUE) {
      $offset = 0;

      // Break when there is no remaining text to process.
      if (empty($remain)) {
        break;
      }

      // Begin freelinking parse or reset.
      if ('[' === $remain[0] && '[' === $remain[1]) {
        $infreelinkp = TRUE;
        $delim = ']]';
      }
      else {
        $infreelinkp = FALSE;
        $delim = '[[';
      }

      // Break out of loop if cannot find anything in remaining text.
      $pos = strpos($remain, $delim);
      if (FALSE === $pos) {
        break;
      }

      // Get the next chunk of text until the position of the delimiter above,
      // and if in freelinking, start processing, otherwise set remaining text
      // to the next chunk from the beginning delimiter.
      $chunk_all = substr($remain, $start, $pos);
      if ($infreelinkp) {
        $chunk_stripped = substr($chunk_all, 2);

        // Find the indicator (plugin) from the first set of characters up until
        // the colon, or use the default plugin.
        $indicatorPosition = strpos($chunk_stripped, ':');
        if (FALSE === $indicatorPosition) {
          $indicator = $defaultplugin;
          $target = $chunk_stripped;
        }
        else {
          $indicator = substr($chunk_stripped, 0, $indicatorPosition);
          $target = substr($chunk_stripped, $indicatorPosition + 1);
        }

        // Load the current plugin from the indicator and available plugins.
        $current_plugin = $this->freelinkingManager->getPluginFromIndicator(
          $indicator,
          $this->extractAllowedPlugins($this->settings['plugins']),
          $this->settings
        );

        if (!$this->settings['global_options']['ignore_upi'] || $current_plugin) {
          // Lazy Builder callback and context must be scalar.
          if (!$current_plugin) {
            $link = $result->createPlaceholder('freelinking.manager:createErrorElement', [$indicator]);
          }
          else {
            // Serialize plugin settings as a string.
            $plugin_settings = self::extractPluginSettings($current_plugin->getPluginId(), $this->settings['plugins']);

            // Failover plugin settings need to be extracted here and passed in
            // to the placeholder as well.
            if ($current_plugin->getFailoverPluginId()) {
              $failover_settings = self::extractPluginSettings($current_plugin->getFailoverPluginId(), $this->settings['plugins']);
            }
            else {
              $failover_settings = [];
            }

            $link = $result->createPlaceholder(
              'freelinking.manager:createFreelinkElement',
              [
                $current_plugin->getPluginId(),
                $target,
                $indicator,
                $user_langcode,
                serialize($plugin_settings),
                serialize($failover_settings),
              ]
            );
          }

          if ($link) {
            $chunk_all = $link;
            $offset = 2;
          }
        }
        $remain = substr($remain, $pos + $offset);
      }
      else {
        $remain = substr($remain, $pos);
      }
      $newtext .= $chunk_all;
    }
    $newtext .= $remain;

    $result->setProcessedText($newtext);

    return $result;
  }

  /**
   * Extract plugin information from freelinking plugin settings.
   *
   * @param string $plugin_name
   *   The plugin ID.
   * @param array $plugins
   *   The plugin array with settings.
   *
   * @return array
   *   The plugin information.
   */
  public static function extractPluginSettings($plugin_name, array $plugins) {
    return array_reduce($plugins, function (&$result, $info) use ($plugin_name) {
      if ($info['plugin'] === $plugin_name) {
        $result = $info;
      }
      return $result;
    }, []);
  }

  /**
   * Extract plugin names that are enabled from configuration.
   *
   * @param array $plugins
   *   The array of plugin information from the settings.
   *
   * @return array
   *   An indexed array of allowed plugin information.
   */
  protected function extractAllowedPlugins(array $plugins) {
    return array_reduce($plugins, function (&$result, $info) {
      if ($info['enabled']) {
        $result[$info['plugin']] = $info;
      }
      return $result;
    }, []);
  }

}
