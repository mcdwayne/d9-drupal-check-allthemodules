<?php

namespace Drupal\block_backup_restore\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\Config;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Theme\ThemeManagerInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controller routines for BlockBackupRestoreController.
 */
class BlockBackupRestoreController extends ControllerBase {

  /**
   * Image Captcha config storage.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * The EntityManager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityManager;

  /**
   * The theme manager.
   *
   * @var \Drupal\Core\Theme\ThemeManagerInterface
   */
  protected $themeManager;

  /**
   * The theme handler.
   *
   * @var \Drupal\Core\Extension\ThemeHandlerInterface
   */
  protected $themeHandler;

  /**
   * The current Request object.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory')->getEditable('block_backup_restore.setting'),
      $container->get('entity_type.manager'),
      $container->get('theme.manager'),
      $container->get('theme_handler'),
      $container->get('request_stack')->getCurrentRequest()
    );
  }

  /**
   * Inject currentUser dependencies.
   *
   * @param \Drupal\Core\Config\Config $config
   *   Service object.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityManager
   *   Service object.
   * @param \Drupal\Core\Theme\ThemeManagerInterface $themeManager
   *   The theme manager.
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $theme_handler
   *   Service object.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Service object.
   */
  public function __construct(
    Config $config,
    EntityTypeManagerInterface $entityManager,
    ThemeManagerInterface $themeManager,
    ThemeHandlerInterface $theme_handler,
    Request $request
  ) {
    $this->config = $config;
    $this->entityManager = $entityManager;
    $this->themeManager = $themeManager;
    $this->themeHandler = $theme_handler;
    $this->request = $request;
  }

  /**
   * Action for the select plan page.
   *
   * @param string|null $theme
   *   Theme key of block list.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Redirecting to user membership page.
   *
   * @throws \InvalidArgumentException
   */
  public function backupBlockSetting($theme = NULL) {

    if (empty($theme)) {
      $theme = $this->config('system.theme')->get('default');
    }

    if (!$this->themeHandler->themeExists($theme)) {
      drupal_set_message($this->t('Theme not found.'), "error");
      return $this->redirect('block.admin_display');
    }

    $defaultThemeBlocks = $this->entityManager->getStorage('block')->loadByProperties(['theme' => $theme]);
    $blockLayoutValue = [];
    foreach ($defaultThemeBlocks as $blockValue) {
      if ($blockValue->status()) {
        $blockLayoutValue[$blockValue->id()] = [
          'region' => $blockValue->getRegion(),
          'weight' => $blockValue->getWeight(),
        ];
      }
    }
    $this->config
      ->set("backupData_" . $theme, json_encode($blockLayoutValue))
      ->save();
    drupal_set_message($this->t('Block backup for @themeName saved successfully.', ['@themeName' => $theme]));

    // If user want to download the data in json file.
    if ($this->request->query->get('download')) {
      $response = new Response();
      $response->headers->set('Content-Type', 'application/txt', TRUE);
      $response->headers->set('Content-Disposition', 'inline; filename="BlockBackupRestore_' . $theme . '.json"', TRUE);
      $response->sendHeaders();
      print json_encode($blockLayoutValue);
      exit;
    }
    return $this->redirect('block.admin_display_theme', ['theme' => $theme]);
  }

  /**
   * Action for the select plan page.
   *
   * * @param string|null $theme
   *   Theme key of block list.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Redirecting to user membership page.
   *
   * @throws \InvalidArgumentException
   */
  public function restoreBlockSetting($theme = NULL) {
    if (empty($theme)) {
      $theme = $this->config('system.theme')->get('default');
    }

    if (!$this->themeHandler->themeExists($theme)) {
      drupal_set_message($this->t('Theme not found.'), "error");
      return $this->redirect('block.admin_display');
    }

    $data = $this->config->get("backupData_" . $theme);
    if (!empty($data)) {
      $json = json_decode($data, TRUE);
      foreach ($json as $key => $value) {
        $blockData = $this->entityManager->getStorage('block')->load($key);
        if (!empty($blockData)) {
          $blockData->setRegion($value['region'])
            ->setWeight($value['weight'])
            ->enable()
            ->save();
        }
      }
      drupal_set_message($this->t('Block restore in the @themeName theme successfully.', ['@themeName' => ucwords($theme)]));
    }
    else {
      drupal_set_message($this->t('Backup is not found for this theme @themeName.', ['@themeName' => ucwords($theme)]), "error");
    }
    return $this->redirect('block.admin_display_theme', ['theme' => $theme]);
  }

}
