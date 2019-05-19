<?php

namespace Drupal\term_split\Form;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Console\Command\Shared\TranslationTrait;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\taxonomy\TermInterface;
use Drupal\term_reference_change\ReferenceFinder;
use Drupal\user\PrivateTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Split terms form.
 */
class SplitTerm extends FormBase {

  use TranslationTrait;

  /**
   * The reference finder.
   *
   * @var \Drupal\term_reference_change\ReferenceFinder
   */
  private $referenceFinder;

  /**
   * The source term.
   *
   * @var \Drupal\taxonomy\TermInterface
   */
  private $sourceTerm;

  /**
   * The private temp store factory.
   *
   * @var \Drupal\user\PrivateTempStoreFactory
   */
  private $tempStoreFactory;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('term_reference_change.reference_finder'),
      $container->get('user.private_tempstore')
    );
  }

  /**
   * SplitTerm constructor.
   *
   * @param \Drupal\term_reference_change\ReferenceFinder $referenceFinder
   *   The reference finder service.
   * @param \Drupal\user\PrivateTempStoreFactory $tempStoreFactory
   *   The temp store factory service.
   */
  public function __construct(ReferenceFinder $referenceFinder, PrivateTempStoreFactory $tempStoreFactory) {
    $this->referenceFinder = $referenceFinder;
    $this->tempStoreFactory = $tempStoreFactory;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'taxonomy_term_split_form';
  }

  /**
   * {@inheritdoc}
   *
   * @SuppressWarnings(camelCase)
   */
  public function buildForm(array $form, FormStateInterface $form_state, TermInterface $taxonomy_term = NULL) {
    $this->sourceTerm = $taxonomy_term;
    $input = $form_state->getUserInput();

    $form['nodes'] = [
      '#type' => 'table',
    ];

    $form['nodes']['header']['node'] = [
      '#markup' => '',
    ];

    $form['nodes']['header']['a'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Term A'),
      '#required' => TRUE,
      '#default_value' => !empty($input['target1']) ?: '',
      '#size' => 25,
    ];

    $form['nodes']['header']['b'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Term B'),
      '#required' => TRUE,
      '#default_value' => !empty($input['target2']) ?: '',
      '#size' => 25,
    ];

    /** @var \Drupal\node\NodeInterface $node */
    foreach ($this->getReferencingNodes($taxonomy_term) as $node) {
      $form['nodes'][$node->id()]['link'] = [
        '#type' => 'link',
        '#title' => $node->label(),
        '#url' => $node->toUrl(),
      ];

      $selectedRadio = !empty($input[$node->id()]) ? $input[$node->id()] : 'a';
      $form['nodes'][$node->id()]['a'] = [
        '#type' => 'radio',
        '#name' => $node->id(),
        '#return_value' => "a",
        '#value' => $selectedRadio,
      ];

      $form['nodes'][$node->id()]['b'] = [
        '#type' => 'radio',
        '#name' => $node->id(),
        '#return_value' => "b",
        '#value' => $selectedRadio,
      ];
    }

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
      '#button_type' => 'primary',
    ];

    $form['actions']['cancel'] = [
      '#type' => 'link',
      '#title' => $this->t('Cancel'),
      '#attributes' => ['class' => ['button']],
      '#url' => $this->getCancelUrl($taxonomy_term),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   *
   * @SuppressWarnings(camelCase)
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $tempStore = $this->tempStoreFactory->get('term_split');

    $values = [
      'tid' => $this->sourceTerm->id(),
      'a' => [
        'name' => $this->getTargetName($form_state, 'a'),
        'nids' => $this->getSelectedNidsForTarget($form_state, 'a'),
      ],
      'b' => [
        'name' => $this->getTargetName($form_state, 'b'),
        'nids' => $this->getSelectedNidsForTarget($form_state, 'b'),
      ],
    ];

    $tempStore->set('term_to_split', $values);
    $routeName = 'entity.taxonomy_term.split_confirm_form';
    $routeParameters['taxonomy_term'] = $this->sourceTerm->id();
    $options = [];

    $destination = $this->getRequest()->query->get('destination');
    $this->getRequest()->query->remove('destination');
    if (!empty($destination)) {
      $options['query']['destination'] = $destination;
    }

    $form_state->setRedirect($routeName, $routeParameters, $options);
  }

  /**
   * A callback for the form title.
   *
   * @param \Drupal\taxonomy\TermInterface $taxonomy_term
   *   The source term.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The title.
   *
   * @SuppressWarnings(camelCase)
   */
  public function titleCallback(TermInterface $taxonomy_term) {
    return new TranslatableMarkup("Split %label", ['%label' => $taxonomy_term->label()]);
  }

  /**
   * Creates a cancel url object.
   *
   * @param \Drupal\taxonomy\TermInterface $sourceTerm
   *   The source term.
   *
   * @return \Drupal\Core\Url
   *   The cancel url.
   *
   * @SuppressWarnings(static)
   */
  private function getCancelUrl(TermInterface $sourceTerm) {
    $fallbackUrl = Url::fromRoute('entity.taxonomy_term.canonical', ['taxonomy_term' => $sourceTerm->id()]);

    $query = $this->getRequest()->query;
    if (!$query->has('destination')) {
      return $fallbackUrl;
    }

    $options = UrlHelper::parse($query->get('destination'));
    try {
      return Url::fromUserInput('/' . ltrim($options['path'], '/'), $options);
    }
    catch (\InvalidArgumentException $e) {
      return $fallbackUrl;
    }
  }

  /**
   * Retrieves all nodes referencing the given term.
   *
   * @param \Drupal\taxonomy\TermInterface $term
   *   The term to retrieve references for.
   *
   * @return \Drupal\node\NodeInterface[]
   *   The referencing nodes.
   */
  private function getReferencingNodes(TermInterface $term) {
    $references = $this->referenceFinder->findReferencesFor($term);

    if (empty($references['node'])) {
      return [];
    }

    return $references['node'];
  }

  /**
   * Retrieves the name for the given target term.
   *
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *   The form state.
   * @param string $target
   *   The target.
   *
   * @return string
   *   The target name.
   */
  private function getTargetName(FormStateInterface $formState, $target) {
    return $formState->getValue(['nodes', 'header', $target]);
  }

  /**
   * Retrieves the selected node ids for the given target from the form state.
   *
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *   The form state.
   * @param string $target
   *   The target.
   *
   * @return int[]
   *   The selected node ids for the given target.
   */
  private function getSelectedNidsForTarget(FormStateInterface $formState, $target) {
    $values = $formState->getValues();

    $nids = [];
    foreach ($values['nodes'] as $key => $value) {
      if (!is_numeric($key)) {
        continue;
      }

      if (strcasecmp($value['a'], $value['b']) !== 0) {
        continue;
      }

      if (strcasecmp($value['a'], $target) !== 0) {
        continue;
      }

      $nids[] = $key;
    }

    return $nids;
  }

}
