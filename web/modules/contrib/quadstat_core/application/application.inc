<?php

use \Drupal\Core\Url;
use \Drupal\Core\Path\PathMatcher;

/**
 * Tell Drupal that we have an additional validation function.
 */
function quadstat_core_form_alter(array &$form, \Drupal\Core\Form\FormStateInterface $form_state, $form_id) {
  // Validate R request
  if (strpos($form_id, 'webform_submission_') !== FALSE) {
    $form['actions']['submit']['#validate'][] = 'quadstat_core_application_form_validate';
  }

  $user = \Drupal::currentUser();
  $current_path = \Drupal::service('path.current')->getPath();

  if ($form_id == 'views_exposed_form' && ($form['#id'] == 'views-exposed-form-my-datasets-block-1' || $form['#id'] == 'views-exposed-form-dataset-block-1')) {
    $nodes = db_select('node_field_data', 'n')->fields('n')->condition('type', 'dataset')->condition('n.uid', array($user->id(), 1),'IN')->orderby('title', 'ASC')->execute(); 
    $options = array('' => '- Select Curated Dataset -');
    while($rec = $nodes->fetchAssoc()) {
      $options[$rec['title']] = substr($rec['title'], 0, 25);
    }
    $form['dataset_selected'] = array(
       '#type' => 'select',
       '#options' => $options,
       '#default_value' => '',
    );
    if ($form['#id'] == 'views-exposed-form-dataset-block-1') {
      $form['application_dataset_edit'] = array(
         '#type' => 'button',
         '#value' => 'View/Edit Entire Dataset',
      );
    }

    $nodes = db_select('node_field_data', 'n')->fields('n')->condition('type', 'dataset')->condition('n.uid', $user->id(),'=')->orderby('title', 'ASC')->execute();
    $options = array('' => '- Select Custom Dataset -');
    while($rec = $nodes->fetchAssoc()) {
      $options[$rec['title']] = substr($rec['title'], 0, 25);
    }
    $form['dataset_selected_custom'] = array(
       '#type' => 'select',
       '#options' => $options,
       '#default_value' => '',
    );
    $form['dataset_last_selected'] = array(
       '#type' => 'hidden',
       '#default_value' => 'curated',
    );
  }

  if ($form_id == 'views_exposed_form' && $form['#id'] == 'views-exposed-form-applications-block-1') {
    $nodes = db_select('node_field_data', 'n')->fields('n')->condition('type', 'application')->orderby('title','ASC')->execute();
    $options = array('' => '- Select App -');
    while($rec = $nodes->fetchAssoc()) {
      $options[$rec['title']] = $rec['title'];
    }
    $form['application_selected'] = array(
       '#type' => 'select',
       '#options' => $options,
       '#default_value' => '',
    );
  }
}

/**
 * Execute the command from the webform
 */
function quadstat_core_application_form_validate(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
  // Get the values the user submitted
  $user = \Drupal::currentUser();
  $form_input = $form_state->getValues();
  $dataset_setup_cmd = _quadstat_core_setup_dataset($form, $form_state, $form_input);
  if(!isset($form_input['r'])) {
    // This is not an R application
    return;
  }
  $command = $form_input['r'];
  $fid = $form_input['x'];
  $file = \Drupal\file\Entity\File::load($fid);
  // dl stands for Dollar Literal (do not replace dollar signs with PHP variables because R is going to use them) 
  if(!isset($form_input['dl'])) {
    // Replace each variable with the user input
    foreach($form_input as $key => $input) {
      if(is_string($key) && is_string($form_input[$key])) {
        $command = str_replace('$' . $key, $form_input[$key], $command);
      }
    }
  }
  $identifier = _quadstat_core_get_identifier();
  if(array_key_exists('image', $form_input)) {
    // User wants an image
    $img_setup = _quadstat_core_setup_img($form, $form_state, $identifier);
    // $img_save has dev.off()
    $img_save = _quadstat_core_save_img($form, $form_state, $identifier);
  } else {
    // No image will be generated
    $img_setup = $img_save = '';
  }
  $command = $dataset_setup_cmd . $img_setup . $command .$img_save;
  _quadstat_core_execute_r($command, $identifier, TRUE);
  $tmp_path = \Drupal::service('file_system')->realpath('temporary://');
  // Let user know of any errors or warnings while processing dataset request
  if(is_file($error_file = "$tmp_path/application-$identifier.err") && filesize($error_file) > 0) {
    drupal_set_message(file_get_contents($error_file), 'error');
  }
  if(is_file($warn_file = "$tmp_path/application-$identifier.warn") && filesize($warn_file) > 0) {
    drupal_set_message(file_get_contents($warn_file), 'warning');
  }
  $output_file = "$tmp_path/application-$identifier.Rout";
  // Remove the trailing lines
  $lines = file($output_file); 
  $last = sizeof($lines); 
  $i = 0;
  $max = 3;
  if(array_key_exists('image', $form_input)) {
    $max = 6;
  }
  while($i < $max) {
    array_pop($lines);
    $i++;
  }
  $output = implode('', $lines);
  if ($file) {
    // replace absolute path to dataset with url
    $form_input['r'] = preg_replace("/\"\/var(.*)\.csv\"/", '"' . file_create_url($file->get('uri')->get(0)->value) . '"', $command);
   } else {
    // dataset
    $form_input['r'] = $command;
  }
  $form_input['output'] = $output;
  $data = false;
  $i = 1;
  $im_command = "cd $tmp_path; convert ";
  $found_img = FALSE;
  // Cycle through all the images, we are going to append them together into a single image
  while (is_file("$tmp_path/application-$identifier-$i.png")) {
    $im_command .= "application-$identifier-$i.png ";
    $found_img = TRUE;
    $i++;
  }
  // Check for ggplots
  $ggplot = FALSE;
  if (is_file("$tmp_path/ggplot.png")) {
    $im_command .= "ggplot.png  ";
    // $i keeps track of how many images we have
    $i++;
    $found_img = TRUE;
    $ggplot = TRUE;
  }
  if($found_img) {
    // execute the command
    $im_command .= "-append application-$identifier.png";
    if($ggplot) {
      system("cd /tmp;convert -resize 455x455 ggplot.png ggplot.png;");
    }
    system($im_command);
    if (is_file("$tmp_path/ggplot.png")) {
      unlink("$tmp_path/ggplot.png");
    }
  }
  // Now save the file to the Drupal database
  $data = file_get_contents("$tmp_path/application-$identifier.png");
  if ($data) {
    $username = $user->getAccountName();
    if($username == '') {
      $username = 'anonymous';
    }
    $png_dir = "private://webform/plot/$username";
    if(!is_dir(\Drupal::service('file_system')->realpath($png_dir))) {
      \Drupal::service('file_system')->mkdir($png_dir);
    }
    if (file_prepare_directory($png_dir)) {
      $file = file_save_data($data, "$png_dir/application-$identifier.png", FILE_EXISTS_RENAME);
      if($file) {
        $form_input['image'] = $file->get('fid')->get(0)->value;
      }
    }
  }
  $form_state->setValues($form_input);
}

