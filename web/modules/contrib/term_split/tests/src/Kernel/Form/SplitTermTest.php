<?php

namespace Drupal\Tests\term_split\Kernel\Form;

use Drupal\Core\Form\FormState;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;
use Drupal\term_split\Form\SplitTerm;
use Drupal\Tests\term_split\Kernel\TermSplitTestBase;
use Drupal\Tests\term_split\Kernel\TestDoubles\TermSplitterSpy;

/**
 * Class SplitTermTest
 *
 * @group term_split
 */
class SplitTermTest extends TermSplitTestBase {

  /**
   * @var \Drupal\taxonomy\TermInterface
   */
  private $term;

  /**
   * @var \Drupal\Tests\term_split\Kernel\TestDoubles\TermSplitterSpy
   */
  private $termSplitter;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->termSplitter = new TermSplitterSpy();
    $this->container->set('term_split.splitter', $this->termSplitter);

    $this->term = $this->createTerm($this->vocabulary);
  }

  /**
   * @test
   **/
  public function buildForm__noReferencingNodes() {
    $this->assertBuiltForm($this->getBasicForm());
  }

  /**
   * @test
   **/
  public function buildForm__singleReferencingNode() {
    $node = $this->createNode(['field_terms' => ['target_id' => $this->term->id()]]);
    $node = Node::load($node->id());

    $expected = $this->getBasicForm();
    $expected['nodes'][$node->id()] = [
      'link' => [
        '#type' => 'link',
        '#title' => $node->label(),
        '#url' => $node->toUrl(),
      ],
      'a' => [
        '#type' => 'radio',
        '#name' => $node->id(),
        '#return_value' => "a",
        '#value' => "a",
      ],
      'b' => [
        '#type' => 'radio',
        '#name' => $node->id(),
        '#return_value' => "b",
        '#value' => "a",
      ],
    ];
    $this->assertBuiltForm($expected);
  }

  /**
   * @test
   **/
  public function onSubmission__storesInputAndRedirectsToConfirmationForm() {
    $node1 = $this->createNode(['field_terms' => ['target_id' => $this->term->id()]]);
    $node2 = $this->createNode(['field_terms' => ['target_id' => $this->term->id()]]);

    $sut = SplitTerm::create($this->container);
    $form = [];
    $formState = new FormState();
    $form = $sut->buildForm($form, $formState, $this->term);

    $target1Name = 'A';
    $target2Name = 'B';
    $target1Nids = [$node1->id()];
    $target2Nids = [$node2->id()];

    $values = $formState->getValues();
    $values['nodes']['header']['a'] = $target1Name;
    $values['nodes']['header']['b'] = $target2Name;
    $values['nodes'][$node1->id()] = ['a' => 'a', 'b' => 'a'];
    $values['nodes'][$node2->id()] = ['a' => 'b', 'b' => 'b'];
    $formState->setValues($values);

    $sut->submitForm($form, $formState);

    $tempStore = $this->privateTempStore->get('term_split');

    $expected = [
      'tid' => $this->term->id(),
      'a' => [
        'name' => $target1Name,
        'nids' => $target1Nids,
      ],
      'b' => [
        'name' => $target2Name,
        'nids' => $target2Nids,
      ],
    ];
    self::assertEquals($expected, $tempStore->get('term_to_split'));
    $routeParameters['taxonomy_term'] = $this->term->id();
    $expected = new Url('entity.taxonomy_term.split_confirm_form', $routeParameters);
    self::assertEquals($expected, $formState->getRedirect());
  }

  /**
   * @return array
   */
  private function getBasicForm() {
    return [
      'nodes' => [
        '#type' => 'table',
        'header' => [
          'node' => [
            '#markup' => '',
          ],
          'a' => [
            '#type' => 'textfield',
            '#title' => $this->t('Term A'),
            '#required' => TRUE,
            '#default_value' => '',
            '#size' => 25,
          ],
          'b' => [
            '#type' => 'textfield',
            '#title' => $this->t('Term B'),
            '#required' => TRUE,
            '#default_value' => '',
            '#size' => 25,
          ],
        ],
      ],
      'actions' => [
        'submit' => [
          '#type' => 'submit',
          '#value' => $this->t('Submit'),
          '#button_type' => 'primary',
        ],
        'cancel' => [
          '#type' => 'link',
          '#title' => $this->t('Cancel'),
          '#attributes' => ['class' => ['button']],
          '#url' => Url::fromRoute('entity.taxonomy_term.canonical', ['taxonomy_term' => $this->term->id()]),
        ],
      ],
    ];
  }

  /**
   * @param $expected
   */
  private function assertBuiltForm($expected): void {
    $sut = SplitTerm::create($this->container);
    $form = [];
    $formState = new FormState();
    $actual = $sut->buildForm($form, $formState, $this->term);
    self::assertEquals($expected, $actual);
  }

}
