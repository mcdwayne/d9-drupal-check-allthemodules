<?php

namespace Drupal\lightspeed_ecom\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Utility\Error;
use Drupal\lightspeed_ecom\ShopInterface;
use Drupal\lightspeed_ecom\Service\Webhook;
use Drupal\lightspeed_ecom\Service\WebhookRegistryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class WebhookAdminController.
 *
 * @package Drupal\lightspeed_ecom\Controller
 */
class WebhookAdminController extends ControllerBase {

  /**
   * @var \Drupal\lightspeed_ecom\Service\WebhookRegistryInterface
   */
  protected $webhook_registry;

  /**
   * {@inheritdoc}
   */
  public function __construct(WebhookRegistryInterface $webhook_registry) {
    $this->webhook_registry = $webhook_registry;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('lightspeed.ecom.webhook_registry')
    );
  }

  /**
   * Provides the title for the Webhooks list page for a given shop.
   *
   * @param \Drupal\lightspeed_ecom\ShopInterface $shop
   *  The shop to return the title for.
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *  The title for the Webhook page for the given collection.
   */
  public function indexTitle(ShopInterface $shop) {
    return $this->t("Webhooks (@shop)", ['@shop' => $shop->label()]);
  }

  /**
   * Page callback for the Webhooks admin page for a given shop.
   *
   * @return array
   *   The page content as a renderable array.
   */
  public function index(ShopInterface $shop) {

    /** @var Webhook[] $webhooks */
    $webhooks = $this->webhook_registry->getWebhooks($shop);

    $status = [
      Webhook::STATUS_ACTIVE => $this->t('Active'),
      Webhook::STATUS_INACTIVE => $this->t('Inactive'),
      Webhook::STATUS_UNREGISTERED => $this->t('Unregistered'),
      Webhook::STATUS_UNKNOWN => $this->t('Unknown'),
    ];

    $table = [
      "#type" => 'table',
      "#header" => [
        $this->t('Group'),
        $this->t('Action'),
        $this->t('Status'),
        $this->t('Services'),
      ],
      "#rows" => [],
      "#empty" => $this->t('No registered Webhooks listener.'),
      '#attached' => [
        'library' => [
          'lightspeed_ecom/admin',
        ]
      ]
    ];
    $page = [];
    $page['list'] = $table;
    //$table['#attached']['library'][] = 'lightspeed_ecom/admin';
    foreach ($webhooks as $webhook) {
      $table['#rows'][] = [
        $webhook->getGroup(),
        $webhook->getAction(),
        $status[$webhook->getStatus()],
        implode(', ', $webhook->getListeners()),
      ];
    }

    return $table;

  }

  /**
   * Page callback for the 'Synchronize Webhooks' local actions.
   *
   * @param \Drupal\lightspeed_ecom\ShopInterface $shop
   *   The shop to synchronize.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A redirect to the shop's webhooks list.
   */
  public function synchronize(ShopInterface $shop) {
    try {
      $this->webhook_registry->synchronize($shop);
    }
    catch (\Exception $exception) {
      $error = Error::decodeException($exception);
      $this->getLogger('lightspeed_ecom')
        ->error("Error while synchronizing webhooks: @message [%type]\n {backtrace_string}", $error);
      drupal_set_message($this->t('Error while synchronizing webhooks: @message [%type]', $error), 'error');
    }
    return $this->redirect('lightspeed_ecom.settings.webhooks_list', [
      'shop' => $shop->id(),
    ]);
  }

}
