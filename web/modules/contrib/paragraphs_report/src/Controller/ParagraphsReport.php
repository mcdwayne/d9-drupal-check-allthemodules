<?php

/**
 * Main Paragraphs Report controller class.
 *
 * @file
 * Contains paragraphs_report.batch.inc.
 */

namespace Drupal\paragraphs_report\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\Xss;
use Drupal\paragraphs\Entity\Paragraph;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * paragraphs_report methods
 */
class ParagraphsReport extends ControllerBase {

  /**
   * Constructs the controller object.
   */
  public function __construct() {
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static();
  }


  // B A T C H - - - - - - - - - - - - - - - - - - - - - - - - //

  /**
   * Batch API starting point.
   *
   * @todo update logic to check for sub-components (nth level paragraphs)
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   * @throws
   */
  public function batchRunReport() {
    // Get all nodes to process.
    $nids = $this->getNodes();
    // Put nodes into batches.
    $batch = $this->batchPrep($nids);
    // Start batch api process.
    batch_set($batch);
    // Redirect page and display message on completion.
    return batch_process('/admin/reports/paragraphs-report');
  }

  /**
   * Setup batch array var
   *
   * @param array $nids
   * @return array of batches ready to run
   */
  function batchPrep($nids = []) {
    $moduleConfig = \Drupal::config('paragraphs_report.settings');
    $totalRows = count($nids);
    $rowsPerBatch = $moduleConfig->get('import_rows_per_batch') ?: 10;
    $batchesPerImport = ceil($totalRows / $rowsPerBatch);
    // Put x-amount of rows into operations array slots.
    $operations = [];
    for($i=0; $i<$batchesPerImport; $i++) {
      $offset = ($i==0) ? 0 : $rowsPerBatch*$i;
      $batchNids = array_slice($nids, $offset, $rowsPerBatch);
      $operations[] = ['batchGetParaFields', [$batchNids]];
    }
    $n = null;
    // Full batch array.
    $batch = [
      'init_message' => t('Executing a batch...'),
      'progress_message' => t('Operation @current out of @total batches, @perBatch per batch.',
        ['@perBatch' => $rowsPerBatch]
      ),
      'progressive' => TRUE,
      'error_message' => t('Batch failed.'),
      'operations' => $operations,
      'finished' => 'batchSave',
      'file' => drupal_get_path('module', 'paragraphs_report') . '/paragraphs_report.batch.inc',
    ];
    return $batch;
  }


  // L O O K U P S - - - - - - - - - - - - - - - - - - - - - - - //


  /**
   * Get paragraph fields from a bundle/type.
   *
   * Example: node/page
   *
   * @param string $bundle
   * @param string $type
   *
   * @return array
   */
  public function getParaFieldsOnType($bundle = '', $type = '') {
    $entityManager = \Drupal::service('entity_field.manager');
    $paraFields = [];
    $fields = $entityManager->getFieldDefinitions($bundle, $type); // node, hero_cta
    foreach($fields as $field_name => $field_definition) {
      if (!empty($field_definition->getTargetBundle()) && $field_definition->getSetting('target_type') == 'paragraph') {
        $paraFields[] = $field_name;
      }
    }
    return $paraFields;
  }

  /**
   * Get paragraph fields for selected content types.
   *
   * @return array of paragraph fields by content type key
   */
  public function getParaFieldDefinitions() {
    $entityManager = \Drupal::service('entity_field.manager');
    $contentTypes = $this->getTypes();
    // then loop through the fields for chosen content types to get paragraph fields
    $paraFields = []; // content_type[] = field_name
    foreach($contentTypes as $contentType) {
      $fields = $entityManager->getFieldDefinitions('node', $contentType);
      foreach($fields as $field_name => $field_definition) {
        if (!empty($field_definition->getTargetBundle()) && $field_definition->getSetting('target_type') == 'paragraph') {
          $paraFields[$contentType][] = $field_name;
        }
      }
    }
    return $paraFields;
  }

  /**
   * Get list of content types chosen from settings.
   *
   * @return array
   */
  public function getTypes() {
    $moduleConfig = \Drupal::config('paragraphs_report.settings');
    $types = array_filter($moduleConfig->get('content_types'));
    return $types;
  }