function _quadstat_core_setup_dataset(&$form, $form_state, $form_input) {
  $fid = isset($form_input['x']) ? $form_input['x'] : NULL;
  $v = isset($form_input['v']) ? $form_input['v'] : NULL;
  $v1 = isset($form_input['v1']) ? $form_input['v1'] : NULL;
  $v2 = isset($form_input['v2']) ? $form_input['v2'] : NULL;
  $v3 = isset($form_input['v3']) ? $form_input['v3'] : NULL;
  if(is_null($fid)) {
    // A dataset is not involved; do nothing
    return '';
  }
  $user = \Drupal::currentUser();
  $file = file_load($fid);
  $uri = $file->get('uri')->get(0)->value;
  $filepath = \Drupal::service('file_system')->realpath($uri); 
  $command = '';
  $command .= "X <- read.table(\"$filepath\", sep=\",\", header = TRUE);\n";
  $command .= "dfr <- read.table(\"$filepath\", sep=\",\", header = TRUE);\n";
  if(!is_null($v) && $v) {
    // The user has chosen entire dataset
    $command .= "colNam <- colnames(X);\n";
    $command .= "X <- cbind(X);\n";
    $command .= "colnames(X) <- colNam;\n";
  } else if((!is_null($v1) && $v1 != '-1') && (!is_null($v2) && $v2 != '-1')  && (!is_null($v3) && $v3 != '-1')) {
    // The user has chosen 3 vectors
    $command .= "colNam <- colnames(X);\n";
    $command .= "X <- cbind(X[,$v1], X[,$v2], X[,$v3]);\n";
    $command .= "colnames(X) <- c(colNam[$v1], colNam[$v2], colNam[$v3]);\n";
    $command .= "dfr <- as.data.frame(c(dfr[$v1], dfr[$v2], dfr[$v3]))\n";
  } else if((!is_null($v1) && $v1 != '-1') && (!is_null($v2) && $v2 != '-1')) {
    // The user has chosen 2 vectors
    $command .= "colNam <- colnames(X);\n";
    $command .= "X <- cbind(X[,$v1], X[,$v2]);\n";
    $command .= "colnames(X) <- c(colNam[$v1], colNam[$v2]);\n";
    $command .= "dfr <- as.data.frame(c(dfr[$v1], dfr[$v2]))\n";
  }
  else if(!is_null($v1) && $v1 != '-1') {
    // The user has chosen 1 vector
    $command .= "colNam <- colnames(X);\n";
    $command .= "X <- cbind(X[,$v1]);\n";
    $command .= "colnames(X) <- c(colNam[$v1]);\n";
    $command .= "dfr <- as.data.frame(c(dfr[$v1]))\n";
  } else {
    // The user has not selected any vectors, use the whole dataset. Don't need to do anything.
  }
  return $command;
}

function _quadstat_core_setup_img(&$form, $form_state, $identifier) {
  $tmp_path = \Drupal::service('file_system')->realpath('temporary://');
  $setup_img_cmd = "png(file = \"$tmp_path/application-$identifier-%01d.png\", bg = \"transparent\");\n";
  return $setup_img_cmd;
}

function _quadstat_core_save_img(&$form, $form_state, $identifier) {
  return $save_img_cmd = "dev.off();\n";
}
