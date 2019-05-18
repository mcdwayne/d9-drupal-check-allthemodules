<?php /**
 * @file
 * Contains \Drupal\field_quiz\Controller\FieldQuizController.
 */

namespace Drupal\field_quiz\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\node\NodeInterface;

/**
 * Default controller for the field_quiz module.
 */
class FieldQuizController extends ControllerBase {

  /**
   * Text, if the user answered wrong.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node the client form belongs to.
   * @return array
   */
  public function sorry(NodeInterface $node) {
    $config = $this->config('field_quiz.settings');
    $text = $config->get('field_quiz.test_answer_wrong');

    $elements[] = array(
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#attributes' => array(
        'style' => 'color: red',
      ),
      '#value' => $this->t($text),
    );

    $url = Url::fromRoute('entity.node.canonical', array('node' => $node->id()));
    $project_link = Link::fromTextAndUrl(t('Please try again.'), $url);
    $project_link = $project_link->toRenderable();
    // If you need some attributes.
    $project_link['#attributes'] = array('class' => array('button', 'button-action', 'button--primary', 'button--small'));

    $elements[] = array(
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#attributes' => array(
      ),
      '#value' => render($project_link) ,
    );

    return $elements;
  }

  /**
   * Text, if the user answered correctly.
   *
   * @return array $elements
   */
  public function success() {
    $config = $this->config('field_quiz.settings');
    $text = $config->get('field_quiz.test_answer_correct');

    $elements[] = array(
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#attributes' => array(
        'style' => 'color: green',
      ),
      '#value' => $this->t($text),
    );

    return $elements;
  }

}
