<?php

namespace Drupal\cmlexchange\Service;

use Drupal\file\Entity\File;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Entity\EntityManager;

/**
 * ImportPipeline.
 */
class ImportPipeline implements ImportPipelineInterface {

  /**
   * Constructs a new Protocol object.
   *
   * @param \Drupal\cmlexchange\Service\DebugServiceInterface $debug
   *   The debug.
   * @param \Drupal\cmlexchange\Service\FileServiceInterface $file
   *   The fiel save.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Entity\EntityManager $entity_manager
   *   Entity Manager service.
   */
  public function __construct(
      DebugServiceInterface $debug,
      FileServiceInterface $file,
      ConfigFactoryInterface $config_factory,
      ModuleHandlerInterface $module_handler,
      EntityManager $entity_manager) {
    $this->debugService = $debug;
    $this->fileService = $file;
    $this->configFactory = $config_factory;
    $this->moduleHandler = $module_handler;
    $this->entityManager = $entity_manager;
  }

  /**
   * Save file (init function).
   */
  public function process($cid, $force = FALSE) {
    $result = FALSE;
    $cml = $this->entityManager->getStorage('cml')->load($cid);
    if ($force) {
      $cml->setState('new');
    }
    $this->cml = $cml;
    // UnZip.
    if ($this->unZip($cml) != '>new') {
      $this->debugService->debug(__CLASS__, "process: unZIP $cid");
      return 'progress';
    }
    // Process Data.
    if ($status = $this->processData($cml) != '>done') {
      $this->debugService->debug(__CLASS__, "process: Data $cid");
      return $status;
    }
    $this->debugService->debug(__CLASS__, "process: OK! $cid");
    // RETURN:
    // 'failure' = ['failure', FALSE]
    // 'progress' = 'progress',
    // 'success' = ['success', TRUE, ANY].
    return 'success';
  }

  /**
   * Process Data.
   */
  public function processData($cml) {
    $result = '>start';
    // Если у нас установлены миграции и включена галочка.
    $config = $this->configFactory->get('cmlexchange.settings');
    $migrations_flag = $config->get('cmlmigrations');
    $migrations_module = $this->moduleHandler->moduleExists('cmlmigrations');
    if ($migrations_flag && $migrations_module) {
      $this->debugService->debug(__CLASS__, "Migrations Init: " . $cml->id());
      // Opts: TRUE, progress, success, failure.
      $result = \Drupal::service('cmlmigrations.pipeline')->init($cml);
    }
    // Если миграций нет - Импорт завершён.
    else {
      $this->debugService->debug(__CLASS__, "SKIP cmlmigrations: " . $cml->id());
      $cml->setState('success');
      $cml->save();
      $result = '>done';
    }
    return $result;
  }