  /**
   * Query db for nodes to check for paragraphs.
   *
   * @return array of nids to check for para fields.
   */
  public function getNodes() {
    $moduleConfig = \Drupal::config('paragraphs_report.settings');
    $contentTypes = array_filter($moduleConfig->get('content_types'));
    // Load all nodes of type
    $query = \Drupal::entityQuery('node')
      ->condition('type', $contentTypes, 'IN');
    $nids = $query->execute();
    return $nids;
  }

  /**
   * Pass node id, return paragraphs report data as array.
   *
   * @param $nid
   * @param $current array of paras to append new ones to
   *
   * @return array
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  public function getParasFromNid($nid = '', $current = []) {
    // Pass any current array items into array.
    $arr = $current;
    $node = \Drupal::entityTypeManager()->getStorage('node')->load($nid);
    // @todo review how to get an alias when this is called from hook_node_insert
    //$alias = $node->toUrl()->toString();
    // Get and loop through first level paragraph fields on the node.
    $paraFields = $this->getParaFieldsOnType('node', $node->bundle());
    foreach($paraFields as $paraField) {
      // Get paragraph values (target_ids).
      $paras = $node->get($paraField)->getValue();
      foreach($paras as $para) {
        // Load paragraph from target_id.
        $p = Paragraph::load($para['target_id']);
        // Add paragraph to report array
        $arr[$p->bundle()]['top'][] = $nid;
        // Check if the top level paragraph has sub-paragraph fields.
        $arr = $this->getParaSubFields($node, $p, $arr);
      }
    }
    return $arr;
  }

  /**
   * Helper recursive method to find embedded paragraphs
   * Send a paragraph, check fields for sub-paragraph fields recursively.
   *
   * @return array of paragraph values
   */
  public function getParaSubFields($node, $paragraph, $reports) {
    $alias = $node->toUrl()->toString();
    $entityManager = \Drupal::service('entity_field.manager');
    // Get fields on paragraph and check field type.
    $fields = $entityManager->getFieldDefinitions('paragraph', $paragraph->bundle());
    foreach ($fields as $field_name => $field_definition) {
      // Is this field a paragraph type?
      if (!empty($field_definition->getTargetBundle()) && $field_definition->getSetting('target_type') == 'paragraph') {
        // Get paragraphs on this field.
        $paras = $paragraph->get($field_name)->getValue();
        foreach ($paras as $para) {
          $p = Paragraph::load($para['target_id']);
          // If yes, add this field to report and check for more sub-fields.
          // arr[main component][parent] = alias of node
          $reports[$p->bundle()][$paragraph->bundle()][] = $node->id();
          $reports = $this->getParaSubFields($node, $p, $reports);
        }
      }
    }
    return $reports;
  }

  /**
   * Get a list of the paragraph components and return as lookup array.
   *
   * @return array of machine name => label
   */
  public function getParaTypes(){
    $paras = paragraphs_type_get_types();
    $names = [];
    foreach($paras as $machine => $obj) {
      $names[$machine] = $obj->label();
    }
    return $names;
  }


  // E D I T - C O N F I G - - - - - - - - - - - - - - - - - - //


  /**
   * Pass array of path data to save for the report.
   *
   * @param array $arr of paragraph->parent->path data.
   */
  public function configSaveReport($arr = []) {
    $moduleConfig = \Drupal::service('config.factory')->getEditable('paragraphs_report.settings');
    $json = Json::encode($arr);
    $moduleConfig->set('report', $json)->save();
  }

  /**
   * Remove a node path from report data.
   *
   * @param string $removeNid to remove from report data
   * @return array updated encoded data.
   */
  public function configRemoveNode($removeNid = '') {
    $moduleConfig = \Drupal::config('paragraphs_report.settings');
    $json = Json::decode($moduleConfig->get('report'));
    // force type to be array
    $json = is_array($json) ? $json : [];
    // Search for nid and remove from array.
    // remove item from array
    $new = [];
    foreach($json as $para => $sets) {
      foreach($sets as $parent => $nids) {
        // remove nid from array
        $tmp = [];
        foreach($nids as $nid) {
          if($nid != $removeNid) {
            $tmp[] = $nid;
          }
        }
        $new[$para][$parent] = $tmp;
      }
    }
    // Save updated array.
    $this->configSaveReport($new);
    return $new;
  }



