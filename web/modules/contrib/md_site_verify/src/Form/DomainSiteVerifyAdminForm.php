<?php

namespace Drupal\md_site_verify\Form;

use Drupal\Core\Database\Connection;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\domain\DomainStorage;
use Drupal\md_site_verify\Service\DomainSiteVerifyService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class DomainSiteVerifyAdminForm
 *
 * @package Drupal\md_site_verify\Form
 */
class DomainSiteVerifyAdminForm extends ConfigFormBase {

  /**
   * @var \Drupal\Core\Database\Connection $database
   */
  protected $database;

  /**
   * Domain storage.
   *
   * @var \Drupal\md_site_verify\Service\DomainSiteVerifyService
   */
  protected $domainSiteVerify;

  /**
   * Domain storage.
   *
   * @var \Drupal\domain\DomainStorageInterface
   */
  protected $domainStorage;

  /**
   * DomainSiteVerifyAdminForm constructor.
   *
   * @param \Drupal\domain\DomainStorage $domainStorage
   *   The domain storage.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database loader.
   *
   * @param \Drupal\md_site_verify\Service\DomainSiteVerifyService $domainSiteVerify
   *   The service domain verification.
   */
  public function __construct(DomainStorage $domainStorage, Connection $database, DomainSiteVerifyService $domainSiteVerify) {
    $this->domainStorage = $domainStorage;
    $this->database = $database;
    $this->domainSiteVerify = $domainSiteVerify;
  }

