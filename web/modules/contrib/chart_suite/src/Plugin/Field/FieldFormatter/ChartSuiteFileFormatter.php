<?php

namespace Drupal\chart_suite\Plugin\Field\FieldFormatter;

// Include the file parsing and data model library.
require_once DRUPAL_ROOT . '/' .
  drupal_get_path('module', 'chart_suite') .
  '/libraries/SDSCStructuredData.1.0.1.php';

use SDSC\StructuredData\Table;
use SDSC\StructuredData\Tree;
use SDSC\StructuredData\Graph;
use SDSC\StructuredData\Format\FormatRegistry;
use SDSC\StructuredData\Format\FormatException;
use SDSC\StructuredData\Format\FileNotFoundException;

use Drupal\file\FileInterface;
use Drupal\file\Plugin\Field\FieldFormatter\FileFormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

use Drupal\chart_suite\Branding;

/**
 * Formats a file's content for tables, trees, and graphs.
 *
 * The formatter uses a file parsing and data model API to create table,
 * tree, or graph objects from data in files using JSON, CSV, TSV, and
 * other well-known formats. The data is then passed to the client where
 * Javascript presents the data as a variety of charts.
 *
 * @ingroup chart_suite
 *
 * @FieldFormatter(
 *   id          = "chart_suite_file",
 *   label       = @Translation("Chart suite - interactive charts"),
 *   weight      = 1000,
 *   field_types = {
 *     "file"
 *   }
 * )
 */
class ChartSuiteFileFormatter extends FileFormatterBase {

  /*---------------------------------------------------------------------
   *
   * Configuration.
   *
   *---------------------------------------------------------------------*/

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'linkToEntity'        => FALSE,
      'emptyIfUnrecognized' => TRUE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();
    $summary[] = 'Display file content as a chart.';

    $doLink = $this->getSetting('linkToEntity') !== 0;
    $emptyIfUnrecognized = $this->getSetting('emptyIfUnrecognized') !== 0;

    if ($doLink === TRUE) {
      $summary[] = t('Link to file.');
    }
    else {
      $summary[] = t('No link to file.');
    }

    if ($emptyIfUnrecognized === TRUE) {
      $summary[] = t('Empty if file type is unrecognized.');
    }
    else {
      $summary[] = '';
    }