  // R E P O R T - - - - - - - - - - - - - - - - - - - - - - - //


  /**
   * Build quick paragraphs type drop down form.
   *
   * @return string
   */
  public function filterForm() {
    // Build filter form.
    // Check and set filters
    $paraNames = $this->getParaTypes();
    $current_path = \Drupal::service('path.current')->getPath();
    $filterForm = '<form method="get" action="' . $current_path . '">';
    $filterForm .= 'Filter by Type: <select name="ptype">';
    $filterForm .= '<option value="">All</option>';
    foreach ($paraNames as $machine => $label) {
      $selected = isset($_GET['ptype']) && $_GET['ptype'] == $machine ? ' selected' : '';
      $filterForm .= '<option name="' . $machine . '" value="' . $machine . '"' . $selected . '>' . $label . '</option>';
    }
    $filterForm .= '</select> <input type="submit" value="Go"></form><br>';
    return $filterForm;
  }

  /**
   * Format the stored JSON config var into a rendered table.
   *
   * @param array $json
   * @return array
   */
  public function formatTable($json = []) {
    $paraNames = $this->getParaTypes();
    // get filter
    $filter = isset($_GET['ptype']) ? trim($_GET['ptype']) : '';
    // get paragraphs label info, translate machine name to label
    // loop results into the table
    $total = 0;
    $rows = [];
    if(!empty($json)) {
      foreach($json as $name => $set) {
        // skip if we are filtering out all but one
        if(!empty($filter) && $filter != $name) {
          continue;
        }
        // be mindful of the parent field
        foreach($set as $parent => $nids) {
          // turn duplicates into counts
          if(!empty($nids)) {
            $counts = array_count_values($nids);
            foreach($counts as $nid => $count) {
              $alias = \Drupal::service('path.alias_manager')->getAliasByPath('/node/'.$nid);
              $link = t('<a href="@alias">@alias</a>',['@alias' => $alias]);
              $label = $paraNames[$name];
              $rows[] = [$label, $parent, $link, $count];
              $total++;
            }
          }
        }
      }
    }
    $header = [
      $this->t('Paragraph'),
      $this->t('Parent'),
      $this->t('Path'),
      $this->t('Count')
    ];
    // Setup pager.
    $per_page = 10;
    $current_page = pager_default_initialize($total, $per_page);
    // split array into page sized chunks, if not empty
    $chunks = !empty($rows) ? array_chunk($rows, $per_page, TRUE) : 0;
    // Output
    $table['table'] = [
      '#type' => 'table',
      '#title' => $this->t('Paragraphs Report'),
      '#header' => $header,
      '#sticky' => TRUE,
      '#rows' => $chunks[$current_page],
      '#empty' => $this->t('No components found. You may need to run the report.')
    ];
    $table['pager'] = array(
      '#type' => 'pager'
    );
    return $table;
  }

  /**
   * Return a rendered table ready for output.
   *
   * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
   */
  public function showReport() {
    $moduleConfig = \Drupal::config('paragraphs_report.settings');
    // Build report from stored JSON in module config.
    $btn['run_button'] = [
      '#type' => 'markup',
      '#markup' => t('<div style="float:right"><a class="button" href="/admin/reports/paragraphs-report/update" onclick="return confirm(\'Update the report data with current node info?\')">Update Report Data</a></div>')
    ];
    $json = Json::decode($moduleConfig->get('report'));
    $json = is_array($json) ? $json : []; // force type to be array
    $filters = [];
    $filters['filter'] = [
      '#type' => 'markup',
      '#markup' => $this->filterForm(),
      '#allowed_tags' => array_merge(Xss::getHtmlTagList(), ['form', 'option', 'select', 'input', 'br'])
    ];
    $table = $this->formatTable($json);
    return [
      $btn,
      $filters,
      $table
    ];
  }

}