  /**
   * Create function return static.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   Load the ContainerInterface.
   *
   * @return static
   *   Return domain loader configuration and database and domain service verfy.
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')->getStorage('domain'),
      $container->get('database'),
      $container->get('md_site_verify_service')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['md_site_verify.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'md_site_verify_admin';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $record = [], $dsverify = NULL) {

    $form = parent::buildform($form, $form_state);

    if (!empty($dsverify)) {
      $record = $this->domainSiteVerify->domainSiteVerifyLoad($dsverify);
    }

    $storage = $form_state->getStorage();
    if (!isset($storage['step'])) {
      $record += [
        'dsv_id' => NULL,
        'domain_id' => '',
        'engine' => '',
        'file' => '',
        'file_contents' => $this->t('This is a verification page.'),
        'meta' => '',
      ];
      !empty($record['engine']) ? $form_state->setStorage([
        'step' => 2,
        'record' => $record,
      ]) : $form_state->setStorage(['step' => 1, 'record' => $record]);
    }
    else {
      $record = $storage['record'];
    }

    $storage = $form_state->getStorage();

    switch ($storage['step']) {
      case 1 :
        $engines = $this->domainSiteVerify->domainSiteVerifyGetEngines();
        $options = [];
        foreach ($engines as $key => $engine) {
          $options[$key] = $engine['name'];
        }

        $form['engine'] = [
          '#type' => 'select',
          '#title' => $this->t('Search engine'),
          '#options' => $options,
        ];
        $form['actions']['submit']['#value'] = $this->t('Next');
        break;
      case 2:
        $form['svid'] = [
          '#type' => 'value',
          '#value' => isset($record['dsv_id']) ? $record['dsv_id'] : NULL,
        ];
        $options = $this->domainStorage->loadOptionsList();
        $form['engine'] = [
          '#type' => 'value',
          '#value' => $record['engine']['key'],
        ];
        $form['engine_name'] = [
          '#type' => 'item',
          '#title' => $this->t('Search engine'),
          '#markup' => $record['engine']['name'],
        ];
        $form['#engine'] = $record['engine'];
        $form['domain_id'] = [
          '#type' => 'select',
          '#title' => 'Domain',
          '#options' => $options,
          '#default_value' => isset($record['domain_id']) ? $record['domain_id'] : t('Choose domain'),
          '#description' => t('Choose the domain for which this verification should be active.'),
        ];
        if (isset($record['engine']['file'])) {
          $form['#attributes'] = ['enctype' => 'multipart/form-data'];
        }
        $form['search_console'] = [
          '#type' => 'vertical_tabs',
          '#default_tab' => 'edit-recommended-method',
        ];
        $form['recommended_method'] = [
          '#type' => 'details',
          '#title' => $this
            ->t('Recommended method'),
          '#group' => 'search_console',
        ];

        $form['recommended_method']['file_upload'] = [
          '#type' => 'file',
          '#title' => $this->t('Upload an existing verification file'),
          '#description' => $this->t('If you have been provided with an actual file, you can simply upload the file.'),
          '#access' => isset($record['engine']['file']) ? $record['engine']['file'] : '',
        ];
        $form['recommended_method']['file'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Verification file'),
          '#default_value' => isset($record['file']) ? $record['file'] : '',
          '#description' => $this->t('The name of the HTML verification file you were asked to upload.'),
          '#element_validate' => $record['engine']['file_validate'],
          '#access' => isset($record['engine']['file']) ? $record['engine']['file'] : '',
          '#attributes' => [
            'placeholder' => $record['engine']['file_example'],
          ],
        ];
        $form['recommended_method']['file_contents'] = [
          '#type' => 'textarea',
          '#title' => $this->t('Verification file contents'),
          '#default_value' => isset($record['file_contents']) ? $record['file_contents'] : '',
          '#element_validate' => $record['engine']['file_contents_validate'],
          '#wysiwyg' => FALSE,
          '#access' => isset($record['file_contents']) ? $record['file_contents'] : '',
        ];
        $form['alternate_methods'] = [
          '#type' => 'details',
          '#title' => $this
            ->t('Alternate methods'),
          '#group' => 'search_console',
        ];
        $form['alternate_methods']['meta'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Verification META tag'),
          '#default_value' => isset($record['meta']) ? $record['meta'] : '',
          '#description' => $this->t('This is the full meta tag provided for verification. Note that this meta tag will only be visible in the source code of your <a href="@frontpage">front page</a>.', ['@frontpage' => \Drupal::url('<front>')]),
          '#element_validate' => $record['engine']['meta_validate'],
          '#access' => $record['engine']['meta'],
          '#maxlength' => NULL,
          '#attributes' => [
            'placeholder' => $record['engine']['meta_example'],
          ],
        ];
        break;
    }

    $form['actions']['cancel'] = [
      '#type' => 'link',
      '#title' => $this->t('Cancel'),
      '#url' => isset($_GET['destination']) ? $_GET['destination'] : Url::fromRoute('md_site_verify.verifications_list'),
      '#weight' => 15,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $storage = $form_state->getStorage();
    $values = &$form_state->getValues();

    // Check META tag.
    if (!empty($values['meta'])) {
      $form_state->setValue('meta', trim($values['meta']));
      if ($values['meta'] != '' && !preg_match('/<meta (.*)>/', $values['meta'])) {
        $form_state->setErrorByName('meta', $this->t('Only META tags are supported at this moment'));
      }
    }

    // Check verification file.
    if (isset($storage['record']['engine']['file'])) {

      // Import the uploaded verification file.
      $validators = ['file_validate_extensions' => []];
      if ($file = file_save_upload('file_upload', $validators, FALSE, 0, FILE_EXISTS_REPLACE)) {
        $contents = @file_get_contents($file->getFileUri());

        $file->delete();
        if ($contents === FALSE) {
          drupal_set_message(t('The verification file import failed, because the file %filename could not be read.', ['%filename' => $file->getFilename()]), 'error');
        }
        else {
          $values['file'] = $file->getFilename();
          $values['file_contents'] = $contents;
        }
      }

      if ($values['file']) {
        $existing_file = $this->database->select('md_site_verify', 'dsv')
          ->fields('dsv', ['dsv_id'])
          ->condition('dsv.file', $values['file'], 'LIKE')
          ->execute()
          ->fetchField();

        if ($existing_file && $values['svid'] !== $existing_file) {
          $form_state->setErrorByName('file', $this->t('The file %filename is already being used in another verification.', ['%filename' => $values['file']]));
        }
      }

      if (!empty($values['file']) && !empty($values['meta'])) {
        $form_state->setErrorByName('form', $this->t(' Please choose only one method, for more infromation %uri.', ['%uri' => 'https://www.google.com/webmasters/tools']));
      }

      if (empty($values['file']) && empty($values['meta'])) {
        $form_state->setErrorByName('form', $this->t('Please choose the Recommended method or the Alternate methods before saving the configuration, for more infromation %uri.', ['%uri' => 'https://www.google.com/webmasters/tools']));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $storage = $form_state->getStorage();

    if ($storage['step'] == 1) {
      $form_state->setStorage([
        'record' => [
          'engine' => $this->domainSiteVerify->domainSiteVerifyEngineLoad($form_state->getValue('engine')),
        ],
        'step' => 2,
      ]);
      $form_state->setRebuild();
    }
    else {

      $this->database->merge('md_site_verify')
        ->key('dsv_id', $form_state->getValue('svid'))
        ->fields([
          'domain_id' => $form_state->getValue('domain_id'),
          'engine' => $form_state->getValue('engine'),
          'file' => $form_state->getValue('file'),
          'file_contents' => $form_state->getValue('file_contents'),
          'meta' => $form_state->getValue('meta'),
        ])
        ->execute();

      drupal_set_message(t('Verification saved.'));

      $form_state->setStorage([]);
      $form_state->setRebuild(NULL);
      $form_state->setRedirect('md_site_verify.verifications_list');

      // Set the menu to be rebuilt.
      \Drupal::service('router.builder')->rebuild();
    }

  }

}