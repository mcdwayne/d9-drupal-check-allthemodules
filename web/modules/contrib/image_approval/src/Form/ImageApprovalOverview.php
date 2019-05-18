<?php

namespace Drupal\image_approval\Form;

use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\image_approval\ImageApprovalItemInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the image approval overview administration form.
 */
class ImageApprovalOverview extends FormBase {


  /**
   * The Database Connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The render service.
   *
   * @var \Drupal\Core\Render\Renderer
   */
  protected $renderer;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * Creates a ImageApprovalOverview form.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The connection service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The render service.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   */
  public function __construct(Connection $database, EntityTypeManagerInterface $entity_type_manager, RendererInterface $renderer, DateFormatterInterface $date_formatter) {
    $this->database = $database;
    $this->fileStorage = $entity_type_manager->getStorage('file');
    $this->renderer = $renderer;
    $this->dateFormatter = $date_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('entity_type.manager'),
      $container->get('renderer'),
      $container->get('date.formatter')
    );
  }

  /**
   * Returns the operations drop down menu for each type of overview forms.
   *
   * Create the approval dropdown menu items
   * and the callbacks.
   *
   * @return array
   *   An associative array to be used with Drupal Menu API.
   */
  private function imageApprovalOperations($op_type) {
    switch ($op_type) {
      case 'approved':
        $operations = [
          'disapprove' => [
            'text' => $this->t('Disapprove the selected images'),
            'callback' => 'image_approval_do_disapprove',
          ],
        ];
        break;

      case 'disapproved':
        $operations = [
          'approve' => [
            'text' => $this->t('Approve the selected images'),
            'callback' => 'image_approval_do_approval',
          ],
          'delete' => [
            'text' => $this->t('Delete the selected images'),
            'callback' => 'image_approval_do_delete',
          ],
        ];
        break;

      default:
        $operations = [
          'approve' => [
            'text' => $this->t('Approve the selected images'),
            'callback' => 'image_approval_do_approval',
          ],
          'disapprove' => [
            'text' => $this->t('Disapprove the selected images'),
            'callback' => 'image_approval_do_disapprove',
          ],
        ];
    }
    return $operations;
  }

  /**
   * Form constructor for the image approval overview administration form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param string $type
   *   The type of the overview form ('awaiting','approve' or 'disapprove').
   *
   * @return array
   *   The form structure.
   */
  public function buildForm(array $form, FormStateInterface $form_state, $type = 'awaiting') {

    $form['options'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Update options'),
      '#prefix' => '<div class="container-inline">',
      '#suffix' => '</div>',
    ];

    $operations = $this->imageApprovalOperations($type);
    $options = [];
    foreach ($operations as $key => $value) {
      // Set each option.
      $options[$key] = $value['text'];
    }

    $form['options']['operation'] = [
      '#type' => 'select',
      '#options' => $options,
      '#default_value' => 'approve',
    ];
    $form['options']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Update'),
    ];

    // Load the form's header.
    $header = [
      'picture' => [
        'data' => $this->t('Image'),
      ],
      'users' => [
        'data' => $this->t('User'),
      ],
      'timestamp' => [
        'data' => $this->t('Time'),
        'field' => 'timestamp',
        'sort' => 'asc',
      ],
      'moderator' => [
        'data' => $this->t('Moderated by'),
        'field' => 'moderator',
        'sort' => 'desc',
      ],
    ];

    // Set the basic SQL Statement.
    $sql = $this->database->select('image_approval', 'im')
      ->fields('im', ['timestamp', 'moderator', 'fid']);
    $table_sort = $sql->extend('Drupal\Core\Database\Query\TableSortExtender')
      ->orderByHeader($header);

    // If $type is NULL, the page is in awaiting approval section.
    // The default menu setting shows all the images that needs to be moderated.
    if ($type == 'awaiting') {
      $sql = $sql->condition('status', ImageApprovalItemInterface::IMAGE_APPROVAL_PENDING, '=');
    }
    elseif ($type == 'disapproved') {
      $sql = $sql->condition('status', ImageApprovalItemInterface::IMAGE_APPROVAL_DISAPPROVED, '=');
    }
    elseif ($type == 'approved') {
      $sql = $sql->condition('status', ImageApprovalItemInterface::IMAGE_APPROVAL_APPROVED, '=');
    }
    // Order by table header.
    // Limit the rows to 20 for each page.
    $pager = $table_sort->extend('Drupal\Core\Database\Query\PagerSelectExtender')
      ->limit(20);
    $result = $pager->execute();

    $images = [];
    foreach ($result as $image) {
      if (!$image->moderator) {
        $image->moderator = $this->t('Pending moderation');
      }

      $file = $this->fileStorage->load($image->fid);

      $user_name = $file->getOwner()->getAccountName();
      $uid = $file->getOwnerId();

      $url = Url::fromRoute('entity.user.canonical', ['user' => $uid]);
      $path = Link::fromTextAndUrl($user_name, $url);

      $render = [
        '#theme' => 'image_style',
        '#style_name' => 'thumbnail',
        '#uri' => $file->getFileUri() ,
      // Optional parameters.
      ];
      $images[$image->fid] = [
        'picture' => $this->renderer->render($render),
        'users' => $path,
        'timestamp' => $this->dateFormatter->format($image->timestamp, 'short'),
        'moderator' => $image->moderator,
      ];
    }

    $form['image_approval'] = [
      '#type' => 'tableselect',
      '#options' => $images,
      '#header' => $header,
      '#multiple' => TRUE,
      '#empty' => $this->t('No images found.'),
    ];

    $form['pager'] = ['#type' => 'pager'];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'image_approval_admin_overview';
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $count = 0;
    foreach ($form_state->getValue('image_approval') as $key => $value) {
      if ($value != 0) {
        $count++;
      }
    }
    if ($count == 0) {
      $form_state->setErrorByName('image_approval', $this->t('Please select one or more images to perform the update on.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $edit = $form_state->getValues();
    // Get a list of operations.
    $arg = $form_state->getBuildInfo();
    $operations = $this->imageApprovalOperations($arg['args'][0]);
    // Select the proper callback from the operation.
    // ie: $operation['disapprove']['callback'].
    if ($operations[$edit['operation']]['callback']) {
      $callback = $operations[$edit['operation']]['callback'];
      if (!is_null($callback)) {
        // Then run through all the images that were checked.
        foreach ($edit['image_approval'] as $key => $value) {
          if (!empty($value)) {
            $callback($key);
          }
        }
      }
    }
    drupal_flush_all_caches();
    $message = $this->t('The update (%operation) has been performed.', ["%operation" => $edit['operation']]);
    drupal_set_message($message);

  }

}
