<?php

namespace Drupal\simple_glossary\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Class SimpleGlossaryForm.
 *
 * @package Drupal\simple_glossary\Form
 */
class SimpleGlossaryForm extends FormBase {

  /**
   * A form state interface instance.
   *
   * @var Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * A Request stack instance.
   *
   * @var Symfony\Component\HttpFoundation\RequestStack
   */
  protected $request;

  /**
   * A entity type manager interface instance.
   *
   * @var Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a SimpleGlossaryFrontendController object.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   A form state variable.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request
   *   A Request stack variable.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   A entity type manager interface variable.
   */
  public function __construct(StateInterface $state, RequestStack $request, EntityTypeManagerInterface $entity_type_manager) {
    $this->state = $state;
    $this->request = $request;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('state'),
      $container->get('request_stack'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * Set Form ID.
   */
  public function getFormId() {
    return 'glossary_listing_view_page';
  }

  /**
   * Build Form.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    global $base_url;
    $keyword = $this->request->getCurrentRequest()->get('keyword');
    $form['#method'] = 'get';
    $form['keyword'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Keyword'),
      '#description' => $this->t('Simple Glossary Term name'),
      '#prefix' => '<div class="glossary-filters"><div id="glossary-filters-keyword" class="glossary-filters-item" >',
      '#required' => TRUE,
      '#suffix' => '</div>',
      '#default_value' => (isset($keyword)) ? $keyword : '',
    ];
    $form['submit'] = [
      '#prefix' => '<div id="glossary-filters-submit" class="glossary-filters-item" >',
      '#type' => 'submit',
      '#value' => 'Search',
      '#suffix' => '<a href="' . $base_url . '/admin/config/system/simple_glossary" class="button">Reset</a></div></div>',
    ];
    $form['glossary_listing'] = SimpleGlossaryForm::fetchAllTermsFromDb();
    return $form;
  }

  /**
   * Form submission.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * HELPER METHODS.
   */
  protected function fetchAllTermsFromDb() {
    $keyword = $this->request->getCurrentRequest()->get('keyword');
    $base_query = "SELECT gid, SUBSTR(term,1,1) as letter, term, description FROM simple_glossary_content";
    if ((isset($keyword)) && (!empty($keyword))) {
      $result = db_query($base_query . " where (term LIKE :d) order by letter ASC", [':d' => "%" . $keyword . "%"]);
    }
    else {
      $result = db_query($base_query . " order by letter ASC");
    }
    $finalGlossaryResult = [];
    if (!empty($result)) {
      $glossaryResult = [];
      foreach ($result as $row) {
        $glossaryResult[] = (array) $row;
      }
      $finalGlossaryResult = [];
      foreach ($glossaryResult as $k => $v) {
        $v['description'] = html_entity_decode(str_replace('\,', ',', $v['description']));
        $finalGlossaryResult[strtolower($v['letter'])]['' . $v['term']] = $v;
      }
      $updatedFinalGlossaryResult = [];
      foreach ($finalGlossaryResult as $k => $v) {
        ksort($v);
        $updatedFinalGlossaryResult[$k] = $v;
      }
    }
    return [
      '#theme' => 'backend_list_view',
      '#terms_data' => $updatedFinalGlossaryResult,
      '#attached' => [
        'library' => [
          'simple_glossary/simple_glossary_list_view_assets',
        ],
      ],
    ];
  }

}
