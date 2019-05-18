<?php

namespace Drupal\brightcove\Controller;

use Brightcove\API\Exception\APIException;
use Drupal\brightcove\BrightcoveUtil;
use Drupal\brightcove\Entity\BrightcoveAPIClient;
use Drupal\brightcove\Entity\BrightcoveSubscription;
use Drupal\brightcove\Entity\BrightcoveVideo;
use Drupal\brightcove\Entity\Exception\BrightcoveSubscriptionException;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Url;
use Drupal\Core\Utility\LinkGeneratorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Provides controller for subscription related callbacks.
 */
class BrightcoveSubscriptionController extends ControllerBase {

  /**
   * Database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Link generator.
   *
   * @var \Drupal\Core\Utility\LinkGeneratorInterface
   */
  protected $linkGenerator;

  /**
   * BrightcoveVideo storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $videoStorage;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('link_generator'),
      $container->get('entity_type.manager')->getStorage('brightcove_video')
    );
  }

  /**
   * BrightcoveSubscriptionController constructor.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   Database connection.
   * @param \Drupal\Core\Utility\LinkGeneratorInterface $link_generator
   *   Link generator.
   * @param \Drupal\Core\Entity\EntityStorageInterface $video_storage
   *   Brightcove video storage.
   */
  public function __construct(Connection $connection, LinkGeneratorInterface $link_generator, EntityStorageInterface $video_storage) {
    $this->connection = $connection;
    $this->linkGenerator = $link_generator;
    $this->videoStorage = $video_storage;
  }

  /**
   * Menu callback to handle the Brightcove notification callback.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Request object.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   Redirection response.
   *
   * @throws \Exception
   */
  public function notificationCallback(Request $request) {
    BrightcoveUtil::runWithSemaphore(function () use ($request) {
      $content = Json::decode($request->getContent());

      switch ($content['event']) {
        case 'video-change':
          // Try to update an existing video or create a new one if not exist.
          try {
            // Get CMS API.
            $api_client = BrightcoveAPIClient::loadByAccountId($content['account_id']);
            if (!empty($api_client)) {
              $cms = BrightcoveUtil::getCmsApi($api_client->id());
              $video = $cms->getVideo($content['video']);
              BrightcoveVideo::createOrUpdate($video, $this->videoStorage, $api_client->id());
            }
          }
          catch (\Exception $e) {
            // Log exception except if it's an APIException and the response
            // code was 404.
            if (($e instanceof APIException && $e->getCode() != 404) || !($e instanceof APIException)) {
              watchdog_exception('brightcove', $e);
            }
          }
          break;
      }
    });

    return new Response();
  }

