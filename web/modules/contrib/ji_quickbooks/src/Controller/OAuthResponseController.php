<?php

namespace Drupal\ji_quickbooks\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\State\StateInterface;
use Drupal\ji_quickbooks\JIQuickBooksService;

/**
 * Processes $_POST data from ji-quickbooks-v3.joshideas.com.
 */
class OAuthResponseController extends ControllerBase {

  /**
   * Dependency injection.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $stateManager;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\State\StateInterface $state_manager
   *   State manager.
   */
  public function __construct(StateInterface $state_manager) {
    $this->stateManager = $state_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('state'));
  }

  /**
   * Work around.
   *
   * Drupal 8 didn't accept $_POST data from the same form that
   * sent a redirect.  This is the only way I got it to work.
   */
  public function saveOauthSettingsPage() {
    if ($this->sanitize('realm_id')) {
      $this->stateManager->set('ji_quickbooks_settings_realm_id', $this->sanitize('realm_id'));

      // We just authenticated, mark when this occured so we can
      // auto-renew after five months, starting now.
      $this->stateManager->set('ji_quickbooks_cron_started_on', \Drupal::time()
        ->getRequestTime());
    }
    if ($this->sanitize('access_token')) {
      $this->stateManager->set('ji_quickbooks_settings_access_token', $this->sanitize('access_token'));
    }
    if ($this->sanitize('refresh_token')) {
      $this->stateManager->set('ji_quickbooks_settings_refresh_token', $this->sanitize('refresh_token'));
    }

    // Get QBO options.
    $ji_quickbooks = new JIQuickBooksService();
    if ($ji_quickbooks) {
      $preferences = $ji_quickbooks->dataService->Query("select * from Preferences");
      if ($preferences) {
        $preferences = reset($preferences);
        /** @var \QuickBooksOnline\API\Data\IPPPreferences $preferences */
        $shipping_enabled = ($preferences->SalesFormsPrefs->AllowShipping === 'true') ? 1 : 0;
        $this->stateManager->set('ji_quickbooks_config_qbo_preferences_shipping_field', $shipping_enabled);

        $discount_enabled = ($preferences->SalesFormsPrefs->AllowDiscount === 'true') ? 1 : 0;
        $this->stateManager->set('ji_quickbooks_config_qbo_preferences_discount_field', $discount_enabled);
        $this->stateManager->set('ji_quickbooks_discount_account', $preferences->SalesFormsPrefs->DefaultDiscountAccount);
      }
    }

    return new RedirectResponse(Url::fromRoute('ji_quickbooks.form')
      ->toString());
  }

  private function sanitize($variable_name, $filter = FILTER_SANITIZE_STRING) {
    return filter_input(INPUT_POST, $variable_name, $filter);
  }

}
