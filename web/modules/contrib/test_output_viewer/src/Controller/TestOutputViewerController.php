<?php

namespace Drupal\test_output_viewer\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Render\HtmlResponse;
use Drupal\test_output_viewer\Exception\WrongOutputException;
use Drupal\test_output_viewer\OutputProcessorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Returns responses for Test Output Viewer routes.
 */
class TestOutputViewerController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * The output parser.
   *
   * @var \Drupal\test_output_viewer\OutputProcessorInterface
   */
  protected $outputProcessor;

  /**
   * The constructor.
   *
   * @param \Drupal\test_output_viewer\OutputProcessorInterface $outputProcessor
   *   The output processor.
   */
  public function __construct(OutputProcessorInterface $outputProcessor) {
    $this->outputProcessor = $outputProcessor;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('test_output_viewer.output_processor')
    );
  }

  /**
   * Builds the viewer.
   */
  public function viewer() {
    $settings = $this->config('test_output_viewer.settings')->get();
    $drupalSettings = [
      'testOutputViewer' => [
        'outputPath' => $settings['output_path'],
        'defaultResult' => $settings['default_result'],
        'autoUpdate' => $settings['auto_update'],
        'autoUpdateTimeout' => $settings['auto_update_timeout'],
      ],
    ];
    return [
      '#theme' => 'test_output',
      '#attached' => [
        'library' => ['test_output_viewer/test_output_viewer'],
        'drupalSettings' => $drupalSettings,
      ],
    ];
  }

  /**
   * Prints contents of test output file.
   */
  public function output($file) {
    $output_path = $this->config('test_output_viewer.settings')->get('output_path');

    if (!preg_match('#^.+-\d+-\d+\.html$#', $file)) {
      throw new BadRequestHttpException('The file name is not correct.');
    }

    if (!file_exists($output_path . '/' . $file)) {
      throw new NotFoundHttpException("The $file does not exist.");
    }

    $html = file_get_contents($output_path . '/' . $file);

    // User login form sets autofocus on 'username' field which leads to iframe
    // stealing focus from the parent page.
    $html = preg_replace('#autofocus="autofocus" ?#', '', $html);

    // Remove the metadata to make the html code valid.
    $html = preg_replace('#<hr />Headers: <pre>.*</pre>#s', '', $html);
    $html = preg_replace('#^<hr />.*<hr />#', '', $html);

    return new HtmlResponse($html);
  }

  /**
   * Returns information about available test results.
   */
  public function data() {
    try {
      $data = $this->outputProcessor->process();
    }
    catch (WrongOutputException $exception) {
      $data = [
        'error' => $exception->getMessage(),
      ];
    }
    return new JsonResponse($data);
  }

}
