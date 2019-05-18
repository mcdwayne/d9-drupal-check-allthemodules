<?php

use Drupal\loopit\Aggregate\AggregateEntity;
use Drupal\loopit\Aggregate\AggregatePlugin;
use Drupal\loopit\Aggregate\AggregateService;

function loopit_drush_command() {
  $items['method-overrides'] = array(
    'description' => 'Compare overrided Plugin/Service methods on core/contrib updates',
    'arguments' => array(
    ),
    'aliases' => ['m-ovrd'],
  );

  return $items;
}

function drush_loopit_method_overrides() {

  foreach (['public://method_override', 'public://method_parent'] as $path) {
    file_unmanaged_delete_recursive($path);
  }

  $aggreg = AggregateEntity::getClasses([
    '*/\*handlers' => '__custom__',
    '*/\*class' => '__custom__',
    '*/\*provider' => '__custom__',
  ]);
  $context = $aggreg->getContext();

  $cid_plugin = 'loopit:aggregate:plugin_definitions';
  $cache_plugin = \Drupal::cache()->get($cid_plugin);
  $aggreg = AggregatePlugin::getClasses(['*class' => '__custom__', '*provider' => '__custom__', '*deriver' => '__custom__']);
  $context += $aggreg->getContext();

  // TODO: The command can't finish if at least one chache is missing for plugins and services.
  // Just run it again
  $cid_service = 'loopit:aggregate:service_definitions';
  $cache_service = \Drupal::cache()->get($cid_service);
  if (!$cache_service && !$cache_plugin) {
    drush_log(t('Run this command again (some cache was missing, but have been just created)'), 'error');
    return;
  }
  $aggreg = AggregateService::getClasses(['*class' => '__custom__', '*Class' => '__custom__']);
  $context += $aggreg->getContext();
  $customs = $context['entity_class_centric'] +  $context['service_class_centric'] +  $context['plugin_class_centric'];

  ksort($customs);

  $codes1 = $codes2 = array();
  foreach($customs as $name => $def) {
    $def['class'] = $name;

    if(!class_exists($def['class'])) {
      continue;
    }
    /** @var $reflection \ReflectionClass */
    $reflection = new ReflectionClass($def['class']);

    $methods = $reflection->getMethods();
    $parent = $reflection->getParentClass();

    // Nothing to do if there is no parent
    if (!$parent || is_string($parent)) {

      drush_log(t('The custom service class @class has no parent class', ['@class' => $def['class']]), 'notice');
      continue;
    }
    // Override methods by class
    $overrides = array();
    foreach($methods as $method) {
      foreach($parent->getMethods() as $parent_method) {
        if(
            $method->name == $parent_method->name
            && $method->class == $def['class']
          ) {
          $overrides[] = array($method, $parent_method);
        }
      }
    }
    if (!$overrides) {
      drush_log(t('The inherit class @class has no override methods', ['@class' => $def['class']]), 'notice');
    }

    foreach($overrides as $override) {
      list($method, $parent_method) = $override;

      $reflection = new ReflectionClass($method->class);

      // TODO: special handling
      if($reflection->isTrait()) {

      }

      $filename = $method->getFileName();
      $start_line = $method->getStartLine() - 1; // it's actually - 1, otherwise you wont get the function() block
      $end_line = $method->getEndLine();
      $length = $end_line - $start_line;
      $source = file($filename);
      $web_root = \Drupal::root();
      if(($pos = strpos($method->getFileName(), $web_root)) === 0) {
        $relative_filename = substr($method->getFileName(), strlen($web_root)+1);

        // Init $codes1 (extender) class if not exists
        if(!isset($codes1[$relative_filename])) {
          $codes1[$relative_filename] = [
            '<?php' . "\n" .
            'namespace ' . $reflection->getNamespaceName() . ';' . "\n" .
            '' . "\n" .
            'class ' . $reflection->getShortName() . ' {' . "\n"
          ];
        }

        // Append $method code to $codes1
        $codes1[$relative_filename][$method->name] = "\n" . implode("", array_slice($source, $start_line, $length));

        $parent_method_reflection = new ReflectionClass($parent_method->class);
        if($parent_method_reflection->isInterface()) {

          unset($codes1[$relative_filename][$method->name]);
          continue;
        }
        $filename = $parent_method->getFileName();
        $start_line = $parent_method->getStartLine() - 1; // it's actually - 1, otherwise you wont get the function() block
        $end_line = $parent_method->getEndLine();
        $length = $end_line - $start_line;
        $source = file($filename);

        // Init $codes2 (parent) class if not exists
        if(!isset($codes2[$relative_filename])) {
          $codes2[$relative_filename] = '<?php' . "\n" .
          'namespace ' . $parent_method_reflection->getNamespaceName() . ';' . "\n" .
          '' . "\n" .
          'class ' . $parent_method_reflection->getShortName() . ' {' . "\n";
        }

        // Append parent $method code to $codes2
        // TODO: #critical: what about if the method is in the grand parent?
        $codes2[$relative_filename] .= "\n" . implode("", array_slice($source, $start_line, $length));
        $code2 = $codes2[$relative_filename];
      }
      else {
        // TODO: #major: How to for overrides in symlinks: ex. /var/www/html/develxp/
        //   Need to get the relative drupal path
        //   Use the psr4 root namespace from $class_loader_casted['*decorated']['-prefixDirsPsr4'] from \Drupal::service('class_loader');
        //   - Drupal\search_krumo\ => /var/www/html/dnd/web/modules/custom/search_krumo/src
        //     for class Drupal\search_krumo\Plugin\Devel\Dumper\KrumoDebug => psr4 is str_replace \ / ?
        //     for realfile /var/www/html/develxp/src/Plugin/Devel/Dumper/KrumoDebug.php
        drush_log(t('Method @method from file @file is not in webroot @webroot', [
          '@method' => $method->name,
          '@file' => $method->getFileName(),
          '@webroot' => $web_root
        ]), 'warning');
      }
    }
  }

  $dirnames = [];
  foreach ($codes1 as $relative_filename => $code1) {

    if (count($code1) == 1) {
      drush_log(t('All parent methods are abstract for class @class', ['@class' => $relative_filename]), 'notice');
      continue;
    }
    $code1 = implode('', $code1);

    // Close $code1 classe
    $code1 .= "\n" . '}' . "\n";

    // Close $code2 classe
    $code2 = $codes2[$relative_filename];
    $code2 .= "\n" . '}' . "\n";

    $filename1 = 'public://method_override/' . $relative_filename;
    $filename2 = 'public://method_parent/' . $relative_filename;
    $dirname1 = substr($filename1, 0, strrpos($filename1, '/'));

    // Delete old files (init) for $dirname1
    if(!isset($dirnames[$dirname1])) {
      //rmdir($dirname1);
      file_prepare_directory($dirname1, FILE_CREATE_DIRECTORY | FILE_MODIFY_PERMISSIONS);
      foreach(glob(drupal_realpath($dirname1) . '/*') as $todelete) {
        if (!is_dir($todelete)) {
          unlink($todelete);
        }
      }
      $dirnames[$dirname1] = $dirname1;
      //echo $dirname1 . "\n";
    }
    $dirname2 = substr($filename2, 0, strrpos($filename2, '/'));

    // Delete old files (init) for $dirname2
    if(!isset($dirnames[$dirname2])) {
      //rmdir($dirname2);
      file_prepare_directory($dirname2, FILE_CREATE_DIRECTORY | FILE_MODIFY_PERMISSIONS);
      foreach(glob(drupal_realpath($dirname2) . '/*') as $todelete) {
        if (!is_dir($todelete)) {
          unlink($todelete);
        }
      }
      $dirnames[$dirname2] = $dirname2;

    }

    // Write files
    file_put_contents($filename1, $code1);
//     drush_log(t('Source class written to file @file', ['@file' => $filename1]), 'success');
    file_put_contents($filename2, $code2);
//     drush_log(t('Extended class written to file @file', ['@file' => $filename2]), 'success');

  }

  $public_files = \Drupal::service('file_system')->realpath('public://');
  drush_log('meld ' . $public_files . '/method_override/ ' . $public_files . '/method_parent/', 'notice');
}