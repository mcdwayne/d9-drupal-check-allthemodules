<?php

namespace Drupal\dashboard_connector\Checker;

use Drupal\Core\Render\RendererInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\system\SystemManager;

/**
 * Checks for requirements.
 */
class RequirementsChecker extends CheckerBase {

  /**
   * The system manager.
   *
   * @var \Drupal\system\SystemManager
   */
  protected $systemManager;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * ModuleStateChecker constructor.
   *
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The translations service.
   * @param \Drupal\system\SystemManager $system_manager
   *   The system manager.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   */
  public function __construct(TranslationInterface $string_translation, SystemManager $system_manager, RendererInterface $renderer) {
    parent::__construct($string_translation);
    $this->systemManager = $system_manager;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public function getChecks() {
    $checks = [];
    // Check run-time requirements and status information.
    $requirements = $this->systemManager->listRequirements();
    foreach ($requirements as $requirement) {
      if (isset($requirement['severity']) && in_array($requirement['severity'], [REQUIREMENT_ERROR, REQUIREMENT_WARNING], TRUE)) {
        $alert_level = $requirement['severity'] === REQUIREMENT_ERROR ? 'error' : 'warning';
        $checks[] = $this->buildCheck('requirement', $this->render($requirement['title']), $this->render($requirement['value']), $alert_level);
      }
    }
    return $checks;
  }

  /**
   * Renders an element.
   *
   * @param string|array $element
   *   The element to be rendered.
   *
   * @return string
   *   A rendered plain text string.
   */
  protected function render($element) {
    if (is_array($element)) {
      return strip_tags((string) $this->renderer->renderPlain($element));
    }
    return strip_tags($element);
  }

}
