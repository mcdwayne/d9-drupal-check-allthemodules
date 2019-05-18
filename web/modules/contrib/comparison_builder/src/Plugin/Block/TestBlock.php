<?php
namespace Drupal\comparison_builder\Plugin\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityViewBuilderInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;
use Drupal\Core\Entity;
use Drupal\Core\Field;
use Drupal\Core\Config;
/**
 * Provides a 'TestBlock' block plugin.
 *
 * @Block(
 *   id = "test_block",
 *   admin_label = @Translation("Test block"),
 *   deriver = "Drupal\comparison_builder\Plugin\Derivative\TestBlock"
 * )
 */
class TestBlock extends BlockBase implements ContainerFactoryPluginInterface {
  /**
   * @var EntityViewBuilderInterface.
   */
  private $viewBuilder;
  /**
   * @var NodeInterface.
   */
  private $node;
  private $block_id;
  /**
   * Creates a TestBlock instance.
   *
   * @param array $configuration
   * @param string $plugin_id
   * @param mixed $plugin_definition
   * @param EntityManagerInterface $entity_manager
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityManagerInterface $entity_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->viewBuilder = $entity_manager->getViewBuilder('node');
    $this->nodeStorage = $entity_manager->getStorage('node');
    $this->node = $entity_manager->getStorage('node')->load($this->getDerivativeId());
  }
  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity.manager')
    );
  }
  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);

    $config = $this->getConfiguration();

    $block_id = $this->getDerivativeId();

    //start field label
  $bundle = str_replace('node.type.', '', $block_id);
  foreach (\Drupal::entityManager()->getFieldDefinitions('node', $bundle) as $field_name => $field_definition) {
    if (!empty($field_definition->getTargetBundle())) {
       $bundleFields[$field_name] = $field_definition->getLabel();
    }
  }

  
    $form['content_type_compare_fields'] = [
      '#type' => 'checkboxes',
      '#title' => t("Content types Fields"),
      '#options' => $bundleFields,
      '#default_value' => isset($config['content_type_compare_fields']['content_type_compare_fields']) ? $config['content_type_compare_fields']['content_type_compare_fields'] : '',
    ];
    //end field label

    //start nodes
    $query_block_form_node = db_query("SELECT * FROM node_field_data WHERE type = '".str_replace('node.type.','',$block_id)."'");
    $result_node = $query_block_form_node->fetchAll();
    $nodes_content = array();
    foreach($result_node as $nodeFields) {
      $nodes_content[$nodeFields->nid] = $nodeFields->title; 
    }
 
    $form['content_type_compare_nodes'] = [
      '#type' => 'checkboxes',
      '#title' => t("Content types nodes"),
      '#options' => $nodes_content,
      '#default_value' => isset($config['content_type_compare_nodes']['content_type_compare_nodes']) ? $config['content_type_compare_nodes']['content_type_compare_nodes'] : '',
    ];
    //end nodes
    return $form;
  }
  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);
    $values = $form_state->getValues();
    $this->configuration['content_type_compare_fields'] = array_filter($form_state->getValues('content_type_compare_fields'));
     unset($this->configuration['content_type_compare_fields']['content_type_compare_nodes']);

    $this->configuration['content_type_compare_nodes'] = array_filter($form_state->getValues('content_type_compare_nodes'));
     unset($this->configuration['content_type_compare_nodes']['content_type_compare_fields']);
  }
  /**
   * {@inheritdoc}
   */
  public function build() {
    $block_id = $this->getDerivativeId();

    $config = $this->getConfiguration();

    $nid_array[] = $config['content_type_compare_nodes']['content_type_compare_nodes'];
      
    $field_array[] = $config['content_type_compare_fields']['content_type_compare_fields'];
      $rows = array();
      $values_field = array();
      $field_group = array();
      $field_label = array();
      $i = 1;
    
      foreach(array_filter($nid_array[0]) as $value_node){
        $node_data = Node::load($value_node);
       
    
        foreach (array_filter($field_array[0]) as $main_field_value) {
          //for field grouping
          $bundle_type = str_replace('node.type.', '', $block_id);
          $node_type_grouping = \Drupal::entityManager()->getFieldDefinitions('node', $bundle_type);
          if(!empty($node_type_grouping )){
            if(!empty($main_field_value)){
              if(!empty($node_type_grouping[$main_field_value])){
                $field_label = $node_type_grouping[$main_field_value]->getLabel();
                $field_group[$field_label] = $node_type_grouping[$main_field_value]->getThirdPartySetting('comparison_builder', 'field_group_name');
              }
          //field grouping end
          $values_field[$field_label] = $node_data->get($main_field_value)->get(0)->get('value')->getValue();
            }
          }
          
        }
        $rows[$node_data->get('title')->get(0)->get('value')->getValue()] = $values_field;
        $i++;

      }
      $main_group = array();
      foreach ($field_group as $group_key => $group_value) {
          $main_group[$group_value][] = $group_key;
      }

      $final = array();
      $header_main = array();
      foreach($rows as $title => $row) {

        foreach($row as $f => $v) {
          $final[$f][0] = strtoupper($f);
          $final[$f][$title] = $v;
        }

        $header_main[$title] = $title;

      }
      
$query_block_form_node = db_query("SELECT * FROM node_field_data WHERE type = '".str_replace('node.type.','',$block_id)."'");
    $result_node = $query_block_form_node->fetchAll();
    $nodes_content = array();
    foreach($result_node as $nodeFields) {
      $nodes_content[$nodeFields->nid] = $nodeFields->title; 
    }
    $options = '';
    $options .=  '<option value="">Choose</option>';
  foreach ($nodes_content as $key_head => $key_value) {
    if(($key_option = array_search($key_head, $nid_array[0])) == false){
    $options .= '<option value="'.$key_head.'">'.$key_value.'</option>';
    }
  }
 $main_field_js = implode(',', $field_array[0]);
 $nid_array_js = implode(',', $nid_array[0]);
 $head_js = implode(',', $header_main);
 $table_header = '';   
foreach ($header_main as $head_key => $head_value) {
    $table_header .= '<th>'.$head_key.' ';
    $table_header .= '<select class="plan_select" head = "'.$head_js.'" nodes = "'.$nid_array_js.'" fields = "'.$main_field_js.'" id="select_id_'.$head_key.'" blockId = "'.$block_id.'" >';
    $table_header .= $options;
    $table_header .= '</select></th>';

}
$rows_final_table = '';
//for colspan rows
$grouing_array_row = array();
foreach ($main_group as $g_key => $g_value) {
  foreach ($g_value as $gr_key => $gr_value) {
    foreach ($final as $grf_key => $grf_value) {
      if($grf_key == $gr_value){
        $grouing_array_row[$g_key][] = $grf_value;
      }
    }
  }
}


$count_cols = count($header_main);
$col_span = $count_cols+1;

foreach ($grouing_array_row as $fkey => $fvalue) {
    $rows_final_table .= '<tr><td colspan="'.$col_span.'"><strong>'.$fkey.'</strong></td></tr>';
    foreach ($fvalue as $rkey => $rvalue) {
      $rows_final_table .= '<tr>';
      foreach ($rvalue as $gkey => $gvalue) {
        $rows_final_table .= '<td>'.$gvalue.'</td>';
      }
        $rows_final_table .= '</tr>';
    }
    
  
}
//end rows

        $form['table_markup'] = [
          '#type' => 'markup',
          '#markup' => '<div class="myTable"><table>
            <thead>
              <th></th>
            '.$table_header.'
              </thead>

            <tbody>
             '.$rows_final_table.'
            </tbody>
          </table></div>',
          '#allowed_tags'=>['select','option','div','table','tr','td','th','tbody','thead']

        ];

        $form['files'] = array(
          '#attached' => array(
            'library' => array('comparison_builder/test-block'),
          ),
        );
    return $form;
  }
  
  /**
   * {@inheritdoc}
   */
  /*public function blockAccess(AccountInterface $account, $return_as_object = FALSE) {
    return $this->node->access('view', NULL, TRUE);
  }*/
}