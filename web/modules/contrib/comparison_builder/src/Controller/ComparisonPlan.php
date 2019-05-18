<?php

namespace Drupal\comparison_builder\Controller;

use Drupal\Core\Controller\ControllerBase;
use \Symfony\Component\HttpFoundation\Response;
use Drupal\node\Entity\Node;
use Drupal\Core\Entity;

/**
 * An compare module ajax controller.
 */
class ComparisonPlan extends ControllerBase {

  /**
   * {@inheritdoc}
   */
  public function Render() {
    $head = explode(',', $_POST['head']);
    $node = explode(',', $_POST['node']);
    $fields_ajax = explode(',', $_POST['fields']);
    $curr_field_id = $_POST['curr_field_id'];
    $curr_field_value = $_POST['curr_field_value'];
    $block_id = $_POST['blockId'];
    $blockId = str_replace('node.type.', '', $block_id);
    //response table start
    $pre_node = array_filter($node);

    $pre_field_id = str_replace('select_id_', '', $curr_field_id);
    $query_for_nid_unset = db_query("SELECT nid FROM node_field_data WHERE title = '".$pre_field_id."' AND type =  '".$blockId."'");
    $result_nid_for_unset = $query_for_nid_unset->fetchAll();
    $unset_node = $result_nid_for_unset[0]->nid;
    if (($key = array_search($unset_node, $pre_node)) !== false) {
      $pre_node[$key] = $curr_field_value;
    }

      $headers = array();
      $row = array();
      $field_label = array();
      $values_field = array();
      $i = 1;

      $final_array = array();
      $group_field = array();
      
      foreach($pre_node as $value_node){
        $newRowKey = '';
        $node_data = Node::load($value_node);

        foreach ($fields_ajax as $main_field_value) {
          //for field grouping
          $node_type_grouping = \Drupal::entityManager()->getFieldDefinitions('node', $blockId);
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
        $newRowKey = $node_data->get('title')->get(0)->get('value')->getValue().'__'.$i;
        $rows[$newRowKey] = $values_field;
        $i++;

      }
      $main_group = array();
      foreach ($field_group as $group_key => $group_value) {
          $main_group[$group_value][] = $group_key;
      }
  
      $final = array();
      $newTitle = array();

      foreach($rows as $title => $row) {
        $newTitle = explode('__',$title);
        foreach($row as $f => $v) {
          $final[$f][0] = strtoupper($f);
          $final[$f][$title] = $v;
        }
        $header_main[$title] = $newTitle[0];
      }
      
$query_block_form_node = db_query("SELECT * FROM node_field_data WHERE type = '".str_replace('node.type.','',$block_id)."'");
    $result_node = $query_block_form_node->fetchAll();
    $nodes_content = array();
    $all_nodes = array();
    foreach($result_node as $nodeFields) {
      $nodes_content[$nodeFields->nid] = $nodeFields->title;
      $all_nodes[] = $nodeFields->nid;
    }
    $option_diff = array_diff($all_nodes,$pre_node);
    $options = '';
    $options .=  '<option value="">Choose</option>';
  foreach ($option_diff as $key_option => $value_option) {
     $query_for_title_option = db_query("SELECT title FROM node_field_data WHERE nid = '".$value_option."'");
    $result_title_for_option = $query_for_title_option->fetchAll();
    $option_node = $result_title_for_option[0]->title;

    $options .= '<option value="'.$value_option.'">'.$option_node.'</option>';
  }
 $main_field_js = implode(',', $fields_ajax);
 $nid_array_js = implode(',', $pre_node);
 $head_js = implode(',', $header_main); 
 $table_header = '';  
foreach ($header_main as $head_key => $head_value) {
    $table_header .= '<th>'.$head_value.' ';
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
          '#markup' => '<table>
            <thead>
              <th></th>
            '.$table_header.'
              </thead>

            <tbody>
             '.$rows_final_table.'
            </tbody>
          </table>',
          '#allowed_tags'=>['select','option','div','table','tr','td','th','tbody','thead']

        ];

        $form['files'] = array(
          '#attached' => array(
            'library' => array('comparison_builder/test-block'),
          ),
        );

    //response table end
    
    return new Response(render($form));
  }

}