<?php

namespace Drupal\flashpoint_course_content\Plugin\flashpoint_course_content;

use Drupal\Core\Link;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Url;
use Drupal\flashpoint_course_content\FlashpointCourseContentRendererInterface;

/**
 * @FlashpointCourseContentRenderer(
 *   id = "flashpoint_course_content_default_renderer",
 *   label = @Translation("Default renderer"),
 * )
 */
class FlashpointCourseContentDefaultRenderer extends PluginBase implements FlashpointCourseContentRendererInterface {

  /**
   * @return string
   *   A string description.
   */
  public function description()
  {
    return $this->t('Default Renderer. Shows items as a list of divs with a set of specified classes.');
  }

  /**
   * @param $bundle
   */
  public static function getContentRenderData($bundle, $type = 'class') {
    $flashpoint_config = \Drupal::configFactory()->getEditable('flashpoint.settings')->getOriginal('flashpoint_course_content');
    $ret = [];
    foreach(['neutral', 'lock', 'pending', 'passed'] as $status) {
      if(isset($flashpoint_config[$bundle][$status . '_' . $type]) && !empty($flashpoint_config[$bundle][$status . '_' . $type])) {
        $ret[$status] = $flashpoint_config[$bundle][$status . '_' . $type];
      }
      elseif (isset($flashpoint_config['default'][$status . '_' . $type]) && !empty($flashpoint_config['default'][$status . '_' . $type])) {
        $ret[$status] = $flashpoint_config['default'][$status . '_' . $type];
      }
      else {
        $ret[$status] = '';
      }
    }
    return $ret;
  }

  /**
   * Render course content in a listing context.
   */
  public static function renderListing($content, $account)
  {
    $id = $content->id();
    $classes = FlashpointCourseContentDefaultRenderer::getContentRenderData($content->bundle(), 'class');
    $icons = FlashpointCourseContentDefaultRenderer::getContentRenderData($content->bundle(), 'icon');
    $status = 'neutral';
    if (!$content->isNeutral($account)) {
      $moduleHandler = \Drupal::service('module_handler');
      if ($moduleHandler->moduleExists('flashpoint_lrs_client')) {
        $lock_status = $content->isLocked($account);
        $pass_status = $content->isPassed($account);
        $status = $pass_status ? 'passed' : 'pending';
        $status = $lock_status ? 'lock' : $status;
      }
    }


    $content_label = $icons[$status] . ' ' . $content->label();
    if ($status !== 'lock') {
      $content_url = Url::fromRoute('entity.flashpoint_course_content.canonical', ['flashpoint_course_content' => $content->id()]);
      $content_label = Link::fromTextAndUrl($content->label(), $content_url)->toString();
    }

    $ret = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#attributes' => [
        'class' => ['flashpoint-content', $classes[$status]],
      ],
      'label' => [
        '#type' => 'html_tag',
        '#tag' => 'h4',
        '#value' => $icons[$status] . ' ' . $content_label,
        '#attributes' => [
          'class' => ['flashpoint-content-label'],
        ],
      ],
    ];
    return $ret;
  }

  /**
   * @param $course
   * @return array
   */
  public function renderProgressButtons($content, $course) {
    $settings = \Drupal::configFactory()->getEditable('flashpoint.settings');
    $classes = $settings->getOriginal('flashpoint_course_content.default.renderer_progress_classes');
    $form = [];
    $buttons = [
      'prev' => '',
      'return' => '',
      'next' => '',
    ];
    $button_text = [];
    $button_prev = $settings->getOriginal('flashpoint_course_content.prev_text');
    $button_text['prev'] = empty($button_prev) ? t('Previous') : $button_prev;
    $button_return = $settings->getOriginal('flashpoint_course_content.return_text');
    $button_text['return'] = empty($button_return) ? t('Return to Course') : $button_return;
    $button_next = $settings->getOriginal('flashpoint_course_content.next_text');
    $button_text['next'] = empty($button_next) ? t('Next') : $button_next;

    $return_url = Url::fromRoute('entity.group.canonical', ['group' => $course->id()], ['attributes' => ['class' => $classes]]);
    $return_link = Link::fromTextAndUrl($button_text['return'], $return_url);
    $buttons['return'] = $return_link->toString();

    if ($module = $content->getCourseModule()) {
      // $type = instructional or examination.
      $type = $module['type'];
      $type_cap = ucfirst($type);
      $module = $module['module'];
      $module_content = $module->getCourseContent($type, TRUE);
      for ($i = 0; $i < count($module_content[$type]); $i++) {
        if($module_content[$type][$i]->id() === $content->id()) {
          if ($i > 0) {
            $prev = $module_content[$type][$i - 1];
            if (!$prev->isLocked(\Drupal::currentUser())) {
              $url = Url::fromRoute('entity.flashpoint_course_content.canonical', ['flashpoint_course_content' => $prev->id()], ['attributes' => ['class' => $classes]]);
              $buttons['prev'] = Link::fromTextAndUrl($button_text['prev'], $url)->toString();
            }
          }
          if ($i < count($module_content[$type]) - 1) {
            $next = $module_content[$type][$i + 1];
            if (!$next->isLocked(\Drupal::currentUser())) {
              $url = Url::fromRoute('entity.flashpoint_course_content.canonical', ['flashpoint_course_content' => $next->id()], ['attributes' => ['class' => $classes]]);
              $buttons['next'] = Link::fromTextAndUrl($button_text['next'], $url)->toString();
            }
          }
        }
      }
    }

    $form['buttons'] = ['#type' => 'markup', '#markup' => implode('', $buttons)];
//    $form['buttons'] = ['#type' => 'markup', '#markup' => $buttons['prev'] . $buttons['return'] . $buttons['next']];
    return $form;
  }

}