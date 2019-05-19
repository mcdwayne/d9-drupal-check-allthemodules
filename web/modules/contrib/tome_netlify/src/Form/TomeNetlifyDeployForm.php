<?php

namespace Drupal\tome_netlify\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\tome_netlify\TomeNetlifyDeployBatch;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Deploys a static build to Netlify.
 */
class TomeNetlifyDeployForm extends FormBase {

  /**
   * The batch service.
   *
   * @var \Drupal\tome_netlify\TomeNetlifyDeployBatch
   */
  protected $batch;

  /**
   * StaticGeneratorForm constructor.
   *
   * @param \Drupal\tome_netlify\TomeNetlifyDeployBatch $batch
   *   The batch service.
   */
  public function __construct(TomeNetlifyDeployBatch $batch) {
    $this->batch = $batch;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('tome_netlify.deploy_batch')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'tome_netlify_deploy_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    if (!$this->batch->checkConfiguration()) {
      $form['error'][] = [
        '#type' => 'markup',
        '#markup' => '<p>' . $this->t('Tome Netlify has not been configured. <a href=":link">Click here to enter your Netlify credentials.</a>', [
          ':link' => Url::fromRoute('tome_netlify.settings')->toString(),
        ]) . '</p>',
      ];
    }

    if (!$this->batch->checkStaticBuild()) {
      $form['error'][] = [
        '#type' => 'markup',
        '#markup' => '<p>' . $this->t('No static build available for deploy. <a href=":link">Click here to generate one.</a>', [
          ':link' => Url::fromRoute('tome_static.generate')->toString(),
        ]) . '</p>',
      ];
    }

    if (isset($form['error'])) {
      return $form;
    }

    $form['description'] = [
      '#type' => 'markup',
      '#markup' => '<p>' . $this->t('Submitting this form will deploy the latest static build to Netlify as a draft.') . '</p>',
    ];

    $form['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Deploy title'),
      '#description' => $this->t('A title to identify this build.'),
      '#required' => TRUE,
      '#default_value' => $this->t('Sent from Tome Netlify'),
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Deploy'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $batch_builder = $this->batch->getBatch($form_state->getValue('title'))
      ->setFinishCallback([$this, 'finishCallback']);
    batch_set($batch_builder->toArray());
  }

  /**
   * Batch finished callback after the static site has been deployed.
   *
   * @param bool $success
   *   Whether or not the batch was successful.
   * @param mixed $results
   *   Batch results set with context.
   */
  public function finishCallback($success, $results) {
    if (!$success) {
      $this->messenger()->addError($this->t('Deploy failed - consult the error log for more details.'));
      return;
    }
    if (!empty($results['errors'])) {
      foreach ($results['errors'] as $error) {
        $this->messenger()->addError($error);
      }
    }
    $this->messenger()->addStatus($this->t('Deploy complete! To view the deploy, <a target="_blank" href=":deploy">click here</a>. To publish the deploy, <a target="_blank" href=":admin">click here.</a>', [
      ':deploy' => $results['deploy_ssl_url'],
      ':admin' => $results['admin_url'] . '/deploys',
    ]));
  }

}
