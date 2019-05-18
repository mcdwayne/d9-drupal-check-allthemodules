<?php

namespace Drupal\recurly_aegir\HostingServiceCalls;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\NodeInterface;
use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class for creating new sites via Aegir's Web service API.
 */
abstract class SiteCreateHostingServiceCall extends SiteHostingServiceCall {
  use TaskCreationTrait;

  /**
   * The machine name of the template to use in creating the site.
   *
   * @var string
   */
  protected $template;

  /**
   * {@inheritdoc}
   *
   * @param string $template
   *   The machine name of the template to use in creating the site.
   */
  public static function create(ContainerInterface $container, NodeInterface $site, $template) {
    return new static(
      $container->get('logger.factory')->get('recurly_aegir'),
      $container->get('http_client'),
      $container->get('config.factory')->get('recurly_aegir.settings'),
      $container->get('request_stack')->getCurrentRequest(),
      $container->get('module_handler'),
      $site,
      $template
    );
  }

  /**
   * {@inheritdoc}
   *
   * @param string $template
   *   The machine name of the template to use in creating the site.
   */
  public function __construct(
    LoggerInterface $logger,
    Client $http_client,
    ImmutableConfig $recurly_config,
    Request $current_request,
    ModuleHandlerInterface $module_handler,
    NodeInterface $site,
    $template
  ) {
    parent::__construct($logger, $http_client, $recurly_config, $current_request, $module_handler, $site);
    if (empty($template)) {
      throw new \Exception('Site-creation service callers must be provided with a template ID on construction.');
    }
    $this->template = $template;
  }

  /**
   * Fetches the owner of the site to be created.
   *
   * @return Drupal\user\Entity\User
   *   The user object representing the site owner.
   */
  public function getClient() {
    return $this->site->getOwner();
  }

  /**
   * Fetches the template used for creating the site.
   *
   * @return string
   *   The template ID.
   */
  public function getTemplate() {
    return $this->template;
  }

  /**
   * Validates the site name chosen by the user.
   *
   * We need to ensure that the chosen name does not have special characters as
   * it's the subdomain of the FQDN, and forms a URL. To test, verify that the
   * name is the same if it's URL encoded. Also, ensure the name isn't taken.
   *
   * @param array $element
   *   The form element to process.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param array $complete_form
   *   The complete form structure.
   *
   * @see Drupal\Core\Render\Element\Url::validateUrl()
   */
  public static function validateNewSiteName(array &$element, FormStateInterface $form_state, &$complete_form) {
    $site_name = trim($element['#value']);
    $form_state->setValueForElement($element, $site_name);

    if ($site_name === '' || (UrlHelper::encodePath($site_name) !== $site_name)) {
      $form_state->setError($element, t('The site name %subdomain is not valid. Special characters are not allowed.', [
        '%subdomain' => $site_name,
      ]));
    }

    if (!self::siteNameIsAvailable($site_name)) {
      $form_state->setError($element, t('The site name %subdomain is already taken. Please choose another.', [
        '%subdomain' => $site_name,
      ]));
    }
  }

  /**
   * Determines if the provided site name is available for use.
   *
   * @param string $site_name
   *   The name of the site.
   *
   * @return bool
   *   TRUE if the site name isn't already in use. False otherwise.
   */
  public static function siteNameIsAvailable($site_name) {
    $sites = SiteListHostingServiceCall::getSiteList();
    $enabled_sites = [];

    foreach ($sites as $site) {
      if ($site['status'] == 1) {
        $enabled_sites[] = $site['title'];
      }
    }

    if (in_array($site_name . '.' . \Drupal::request()->getHost(), $enabled_sites)) {
      return FALSE;
    }
    return TRUE;
  }

}
