<?php

namespace Drupal\inmail_test\Plugin\inmail\Analyzer;

use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\inmail\MIME\MimeMessageInterface;
use Drupal\inmail\Plugin\inmail\Analyzer\AnalyzerBase;
use Drupal\inmail\ProcessorResultInterface;
use Drupal\user\Entity\User;

/**
 * Provides a test analyzer.
 *
 * @Analyzer(
 *   id = "test_analyzer",
 *   label = @Translation("Test Analyzer")
 * )
 */
class TestAnalyzer extends AnalyzerBase {

  /**
   * {@inheritdoc}
   */
  public function analyze(MimeMessageInterface $message, ProcessorResultInterface $processor_result) {
    /** @var \Drupal\inmail\DefaultAnalyzerResult $default_result */
    $default_result = $processor_result->getAnalyzerResult();

    // Provide sample context.
    $this->addContext($default_result);

    // Update default result with example account.
    $users = \Drupal::entityTypeManager()->getStorage('user')->loadByProperties(['mail' => 'demo@example.com']);
    if (!$demo_user = reset($users)) {
      $demo_user = User::create([
        'mail' => 'demo@example.com',
        'name' => 'Demo User',
      ]);
      $demo_user->save();
    }
    $default_result->setAccount($demo_user);

    // Update the body of the default result.
    $default_result->setBody($message->getBody());
  }

  /**
   * Adds a sample context.
   *
   * @param \Drupal\inmail\DefaultAnalyzerResult $default_result
   */
  protected function addContext($default_result) {
    $context_definition = new ContextDefinition('string', $this->t('Test Context'));
    $context = new Context($context_definition, 'Sample context value');
    $default_result->setContext('test', $context);
  }

}
