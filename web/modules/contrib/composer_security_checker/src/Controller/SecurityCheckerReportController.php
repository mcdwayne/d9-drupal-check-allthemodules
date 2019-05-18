<?php

namespace Drupal\composer_security_checker\Controller;

use Drupal\Component\Utility\UrlHelper;
use Drupal\composer_security_checker\Collections\AdvisoryCollection;
use Drupal\composer_security_checker\Models\Advisory;
use Drupal\composer_security_checker\Repositories\RepositoryInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\Core\Utility\LinkGenerator;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class SecurityCheckerReportController.
 *
 * @package Drupal\composer_security_checker\Controller
 */
class SecurityCheckerReportController extends ControllerBase {

  /**
   * A security repository to collect security advisories.
   *
   * @var RepositoryInterface
   */
  protected $composerSecurityRepository;

  /**
   * A LinkGenerator instance.
   *
   * @var LinkGenerator
   */
  protected $linkGenerator;

  /**
   * SecurityCheckerReportController constructor.
   *
   * @param RepositoryInterface $composer_security_repository
   *   A security repository to collect security advisories.
   * @param LinkGenerator $link_generator
   *   A LinkGenerator instance.
   */
  public function __construct(RepositoryInterface $composer_security_repository, LinkGenerator $link_generator) {
    $this->composerSecurityRepository = $composer_security_repository;
    $this->linkGenerator = $link_generator;
  }

  /**
   * Index.
   *
   * @return string
   *   Return Hello string.
   */
  public function index() {

    $advisories = $this->composerSecurityRepository->getAvailableUpdates();

    // If we've got no advisories, then we can return early with a message.
    if ($advisories->count() <= 0) {
      return $this->getNoResultsMarkup();
    }

    $rows = $this->getRows($advisories);

    return [
      '#type' => 'table',
      '#header' => $this->getTableHeaders(),
      '#rows' => $rows,
      '#sticky' => TRUE,
    ];

  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('composer_security_checker.security_checker_repository'),
      $container->get('link_generator')
    );
  }

  /**
   * Get the "No results found" text.
   *
   * @return array
   *   A renderable array containing no results found markup.
   */
  private function getNoResultsMarkup() {
    return [
      '#markup' => $this->t('There are no available security updates at this time.'),
    ];
  }

  /**
   * Get an array of table headers for the report page.
   *
   * @return array
   *   A translatable array of table headers.
   */
  private function getTableHeaders() {
    return [
      $this->t('Package name'),
      $this->t('Version'),
      $this->t('Identifier'),
      $this->t('Advisory'),
      $this->t('Link'),
    ];
  }

  /**
   * Get the rows for the security advisory table.
   *
   * @param AdvisoryCollection $advisories
   *   A collection of security advisory objects.
   *
   * @return array
   *   An array of row data for populating the advisory table.
   */
  private function getRows(AdvisoryCollection $advisories) {
    $rows = [];

    /** @var Advisory $advisory */
    foreach ($advisories as $advisory) {

      $rows[] = [
        $advisory->getLibraryName(),
        $advisory->getLibraryVersion(),
        $advisory->getAdvisoryIdentifier(),
        $advisory->getAdvisoryTitle(),
        $this->getAdvisoryLinkMarkup($advisory),
      ];

    }

    return $rows;
  }

  /**
   * Get an actual link to the security advisory.
   *
   * @param Advisory $advisory
   *   A security advisory instance. We could just pass the link text in here,
   *   but I'd rather be able to typehint it.
   *
   * @return \Drupal\Core\GeneratedLink
   *   A sanitized <a> tag linking to the security notice.
   */
  private function getAdvisoryLinkMarkup(Advisory $advisory) {

    // We can be pretty sure the URL is safe, but we should check anyway.
    $unsafe_url = $advisory->getAdvisoryLink();
    $safe_url = UrlHelper::stripDangerousProtocols($unsafe_url);

    // Build the URL.
    $url = Url::fromUri($safe_url);
    $url->setOption('external', TRUE);
    $url->setOption('attributes', ['target' => '_blank']);

    return $this->linkGenerator->generate($url->getUri(), $url);

  }

}
