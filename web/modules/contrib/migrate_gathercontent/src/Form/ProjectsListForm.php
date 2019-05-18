<?php

namespace Drupal\migrate_gathercontent\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use GuzzleHttp\Exception\ClientException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\migrate_gathercontent\DrupalGatherContentClient;
use Drupal\Core\Datetime\DateFormatter;

/**
 * Class SettingsForm.
 */
class ProjectsListForm extends ConfigFormBase {

  /**
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * @var \Drupal\migrate_gathercontent\DrupalGatherContentClient
   */
  protected $client;

  /**
   * @var \Drupal\Core\DateTime\DateFormatInterface;
   */
  protected $dateFormatter;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {

    return new static(
      $container->get('config.factory'),
      $container->get('migrate_gathercontent.client'),
      $container->get('date.formatter')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory, DrupalGatherContentClient $gathercontent_client, DateFormatter $date_formatter) {
    parent::__construct($config_factory);
    $this->client = $gathercontent_client;
    $this->dateFormatter = $date_formatter;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'migrate_gathercontent.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'migrate_gathercontent_projects_list_form';
  }

  /**
   * Render the list of projects.
   *
   * @return mixed
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $header = [
      'name' => $this->t('Project'),
      'id' => $this->t('ID'),
      'updated' => $this->t('Updated'),
    ];

    try {
      if ($this->client->getAccountId()) {

        $projects = $this->client->getActiveProjects($this->client->getAccountId());
        $selected_projects = $this->config('migrate_gathercontent.settings')->get('projects');

        $rows = [];
        $default_value = [];
        if (!empty($projects)) {
          foreach ($projects as $id => $project) {
            // Get used projects.
            if (!empty($selected_projects) && in_array($id, $selected_projects)) {
              $default_value[$id] = $project->name;
            }

            $rows[$id] = [
              'name' => $project->name,
              'id' => $id,
              'updated' => $this->dateFormatter->format($project->updatedAt, 'short'),
            ];
          }
        }
      }
    } catch (ClientException $e) {
      $rows = [];
    }

    $form['projects'] = array(
      '#type' => 'tableselect',
      '#header' => $header,
      '#options' => $rows,
      '#default_value' => $default_value,
      '#empty' => $this
        ->t('No projects found'),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $projects = $form_state->getValue('projects');
    foreach ($projects as $project_id) {
      if ($project_id != 0) {
       $selected_projects[] = $project_id;
      }
    }

    $this->config('migrate_gathercontent.settings')
      ->set('projects', $selected_projects)
      ->save();
  }
}
