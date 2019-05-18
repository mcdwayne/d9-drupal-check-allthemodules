<?php

namespace Drupal\rules_scheduler\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\views\Views;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Schedule page with a View for the scheduled tasks.
 */
class SchedulerPageController extends ControllerBase {

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The form builder service.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * Constructs a SchedulePageController object.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder service.
   */
  public function __construct(ModuleHandlerInterface $module_handler, FormBuilderInterface $form_builder) {
    $this->moduleHandler = $module_handler;
    $this->formBuilder = $form_builder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('module_handler'),
      $container->get('form_builder')
    );
  }

  /**
   * Composes an embedded View of scheduled tasks with a task management form.
   */
  public function schedulerPage() {
    // Display view for all scheduled tasks.
    if ($this->moduleHandler->moduleExists('views')) {
      // We cannot use views_embed_view() here as we need to set the path for
      // the component filter form.
      $view = Views::getView('rules_scheduler');
      $view->override_path = 'rules_scheduler.schedule';
      $task_list = $view->preview();
    }
    else {
      $task_list = $this->t('To display scheduled tasks you have to install the <a href=":url">Views</a> module.', [':url' => Url::fromUri('https://www.drupal.org/project/views')->toString()]);
    }
    $build['task_view'] = $task_list;
    $build['delete'] = $this->formBuilder->getForm('Drupal\rules_scheduler\Form\SchedulerForm');

    return $build;
  }

}
