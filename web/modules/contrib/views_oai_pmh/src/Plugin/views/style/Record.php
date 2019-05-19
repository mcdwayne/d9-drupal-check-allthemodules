<?php

namespace Drupal\views_oai_pmh\Plugin\views\style;

use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\style\StylePluginBase;
use Drupal\views\ResultRow;
use Drupal\views_oai_pmh\Plugin\MetadataPrefixInterface;
use Drupal\views_oai_pmh\Plugin\MetadataPrefixManager;
use Drupal\views_oai_pmh\Service\FormatRowToXml;
use Drupal\views_oai_pmh\Service\Repository;
use Drupal\views_oai_pmh\Service\ValueConvertTrait;
use Drupal\views_oai_pmh\Service\Provider;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Serializer\Serializer;
use Picturae\OaiPmh\Implementation\Record as OAIRecord;
use Picturae\OaiPmh\Implementation\Record\Header;
use Picturae\OaiPmh\Implementation\MetadataFormatType;
use Drupal\Core\Cache\Cache;

/**
 * @ingroup views_style_plugins
 *
 * @ViewsStyle(
 *   id = "views_oai_pmh_record",
 *   title = @Translation("OAI-PMH"),
 *   help = @Translation("Displays rows in OAI-PMH records."),
 *   display_types = {"oai_pmh"}
 * )
 */
class Record extends StylePluginBase implements CacheableDependencyInterface {

  use ValueConvertTrait;

  protected $usesFields = TRUE;

  protected $usesOptions = TRUE;

  protected $usesRowClass = FALSE;

  protected $usesRowPlugin = FALSE;

  protected $rowToXml;

  protected $prefixManager;

  protected $metadataPrefix = [];

  protected $serializer;

  /**
   * @var \Drupal\views_oai_pmh\Plugin\views\display\OAIPMH
   */
  public $displayHandler;

  protected $repository;

  protected $provider;