  /**
   * Un Zip.
   */
  public function unZip($cml) {
    $result = FALSE;
    $config = $this->configFactory->get('cmlexchange.settings');
    $dir = 'cml-files';
    if ($config->get('file-path')) {
      $dir = $config->get('file-path');
    }
    // Path to CML data.
    $path = \Drupal::service('file_system')->realpath("public://$dir");
    $cmldir = $this->fileService->cmlDir($cml);
    // Current CML directory.
    $datadir = "$path/$cmldir";
    if ($cml->getState() == 'zip') {
      $xmlfiles = $cml->field_file->getValue();
      $proccess = TRUE;
      // STEP 1. (*.zip)
      // Unzip (if *.ZIP attached).
      foreach ($xmlfiles as $key => $value) {
        $file = File::load($value['target_id']);
        $uri = $file->getFileUri();
        // Check *.zip!
        if (substr($uri, -4) == '.zip') {
          $filename = $file->getFilename();
          $cmd = "cd $datadir && unzip $filename > unzip.log";
          // Exec Unzip & wrire log.
          $result .= shell_exec($cmd);
          // Remove zip file.
          file_delete_multiple([$file->id()]);
          unset($xmlfiles[$key]);
          // Set proccess FLAG.
          $proccess = FALSE;
          $this->debugService->debug(__CLASS__, "STEP1 unZip: $uri");
        }
      }
      // STEP 2 (check log modify time).
      // UnZip can be too long, need unzip.log timestamp check.
      if ($proccess) {
        $log = FALSE;
        if (!empty($xmlfiles)) {
          foreach ($xmlfiles as $key => $value) {
            $file = File::load($value['target_id']);
            $uri = $file->getFileUri();
            if (substr($uri, -4) == '.log') {
              $log = TRUE;
            }
          }
        }
        if (!$log) {
          // Set proccess FLAG.
          $proccess = FALSE;
          // 5 sec form log was changed.
          $logpath = "$datadir/unzip.log";
          if (file_exists($logpath) && filemtime($logpath) < REQUEST_TIME + 5) {
            $proccess = TRUE;
            // Move import_files to `CML-data/import_files`.
            $cmd = "cp -Rf import_files/ $path/";
            shell_exec("cd $datadir && $cmd && rm -r import_files");
            $result .= $cmd;
          }
          $this->debugService->debug(__CLASS__, "STEP2: unZip done, LOG=OK");
        }
      }
      // STEP 3 (scan & attach xml).
      if ($proccess) {
        $xml = FALSE;
        if (!empty($xmlfiles)) {
          foreach ($xmlfiles as $key => $value) {
            $file = File::load($value['target_id']);
            $uri = $file->getFileUri();
            if (substr($uri, -4) == '.xml') {
              $xml = TRUE;
            }
          }
        }
        if (!$xml) {
          // Scan directory for files.
          $result .= ">scan: $datadir\n";
          foreach (scandir($datadir) as $filename) {
            // Find files.
            if (strpos($filename, ".") > 3) {
              $uri = "public://{$dir}/{$cmldir}/$filename";
              $result .= "$uri\n";
              // Save file as file-entity.
              $file = File::create([
                'uri' => $uri,
                'uid' => \Drupal::currentUser()->id(),
                'filename' => $filename,
                'status' => FILE_STATUS_PERMANENT,
              ]);
              $file->save();
              if ($file->id()) {
                // Add file to CML.
                $xmlfiles[] = ['target_id' => $file->id()];
              }
            }
          }
          // Set skip FLAG.
          $proccess = FALSE;
          $this->debugService->debug(__CLASS__, "STEP3" . json_encode($xmlfiles));
        }
      }
      // STEP 4.
      // Done, Update files.
      if ($proccess) {
        $log = FALSE;
        if (!empty($xmlfiles)) {
          foreach ($xmlfiles as $key => $value) {
            $file = File::load($value['target_id']);
            $uri = $file->getFileUri();
            if (substr($uri, -4) == '.log') {
              $log = $uri;
            }
          }
        }
        if ($log) {
          $logpath = \Drupal::service('file_system')->realpath($log);
          $logdata = file_get_contents($logpath);
          foreach (explode("\n", $logdata) as $line) {
            if ($img = strstr($line, "import_files/")) {
              $imgpath = explode('/', $img);
              $filename = array_pop($imgpath);
              $filepath = implode('/', $imgpath);
              $uri = "public://{$dir}/$filepath/$filename";
              $result .= "$uri\n";
              $file = File::create([
                'uri' => $uri,
                'uid' => \Drupal::currentUser()->id(),
                'filename' => $filename,
                'status' => FILE_STATUS_PERMANENT,
              ]);
              $existing_files = entity_load_multiple_by_properties('file', ['uri' => $uri]);
              if (count($existing_files)) {
                $existing = reset($existing_files);
                $fid = $existing->id();
                $file->fid = $fid;
                $file->setOriginalId($fid);
                $result .= "Exist: $fid >> $uri\n";
              }
              $file->save();
            }
          }
        }
        $cml->setState('new');
        $this->debugService->debug(__CLASS__, "STEP4: Done, Update files, OK");
      }
      $cml->field_file->setValue($xmlfiles);
      $cml->save();
    }
    else {
      $result = ">new";
    }
    return $result;
  }

}
