<?php

namespace Drupal\account_modal;

use Drupal\account_modal\Event\AccountModalEvents;
use Drupal\account_modal\Event\PagesEvent;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * A helper class for managing configured account pages.
 */
class AccountPageHelper {

  use StringTranslationTrait;

  /**
   * @return array
   */
  public function getPages() {
    $pages = [
      'page' => [
        'label' => $this->t('User page'),
        'routes' => ['user.page'],
        'form' => FALSE,
      ],
      'login' => [
        'label' => $this->t('Login'),
        'routes' => ['user.login'],
        'form' => 'user_login_form',
      ],
      'register' => [
        'label' => $this->t('Register'),
        'routes' => ['user.register'],
        'form' => 'user_register_form',
      ],
      'password' => [
        'label' => $this->t('Password reset'),
        'routes' => ['user.pass'],
        'form' => 'user_pass',
      ],
      'cancel' => [
        'label' => $this->t('Account cancellation'),
        'routes' => ['user.cancel_confirm'],
        'form' => 'user_cancel',
      ],
    ];

    /** @var \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler */
    $moduleHandler = \Drupal::service('module_handler');
    $profileIsInstalled = $moduleHandler->moduleExists('profile');

    if ($profileIsInstalled) {
      $pages['profile_add'] = [
        'label' => $this->t('Profile add'),
        'routes' => ['/entity\.profile\.type\..+.\.user_profile_form\.add/'],
        'form' => '/profile_.+_add_form/',
      ];

      $pages['profile_edit'] = [
        'label' => $this->t('Profile edit'),
        'routes' => ['entity.profile.edit_form'],
        'form' => '/profile_.+_edit_form/',
      ];
    }

    $event = new PagesEvent($pages);
    /** @var \Symfony\Component\EventDispatcher\EventDispatcherInterface $eventDispatcher */
    $eventDispatcher = \Drupal::service('event_dispatcher');
    $eventDispatcher->dispatch(AccountModalEvents::PAGES, $event);
    return $event->getPages();
  }

  /**
   * @return array
   */
  public function getPageOptions() {
    $pages = $this->getPages();
    $options = [];

    foreach ($pages as $pageId => $page) {
      $options[$pageId] = $page['label'];
    }

    return $options;
  }

  /**
   * @param $page
   *
   * @return array
   */
  public function getRoutes($page) {
    $pages = $this->getPages();
    $routes = [];

    if (isset($pages[$page])) {
      $routes = $pages[$page]['routes'];
    }

    return $routes;
  }

  /**
   * @return array
   */
  public function getAllRoutes() {
    $pages = $this->getPages();
    $routes = [];

    foreach ($pages as $pageId => $page) {
      $routes += $page['routes'];
    }

    return $routes;
  }

  /**
   * @return array
   */
  public function getEnabledPages() {
    $config = \Drupal::config('account_modal.settings');
    $enabledPages = $config->get('enabled_pages');
    $pages = $this->getPages();
    $results = [];

    foreach ($pages as $pageId => $pageInfo) {
      if (in_array($pageId, $enabledPages, TRUE)) {
        $results[$pageId] = $pageInfo;
      }
    }

    return $results;
  }

  /**
   * @param $pageId
   *
   * @return mixed|null
   */
  public function getPage($pageId) {
    $pages = $this->getPages();

    if (!isset($pages[$pageId])) {
      return NULL;
    }

    return $pages[$pageId];
  }

  /**
   * @param $route
   *
   * @return int|null|string
   */
  public function getPageFromRoute($route) {
    $page = NULL;

    foreach ($this->getEnabledPages() as $pageId => $pageInfo) {
      if (in_array($route, $pageInfo['routes'], TRUE)) {
        $page = $pageId;

        break;
      }

      foreach ($pageInfo['routes'] as $pageRoute) {
        if (strpos($pageRoute, '/') === 0 && preg_match($pageRoute, $route)) {
          $page = $pageId;

          break;
        }
      }
    }

    return $page;
  }

  /**
   * @param $formId
   *
   * @return int|null|string
   */
  public function getPageFromFormId($formId) {
    $page = NULL;

    foreach ($this->getEnabledPages() as $pageId => $pageInfo) {
      if ($formId === $pageInfo['form']) {
        $page = $pageId;
        break;
      }

      if (strpos($pageInfo['form'], '/') === 0 && preg_match($pageInfo['form'], $formId)) {
        $page = $pageId;
        break;
      }
    }

    return $page;
  }

}