    return $summary;
  }

  /*---------------------------------------------------------------------
   *
   * Settings form.
   *
   *---------------------------------------------------------------------*/

  /**
   * Returns a brief description of the formatter.
   *
   * @return string
   *   Returns a brief translated description of the formatter.
   */
  protected function getDescription() {
    return t("Display an interactive chart of the file's contents. Supported file formats include CSV, TSV, JSON, and HTML for tables, trees, and graphs.");
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $formState) {
    //
    // Start with the parent form.
    $elements = parent::settingsForm($form, $formState);

    // Add branding.
    $elements = Branding::addFieldFormatterBranding($elements);
    $elements['#attached']['library'][] =
      'chart_suite/chart_suite.fieldformatter';

    // Add a description.
    //
    // Use a large negative weight to insure it comes first.
    $elements['description'] = [
      '#type'       => 'html_tag',
      '#tag'        => 'div',
      '#value'      => $this->getDescription(),
      '#weight'     => -1000,
      '#attributes' => [
        'class'     => [
          'chart_suite-settings-description',
        ],
      ],
    ];

    $weight = 0;

    // Add a checkbox to enable/disable linking the MIME type name or icon
    // to the underlying entity.
    $elements['linkToEntity'] = [
      '#title'         => t("Show the file's name with a link to the file."),
      '#type'          => 'checkbox',
      '#default_value' => $this->getSetting('linkToEntity'),
      '#weight'        => $weight++,
      '#wrapper_attributes' => [
        'class'        => [
          'chart_suite-link-to-entity',
        ],
      ],
    ];

    // Add a checkbox to enable/disable showing anything if the file format
    // not recognized.
    $elements['emptyIfUnrecognized'] = [
      '#type'          => 'checkbox',
      '#title'         => t("Disable all content if file format not recognized."),
      '#default_value' => $this->getSetting('emptyIfUnrecognized'),
      '#description'   => t("If a file's content is not recognized and cannot be drawn, show nothing."),
      '#weight'        => $weight++,
      '#wrapper_attributes' => [
        'class'        => [
          'chart_suite-empty-if-unrecognized',
        ],
      ],
    ];

    return $elements;
  }

  /*---------------------------------------------------------------------
   *
   * View.
   *
   *---------------------------------------------------------------------*/

  /**
   * {@inheritdoc}
   *
   * Sets up a render array for each item, assigning an appropriate
   * theme and parameters. In all cases, we load the file, parse it
   * as a table, tree, or graph, then convert it to a presentable
   * form. That presentable form may be as simplified JSON text suitable
   * for a visualization library to build a chart, or it may be HTML
   * text when no visualization is available.
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    // Create an empty render array.
    $elements = [];

    $doLink = $this->getSetting('linkToEntity') !== 0;
    $emptyIfUnrecognized = $this->getSetting('emptyIfUnrecognized') !== 0;

    // Loop through the entities we need to process and create a render
    // array for each one.
    foreach ($this->getEntitiesToView($items, $langcode) as $delta => $file) {
      $markup = '';

      // Get the server file system's real path to the file.
      $path = \Drupal::service('file_system')->realpath($file->getFileUri());
      if ($path === FALSE) {
        $markup .= '<p>' .
          $this->t('A server error occurred. The file could not be found.') .
          '</p>';
        continue;
      }

      // Get the user-visible file name.
      $filename = $file->getFilename();

      // Get the file name extension and see if it is supported by
      // any of the known file formats.
      $ext = pathinfo($filename, PATHINFO_EXTENSION);
      $supported = FALSE;
      if (empty($ext) === FALSE) {
        $formats = FormatRegistry::findFormatsByExtension($ext);
        if (empty($formats) === FALSE) {
          $supported = TRUE;
        }
      }

      // If the file's extension is not supported, return empty
      // markup. This is not an error. The file may be an image,
      // video, PDF, data file, or something else legitimate that
      // we just don't handle.
      if ($supported === TRUE) {
        // Decode the file into a table, tree, or graph. On an
        // exception, the file could not be parsed and is either
        // in an unknown format or there was a syntax error.
        $objectList = [];
        try {
          // Decode it. Do pay attention to the file name extension
          // on the file as guidance on how to parse the file.
          $objectList = FormatRegistry::decodeFile($path, TRUE);

          // At this point the file was parsed and $objectList
          // has a list of zero or more items. The list could
          // be empty if the file's syntax was good, but there
          // was simply nothing in it. For instance, the file
          // could have contained HTML but there were no tables
          // found. Or it could have been a JSON file that was
          // empty.
        }
        catch (FormatException $e) {
          // File's format was recognized, but couldn't be parsed.
          // Fall thru with an empty $objectList.
          $markup .= '<p>' .
            $this->t("The file's content could not be understood.") .
            '</br>' .
            $this->t('Error: @message', ['@message' => $e->getMessage()]) .
            '</p>';
        }
        catch (FileNotFoundException $e) {
          // File could not be found!
          // Fall thru with an empty $objectList.
          $markup .= '<p>' .
            $this->t('A server error occurred. The file could not be found.') .
            '</p>';
        }
        catch (\InvalidArgumentException $e) {
          // File path couldn't be parsed?
          // Fall thru with an empty $objectList.
          $markup .= '<p>' .
            $this->t('A server error occurred. The file could not be read.') .
            '</br>' .
            $this->t('Error: @message', ['@message' => $e->getMessage()]) .
            '</p>';
        }
        catch (\Exception $e) {
          // Unknown error!
          // Fall thru with an empty $objectList.
          $markup .= '<p>' .
            $this->t('A server error occurred.') .
            '</br>' .
            $this->t('Error: @message', ['@message' => $e->getMessage()]) .
            '</p>';
        }

        if (empty($objectList) === FALSE) {
          // TODO: Handle multiple objects in same file, though this is rare.
          // Only handle the 1st object in the file.
          $object = reset($objectList);

          // Table.
          if ($object instanceof Table) {
            $ren = $this->getRenderTable($file, $object);
            if ($emptyIfUnrecognized === FALSE || empty($ren) === FALSE) {
              $elements[$delta] = $ren;
            }

            continue;
          }

          // Tree.
          if ($object instanceof Tree) {
            $ren = $this->getRenderTree($file, $object);
            if ($emptyIfUnrecognized === FALSE || empty($ren) === FALSE) {
              $elements[$delta] = $ren;
            }

            continue;
          }

          // Graph.
          if ($object instanceof Graph) {
            $ren = $this->getRenderGraph($file, $object);
            if ($emptyIfUnrecognized === FALSE || empty($ren) === FALSE) {
              $elements[$delta] = $ren;
            }

            continue;
          }

          // Unknown object or empty file. Don't provide any markup.
        }
      }

      // Several conditions cause us to fall back to a simple
      // HTML message:
      //
      //  1. No objects parsed.
      //  2. The file could not be found.
      //  3. The file could not be read.
      //  4. The file's format was not recognized.
      //  5. The file's format was recognized, but had a syntax error.
      //  6. The file was a table, but with < 2 columns.
      //
      // For each of these, $markup is an HTML error message.
      if ($emptyIfUnrecognized === FALSE) {
        $elements[$delta] = [
          '#theme'    => 'chart_suite_file_formatter_as_html',
          '#filename' => $file->getFilename(),
          '#uri'      => file_create_url($file->getFileUri()),
          '#filesize' => $file->getSize(),
          '#dolink'   => $doLink,
          '#markup'   => $markup,
        ];
      }
    }

    return $elements;
  }

  /**
   * Returns a render array set up to display the given table.
   *
   * The array references one of several themes:
   * - Search index & results theme containing raw keywords.
   * - HTML theme containing an HTML version of the table.
   * - JSON theme containing an HTML + JavaScript version of the table.
   *
   * @param \Drupal\file\FileInterface $file
   *   The File object whose local file is parsed and presented.
   * @param \SDSC\StructuredData\Table $table
   *   The table to render.
   *
   * @return array
   *   Returns a render element array to present the table.
   */
  private function getRenderTable(FileInterface $file, Table $table) {

    $nColumns = $table->getNumberOfColumns();

    $doLink = $this->getSetting('linkToEntity') !== 0;
    $emptyIfUnrecognized = $this->getSetting('emptyIfUnrecognized') !== 0;

    // If we are building a search index entry, or presenting search
    // results with a few matching words highlighted, don't display
    // the table in a fancy way. Instead, use a simple theme that only
    // shows keywords extracted from the table's attributes, columns,
    // and rows.
    if ($this->viewMode === 'search_index' ||
        $this->viewMode === 'search_result') {
      // Add the table name and description, maintained in their
      // original word order so that search can find them using phrases.
      $markup = '';
      $markup .= $table->getBestName() . ' ';
      $markup .= $table->getDescription() . ' ';

      // Assemble a list of table and column attributes,
      // and row value keywords.  The returned arrays have
      // already split up multi-word text items into individual
      // keywords, removed punctuation, made them uniform case,
      // and sorted them.
      $keywords = array_merge(
        $table->getAttributeKeywords(),
        $table->getAllColumnAttributeKeywords(),
        $table->getAllRowKeywords());

      // Sort the combined list and remove duplicates.
      // Create simple text from the resulting list.
      sort($keywords, (SORT_NATURAL | SORT_FLAG_CASE));
      $markup .= implode(' ', array_unique($keywords));

      return [
        '#theme'    => 'chart_suite_file_formatter_as_search_index',
        '#filename' => $file->getFilename(),
        '#markup'   => $markup,
      ];
    }

    // If we have no columns, then the table is empty and there
    // is nothing useful we can show.
    //
    // If we have just one column, then the table is really a list
    // and there is no slick way to visualize this with plots.
    //
    // So, in both cases, show the "table" as HTML.
    if ($nColumns < 2) {
      if ($emptyIfUnrecognized === TRUE) {
        return [];
      }

      return [
        '#theme'    => 'chart_suite_file_formatter_as_html',
        '#filename' => $file->getFilename(),
        '#uri'      => file_create_url($file->getFileUri()),
        '#filesize' => $file->getSize(),
        '#dolink'   => $doLink,
        '#markup'   => HTMLPresenter::encodeTable($table),
      ];
    }

    // Otherwise, when we have a bigger table, show the table
    // using some JavaScript and Google Charts. Choose among
    // variants based upon whether the table's 1st column is
    // entirely strings or not.
    //
    // When the 1st column is NOT strings, we can plot the data
    // as lines, etc.  But when the 1st column IS strings, then
    // the strings provide category names for pie charts and
    // bar charts.
    if ($table->isColumnStrings(0) === TRUE) {
      $theme = 'chart_suite_file_formatter_as_strtable';
    }
    else {
      $theme = 'chart_suite_file_formatter_as_table';
    }

    return [
      '#theme'       => $theme,
      '#filename'    => $file->getFilename(),
      '#uri'         => file_create_url($file->getFileUri()),
      '#filesize'    => $file->getSize(),
      '#id'          => $file->id(),
      '#title'       => $table->getBestName(),
      '#description' => $table->getDescription(),
      '#xaxis'       => $table->getColumnBestName(0),
      '#yaxis'       => (($nColumns === 2) ? $table->getColumnBestName(1) : ''),
      '#dolink'      => $doLink,
      '#markup'      => GoogleChartsPresenter::encodeTable($table),
      '#attached'    => [
        'library'    => [
          'chart_suite/chart_suite.usage',
          'chart_suite/chart_suite.googlecharts',
        ],
      ],
    ];
  }

  /**
   * Returns a render array set up to display the given tree.
   *
   * The array references one of several themes:
   * - search index & results theme containing raw keywords.
   * - HTML theme containing an HTML version of the tree.
   * - JSON theme containing an HTML + JavaScript version of the tree.
   *
   * @param \Drupal\file\FileInterface $file
   *   The File object whose local file is parsed and presented.
   * @param \SDSC\StructuredData\Tree $tree
   *   The tree to render.
   *
   * @return array
   *   Returns a render element array to present the tree.
   */
  private function getRenderTree(FileInterface $file, Tree $tree) {

    $doLink = $this->getSetting('linkToEntity') !== 0;

    // If we are building a search index entry, or presenting search
    // results with a few matching words highlighted, don't display
    // the tree in a fancy way. Instead, use a simple theme that only
    // shows keywords extracted from the tree's attributes and nodes.
    if ($this->viewMode === 'search_index' ||
        $this->viewMode === 'search_result') {
      $markup = '';

      // Add the tree name and description, maintained in their
      // original word order so that search can find them using phrases.
      $markup .= $tree->getBestName() . ' ';
      $markup .= $tree->getDescription() . ' ';

      // Assemble a list of tree and node attributes,
      // and row value keywords.  The returned arrays have
      // already split up multi-word text items into individual
      // keywords, removed punctuation, made them uniform case,
      // and sorted them.
      $keywords = array_merge(
        $tree->getAttributeKeywords(),
        $tree->getAllNodeKeywords());

      // Sort the combined list and remove duplicates.
      // Create simple text from the resulting list.
      sort($keywords, (SORT_NATURAL | SORT_FLAG_CASE));
      $markup .= implode(' ', array_unique($keywords));

      return [
        '#theme'    => 'chart_suite_file_formatter_as_search_index',
        '#filename' => $file->getFilename(),
        '#markup'   => $markup,
      ];
    }

    // Otherwise, show the tree using some JavaScript and
    // Google Charts.
    return [
      '#theme'       => 'chart_suite_file_formatter_as_tree',
      '#filename'    => $file->getFilename(),
      '#uri'         => file_create_url($file->getFileUri()),
      '#filesize'    => $file->getSize(),
      '#id'          => $file->id(),
      '#title'       => $tree->getBestName(),
      '#dolink'      => $doLink,
      '#description' => $tree->getDescription(),
      '#markup'      => GoogleChartsPresenter::encodeTree($tree),
      '#attached'    => [
        'library'    => [
          'chart_suite/chart_suite.usage',
          'chart_suite/chart_suite.googlecharts',
        ],
      ],
    ];
  }

  /**
   * Returns a render array set up to display the given graph.
   *
   * The array references one of several themes:
   * - Search index & results theme containing raw keywords.
   * - HTML theme containing an HTML version of the graph.
   *
   * @param \Drupal\file\FileInterface $file
   *   The File object whose local file is parsed and presented.
   * @param \SDSC\StructuredData\Graph $graph
   *   The graph to render.
   *
   * @return array
   *   Returns a render element array to present the graph.
   */
  private function getRenderGraph(FileInterface $file, Graph $graph) {

    $doLink = $this->getSetting('linkToEntity') !== 0;
    $emptyIfUnrecognized = $this->getSetting('emptyIfUnrecognized') !== 0;

    // If we are building a search index entry, or presenting search
    // results with a few matching words highlighted, don't display
    // the graph in a fancy way. Instead, use a simple theme that only
    // shows keywords extracted from the graph's attributes, nodes,
    // and edges.
    if ($this->viewMode === 'search_index' ||
        $this->viewMode === 'search_result') {
      // Add the graph name and description, maintained in their
      // original word order so that search can find them using phrases.
      $markup = '';
      $markup .= $graph->getBestName() . ' ';
      $markup .= $graph->getDescription() . ' ';

      // Assemble a list of graph, node, and edge attributes,
      // and row value keywords.  The returned arrays have
      // already split up multi-word text items into individual
      // keywords, removed punctuation, made them uniform case,
      // and sorted them.
      $keywords = array_merge(
        $graph->getAttributeKeywords(),
        $graph->getAllNodeKeywords(),
        $graph->getAllEdgeKeywords());

      // Sort the combined list and remove duplicates.
      // Create simple text from the resulting list.
      sort($keywords, (SORT_NATURAL | SORT_FLAG_CASE));
      $markup .= implode(' ', array_unique($keywords));

      return [
        '#theme'    => 'chart_suite_file_formatter_as_search_index',
        '#filename' => $file->getFilename(),
        '#markup'   => $markup,
      ];
    }

    // Otherwise, show the graph using HTML since GoogleCharts
    // does not support graphs.
    if ($emptyIfUnrecognized === TRUE) {
      return [];
    }

    return [
      '#theme'    => 'chart_suite_file_formatter_as_html',
      '#filename' => $file->getFilename(),
      '#uri'      => file_create_url($file->getFileUri()),
      '#filesize' => $file->getSize(),
      '#dolink'   => $doLink,
      '#markup'   => HTMLPresenter::encodeGraph($graph),
    ];
  }

}
