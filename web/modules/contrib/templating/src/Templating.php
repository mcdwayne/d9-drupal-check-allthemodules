<?php

namespace Drupal\templating;

use Drupal\Component\FileSystem\FileSystem;
use Drupal\node\Entity\Node;
use Symfony\Component\Yaml\Dumper;
use Symfony\Component\Yaml\Parser;
use Symfony\Component\Yaml\Yaml as SymfonyYaml;
/**
 * Created by PhpStorm.
 * User: USER
 * Date: 11/8/18
 * Time: 2:52 PM
 */
class Templating {

  public $logger;

  public function __construct() {
    $this->logger = \Drupal::logger('templating');
  }
  public function importConfigTemplate(){
    $result = $this->getConfigTemplate();
    if(!empty($result)){
      foreach ($result as $item){
        $this->save_item($item);
      }
    }

  }
  public function save_item($item){
    $template_list = \Drupal::entityTypeManager()->getStorage('node')
      ->loadByProperties([
        'type' => 'templating',
        'field_template_name' => $item['machine_name']
      ]);
    $node_template = null;
    if(!empty($template_list)){
      $node_template =  array_keys($template_list)[0];
    }else{
      $entity_def = \Drupal::entityTypeManager()->getDefinition('node');
      $array = array(
        $entity_def->get('entity_keys')['bundle']=>'templating'
      );
      $node_template = \Drupal::entityTypeManager()->getStorage('node')->create($array);

    }
    if($node_template instanceof  Node){
      if($item['machine_name']){
        $node_template->field_template_name->value =  $item['machine_name'];
      }
   // $node_template->field_content->value =  $item['content'];
      if($item['module']){
        $node_template->field_module->value =  $item['module'];
      }
    $node_template->title->value =  $item['title'];
    $node_template->field_variables =  $item['variable'];
      $node_template->field_source->value =  $item['source'];
    $node_template->save();
    }

  }
  public function item_template($template){
    $name = $template->field_template_name->value;
    $list = $this->get_field($template, 'field_module');
    $variable_list = [];
    if(!empty($template->field_variables)){
      foreach ($template->field_variables as $var){
        $variable_list[] = $var->value;
      }
    }
    return [
      'machine_name' => $name,
      'module' => $list[0],
      'variable' => $variable_list,
      'title' => $template->title->value,
      'source' => ($template->field_source->value)?$template->field_source->value :""
    ];
  }
  public function generateConfigTemplating($path,$content=''){
    $path_full = DRUPAL_ROOT.$path;
    $file_new = $path_full."/templating_feature.yml";
    if (!file_exists($file_new)) {
      $this->generateFile($path_full, 'templating_feature', $content, '.yml');
      @chmod($file_new, 0777);
    }
    $result = $this->load_config_db_templating();
    $this->saveConfigTemplate($result);
    return true;
  }

