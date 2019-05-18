<?php

namespace Drupal\campaignmonitor_campaign\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
// Use Drupal\Core\Url;
// use Drupal\user\PermissionHandlerInterface;.
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\campaignmonitor_campaign\CampaignMonitorCampaign;
use Drupal\user\Entity\User;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Render\HtmlResponse;
use Drupal\Core\Render\HtmlResponseAttachmentsProcessor;

/**
 * Campaign Monitor send form.
 *
 * @package Drupal\campaignmonitor_campaign\Form
 */
class CampaignMonitorCampaignSendForm extends FormBase {


  /**
   * The entity handler.
   */
  protected $entityManager;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\Renderer
   */
  protected $renderer;

  /**
   * The HTML response attachments processor service.
   *
   * @var \Drupal\Core\Render\AttachmentsResponseProcessorInterface
   */
  protected $htmlResponseAttachmentsProcessor;

  /**
   * Constructs a new CampaignMonitorNodeSettingsForm.
   *
   * @param \Drupal\user\PermissionHandlerInterface $permission_handler
   *   The permission handler.
   */
  public function __construct(EntityManagerInterface $entity_manager, RendererInterface $renderer, HtmlResponseAttachmentsProcessor $html_response_attachments_processor) {
    // $this->permissionHandler = $permission_handler;.
    $this->entityManager = $entity_manager;
    $this->renderer = $renderer;
    $this->htmlResponseAttachmentsProcessor = $html_response_attachments_processor;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager'),
      $container->get('renderer'),
      $container->get('html_response.attachments_processor')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'campaignmonitor_campaign_node_settings';
  }

  /**
   * @param int $node
   *   Node nid
   *   {@inheritdoc}.
   */
  public function buildForm(array $form, FormStateInterface $form_state, $node = NULL) {
    $node_storage = $this->entityManager->getStorage('node');

    // We use the load function to load a single node object.
    $node = $node_storage->load($node);
    $storage = [
      'node' => $node,
    ];

    $form_state->setStorage($storage);

    $form['send'] = [
      '#type' => 'submit',
      '#value' => t('Send'),
      '#weight' => 10,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $storage = $form_state->getStorage();
    $node = $storage['node'];

    // Get settings.
    $settings = campaignmonitor_campaign_get_node_settings('all', $node->bundle());

    $filepath = $this->createHtmlFile($settings, $node);

    $data = $this->createDataPacket($settings, $node, $filepath);

    $cm_campaign = new CampaignMonitorCampaign();

    if ($cm_campaign->createCampaign($data)) {
      drupal_set_message(t('Your campaign has been sent!!.'));
    }
    else {
      drupal_set_message(t('Your campaign failed to send.'));
    }
  }

  /**
   * Create the file that campaign monitor will use for the campaign.
   *
   * @param array $settings
   * @param object $node
   *
   * @return Web accessible filepath of saved file
   */
  protected function createHtmlFile($settings, $node) {
    $config = \Drupal::config('campaignmonitor_campaign.settings');
    $view_builder = \Drupal::entityTypeManager()->getViewBuilder('node');
    $render_array = $view_builder->view($node, $settings['view_mode']);
    $theme = \Drupal::service('theme.manager')->getActiveTheme();
    $renderer = \Drupal::service('bare_html_page_renderer');
    $libraries = $theme->getLibraries();

    // Foreach ($libraries as $library) {
    //      $render_array['#attached']['library'][] = $library;
    //    }.
    $render_array['#attached']['library'][] = $config->get('css_library');
    $render_array['#attached']['html_response_attachment_placeholders'] = ['styles' => '[styles]'];
    $html = render($render_array);

    // Add the document theme wrapper.
    $template_array = [
      '#html' => $html->__toString(),
      '#theme' => 'campaignmonitor_campaign_html',
    ];

    $template = render($template_array);
    $response = new HtmlResponse();
    $response->setContent($template_array);

    // Process attachments, because this does not go via the regular render
    // pipeline, but will be sent directly.
    $response->setAttachments($render_array['#attached']);
    $response = $this->htmlResponseAttachmentsProcessor->processAttachments($response);
    $contents = $response->getContent();

    // Save the contents to a file in the public file directory.
    $filepath = campaignmonitor_campaign_get_filepath($node);
    file_put_contents($filepath, $contents);
    return campaignmonitor_campaign_get_filepath($node, 'path');
  }

  /**
   * Create the packet to send to Campaign Monitor.
   *
   * @param array $settings
   * @param object $node
   * @param string $filepath
   */
  protected function createDataPacket($settings, $node, $filepath) {
    $author = $node->getOwner();
    $account = User::load($author->id());
    return [
      'Subject' => $node->getTitle(),
      'Name' => $node->getTitle(),
      'FromName' => $account->getDisplayName(),
      'FromEmail' => $account->getEmail(),
      'ReplyTo' => $account->getEmail(),
      'HtmlUrl' => $filepath,
      // 'TextUrl' => 'Optional campaign text import URL',.
      'ListIDs' => $settings['lists'],
    // 'SegmentIDs' => array('First Segment', 'Second Segment')
    ];

  }

}
