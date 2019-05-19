<?php

namespace Drupal\uc_file\Form;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form step values.
 */
define('UC_FILE_FORM_ACTION', 1);

/**
 * Displays all files that may be purchased and downloaded for administration.
 */
class ShowForm extends FormBase {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Form constructor.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(ModuleHandlerInterface $module_handler) {
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'uc_file_admin_files_show_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#tree'] = TRUE;
    $form['#attached']['library'][] = 'uc_file/uc_file.styles';

    $header = [
      'filename' => ['data' => $this->t('File'), 'field' => 'f.filename', 'sort' => 'asc'],
      'title' => ['data' => $this->t('Product'), 'field' => 'n.title'],
      'model' => ['data' => $this->t('SKU'), 'field' => 'fp.model'],
    ];

    // Create pager.
    $query = db_select('uc_files', 'f')
      ->extend('Drupal\Core\Database\Query\PagerSelectExtender')
      ->extend('Drupal\Core\Database\Query\TableSortExtender')
      ->orderByHeader($header)
      ->limit(UC_FILE_PAGER_SIZE);
    $query->leftJoin('uc_file_products', 'fp', 'f.fid = fp.fid');
    $query->leftJoin('uc_product_features', 'pf', 'fp.pfid = pf.pfid');
    $query->leftJoin('node_field_data', 'n', 'pf.nid = n.nid');
    $query->addField('n', 'nid');
    $query->addField('f', 'filename');
    $query->addField('n', 'title');
    $query->addField('fp', 'model');
    $query->addField('f', 'fid');
    $query->addField('pf', 'pfid');

    $count_query = db_select('uc_files');
    $count_query->addExpression('COUNT(*)');

    $query->setCountQuery($count_query);
    $result = $query->execute();

    $options = [];
    foreach ($result as $file) {
      // All files are shown here, including files which are not attached
      // to products.
      if (isset($file->nid)) {
        $options[$file->fid] = [
          'filename' => [
            'data' => ['#plain_text' => $file->filename],
            'class' => is_dir(uc_file_qualify_file($file->filename)) ? ['uc-file-directory-view'] : [],
          ],
          'title' => [
            'data' => [
              '#type' => 'link',
              '#title' => $file->title,
              '#url' => Url::fromRoute('entity.node.canonical', ['node' => $file->nid]),
            ],
          ],
          'model' => [
            'data' => ['#plain_text' => $file->model],
          ],
        ];
      }
      else {
        $options[$file->fid] = [
          'filename' => [
            'data' => ['#plain_text' => $file->filename],
            'class' => is_dir(uc_file_qualify_file($file->filename)) ? ['uc-file-directory-view'] : [],
          ],
          'title' => '',
          'model' => '',
        ];
      }
    }

    // Create checkboxes for each file.
    $form['file_select'] = [
      '#type' => 'tableselect',
      '#header' => $header,
      '#options' => $options,
      '#empty' => $this->t('No file downloads available.'),
    ];

    $form['uc_file_action'] = [
      '#type' => 'details',
      '#title' => $this->t('File options'),
      '#open' => TRUE,
    ];

    // Set our default actions.
    $file_actions = [
      'uc_file_upload' => $this->t('Upload file'),
      'uc_file_delete' => $this->t('Delete file(s)'),
    ];

    // Check if any hook_uc_file_action('info', $args) are implemented.
    foreach ($this->moduleHandler->getImplementations('uc_file_action') as $module) {
      $name = $module . '_uc_file_action';
      $result = $name('info', NULL);
      if (is_array($result)) {
        foreach ($result as $key => $action) {
          if ($key != 'uc_file_delete' && $key != 'uc_file_upload') {
            $file_actions[$key] = $action;
          }
        }
      }
    }

    $form['uc_file_action']['action'] = [
      '#type' => 'select',
      '#title' => $this->t('Action'),
      '#prefix' => '<div class="duration">',
      '#options' => $file_actions,
      '#suffix' => '</div>',
    ];

    $form['uc_file_actions']['actions'] = ['#type' => 'actions'];
    $form['uc_file_action']['actions']['submit'] = [
      '#type' => 'submit',
      '#prefix' => '<div class="duration">',
      '#value' => $this->t('Perform action'),
      '#suffix' => '</div>',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    switch ($form_state->getValue(['uc_file_action', 'action'])) {
      case 'uc_file_delete':
        $file_ids = [];
        if (is_array($form_state->getValue('file_select'))) {
          foreach ($form_state->getValue('file_select') as $fid => $value) {
            if ($value) {
              $file_ids[] = $fid;
            }
          }
        }
        if (count($file_ids) == 0) {
          $form_state->setErrorByName('', $this->t('You must select at least one file to delete.'));
        }
        break;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Increment the form step.
    $form_state->set('step', UC_FILE_FORM_ACTION);
    $form_state->setRebuild();
  }

}