  /**
   * Lists available Brightcove Subscriptions.
   *
   * @throws \Drupal\brightcove\Entity\Exception\BrightcoveSubscriptionException
   */
  public function listSubscriptions() {
    // Set headers.
    $header = [
      'endpoint' => $this->t('Endpoint'),
      'api_client' => $this->t('API Client'),
      'events' => $this->t('Events'),
      'operations' => $this->t('Operations'),
    ];

    // Get Subscriptions.
    $brightcove_subscriptions = BrightcoveSubscription::loadMultiple();

    // Whether a warning has benn shown about the missing subscriptions on
    // Brightcove or not.
    $warning_set = FALSE;

    // Assemble subscription list.
    $rows = [];
    foreach ($brightcove_subscriptions as $key => $brightcove_subscription) {
      $api_client = $brightcove_subscription->getApiClient();

      $rows[$key] = [
        'endpoint' => $brightcove_subscription->getEndpoint() . ($brightcove_subscription->isDefault() ? " ({$this->t('default')})" : ''),
        'api_client' => !empty($api_client) ? $this->linkGenerator->generate($api_client->label(), Url::fromRoute('entity.brightcove_api_client.edit_form', [
          'brightcove_api_client' => $api_client->id(),
        ])) : '',
        'events' => implode(', ', array_filter($brightcove_subscription->getEvents(), function ($value) {
          return !empty($value);
        })),
      ];

      // Default subscriptions can be enabled or disabled only.
      if ((bool) $brightcove_subscription->isDefault()) {
        $enable_link = Url::fromRoute('entity.brightcove_subscription.enable', [
          'id' => $brightcove_subscription->getId(),
        ]);

        $disable_link = Url::fromRoute('entity.brightcove_subscription.disable', [
          'id' => $brightcove_subscription->getId(),
        ]);

        $rows[$key]['operations'] = [
          'data' => [
            '#type' => 'operations',
            '#links' => [
              'change_status' => [
                'title' => $brightcove_subscription->isActive() ? $this->t('Disable') : $this->t('Enable'),
                'url' => $brightcove_subscription->isActive() ? $disable_link : $enable_link,
              ],
            ],
          ],
        ];
      }
      // Otherwise show delete button or create button as well if needed.
      else {
        $subscriptions = BrightcoveSubscription::listFromBrightcove($api_client);

        $subscription_found = FALSE;
        foreach ($subscriptions as $subscription) {
          if ($brightcove_subscription->getEndpoint() == $subscription->getEndpoint()) {
            $subscription_found = TRUE;

            // If the endpoint exist but their ID is different, fix it.
            if ($brightcove_subscription->getBcSid() != ($id = $subscription->getId())) {
              $brightcove_subscription->setBcSid($id);
              $brightcove_subscription->save();
            }
            break;
          }
        }

        if (!$warning_set && !$subscription_found) {
          drupal_set_message($this->t('There are subscriptions which are not available on Brightcove.<br>You can either <strong>create</strong> them on Brightcove or <strong>delete</strong> them if no longer needed.'), 'warning');
          $warning_set = TRUE;
        }

        // Add create link if the subscription is missing from Brightcove.
        $create_link = [];
        if (!$subscription_found) {
          $create_link = [
            'create' => [
              'title' => $this->t('Create'),
              'url' => Url::fromRoute('entity.brightcove_subscription.create', [
                'id' => $brightcove_subscription->getId(),
              ]),
            ],
          ];
        }

        $rows[$key]['operations'] = [
          'data' => [
            '#type' => 'operations',
            '#links' => $create_link + [
              'delete' => [
                'title' => $this->t('Delete'),
                'weight' => 10,
                'url' => Url::fromRoute('entity.brightcove_subscription.delete_form', [
                  'id' => $brightcove_subscription->getId(),
                ]),
              ],
            ],
          ],
        ];
      }
    }

    // Check default subscriptions for each api client.
    $api_clients_without_default_subscription = [];
    /** @var \Drupal\brightcove\Entity\BrightcoveAPIClient $api_client */
    foreach (BrightcoveAPIClient::loadMultiple() as $api_client) {
      if (BrightcoveSubscription::loadDefault($api_client) == NULL) {
        $api_clients_without_default_subscription[] = $api_client->getLabel();
      }
    }
    if (!empty($api_clients_without_default_subscription)) {
      drupal_set_message($this->t('There are missing default subscription(s) for the following API Client(s): %api_clients<br><a href="@link">Create missing subscription(s)</a>.', [
        '%api_clients' => implode(', ', $api_clients_without_default_subscription),
        '@link' => Url::fromRoute('entity.brightcove_subscription.create_defaults')->toString(),
      ]), 'warning');
    }

    $page['subscriptions'] = [
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('There are no subscriptions yet.'),
    ];

    return $page;
  }

  /**
   * Create a subscription on Brightcove from an already existing entity.
   *
   * @param int $id
   *   BrightcoveSubscription entity ID.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Redirect response to redirect user after creating a Drupal only
   *   subscription.
   */
  public function createSubscription($id) {
    try {
      $brightcove_subscription = BrightcoveSubscription::load($id);
      $brightcove_subscription->saveToBrightcove();
    }
    catch (BrightcoveSubscriptionException $e) {
      drupal_set_message($this->t('Failed to create Subscription on Brightcove: @error', ['@error' => $e->getMessage()]), 'error');
    }

    return $this->redirect('entity.brightcove_subscription.list');
  }

