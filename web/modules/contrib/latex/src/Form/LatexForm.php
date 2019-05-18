<?php
/**
 * @file
 * Contains \Drupal\latex\Form\LatexForm
 */
namespace Drupal\latex\Form;
use Drupal\user\Entity;
use Drupal\Drupal\latex\Form\FormInterface;
use Drupal\Core\Database\Database;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Utility\Token;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Ajax\InsertCommand;
use Drupal\Core\Ajax\AppendCommand;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Extension\ModuleHandler;
use Drupal\file\Entity\File;
use Drupal\Core\Ajax\OpenModalDialogCommand;

/**
 * Provides a latex  form.
 */
class LatexForm extends FormBase {
  
  public function getFormId() {
    return 'latex_form';
   }

  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['latex']['intro_text'] = [
      '#markup' => '<p>' . t('Latex provided to download pdf format of any node and as your requirement to change content of node using Tex syntax, because node\'s content save into tex file and convert to pdf file.<br /> <strong>Use tokens below to create your own tex file.</strong>') . '</p>',
    ];

    $form['latex']['tokens'] = [
      '#theme' => 'token_tree_link',
      '#token_types' => array('node'),
      '#global_types' => TRUE,
      '#show_nested' => FALSE,
    ];

    $form['latex']['nodeinfo'] = [
      '#type' => 'entity_autocomplete',
      '#title'=> t('Node Title'),
      '#target_type' => 'node',
       '#suffix' => '<div  id="upd1" name="hello"></div>',
         '#ajax' => array(
           'callback' => 'Drupal\latex\Form\LatexForm::updateData',
           'event' => 'change',
           ),
        ];

    $form['latex']['noad_load'] = array(
      '#type' => 'button',
      '#ajax' => array(
           'callback' => 'Drupal\latex\Form\LatexForm::updateData',
           'event' => 'click',
           'progress' => array(
              'type' => 'throbber',
              'message' => 'Loading node...',
            ),
           ),
      '#value' => t('Load'),
    );

    $form['submit']['content'] = array(
      '#type' => 'submit',
      '#value' => t('Submit'),
    );
    return $form;
  }
  
  public function updateData(array $form, FormStateInterface $form_state) {
    $ajax_response = new AjaxResponse();
    $nid = $form_state->getValue('nodeinfo');
    $node = node_load($nid); // $nid contains the node id
    $title = $node->get('title')->value;
    $body = $node->get('body')->value;
    $uid_array = $node->get('uid')->getValue();
    $author_id = $uid_array[0]['target_id'];
    if($author_id != 0){
      $account = \Drupal\user\Entity\User::load($author_id);
      $name = $account->getUsername();
    }
    else $name = 'anonymous';
    $created_date = $node->get('created')->value;
    $readable_date = date('F j, Y, g:i a', $created_date);
    $file_array = $node->get('field_image')->getValue();
    $fid = $file_array[0]['target_id'];
    if(!empty($fid)){
      $file = File::load($fid);
      $url = $file->getFileUri();
      $file_path = file_create_url($url);
    }
    $text = '\documentclass[12pt]{article}
              \usepackage{graphicx}
              \title{'.$title.'}
              \author{'.$name.'}
              \date{'.$readable_date.'}
              \begin{document}
              \maketitle
              '.$body.'
              \includegraphics{'.$file_path.'}
              \end{document}';
    $output = $form['latex']['edit'] = [
     '#type' => 'textarea',
     '#name' => 'edit',
     '#id' => 'edit-edit',
     '#attributes' => array('class' => 'form-textarea resize-vertical'),
     '#title' => t('Document'),
     '#value' => strip_tags($text),
    ];
    $ajax_response->addCommand(new HtmlCommand('#upd1', $output));
    return $ajax_response;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $file = \Drupal::request()->request->get('edit');
    $nid = $form_state->getValue('nodeinfo');
    $node = node_load($nid);
    $body = $node->get('body')->value;
    $pattern = '/(?:\[)\w+:\w+(?:\])/m'; // Regex pattern to find tokens
    preg_match_all($pattern, $file, $match);
    foreach ($match[0] as $key => $value) {
      $token = \Drupal::service('token')->replace($value, ['node' => $node], []); // Render value of tokens
      $file = preg_replace($pattern, $token, $file);
    }
    if(!empty($node)){
      $title = $node->get('title')->value;
      $filename = "$title.tex";
      $myfile = fopen("$filename", "w") or die("Unable to open file!");
      fwrite($myfile, $file);
      //$file = \Drupal::request()->request->get('edit');
      fclose($myfile);
      shell_exec("/usr/bin/pdflatex --interaction batchmode $filename");
      $filename = "$title.tex" ; // of course find the exact filename....
      header('Pragma: public');
      header('Expires: 0');
      header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
      header('Content-Type:  application/pdf');
      header('Content-Disposition: attachment; filename="'. basename($filename) . '";');
      header('Content-Transfer-Encoding: binary');
      header('Content-Length: ' . filesize($filename));
      readfile($filename);
      exit;
      drupal_set_message("Operation Successfully..");
    }
    else{
      drupal_set_message('Please click on "Load" first', 'error');
    }
  }
}