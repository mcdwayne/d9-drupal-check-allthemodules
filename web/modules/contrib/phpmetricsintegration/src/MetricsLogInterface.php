<?php

namespace Drupal\phpmetricsintegration;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\user\UserInterface;

/**
 * Provides an interface defining an Example entity.
 */
interface MetricsLogInterface extends ConfigEntityInterface
{

    /**
     * Returns the id.
     *
     * @return integer
     *   The id of the log.
     */
    public function getId();

    /**
     * Returns the id.
     *
     * @param string $integer
     *
     * @return \Drupal\phpmetricsintegration\MetricsLogInterface
     *   The class instance that this method is called on.
     */
    public function setId($id);

    /**
     * Returns the label.
     *
     * @return string
     *   The label of the log.
     */
    public function getLabel();

    /**
     * Returns the success_report.
     *
     * @param string $label
     *
     * @return \Drupal\phpmetricsintegration\MetricsLogInterface
     *   The class instance that this method is called on.
     */
    public function setLabel($label);

    /**
     * Returns the success_report.
     *
     * @return string
     *   The success_report of the log.
     */
    public function getSuccessReport();

    /**
     * Sets the success_report to the log.
     *
     * @param string $success_report
     *   A string containing the success_report of the log.
     *
     * @return \Drupal\phpmetricsintegration\MetricsLogInterface
     *   The class instance that this method is called on.
     */
    public function setSuccessReport($success_report);

    /**
     * Returns the error_report.
     *
     * @return string
     *   The error_report of the log.
     */
    public function getErrorReport();

    /**
     * Sets the error_report to the log.
     *
     * @param string $error_report
     *   A string containing the error_report of the log.
     *
     * @return \Drupal\phpmetricsintegration\MetricsLogInterface
     *   The class instance that this method is called on.
     */
    public function setErrorReport($error_report);

    /**
     * Returns the status_code.
     *
     * @return integer
     *   The status_code of the log.
     */
    public function getStatusCode();

    /**
     * Sets the status_code to the log.
     *
     * @param string $status_code
     *   A string containing the status_code of the log.
     *
     * @return \Drupal\phpmetricsintegration\MetricsLogInterface
     *   The class instance that this method is called on.
     */
    public function setStatusCode($status_code);

    /**
     * Returns the created.
     *
     * @return integer
     *   The created of the log.
     */
    public function getCreated();

    /**
     * Sets the created to the log.
     *
     * @param integer $created
     *   A integer containing the created of the log.
     *
     * @return \Drupal\phpmetricsintegration\MetricsLogInterface
     *   The class instance that this method is called on.
     */
    public function setCreated($created);

    /**
     * Returns the analyzedby.
     *
     * @return integer
     *   The analyzedby of the log.
     */
    public function getAnalyzedby();

    /**
     * Sets the analyzedby to the log.
     *
     * @param object Drupal\user\UserInterface
     *   A integer containing the analyzedby of the log.
     *
     * @return \Drupal\phpmetricsintegration\MetricsLogInterface
     *   The class instance that this method is called on.
     */
    public function setAnalyzedby(UserInterface $account);

    /**
     * Returns the report_path.
     *
     * @return integer
     *   The report_path of the log.
     */
    public function getReportPath();

    /**
     * Sets the report_path to the log.
     *
     * @param integer $report_path
     *   A integer containing the report_path of the log.
     *
     * @return \Drupal\phpmetricsintegration\MetricsLogInterface
     *   The class instance that this method is called on.
     */
    public function setReportPath($report_path);

    /**
     * Returns the formatted created.
     *
     * @param integer $created
     *   A integer containing the analyzedby of the log.
     *
     * @return string
     *   The created of the log.
     */
    public function getFormattedDate($created);
}
