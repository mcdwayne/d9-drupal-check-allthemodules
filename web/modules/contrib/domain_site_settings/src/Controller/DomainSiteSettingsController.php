<?php

namespace Drupal\domain_site_settings\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\domain\DomainLoader;

/**
 * Class DomainSiteSettingsController.
 *
 * @package Drupal\domain_site_settings\Controller
 */
class DomainSiteSettingsController extends ControllerBase {

  /**
   * Drupal\domain\DomainLoader definition.
   *
   * @var \Drupal\domain\DomainLoader
   */
  protected $domainLoader;

  /**
   * Construct function.
   *
   * @param \Drupal\domain\DomainLoader $domain_loader
   *   Load the domain records.
   */
  public function __construct(
  DomainLoader $domain_loader
  ) {
    $this->domainLoader = $domain_loader;
  }

  /**
   * Create function return static domain loader configuration.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   Load the ContainerInterface.
   *
   * @return \static
   *   return domain loader configuration.
   */
  public static function create(ContainerInterface $container) {
    return new static(
        $container->get('domain.loader')
    );
  }

  /**
   * Function Provide the list of modules.
   *
   * @return typearray
   *   Domain list.
   */
  public function domainList() {
    $domains = $this->domainLoader->loadMultipleSorted();
    $rows = [];
    /** @var \Drupal\domain\DomainInterface $domain */
    foreach ($domains as $domain) {
      $row = [
        $domain->label(),
        $domain->getCanonical(),
        Link::fromTextAndUrl($this->t('Edit'), Url::fromRoute('domain_site_settings.config_form', ['domain_id' => $domain->id()])),
      ];
      $rows[] = $row;
    }
    // Build a render array which will be themed as a table.
    $build['pager_example'] = [
      '#rows' => $rows,
      '#header' => [
        $this->t('Name'),
        $this->t('Hostname'),
        $this->t('Edit Settings'),
      ],
      '#type' => 'table',
      '#empty' => $this->t('No domain record found.'),
    ];
    return $build;
  }

}
