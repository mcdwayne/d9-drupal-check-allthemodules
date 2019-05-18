<?php

namespace Drupal\automatic_updates\Controller;

use Drupal\automatic_updates\ReadinessChecker\ReadinessCheckerManagerInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\StringTranslation\TranslationInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ReadinessCheckerController.
 */
class ReadinessCheckerController extends ControllerBase {

  /**
   * The readiness checker.
   *
   * @var \Drupal\automatic_updates\ReadinessChecker\ReadinessCheckerManagerInterface
   */
  protected $checker;

  /**
   * ReadinessCheckerController constructor.
   *
   * @param \Drupal\automatic_updates\ReadinessChecker\ReadinessCheckerManagerInterface $checker
   *   The readiness checker.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   */
  public function __construct(ReadinessCheckerManagerInterface $checker, TranslationInterface $string_translation) {
    $this->checker = $checker;
    $this->stringTranslation = $string_translation;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('automatic_updates.readiness_checker'),
      $container->get('string_translation')
    );
  }

  /**
   * Run the readiness checkers.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A redirect
   */
  public function run() {
    $messages = [];
    foreach ($this->checker->getCategories() as $category) {
      $messages = array_merge($this->checker->run($category), $messages);
    }
    if (empty($messages)) {
      $this->messenger()->addStatus($this->t('No issues found. Your site is ready to for <a href="@readiness_checks">automatic updates</a>.', ['@readiness_checks' => 'https://www.drupal.org/docs/8/update/automatic-updates#readiness-checks']));
    }
    return $this->redirect('automatic_updates.settings');
  }

}
