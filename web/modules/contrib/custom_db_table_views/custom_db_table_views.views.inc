<?php

/**
 * Implements hook_views_data().
 */

  function custom_db_table_views_views_data() {
    $table_name = \Drupal::config('custom_db_table_views.settings')->get('db_table_name');
    $views_name = \Drupal::config('custom_db_table_views.settings')->get('db_views_name');
    $column_name = \Drupal::config('custom_db_table_views.settings')->get('db_cloumn_name');
    $reference_type = \Drupal::config('custom_db_table_views.settings')->get('db_reference_type');

    if ($table_name!='' && $views_name!='') {
      $data = array();
      $data[$table_name] = array();
      $data[$table_name]['table'] = array();
      $data[$table_name]['table']['group'] = t('Id unique value');
      $data[$table_name]['table']['provider'] = 'custom_db_table_views';
      $service = \Drupal::service('custom_db_table_views.queryresult');
      $table_structure = $service->custom_db_table($table_name);
      $primarykey= $service->custom_db_table_primary_key($table_name);
      $total_col =count($table_structure);
        if($primarykey) {
        $pri_key = $primarykey['0']->Column_name;
        } else {
        $pri_key = $table_structure[0]->Column_name;
        }


	  $data[$table_name]['table']['group'] = t($views_name);
      $data[$table_name]['table']['base'] = array(
      'field' => $pri_key,
      'title' => t($views_name),
      'help' => t('All fields of '.$table_name.'.'),
	  'weight' => -10,
      );

   if($reference_type!='') {
	   if($reference_type==0) {
		   $base_table = 'node_field_data';
		   $ref_field = $column_name;
		   $base_field = 'nid';
		   $type ='Node';
		} elseif($reference_type==1) {
		   $base_table = 'users_field_data';
		   $ref_field = $column_name;
		   $base_field = 'uid';
		   $type ='User';
		} else {
		   $base_table = 'taxonomy_term_field_data';
		   $ref_field = $column_name;
		   $base_field = 'tid';
		   $type ='Taxonomy';
		}

	   $data[$table_name][$base_field] = array(
		'title' => t('Custom '.$type.' Relation'),
		'help' => t('Relate custom content to the node content'),
		'relationship' => array(
		  'handler' => 'views_handler_relationship',
		  'base' => $base_table,
		  'base field' => $base_field,
		  'field' => $ref_field,
		  'id' => 'standard',
		  'label' => t('Custom '.$type.' Relation'),
		),
	  );
   }
  	  //Assign each filter and type for each rows
	  foreach($table_structure as $field_val){
	 		if(stristr($field_val->Type, "int")
			|| stristr($field_val->Type, "float")
			|| stristr($field_val->Type, "double")
			|| stristr($field_val->Type, "decimal")
			|| stristr($field_val->Type, "numeric") ){

		       $filter_handler = 'numeric';
			   $sort_handler = 'standard';
			   $field_handler = 'numeric';
			   $argument_handler = 'numeric';
			} elseif (stristr($field_val->Type, "char") || stristr($field_val->Type, "text")) {
	      $filter_handler = 'string';
			 $sort_handler = 'standard';
			 $field_handler = 'standard';
			 $argument_handler = 'string';
			} elseif (stristr($field_val->Type, "time") || stristr($field_val->Type, "date") || stristr($field_val->Type, "year")){
	            $filter_handler = 'date';
				$sort_handler = 'date';
				$field_handler = 'date';
				$argument_handler = 'date';
			} else{
              $filter_handler = 'standard';
			  $sort_handler = 'standard';
			  $field_handler = 'standard';
			  $argument_handler = 'standard';
			}
		if(($field_val->Field=='timestamp' && stristr($field_val->Type, "int"))
		     || ($field_val->Field=='created' && stristr($field_val->Type, "int"))
			 || ($field_val->Field=='changed' && stristr($field_val->Type, "int"))
			 || ($field_val->Field=='revision' && stristr($field_val->Type, "int"))) {
		        $filter_handler = 'date';
				$sort_handler = 'date';
				$field_handler = 'date';
				$argument_handler = 'date';

		}

         $col_names  = $field_val->Field;
		 $data[$table_name][$col_names] = array (
		 'title' => t($col_names.' field'),
		 'help' => t($col_names.' field.'),
		 'field' => array(
		 'id' =>$field_handler,
	      ),
		'sort' => array(
		  'id' => $sort_handler,),
		'filter' => array(
		  'id' => $filter_handler,),
		'argument' => array(
		  'id' => $argument_handler,),

		);
      }


      return $data;
    }
  }
