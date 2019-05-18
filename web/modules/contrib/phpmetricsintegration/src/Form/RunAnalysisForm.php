<?php

namespace Drupal\phpmetricsintegration\Form;

/* this is done to resolve a bug that occurs when execution exceeds the max execution time */
set_time_limit (0);

use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Url;
use Drupal\Core\Form\FormStateInterface;
use Drupal\phpmetricsintegration\Entity\MetricsLog;

/**
 * Builds the form to delete an Example.
 */

class RunAnalysisForm extends EntityConfirmFormBase
{
    private $configVal;

    /**
    * Constructs an RunAnalysisForm object.
    *
    */
    public function __construct()
    {
        $this->configVal = \Drupal::config('phpmetricsintegration.settings');
    }

    /**
    * {@inheritdoc}
    */
    public function getQuestion()
    {
        return $this->t('Are you sure you want to run this current analysis? You are running analysis on %scan_dir and reports will be saved at %report_dir. You can change these settings by clicking <a href="'.Url::fromRoute('entity.phpmetricsintegration.settings_form')->toString().'">here</a>', array('%scan_dir' => $this->configVal->get('phpmetricsintegration.scan_dir'), '%report_dir' => $this->configVal->get('phpmetricsintegration.report_dir')));
    }

    /**
     * {@inheritdoc}
     */
    public function getCancelUrl()
    {
        return new Url('entity.phpmetricsintegration.collection');
    }

    /**
     * {@inheritdoc}
     */
    public function getConfirmText()
    {
        return $this->t('Run Analysis');
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        $entityDetails = $this->runAnalysis();

        $logger = MetricsLog::create(
            [
            'label' => 'MetricsLog',
            'id' => time(),
            'success_report' => $entityDetails[0],
            'error_report' => $entityDetails[1],
            'status_code' => $entityDetails[3],
            'created' => $entityDetails[2],
            'analyzedby' => \Drupal::currentUser()->id(),
            'report_path' => $entityDetails[4]
            ]
        );
        if ($logger->save()) {
            drupal_set_message('Your analysis is complete. Report has been generated.');
            $form_state->setRedirectUrl($this->getCancelUrl());
        } else {
            drupal_set_message($this->t('Error saving report.'));
        }
    }

    /**
     * Helper function to check whether an Example configuration entity exists.
     */
    private function runAnalysis()
    {
        $timeNow = time();
        $reportDir = $this->configVal->get('phpmetricsintegration.report_dir');
        $scanDir = $this->configVal->get('phpmetricsintegration.scan_dir');
        $dirNow = $reportDir . "-" . $timeNow;
        $moduleHandler = \Drupal::service('module_handler');
        $modulePath = $moduleHandler->getModule('phpmetricsintegration')->getPath();
        $command = "php " . $modulePath . "/vendor/bin/phpmetrics.phar --report-html=sites/default/files/$dirNow $scanDir";
        $op = [];
        $err = [];
        $status = 0;
        $descriptorspec = array(
            0 => array("pipe", "r"),  // stdin is a pipe that the child will read from
            1 => array("pipe", "w"),  // stdout is a pipe that the child will write to
            2 => array("pipe", "w")   // stderr is a pipe where error will be written to
        );
        $process = proc_open($command, $descriptorspec, $pipes);
        if (is_resource($process)) {
            // $pipes now looks like this:
            // 0 => writeable handle connected to child stdin
            // 1 => readable handle connected to child stdout
            // Any error output will be appended to /tmp/error-output.txt

            fwrite($pipes[0], '<?php print_r($_ENV); ?>');
            fclose($pipes[0]);

            $op[] = stream_get_contents($pipes[1]);
            fclose($pipes[1]);
      
            $err[] = stream_get_contents($pipes[2]);
            fclose($pipes[2]);

            // It is important that you close any pipes before calling
            // proc_close in order to avoid a deadlock
            $status = proc_close($process);

            echo "command returned $status\n";
        }
    
        $opStr = \serialize($op);
        $errStr = \serialize($err);

        return [$opStr, $errStr, $timeNow, $status, $dirNow];
    }
}