  protected $pluginInstances = [];

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('views_oai_pmh.format_row_xml'),
      $container->get('plugin.manager.views_oai_pmh_prefix'),
      $container->get('serializer'),
      $container->get('views_oai_pmh.repository'),
      $container->get('views_oai_pmh.provider')
    );
  }

  /**
   * Record constructor.
   *
   * @param array $configuration
   * @param $plugin_id
   * @param $plugin_definition
   * @param \Drupal\views_oai_pmh\Service\FormatRowToXml $rowToXml
   * @param \Drupal\views_oai_pmh\Plugin\MetadataPrefixManager $prefixManager
   * @param \Symfony\Component\Serializer\Serializer $serializer
   * @param \Drupal\views_oai_pmh\Service\Repository $repository
   * @param \Drupal\views_oai_pmh\Service\Provider $provider
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, FormatRowToXml $rowToXml, MetadataPrefixManager $prefixManager, Serializer $serializer, Repository $repository, Provider $provider) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->rowToXml = $rowToXml;
    $this->prefixManager = $prefixManager;
    $this->serializer = $serializer;
    $this->repository = $repository;
    $this->provider = $provider;

    foreach ($prefixManager->getDefinitions() as $id => $plugin) {
      $this->metadataPrefix[$id] = $plugin;
    }
  }

  /**
   * @param $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $handlers = $this->displayHandler->getHandlers('field');
    if (empty($handlers)) {
      $form['error_markup'] = [
        '#markup' => '<div class="error messages">' . $this->t('You need at least one field before you can configure your table settings') . '</div>',
      ];
      return;
    }

    $formats = [];
    foreach ($this->metadataPrefix as $prefix_id => $prefix) {
      $formats[$prefix_id] = $prefix['label'];
    }

    $form['enabled_formats'] = [
      '#type' => 'checkboxes',
      '#title' => t('OAI-PMH metadata formats'),
      '#description' => t('Select the metadata format(s) that you wish to publish. Note that the Dublin Core format must remain enabled as it is required by the OAI-PMH standard.'),
      '#default_value' => $this->options['enabled_formats'],
      '#options' => $formats,
    ];

    $form['metadata_prefix'] = [
      '#type' => 'fieldset',
      '#title' => t('Metadata prefixes'),
    ];

    $field_labels = $this->displayHandler->getFieldLabels();
    foreach ($this->metadataPrefix as $prefix_id => $prefix) {
      $form['metadata_prefix'][$prefix_id] = [
        '#type' => 'textfield',
        '#title' => $prefix['label'],
        '#default_value' => $this->options['metadata_prefix'][$prefix_id] ? $this->options['metadata_prefix'][$prefix_id] : $prefix['prefix'],
        '#required' => TRUE,
        '#size' => 16,
        '#maxlength' => 32,
      ];
      $form['field_mappings'][$prefix_id] = [
        '#type' => 'fieldset',
        '#title' => t('Field mappings for <em>@format</em>', ['@format' => $prefix['label']]),
        '#theme' => 'views_oai_pmh_field_mappings_form',
        '#states' => [
          'visible' => [
            ':input[name="style_options[enabled_formats][' . $prefix_id . ']"]' => ['checked' => TRUE],
          ],
        ],
      ];

      $prefixPlugin = $this->getInstancePlugin($prefix_id);
      foreach ($this->displayHandler->getOption('fields') as $field_name => $field) {
        $form['field_mappings'][$prefix_id][$field_name] = [
          '#type' => 'select',
          '#options' => $prefixPlugin->getElements(),
          '#default_value' => !empty($this->options['field_mappings'][$prefix_id][$field_name]) ? $this->options['field_mappings'][$prefix_id][$field_name] : '',
          '#title' => $field_labels[$field_name],
        ];
      }
    }

  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $rows = $this->getResultRows();

    /** @var \Drupal\views_oai_pmh\Plugin\MetadataPrefixInterface $currentPrefixPlugin */
    $currentPrefixPlugin = $this->prefixManager->createInstance(
      $this->displayHandler->getCurrentMetadataPrefix()
    );

    $records = [];
    foreach ($rows as $row_id => $row) {
      $this->rowToXml->resetTagsPrefixedWith0();
      $data = $currentPrefixPlugin->getRootNodeAttributes() + $this->rowToXml->transform($row);

      // path id for datacite, dcc or dc
      $path_id = (!empty($data['identifier']))? $data['identifier']['#'] : $data['dc:identifier']['#'];

      $xmlDoc = new \DOMDocument();
      $xmlDoc->loadXML($this->serializer->encode($data, 'xml', [
        'xml_root_node_name' => $currentPrefixPlugin->getRootNodeName(),
      ]));

      // Set xml format to $serializer attribute
      $xml = <<<XML
      <record>
      <descriptions>
      <description subscheme='CTI'>lala</description>
      <description>wawa</description>
      <description>cupi</description>
      </descriptions>
      </record>
XML;
      $a = $this->serializer->decode($xml, 'xml');
      $header = new Header($this->getIdentifier($path_id), new \DateTime());
      $records[$row_id] = new OAIRecord($header, $xmlDoc);
    }

    $formats = [];
    foreach ($this->options['enabled_formats'] as $format) {
      $plugin = $this->getInstancePlugin($format);
      $formats[] = new MetadataFormatType(
        $format,
        $plugin->getSchema(),
        $plugin->getNamespace()
      );
    }

    $this->repository->setRecords($records);

    if ($pager = $this->view->pager->hasMoreRecords()) {
      $this->repository->setOffset($this->view->getCurrentPage() + 1);
      $this->repository->setTotalRecords($this->view->total_rows);
    }

    $this->repository->setMetadataFormats($formats);

    return $this->provider;
  }

  /**
   * The identifier for record header
   *
   * @param $id
   *
   * @return string
   * format: oai:domain/nid
   */
  protected function getIdentifier($id) {
    $path = "";
    if(strpos($id,'https://') !== false) {
      $path = str_replace("https://", "oai:", $id);
    } else  if(strpos($id,'http://') !== false) {
      $path = str_replace("http://", "oai:", $id);
    }
    return $path;
  }

  /**
   * Get result that view expose as cartesian product removing duplicates tuples
   *
   * @return array
   */
  protected function getResultRows(): array {
    $rows = [];
    foreach ($this->view->result as $row_id => $row) {
      $this->view->row_index = $row_id;
      $item = $this->populateRow($row);
      $id = $row->_entity->id();

      if (key_exists($id, $rows)) {
        $rows[$id] = array_merge_recursive($rows[$id], $item);
      }
      else {
        $rows[$id] = $item;
      }
    }
    $rows = $this->removeDuplicates($rows);

    return $rows;
  }

  /**
   * Remove all duplicate rows for array considering array keys and key brothers
   * @todo refactor this function to more elegant and faster
   *
   * @param $array
   * @return array
   */
  protected function removeDuplicates($array) {
    $output = [];
    foreach ($array As $key => $value) {
      foreach ($value As $key_i => $value_i) {
        $value_old = $value_i[0];
        $all_equal = true;
        if(is_array(($value_i))) {
          for($j =0; $j < count($value_i); $j++){
            if ($value_i[$j] !== $value_old) {
              $all_equal = false;
              break;
            }
            $value_old = $value_i[$j];
          }
        } else {
          $value_old = $value_i;
        }

        $delimiter = '';
        $key_delimiter = '';
        if(strpos($key_i, '>') !== false){ //datacite
          $delimiter = '>';
          $key_delimiter = '@';
        } else if( strpos($key_i, 'dc') !== false ) { //dcc
          $delimiter = '@';
        }

        $brothers = $this->getBrothersKey($key_i, $value, $delimiter, $key_delimiter);

        if($all_equal && empty($brothers)) { // all values equals and without brother(s)
          $output[$key][$key_i] = $value_old;

        } else if(!$all_equal && empty($brothers)) { // not all values are equals and without brother(s) for dc, dcc cases
          for($j =0; $j < count($value_i); $j++){
            if (!in_array($value_i[$j], $output[$key][$key_i])) {
              $output[$key][$key_i][] = $value_i[$j];
            }
          }

        } else { // has brother key
          $tuples = [];
          $m = 0;
          $nChildren = count($brothers[0][key($brothers[0])]);
          for ($k = 0; $k < $nChildren; $k++) {
            $tuple = "";
            for ($l = 0; $l < count($brothers); $l++) {
              if(is_array($brothers[$l][key($brothers[$l])])){
                $tuple = $tuple . $brothers[$l][key($brothers[$l])][$m];
              } else {
                $tuple = $tuple . $brothers[$l][key($brothers[$l])];
              }
            }
            if(!in_array($tuple, $tuples)) {
              if(is_array($array[$key][$key_i])){
                $output[$key][$key_i][] = $array[$key][$key_i][$m];
              } else {
                $output[$key][$key_i][] = $array[$key][$key_i];
              }
            }
            $m++;
            $tuples[] = $tuple;
          }
        }
      }
    }

    return $output;
  }

  /**
   * Get all brothers for a key in an array
   *
   * @param $key
   * @param $array
   * @param $delimiter
   *  e.g. >
   * @param $key_delimiter
   *  e.g. @
   *
   * @return array
   */
  public function getBrothersKey($key, $array, $delimiter, $key_delimiter) {
    $parts = explode($delimiter, $key);
    $sub_key = "";
    $output = [];

    if($delimiter === ">") { // datacite
      if(count($parts) <= 1) {
        return $output;
      }
      if(count($parts) < 3 || strpos($key, $key_delimiter) !== false) {
        return $output;
      }
      // Compose family key (sub_key)
      for($i = 0; $i < count($parts)-1; $i++){
        if($sub_key === ""){
          $sub_key = $parts[$i];
        } else {
          $sub_key = $sub_key . $delimiter . $parts[$i];
        }
      }
    } else { // dcc
      // Only one sub_key
      $sub_key = $parts[0];
    }

    foreach ($array as $key => $value){
      if (strpos($key, $sub_key) !== false) {
        $output[] = [$key => $value];
      }
    }

    // Remove if has only one value
    if(count($output) === 1) {
      $output = [];
    }

    return $output;
  }

  /**
   * Return a formatted row value in array with all values as cartesian product of rows
   *
   * @param ResultRow $row
   *
   * @return array
   *
   */
  protected function populateRow(ResultRow $row): array {
    $output = [];

    foreach ($this->view->field as $id => $field) {
      try {
        $value = $field->getValue($row);
        if($field->options['type'] == "datetime_default") {
          $value = \Drupal::service('date.formatter')->format(
            strtotime($value), $field->options['settings']['format_type']
          );
        }
      }
      catch (\TypeError $e) {
        // If relations are NULL's.
        $value = false;
      }
      if (($alias = $this->getFieldKeyAlias($id)) && $value) {
        if (array_key_exists($alias, $output)) {
          $output[$alias] = $this->convert($output[$alias], $value);
        }
        else {
          $output[$alias] = $value;
        }
      }

    }

    return $output;
  }

  /**
   * Get alias for a key in fields list
   *
   * @param $id
   *
   * @return array|null
   */
  protected function getFieldKeyAlias($id) {
    $fields = $this->options['field_mappings'][$this->displayHandler->getCurrentMetadataPrefix()];

    if (isset($fields) && isset($fields[$id]) && $fields[$id] !== 'none') {
      return $fields[$id];
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return Cache::PERMANENT;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return ['request_format'];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return ['views_oai_pmh'];
  }

  /**
   * Get plugin entity for some plugin id
   *
   * @param $plugin_id
   *
   * @return \Drupal\views_oai_pmh\Plugin\MetadataPrefixInterface
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  protected function getInstancePlugin($plugin_id): MetadataPrefixInterface {
    if (isset($this->pluginInstances[$plugin_id])) {
      return $this->pluginInstances[$plugin_id];
    }
    $this->pluginInstances[$plugin_id] = $this->prefixManager->createInstance($plugin_id);

    return $this->pluginInstances[$plugin_id];
  }

}