  public function saveConfigTemplate($array_new){
    $config = \Drupal::config('templating.templating');
    $path = $config->get('template_path');
    $path_full = DRUPAL_ROOT.$path;
    $file_new =  $path_full."/templating_feature.yml";


    if (file_exists($file_new)) {
      $content = file_get_contents( $path_full."/templating_feature.yml", FILE_USE_INCLUDE_PATH);
      //$parsed = new Parser();
      //$array_content = $parsed->parse($content,SymfonyYaml::DUMP_EMPTY_ARRAY_AS_SEQUENCE);
      //if(!$array_content){$array_content = [] ;}
      //$content = array_merge($array_content,$array_new);
      $yaml = new Dumper(2);
      $content_yaml = $yaml->dump($array_new);
      $this->generateFile($path_full,'templating_feature',$content_yaml,'.yml');
    }
    else {
      $this->logger->error('File  not find exist : ' . $file_new);
      return FALSE;
    }
  }
  public function getConfigTemplateFile(){
    $config = \Drupal::config('templating.templating');
    $path = $config->get('template_path');
    $file_new =  DRUPAL_ROOT.$path."/templating_feature.yml";
    if (file_exists($file_new)) {
      return file_get_contents($file_new, FILE_USE_INCLUDE_PATH);
    }
    else {
      $this->logger->error('File  not find exist : ' . $file_new);
      return FALSE;
    }
  }
  public function getConfigTemplate(){
    $config = \Drupal::config('templating.templating');
    $path = $config->get('template_path');
    $file_new =  DRUPAL_ROOT.$path."/templating_feature.yml";
    if (file_exists($file_new)) {
      $content = file_get_contents($file_new, FILE_USE_INCLUDE_PATH);
      $parsed = new Parser();
      return $parsed->parse($content);
    }
    else {
      $this->logger->error('File  not find exist : ' . $file_new);
      return FALSE;
    }
  }
  public function getTemplating($content) {
    $template_item = NULL;
    if(!empty($content)){
    foreach ($content as $key => $item) {
      if ($content->{$key}->entity && $content->{$key}->entity instanceof \Drupal\node\Entity\Node) {
        $template_node = $content->{$key}->entity;
        if ($template_node->getType() == 'templating') {
          $template_item = $template_node;
        }
      }
    }
    }
    return $template_item;
  }

  public function is_field_ready($entity, $field) {
    $bool = FALSE;
    if (is_object($entity) && $entity->hasField($field)) {
      $field_value = $entity->get($field)->getValue();
      if (!empty($field_value)) {
        $bool = TRUE;
      }
    }
    return $bool;
  }

  function get_field($entity, $field) {
    $bool = $this->is_field_ready($entity, $field);
    $result = [];
    if ($bool) {
      $items = $entity->get($field)->getValue();
      foreach ($items as $key => $item) {
        $result[] = $item['value'];
      }
    }
    return $result;

  }

  function create_file($template_name, $module_name, $type = 'block_content') {
    $module_handler = \Drupal::service('module_handler');
    $template_name = str_replace("_", "-", $template_name);
    $path_module = $module_handler->getModule($module_name)->getPath();
    $path_file =  $path_module . '/templates' . '/' . $template_name . '.html.twig';
    @chmod($path_file, 0777);
    if (file_exists($path_file)) {

    }
    else {
      $path_module = DRUPAL_ROOT . "/" . $path_module . "/templates";
      $content = $this->defaultContent($type);
      return $this->generateFile($path_module, $template_name, $content);
    }
  }
  function udpate_file($template_name, $module_name,$content,$type = 'block_content') {
    $module_handler = \Drupal::service('module_handler');
    $template_name = str_replace("_", "-", $template_name);
    $path_module = $module_handler->getModule($module_name)->getPath();
    $path_file =  $path_module . '/templates' . '/' . $template_name . '.html.twig';
    @chmod($path_file, 0777);
    $path_module = DRUPAL_ROOT . "/" . $path_module . "/templates";
    return $this->generateFile($path_module, $template_name, $content);

  }

