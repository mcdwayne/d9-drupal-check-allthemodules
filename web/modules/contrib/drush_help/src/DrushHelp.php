<?php

namespace Drupal\drush_help;

use Drupal\Core\Render\RendererInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\filter\FilterPluginCollection;
use Drupal\Component\Plugin\PluginManagerInterface;

/**
 * Class DrushHelp.
 *
 * @package Drupal\drush_help
 */
class DrushHelp implements DrushHelpInterface {

  use StringTranslationTrait;

  /**
   * The render service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The filter plugin manager.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface|\Drupal\Component\Plugin\FallbackPluginManagerInterface
   */
  protected $filterManager;

  /**
   * Constructs a new DrushHelp object.
   *
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The render service.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation.
   * @param \Drupal\Component\Plugin\PluginManagerInterface|\Drupal\Component\Plugin\FallbackPluginManagerInterface $filter_manager
   *   The filter plugin manager.
   */
  public function __construct(RendererInterface $renderer, TranslationInterface $string_translation, PluginManagerInterface $filter_manager) {
    $this->renderer = $renderer;
    $this->stringTranslation = $string_translation;
    $this->filterManager = $filter_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function getDrushCommandsHelp($drush_commands) {
    // Adding the Drush commands section.
    $section['drush_help'] = ['#markup' => '<h3>' . $this->t('Drush commands') . '</h3>'];

    foreach ($drush_commands as $command => $definition) {
      // The details drush command element container.
      $section[$command] = [
        '#type' => 'details',
        '#title' => $command,
        '#description' => $definition['description'],
        '#open' => FALSE,
        '#attributes' => [
          'class' => [
            'drush_help'
          ]
        ]
      ];

      // Examples.
      if (isset($definition['examples'])) {
        $rows = [];
        // Iterating over each example.
        foreach ($definition['examples'] as $example_command => $section_definition) {
          $rows[] = [$example_command, $section_definition];
        }
        // Examples render array.
        $section[$command]['examples'] = [
          '#type' => 'table',
          '#caption' => $this->t('Examples'),
          '#header' => [
            $this->t('Command'),
            [
              'data' => $this->t('Description'),
              // Hidding the description on narrow width devices.
              'class' => [RESPONSIVE_PRIORITY_MEDIUM],
            ],
          ],
          '#rows' => $rows,
          '#sticky' => TRUE,
        ];
      }

      // Arguments.
      if (isset($definition['arguments'])) {
        $rows = [];
        // Iterating over each argument.
        foreach ($definition['arguments'] as $example_command => $section_definition) {
          // Sometimes we can have links in the drush descriptions, so we need
          // to extract it and convert it in links (in the Drupal way) to be
          // displayed in the Drupal interface.
          // Searching if the description have links inside to convert it.
          $section_definition = $this->convertPlainUrlInLink($section_definition);

          $rows[] = [$example_command, $section_definition];
        }
        // Arguments render array.
        $section[$command]['arguments'] = [
          '#type' => 'table',
          '#caption' => $this->t('Arguments'),
          '#header' => [$this->t('Name'), $this->t('Description')],
          '#rows' => $rows,
          '#sticky' => TRUE,
        ];
      }

      // Options.
      if (isset($definition['options'])) {
        $rows = [];
        // Iterating over each option.
        foreach ($definition['options'] as $example_command => $section_definition) {
          $example_command = '--' . $example_command;
          // Verifying that we have example-value.
          if (isset($section_definition['example-value'])) {
            // Checking for the value.
            switch ($section_definition['value']) {
              case 'optional':
                $example_command .= '=[' . $section_definition['example-value'] . ']';
                break;

              // By default is required.
              default:
                $example_command .= '=<' . $section_definition['example-value'] . '>';
            }
          }
          // Sometimes we can have links in the drush descriptions, so we need
          // to extract it and convert it in links (in the Drupal way) to be
          // displayed in the Drupal interface.
          // Searching if the description have links inside to convert it.
          $section_definition['description'] = $this->convertPlainUrlInLink($section_definition['description']);

          $rows[] = [$example_command, $section_definition['description']];
        }
        // Options render array.
        $section[$command]['options'] = [
          '#type' => 'table',
          '#caption' => $this->t('Options'),
          '#header' => [$this->t('Name'), $this->t('Description')],
          '#rows' => $rows,
          '#sticky' => TRUE,
        ];
      }

      // Alias.
      if (isset($definition['aliases'])) {
        $rows = [];
        // Iterating over all the aliases.
        foreach ($definition['aliases'] as $alias) {
          $rows[] = $alias;
        }
        // Aliases render array.
        $section[$command][] = [
          '#markup' => '<div class="aliases">' . $this->t('Aliases') . '</div>',
        ];
        // List of aliases.
        $section[$command]['aliases'] = [
          '#theme' => 'item_list',
          '#items' => $rows,
          '#context' => ['list_style' => 'comma-list'],
        ];
      }
    }
    // Attaching css styles.
    $section[$command]['aliases']['#attached']['library'][] = 'drush_help/help_page';
    // Rendering all the elements.
    $drush_command_html = $this->renderer->render($section);

    return $drush_command_html;
  }

  /**
   * Convert plain url to links in a given string.
   *
   * @param string $string
   *   The string to review.
   *
   * @return string
   *   The new string.
   */
  public function convertPlainUrlInLink($string) {
    // Getting the filter plugin collection.
    $filter_collection = new FilterPluginCollection($this->filterManager, []);
    // Getting the filter_url plugin.
    $filter = $filter_collection->get('filter_url');

    // Setting the filter_url plugin configuration.
    $filter->setConfiguration([
      'settings' => [
        'filter_url_length' => 496,
      ],
    ]);

    // Applying the filter.
    $html['#markup'] = _filter_url($string, $filter);
    // Rendering the element.
    $result = $this->renderer->render($html);

    return $result;
  }

}
