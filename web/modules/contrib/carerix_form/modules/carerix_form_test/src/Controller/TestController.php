<?php

namespace Drupal\carerix_form_test\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\carerix_form\CarerixServiceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class TestController.
 */
class TestController extends ControllerBase {

  /**
   * Carerix service.
   *
   * @var \Drupal\carerix_form\CarerixService
   */
  protected $carerix;

  /**
   * CarerixIntegrationServiceController constructor.
   *
   * @param \Drupal\carerix_form\CarerixServiceInterface $carerix
   *   The Carerix service.
   */
  public function __construct(CarerixServiceInterface $carerix) {
    $this->carerix = $carerix;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      // Load the service required to construct this class.
      $container->get('carerix')
    );
  }

  /**
   * A test page.
   *
   * @return array
   *   A simple renderable array.
   */
  public function testVacancies() {
    // $vacancies = $this->carerix->getAllEntities('Vacancy')->toArray();
    $vacancy = $this->carerix->getEntityById('Vacancy', 5775, [], 'French')->toArray();

    return [
      '#type' => 'markup',
      '#markup' => highlight_string("<?php\n\$data =\n" . var_export($vacancy, TRUE) . ";\n?>"),
    ];
  }

  /**
   * A renderable array.
   *
   * @return array
   *   The renderable markup.
   */
  public function testJobs() {
    $jobs = $this->carerix->getAllEntities(
      'Job',
      [
        'qualifier' => "name like '*QA Engineer*'",
      ]
    )->toArray();

    return [
      '#type' => 'markup',
      '#markup' => highlight_string("<?php\n\$data =\n" . var_export($jobs, TRUE) . ";\n?>"),
    ];
  }

  /**
   * A renderable array.
   *
   * @return array
   *   The renderable markup.
   */
  public function testPublications() {
    $publications = $this->carerix->getAllEntities(
      'Publication', [
        'qualifier' => "name like '*QA Engineer*'",
      ]
    );

    return empty($publications) ? [] : $publications->toArray();
  }

  /**
   * TYPE for documents is always 18 (on all systems apparently (all apikeys)
   *
   * typeId: 18
   * name: Document-type
   * identifier: Document type
   *
   * ///////////////////////////////
   *
   * DataNodes always vary per system. Needs to be synced to Drupal.
   *
   * dataNodeId: 70
   * value: CV
   *
   * ---------------------------------
   *
   * dataNodeId: 2538
   * value: Photo
   *
   * ---------------------------------
   *
   * dataNodeId: 1461
   * value: documentatie
   *
   */
  public function testEmployees() {
    \Carerix_Api_Rest_Entity_CREmployee::class;
    \Carerix_Api_Rest_Entity_CRUser::class;
    \Carerix_Api_Rest_Entity_CRUrl::class;

    dpm('init DataNodes');

    // Common ids.

//      // TYPE
    dpm(\Carerix_Api_Rest_Entity_CRNodeType::find(200)->toArray(), 'Type');
//
    // Publictest System only.

//      // DATA NODES
//      dpm(\Carerix_Api_Rest_Entity_CRDataNode::find(70)->toArray(), 'test CV');
//      dpm(\Carerix_Api_Rest_Entity_CRDataNode::find(2538)->toArray(), 'test Photo');
//      dpm(\Carerix_Api_Rest_Entity_CRDataNode::find(1461)->toArray(), 'test what');

    // Fernvalley System only.
    dpm(\Carerix_Api_Rest_Entity_CRDataNode::find(2873)->toArray(), 'test URLS');
    dpm(\Carerix_Api_Rest_Entity_CRDataNode::find(2870)->toArray(), 'test URLS');


    // Fetch data nodes.
    $dataNodes = $this->carerix->getAllEntities('DataNode', [
//        'qualifier' => 'notActive != 1 and deleted != 1 and type.name = "Document-type" and (value like "*CV*" or value = "Photo")',
//       'qualifier' => 'notActive != 1 and deleted != 1 and type.name = "Document-type"',
      'qualifier' => 'notActive != 1 and deleted != 1 and type.name = "URL-label"',
//        'start' => 20,
      'show' => ['value', 'dataNodeID']
    ]);

    dpm($dataNodes, 'dataNodes');

    ////

    dpm('init Entities');

    $params = [
//        'qualifier' => "firstName = 'Sang'",
      //      'qualifier' => "firstName like '*TestUser12345*'",
      'qualifier' => "firstName like '*afakgkapogaAZFJOAI*'",
      'show' => [
        'attachments',
        'attachments.toTypeNode',
        'photo',
        'toUser',
        'toUser.urls', // Linkedin, ... urls
      ],
    ];

    $collection = $this->carerix->getAllEntities('Employee', $params);

    foreach ($collection as $employee) {

      dpm($employee);
      dpm($employee->toArray(), 'employee to ARR');

      // $owner = $employee->owner;

      // dpm($owner);
      // dpm($owner->getEmailAddress());

      // find photo attachment id
      $photo = (!is_null($employee->getPhoto())) ? $employee->getPhoto() : false;
      dpm($photo);

      $documents = (!is_null($employee->getDocuments())) ? $employee->getDocuments() : false;
      dpm($documents);

    }

    return [];

  }

}