  function defaultContent($type) {
    $content = '{# add your content here #}';
    switch ($type) {
      case 'block_content':
        $content = "<div{{ attributes }}>" . "\n";
        $content = $content . "{{ title_prefix }}" . "\n";
        $content = $content . "{% if label %}";
        $content = $content . "<h2{{ title_attributes }}>{{ label }}</h2>" . "\n";
        $content = $content . "{% endif %}" . "\n";
        $content = $content . "  {{ title_suffix }}" . "\n";
        $content = $content . "  {% block content %}" . "\n";
        $content = $content . "    {{ content }}" . "\n";
        $content = $content . "  {% endblock %}" . "\n";
        $content = $content . "</div>";

        break;
      case 'node':
        $content = "<article{{ attributes }}>" . "\n";
        $content = $content . "{{ title_prefix }}" . "\n";
        $content = $content . "{% if not page %}" . "\n";
        $content = $content . "<h2{{ title_attributes }}>" . "\n";
        $content = $content . " <a href='{{url}}' rel='bookmark'>{{ label }}</a>" . "\n";
         $content = $content . " </h2>" . "\n";
      $content = $content . "{% endif %}" . "\n";
      $content = $content . "{{ title_suffix }}" . "\n";
      $content = $content . "{% if display_submitted %}" . "\n";
          $content = $content . "<footer>" . "\n";
            $content = $content . "  {{ author_picture }}" . "\n";
           $content = $content . "   <div{{ author_attributes }}>" . "\n";
              $content = $content . "    {% trans %}Submitted by {{ author_name }} on {{ date }}{% endtrans %}" . "\n";
                  $content = $content . "{{ metadata }}" . "\n";
             $content = $content . " </div>" . "\n";
          $content = $content . "</footer>" . "\n";
      $content = $content . " {% endif %}" . "\n";
      $content = $content . "<div{{ content_attributes }}>" . "\n";
        $content = $content . "  {{ content }}" . "\n";
     $content = $content . " </div>" . "\n";
      $content = $content . "</article>" . "\n";

    }
    return $content;
  }

  function generateFile($directory, $filename, $content,$format = '.html.twig') {
    $fileSystem = \Drupal::service('file_system');
    if (!is_dir($directory)) {
      if ($fileSystem->mkdir($directory, NULL, TRUE) === FALSE) {
        $this->logger->error('Failed to create directory ' . $directory);
        return FALSE;
      }
    }
    if (file_put_contents($directory . '/' . $filename . $format, $content) === FALSE) {
      $this->logger->error('Failed to write file ' . $filename);
      return FALSE;
    }
    if (@chmod($directory . '/' . $filename . '.html.twig', 0777)) {
      $this->logger->error('Failed to change permission file ' . $filename);
    }
    return TRUE;
  }

  function twigCompileInline($string) {
    $elements = [
      '#type' => 'inline_template',
      '#template' => $string,
    ];
    $renderer = \Drupal::service('renderer');

    try {
      return $renderer->renderPlain($elements);
    } catch (\Twig_Error_Syntax $e) {
      $this->logger->error('Failed to complied Templating content error: ' . $e->getMessage());
      return FALSE;
    } catch (\Exception $e) {
      $this->logger->error('Failed to complied Templating error: ' . $e->getMessage());
      return FALSE;
    }
  }

  function getFilePathTemplating($module_name, $template_name) {
    $module_handler = \Drupal::service('module_handler');
    $status = \Drupal::moduleHandler()->moduleExists($module_name);
    if ($status) {
      $path_module = $module_handler->getModule($module_name)->getPath();
      $template_name = str_replace("_", "-", $template_name);
      return DRUPAL_ROOT . "/" . $path_module . "/templates/" . $template_name . '.html.twig';

    }
    else {
      $this->logger->error('Module not find');
      return FALSE;
    }
  }

  function loadtemplateContent($module_name, $template_name) {
    $file = $this->getFilePathTemplating($module_name, $template_name);
    if (file_exists($file)) {
      return file_get_contents($file, FILE_USE_INCLUDE_PATH);
    }
    else {
      $this->logger->error('File  not find exist : ' . $file);
      return FALSE;
    }
  }
  function load_config_db_templating(){
    $result = [];
    $template_list = \Drupal::entityTypeManager()
      ->getStorage('node')
      ->loadByProperties(['type' => 'templating']);
    if(!empty($template_list)){
      foreach ($template_list as $template) {
          $item = $this->item_template($template);
          $result[$item['machine_name']] = $item;
      }
    }
    return $result ;
  }

  function delete($node) {
    $template_name = $node->field_template_name->value;
    $module_name = $this->get_field($node, 'field_module');
    if (!empty($module_name)) {
      $file = $this->getFilePathTemplating($module_name[0], $template_name);
      if (is_writable($file) && @unlink($file)) {
      }
      else {
        $this->logger->error('File  not write : ' . $file);
      }
    }
  }


}