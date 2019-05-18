<?php

namespace Drupal\aegir_site_subscriptions\HostingServiceCalls;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\Core\Messenger\MessengerInterface;
use GuzzleHttp\Client;
use Symfony\Component\HttpFoundation\RequestStack;

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
   * @throws \Exception
   */
  public function __construct(
    LoggerChannelFactory $logger_factory,
    Client $http_client,
    ConfigFactory $config_factory,
    RequestStack $request_stack,
    MessengerInterface $user_messenger
  ) {
    parent::__construct($logger_factory, $http_client, $config_factory, $request_stack, $user_messenger);
    $this->template = NULL;
  }

  /**
   * Set the template used for site creation.
   *
   * @param string $template
   *   The machine name of the template to use in creating the site.
   *
   * @return $this
   *   The object itself, for method chaining.
   *
   * @throws \Exception
   */
  public function setTemplate($template) {
    if (empty($template)) {
      throw new \Exception('Site-creation service callers must provide a template ID.');
    }
    $this->template = $template;
    return $this;
  }

  /**
   * Fetches the template used for creating the site.
   *
   * @return string
   *   The template ID.
   *
   * @throws \Exception
   */
  public function getTemplate() {
    if (empty($this->template)) {
      throw new \Exception('Site-creation service callers must have provided a template ID.');
    }
    return $this->template;
  }

  /**
   * Fetches the owner of the site to be created.
   *
   * @return \Drupal\user\Entity\User
   *   The user object representing the site owner.
   */
  public function getClient() {
    return $this->getSite()->getOwner();
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
   * @see \Drupal\Core\Render\Element\Url::validateUrl()
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
    $sites = \Drupal::service('aegir_site_subscriptions.hosting.site_listing')->getSiteList();
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
