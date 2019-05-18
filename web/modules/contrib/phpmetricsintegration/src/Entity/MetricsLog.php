<?php

namespace Drupal\phpmetricsintegration\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\phpmetricsintegration\MetricsLogInterface;
use Drupal\user\UserInterface;

/**
 * Defines the MetricsLog entity.
 *
 * @ConfigEntityType(
 *   id = "phpmetricsintegration",
 *   label = @Translation("MetricsLog"),
 *   handlers = {
 *     "list_builder" = "Drupal\phpmetricsintegration\Controller\MetricsLogListBuilder",
 *     "form" = {
 *       "analysis" = "Drupal\phpmetricsintegration\Form\RunAnalysisForm",
 *       "settings" = "Drupal\phpmetricsintegration\Form\MetricsLogSettingsForm",
 *       "delete" = "Drupal\phpmetricsintegration\Form\MetricsLogDeleteForm",
 *     }
 *   },
 *   config_prefix = "phpmetricsintegration",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "success_report" = "success_report",
 *     "error_report" = "error_report",
 *     "status_code" = "status_code",
 *     "created" = "created",
 *     "analyzedby" = "analyzedby",
 *     "report_path" = "report_path",
 *   },
 *   links = {
 *     "delete-form" = "/admin/config/development/metrics-log/{phpmetricsintegration}/delete",
 *   }
 * )
 */
class MetricsLog extends ConfigEntityBase implements MetricsLogInterface
{

  /**
   * The MetricsLog ID.
   *
   * @var integer
   */
    public $id;

    /**
     * The Example label.
     *
     * @var string
     */
    public $label;

    /**
     * The MetricsLog success_report.
     *
     * @var string
     */
    public $success_report;

    /**
     * The MetricsLog error_report.
     *
     * @var string
     */
    public $error_report;

    /**
     * The MetricsLog status_code.
     *
     * @var integer
     */
    public $status_code;

    /**
     * The MetricsLog created.
     *
     * @var integer
     */
    public $created;

    /**
     * The MetricsLog analyzedby.
     *
     * @var integer
     */
    public $analyzedby;

    /**
     * The MetricsLog report_path.
     *
     * @var integer
     */
    public $report_path;

    // Your specific configuration property get/set methods go here,
    // implementing the interface.

    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * {@inheritdoc}
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * {@inheritdoc}
     */
    public function setLabel($label)
    {
        $this->label = $label;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getSuccessReport()
    {
        return $this->success_report;
    }

    /**
     * {@inheritdoc}
     */
    public function setSuccessReport($success_report)
    {
        $this->success_report = $success_report;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getErrorReport()
    {
        return $this->error_report;
    }

    /**
     * {@inheritdoc}
     */
    public function setErrorReport($error_report)
    {
        $this->error_report = $error_report;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getStatusCode()
    {
        return $this->status_code;
    }

    /**
     * {@inheritdoc}
     */
    public function setStatusCode($status_code)
    {
        $this->status_code = $status_code;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * {@inheritdoc}
     */
    public function setCreated($created)
    {
        $this->created = $created;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getAnalyzedby()
    {
        return $this->get('analyzedby')->entity;
    }

    /**
     * {@inheritdoc}
     */
    public function setAnalyzedby(UserInterface $account)
    {
        $this->set('analyzedby', $account->id());
        return $this;
    }

    /**
     * @return integer
     */
    public function getReportPath()
    {
        return $this->report_path;
    }

    /**
     * {@inheritdoc}
     */
    public function setReportPath($report_path)
    {
        $this->report_path = $report_path;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getFormattedDate($created)
    {
        $formattedDateTime = date('Y-m-d H:i:s', $created);
        return $formattedDateTime;
    }

    /**
     * {@inheritdoc}
     */
    public static function baseFieldDefinitions(EntityTypeInterface $entity_type)
    {
        $fields = parent::baseFieldDefinitions($entity_type);

        $fields['analyzedby'] = BaseFieldDefinition::create('entity_reference')
        ->setLabel(t('Analyzed by'))
        ->setDescription(t('The username of the content author.'))
        ->setRevisionable(true)
        ->setSetting('target_type', 'user')
        ->setDefaultValueCallback('Drupal\phpmetricsintegration\Entity\MetricsLog::getCurrentUserId')
        ->setTranslatable(true)
        ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'author',
        'weight' => 0,
        ]);
    }



    /**
     * Default value callback for 'uid' base field definition.
     *
     * @see ::baseFieldDefinitions()
     *
     * @return array
     *   An array of default values.
     */
    public static function getCurrentUserId()
    {
        return [\Drupal::currentUser()->id()];
    }
}
