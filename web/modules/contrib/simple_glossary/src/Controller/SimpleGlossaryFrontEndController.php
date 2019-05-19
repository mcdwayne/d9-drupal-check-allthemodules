<?php

namespace Drupal\simple_glossary\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Class SimpleGlossaryFrontendController.
 *
 * @package Drupal\simple_glossary\Controller
 */
class SimpleGlossaryFrontendController extends ControllerBase {

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
   * Method Content to get all terms.
   */
  public function content() {
    return SimpleGlossaryFrontendController::fetchAllTermsFromDb('A');
  }

  /**
   * Method to fetch Content on the basis of filters.
   */
  public function contentByLetter($ltr = '') {
    return SimpleGlossaryFrontendController::fetchAllTermsFromDb($ltr);
  }

  /**
   * Method to fetch Content on the basis of filters.
   */
  public function fetchAllTermsFromDb($ltr = '') {
    global $base_url;
    $keyword = $this->request->getCurrentRequest()->get('keyword');
    $base_query = "SELECT g.gid, g.term, g.description FROM simple_glossary_content AS g";
    if ((isset($keyword)) && (!empty($keyword))) {
      $keywordLength = strlen($keyword);
      if (($keywordLength == 1) && (preg_match("/^[a-zA-Z]$/", strtoupper($keyword)))) {
        $qry = db_query($base_query . " WHERE ( SUBSTR(term,1,1) = :d )", [':d' => $keyword]);
      }
      else {
        $qry = db_query($base_query . " WHERE ( term LIKE :d )", [':d' => '%' . $keyword . '%']);
      }
    }
    elseif ((isset($ltr)) && (!empty($ltr))) {
      $qry = db_query($base_query . " WHERE ( SUBSTR(term,1,1) = :d )", [':d' => $ltr]);
    }
    else {
      $qry = db_query($base_query);
    }
    $glossaryResult = [];
    if ($qry) {
      while ($row = $qry->fetchAssoc()) {
        $glossaryResult[] = $row;
      }
    }
    $finalGlossaryResult = [];
    if (count($glossaryResult) > 0) {
      $j = 0;
      $temp = [];
      for ($i = 0; $i < count($glossaryResult); $i++) {
        $glossaryResult[$i]['term'] = ucfirst($glossaryResult[$i]['term']);
        $glossaryResult[$i]['description'] = html_entity_decode(str_replace('\,', ',', $glossaryResult[$i]['description']));
        $temp[] = $glossaryResult[$i];
        if (($j == 1) || (count($glossaryResult) == $i + 1)) {
          $finalGlossaryResult[] = $temp;
          unset($temp);
          $j = 0;
        }
        else {
          $j++;
        }
      }
    }
    $qry_string = [];
    $qry_string['keyword'] = (isset($keyword)) ? $keyword : '';
    $qry_string['ltr'] = (isset($ltr)) ? $ltr : '';
    $glossaryConfig = [];
    $glossaryConfig['glossary_page_title'] = $this->state->get('glossary_page_title');
    $glossaryConfig['glossary_page_subheading'] = $this->state->get('glossary_page_subheading');
    $imagesTemp = json_decode($this->state->get('glossary_bg_image'));
    if (!empty($imagesTemp[0])) {
      $file = $this->entityTypeManager->getStorage('file')->load($imagesTemp[0]);
      $path = $file->getFileUri();
      $glossaryConfig['glossary_bg_image'] = file_create_url($path);
    }
    else {
      $glossaryConfig['glossary_bg_image'] = '';
    }
    $glossaryConfig['glossary_bottom_text'] = html_entity_decode($this->state->get('glossary_bottom_text'));
    return [
      '#theme' => 'forntend_list_view',
      '#terms_data' => $finalGlossaryResult,
      '#config' => $glossaryConfig,
      '#base_url' => $base_url,
      '#qry_string' => $qry_string,
      '#attached' => ['library' => ['simple_glossary/simple_glossary_list_view_assets']],
    ];
  }

}