  /**
   * Enables and creates the default Subscription from Brightcove.
   *
   * @param string $id
   *   The ID of the Brightcove Subscription.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Redirect response to redirect user after enabling the default
   *   subscription.
   */
  public function enable($id) {
    try {
      $subscription = BrightcoveSubscription::load($id);
      $subscription->saveToBrightcove();
      drupal_set_message($this->t('Default subscription for the "@api_client" API client has been successfully enabled.', ['@api_client' => $subscription->getApiClient()->label()]));
    }
    catch (\Exception $e) {
      drupal_set_message($this->t('Failed to enable the default subscription: @error', ['@error' => $e->getMessage()]), 'error');
    }
    return $this->redirect('entity.brightcove_subscription.list');
  }

  /**
   * Disabled and removed the default Subscription from Brightcove.
   *
   * @param string $id
   *   The ID of the Brightcove Subscription.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Redirect response to redirect user after enabling the default
   *   subscription.
   */
  public function disable($id) {
    try {
      $subscription = BrightcoveSubscription::load($id);
      $subscription->deleteFromBrightcove();
      drupal_set_message($this->t('Default subscription for the "@api_client" API client has been successfully disabled.', ['@api_client' => $subscription->getApiClient()->label()]));
    }
    catch (\Exception $e) {
      drupal_set_message($this->t('Failed to disable the default subscription: @error', ['@error' => $e->getMessage()]), 'error');
    }
    return $this->redirect('entity.brightcove_subscription.list');
  }

  /**
   * Creates default subscriptions.
   *
   * This method must be called through the site's URL, otherwise the default
   * subscriptions won't be possible to create, because of the missing site URL.
   */
  public function createDefaults() {
    try {
      // Get all available API clients.
      $api_clients = BrightcoveAPIClient::loadMultiple();

      foreach ($api_clients as $api_client) {
        $brightcove_subscription = BrightcoveSubscription::loadDefault($api_client);

        // Try to grab an existing subscription by the site's endpoint URL if
        // the default doesn't exist for the current API client.
        $default_endpoint = BrightcoveUtil::getDefaultSubscriptionUrl();
        if (empty($brightcove_subscription)) {
          $brightcove_subscription = BrightcoveSubscription::loadByEndpoint($default_endpoint);
        }

        // If there is an existing subscription with an endpoint, make it
        // default.
        if (!empty($brightcove_subscription)) {
          $this->connection->update('brightcove_subscription')
            ->fields([
              'is_default' => 1,
            ])
            ->condition('id', $brightcove_subscription->getId())
            ->execute();
        }
        // Otherwise create a new local subscription with the site's URL.
        else {
          // Check Brightcove whether if it has a subscription for the default
          // one.
          $subscriptions = BrightcoveSubscription::listFromBrightcove($api_client);
          $subscription_with_default_endpoint = NULL;
          foreach ($subscriptions as $subscription) {
            if ($subscription->getEndpoint() == $default_endpoint) {
              $subscription_with_default_endpoint = $subscription;
              break;
            }
          }

          // Create a new default subscription for the API client.
          $brightcove_subscription = new BrightcoveSubscription(TRUE);
          $brightcove_subscription->setEvents(['video-change']);
          $brightcove_subscription->setEndpoint($default_endpoint);
          $brightcove_subscription->setApiClient($api_client);

          if ($subscription_with_default_endpoint !== NULL) {
            $brightcove_subscription->setBcSid($subscription_with_default_endpoint->getId());
            $brightcove_subscription->setStatus(TRUE);
          }
          else {
            $brightcove_subscription->setStatus(FALSE);
          }

          $brightcove_subscription->save();
        }
      }

      drupal_set_message($this->t('Default subscriptions has been successfully created.'));
    }
    catch (\Exception $e) {
      drupal_set_message($this->t('Failed to create default subscription(s), @error', ['@error' => $e->getMessage()]), 'error');
      watchdog_exception('brightcove', $e, 'Failed to create default subscription(s), @error', ['@error' => $e->getMessage()]);
    }

    return $this->redirect('entity.brightcove_subscription.list');
  }

}
