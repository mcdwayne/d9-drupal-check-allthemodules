<?php

namespace Drupal\w3c_validator\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Link;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Url;
use Drupal\w3c_validator\W3CProcessor;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller routines for w3c_validator module validation log routes.
 */
class W3CLogController extends ControllerBase {

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\Renderer
   */
  protected $renderer;

  /**
   * The form builder service.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * @var \Drupal\w3c_validator\W3CProcessor
   */
  protected $w3cProcessor;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('renderer'),
      $container->get('form_builder'),
      $container->get('w3c.processor')
    );
  }

  /**
   * Constructs a W3CLogController object.
   *
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder service.
   */
  public function __construct(RendererInterface $renderer, FormBuilderInterface $form_builder, W3CProcessor $w3c_processor) {
    $this->renderer = $renderer;
    $this->formBuilder = $form_builder;
    $this->w3cProcessor = $w3c_processor;
  }

  /**
   * Return the 'overview' page.
   *
   * This page displays a report of all pages, exposing their current validation
   * state. Validation errors are displayed if existing as well as a form to
   * re-validate it all if necessary.
   *
   * @return array
   *   A render array containing our 'overview report' page content.
   */
  public function overview() {
    $output = [
      '#prefix' => '<div id="foobar">',
      '#suffix' => '</div>',
    ];
    $rows = [];

    // Add re-validation form on top.
    $output['operations'] = $this->formBuilder->getForm('Drupal\w3c_validator\Form\W3CValidatorOperationForm');

    // Retrieve all site pages.
    $pages = $this->w3cProcessor->findAllPages();

    // Retrieve all validated pages.
    $all_validated_pages = $this->w3cProcessor->findAllValidatedPages();

    // Loop over result to build display.
    foreach ($pages as $url => $page) {

      // Build validation result.
      $validation = $this->buildValidationResult($all_validated_pages, $url);

      // Build the result display using form API.
      $row = [];
      $row[$url]['summary'] = $this->buildValidationDisplay($page, $validation);
      if (isset($validation['status']) && $validation['status'] != $this->t('Unknown')) {
        $row[$url]['details'] = $this->buildValidationDetailDisplay($validation);
      }
      // Render results.
      $rows[] = [
        'data' => [
          [
            'data' => $this->renderer->render($row),
            'class' => 'w3c_validator-wrapper collapsed',
          ],
        ],
        'class' => [$validation['class']],
      ];
    }

    $output['pages'] = [
      '#type' => 'table',
      '#rows' => $rows,
      '#attributes' => ['id' => 'w3c-report'],
      '#empty' => $this->t('No data to display.'),
    ];

    $output['#attached']['library'][] = 'w3c_validator/w3c_validator.report';
    return $output;
  }

  /**
   *  This private method builds the validation result data.
   *
   * @param String $all_validated_pages
   *   The array of all stored results for already validated pages.
   * @param String $url
   *   The URL to build validation result for
   *
   * @return array
   *   The validation result.
   */
  protected function buildValidationResult($all_validated_pages, $url) {
    $validation = [];

    // Check if the page is validated.
    if (isset($all_validated_pages[$url])) {

      // Retrieve the validation result.
      $validation = $all_validated_pages[$url];

      $validation['result'] = $this->t('@errors errors, @warnings warnings', ['@errors' => $validation['error_count'], '@warnings' => $validation['warning_count']]);

      // If page is not yet validated.
      if ($validation['need_validation']) {
        $validation['class'] = 'color-outdated';
        $validation['status']  = $this->t('Outdated');
      }
      // If page is valid.
      elseif ($validation['validity']) {
        $validation['class'] = ($validation['warning_count']) ? 'color-warning' : 'color-status';
        $validation['status']  = $this->t('Valid');
      }
      // If page is invalid.
      else {
        $validation['class'] = 'color-error';
        $validation['status']  = $this->t('Invalid');
      }
    }
    // If completely unknown page.
    else {
      $validation['class'] = 'color-unknown';
      $validation['status']  = $this->t('Unknown');
      $validation['result']  = $this->t('Not yet validated');
    }
    return $validation;
  }

  /**
   * Helper method to build the result row ready to display.
   *
   * @param array $page
   *   The page to validate, as per stored in DB from this module.
   * @param array $validation
   *   An array of preprocess validation values for that page.
   *
   * @return array
   *   A formAPI array representing a result row ready to display.
   */
  protected function buildValidationDisplay($page, $validation) {
    $display = [
      '#type' => 'container',
      '#attributes' => ['class' => ['page-summary']],
    ];
    $display['icon'] = [
      '#prefix' => '<span class="icon">',
      '#suffix' => '</span>',
    ];
    $display['title'] = [
      '#prefix' => '<span class="title">',
      '#suffix' => '</span>',
      '#markup'  => $page['title'],
    ];
    $display['result'] = [
      '#prefix' => '<span class="result">',
      '#suffix' => '</span>',
      '#markup'  => $validation['result'],
    ];
    $display['status'] = [
      '#prefix' => '<span class="status">',
      '#suffix' => '</span>',
      '#markup'  => $validation['status'],
    ];

    return $display;
  }

  /**
   * Helper method to build the details of validation results for the current
   * row, ready to display.
   *
   * @param array $validation
   *   An array of preprocess validation values for that page.
   *
   * @return array
   *   A formAPI array representing the details of validation results, ready to
   *   display.
   */
  protected function buildValidationDetailDisplay($validation) {

    // Build the container for details results.
    $display = [
      '#prefix' => '<div class="fieldset-wrapper analysis-results">',
      '#suffix' => '</div>',
    ];

    // Build the title according to validity.
    if ($validation['validity']) {
      $output = $this->t('This document was successfully checked !');
    }
    else {
      $output = $this->t('Errors found while checking this document !');
    }
    $display['message'] = [
      '#prefix' => '<h2 class="message ' . $validation['class'] . '">',
      '#suffix' => '</h2>',
      '#markup'  => $output,
    ];

    // Build rows for details summary table.
    // Render results.
    $uri = Url::fromUri('base:' . $validation['uri'], ['absolute' => TRUE]);
    $rows[] = [$this->t('Uri'), Link::fromTextAndUrl($uri->toString(), $uri)];
    $rows[] = [$this->t('Validity'), $validation['status']];
    $url = Url::fromUri('https://validator.w3.org/nu', ['query' => ['doc' => $uri->toString()], 'attributes' => ['target' => '_new']]);
    $rows[] = [$this->t('Validator results'), Link::fromTextAndUrl($url->toString(), $url)];
    $rows[] = [$this->t('Doctype'), $validation['doctype']];
    $rows[] = [$this->t('Summary'), $validation['result']];
    $display['detail-table'] = [
      '#type' => 'table',
      '#rows' => $rows,
      '#attributes' => ['class' => 'report'],
      '#empty' => $this->t('No data to display.'),
    ];

    // Display errors.
    $display['errors-title'] = [
      '#prefix' => '<h2>',
      '#suffix' => '</h2>',
      '#markup'  => $this->t('Errors'),
    ];
    $validation['errors'] = is_array($validation['errors']) ? $validation['errors'] : unserialize($validation['errors']);
    if (is_array($validation['errors']) && !empty($validation['errors'])) {
      /** @var \HtmlValidator\Message $error */
      foreach ($validation['errors'] as $id => $error) {
        $display['error'][$id] = [
          '#prefix' => '<div class="message-wrapper message-error">',
          '#suffix' => '</div>',
          'message' => [
            '#prefix' => '<div class="message">',
            '#suffix' => '</div>',
            '#markup' => '<span class="where">' . $this->t('Line @line, Column @col:', ['@line' => $error->getFirstLine(), '@col' => $error->getFirstColumn()]) . '</span><span class="descr">' . $this->t(' @descr', ['@descr' => $error->getText()]) . '</span>',
          ],
          'source' => [
            '#prefix' => '<div class="source">',
            '#suffix' => '</div>',
            '#markup' => '<pre>' . htmlspecialchars($this->highlightExtract($error->getExtract(), $error->getHighlightStart(), $error->getHighlightLength())) . '</pre>',
          ],
        ];
      }
    }
    else {
      $display['error'] = [
        '#prefix' => '<div>',
        '#suffix' => '</div>',
        '#markup'  => $this->t('No errors found'),
      ];
    }

    // Display warnings.
    $display['warnings-title'] = [
      '#prefix' => '<h2>',
      '#suffix' => '</h2>',
      '#markup'  => $this->t('Warnings'),
    ];
    $validation['warnings'] = is_array($validation['warnings']) ? $validation['warnings'] : unserialize($validation['warnings']);
    if (is_array($validation['warnings']) && !empty($validation['warnings'])) {
      /** @var \HtmlValidator\Message $warning */
      foreach ($validation['warnings'] as $id => $warning) {
        $display['warning'][$id] = [
          '#prefix' => '<div class="message-wrapper message-warning">',
          '#suffix' => '</div>',
          'message' => [
            '#prefix' => '<div class="message">',
            '#suffix' => '</div>',
            '#markup' => '<span class="where">' . $this->t('Line @line, Column @col:', ['@line' => $warning->getFirstLine(), '@col' => $warning->getFirstColumn()]) . '</span><span class="descr">' . $this->t(' @descr', ['@descr' => $warning->getText()]) . '</span>',
          ],
          'source' => [
            '#prefix' => '<div class="source">',
            '#suffix' => '</div>',
            '#markup' => '<pre>' . htmlspecialchars($this->highlightExtract($warning->getExtract(), $warning->getHighlightStart(), $warning->getHighlightLength())) . '</pre>',
          ],
        ];
      }
    }
    else {
      $display['warning'] = [
        '#prefix' => '<div>',
        '#suffix' => '</div>',
        '#markup'  => $this->t('No warnings found'),
      ];
    }

    // Display messages.
    $display['infos-title'] = [
      '#prefix' => '<h2>',
      '#suffix' => '</h2>',
      '#markup'  => $this->t('Infos'),
    ];
    $validation['infos'] = is_array($validation['infos']) ? $validation['infos'] : unserialize($validation['infos']);
    if (is_array($validation['infos']) && !empty($validation['infos'])) {
      /** @var \HtmlValidator\Message $info */
      foreach ($validation['infos'] as $id => $info) {
        $display['info'][$id] = [
          '#prefix' => '<div class="message-wrapper message-info">',
          '#suffix' => '</div>',
          'message' => [
            '#prefix' => '<div class="message">',
            '#suffix' => '</div>',
            '#markup' => '<span class="where">' . $this->t('Line @line, Column @col:', ['@line' => $info->getFirstLine(), '@col' => $info->getFirstColumn()]) . '</span><span class="descr">' . $this->t(' @descr', ['@descr' => $info->getText()]) . '</span>',
          ],
          'source' => [
            '#prefix' => '<div class="source">',
            '#suffix' => '</div>',
            '#markup' => '<code>' . $this->highlightExtract($info->getExtract(), $info->getHighlightStart(), $info->getHighlightLength()) . '</code>',
          ],
        ];
      }
    }
    else {
      $display['infos'] = [
        '#prefix' => '<div>',
        '#suffix' => '</div>',
        '#markup'  => $this->t('No information found'),
      ];
    }

    return $display;
  }

  /**
   * Highlight the given string, enclosing it in a span
   *
   * @param  string $str    String to highlight
   * @param  int    $start  Start index of substring to highlight
   * @param  int    $length Length of substring to highlight
   * @return string
   */
  protected function highlightExtract($str, $start, $length) {
    $parts = array(
      substr($str, 0, $start),
      substr($str, $start, $length),
      substr($str, $start + $length)
    );

    $parts = array_map('htmlentities', $parts);

    $highlighted  = $parts[0] . '<b>';
    $highlighted .= $parts[1] . '</b>' . $parts[2];

    return $highlighted;
  }
}
