<?php

namespace Drupal\cmlexchange\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * CommerceML Protocol service.
 */
class Protocol implements ProtocolInterface {

  /**
   * Constructs a new Protocol object.
   *
   * @param \Drupal\cmlexchange\Service\CheckAuthInterface $check_auth
   *   The check auth.
   * @param \Drupal\cmlexchange\Service\DebugServiceInterface $debug
   *   The debug.
   * @param \Drupal\cmlexchange\Service\FileServiceInterface $file
   *   The fiel save.
   * @param \Drupal\cmlexchange\Service\OrdersInterface $orders
   *   The fiel save.
   * @param \Drupal\cmlexchange\Service\ImportPipelineInterface $import_pipeline
   *   The fiel save.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   Request stack service.
   */
  public function __construct(
      CheckAuthInterface $check_auth,
      DebugServiceInterface $debug,
      FileServiceInterface $file,
      OrdersInterface $orders,
      ImportPipelineInterface $import_pipeline,
      ConfigFactoryInterface $config_factory,
      ModuleHandlerInterface $module_handler,
      RequestStack $request_stack) {
    $this->auth = $check_auth;
    $this->debugService = $debug;
    $this->fileService = $file;
    $this->ordersService = $orders;
    $this->importPipeline = $import_pipeline;
    $this->configFactory = $config_factory;
    $this->moduleHandler = $module_handler;
    $this->requestStack = $request_stack;
  }

  /**
   * Main.
   */
  public function init() {
    $mode = \Drupal::request()->query->get('mode');
    $type = \Drupal::request()->query->get('type');
    $log = t("START↑↑: mode=@m|type=@t", ['@m' => $mode, '@t' => $type]);
    $this->debugService->debug(__CLASS__, $log);
    if ($this->checkIpAccess()) {
      // Authorization.
      if ($mode == 'checkauth') {
        return $this->auth->modeCheckAuth($type);
      }
      // Authorization status.
      $state = $this->auth->state();
      // Mode state machine.
      if ($state['status']) {
        $cml = $state['cml'];

        switch ($mode) {

          case 'init':
            $result = $this->modeInit();
            break;

          case 'file':
            $result = $this->modeFile($type, $state['cml']);
            break;

          case 'query':
            $result = $this->modeQuery($type, $cml);
            break;

          case 'import':
            $result = $this->modeImport($type, $cml);
            break;

          case 'success':
            $result = $this->modeSuccess($type);
            break;

          default:
            $result = "failure\n";
            $result .= "unknown mode\n";
            $this->debugService->debug(__CLASS__, "unknown mode");
            break;
        }
      }
      else {
        $result = $state['message'];
      }

      $this->debugService->debug(__CLASS__, "Response: {$result}");
    }
    else {
      $result = "failure\n";
      $result .= "ip forbidden\n";
    }

    return $result;
  }

  /**
   * Protocol MODE-Init.
   */
  private function modeInit() {
    $config = $this->configFactory->get('cmlexchange.settings');
    $zip = $config->get('zip') ? 'yes' : 'no';
    $file_limit = $config->get('file-limit');
    $result = "zip={$zip}\n";
    $result .= "file_limit={$file_limit}\n";
    return $result;
  }

  /**
   * Protocol MODE-File.
   */
  private function modeFile($type, $cml) {
    $result = "failure\n";
    $filename = $this->requestStack->getCurrentRequest()->query->get('filename');
    $content = file_get_contents('php://input');
    // Check inpuf file.
    if ($filename && $content) {
      $cid = $cml->id();
      $this->debugService->debug(__CLASS__, "File: {$cid}-{$type} {$filename}");
      $file = $this->fileService->file($content, $filename, $cid, $type);
      if ($file) {
        $this->moduleHandler->alter('cmlexchange_file_load', $cid, $type);
        $result = "success\n";
      }
      else {
        $result .= "Error during writing file.\n";
        $this->debugService->debug(__CLASS__, "Ошибка при записи файла.");
      }
    }
    elseif (!$filename) {
      $result .= "filename error\n";
      $msg = "Ошибка загрузки файла, не определено имя файла.";
      $this->debugService->debug(__CLASS__, $msg);
    }
    elseif (!$content) {
      $result .= "filecontent error\n";
      $msg = "Ошибка загрузки файла, нет переданного файла в потоке";
      $this->debugService->debug(__CLASS__, $msg);
    }
    return $result;
  }

  /**
   * Protocol MODE-File.
   */
  private function modeQuery($type, $cml) {
    $result = "failure\n";
    // Check type.
    if ($type == 'sale') {
      $filename = 'export.xml';
      $xml = $this->ordersService->xml();
      $file = $this->fileService->file($xml, $filename, $cml->id(), $type);
      if ($file) {
        $cml->setState('success');
        $cml->save();
        header("Cache-Control: private");
        header('Content-Type: application/xml');
        header('Content-Length: ' . strlen($xml));
        header("Content-Disposition: attachment; filename=$filename");
        header('Cache-Control: public');
        header('Pragma: no-cache');
        header("Expires: 0");
        die($xml);
      }
      else {
        $cml->setState('failure');
        $cml->save();
        $result .= "file is missing\n";
      }
    }
    else {
      $result .= "unknown type\n";
    }
    return $result;
  }

  /**
   * Protocol MODE-Success.
   */
  private function modeImport($type, $cml) {
    $id = $cml->id();
    $this->debugService->debug(__CLASS__, "Import: {$id}");
    $result = $this->importPipeline->process($id);
    if ($result == 'progress') {
      return "progress";
    }
    elseif ($result == 'success') {
      return "success";
    }
    elseif ($result == 'failure' || $result == FALSE) {
      return "failure";
    }
    return "success";
  }

  /**
   * Protocol MODE-Success.
   */
  private function modeSuccess($type) {
    if ($type == 'sale') {
      $result = "success\n";
      $this->moduleHandler->alter('cmlexchange_end_sale', $type);
    }
    else {
      $result = "failure\n";
      $result .= "unknown type\n";
    }
    $this->debugService->debug(__CLASS__, "sale mode query");
    return $result;
  }

  /**
   * Check IP.
   */
  public function checkIpAccess() {
    $config = $this->configFactory->get('cmlexchange.settings');
    $ip = $this->requestStack->getCurrentRequest()->getClientIp();
    $auth_ip = $config->get('auth-ip');

    $access = TRUE;
    if (strlen($auth_ip) > 1 && $ip != $auth_ip) {
      $this->debugService->debug(__CLASS__, "Forbidden IP: {$ip}");
      $access = FALSE;
    }
    return $access;
  }

}
