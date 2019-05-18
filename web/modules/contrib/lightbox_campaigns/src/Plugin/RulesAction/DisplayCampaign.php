<?php

namespace Drupal\lightbox_campaigns\Plugin\RulesAction;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\rules\Core\RulesActionBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Display a campaign' action.
 *
 * @RulesAction(
 *   id = "lightbox_campaigns_rules_action_display_campaign",
 *   label = @Translation("Display a campaign"),
 *   category = @Translation("Lightbox Campaigns"),
 *   context = {
 *     "campaign_id" = @ContextDefinition("integer",
 *       label = @Translation("Lightbox Campaign ID"),
 *       description = @Translation("The ID of the lightbox campaign to display to the user.")
 *     )
 *   }
 * )
 */
class DisplayCampaign extends RulesActionBase implements ContainerFactoryPluginInterface {

  /**
   * The Rules Action ID for reference within this class.
   *
   * @var string
   */
  protected $id;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The temp store factory.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStoreFactory
   */
  protected $tempStore;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, PrivateTempStoreFactory $temp_store_factory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->id = 'lightbox_campaigns_rules_action_display_campaign';
    $this->tempStore = $temp_store_factory->get('lightbox_campaigns');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('tempstore.private')
    );
  }

  /**
   * Action: Display a campaign.
   *
   * Assigns a temp storage array and adds the Campaign ID. This array is
   * evaluated in lightbox_campaigns_page_bottom() so it may not be picked up
   * until the _next_ page load if the triggering event fires this action after
   * that hook is executed.
   *
   * @param int $campaign_id
   *   The ID of the campaign to display.
   */
  protected function doExecute($campaign_id) {
    try {
      $campaigns = $this->tempStore->get($this->id);
      if (is_array($campaigns)) {
        $campaigns[$campaign_id] = $campaign_id;
      }
      else {
        $campaigns = [$campaign_id => $campaign_id];
      }
      $this->tempStore->set($this->id, $campaigns);
    }
    catch (\Exception $e) {
      watchdog_exception('lightbox_campaigns', $e);
    }

  }

}
