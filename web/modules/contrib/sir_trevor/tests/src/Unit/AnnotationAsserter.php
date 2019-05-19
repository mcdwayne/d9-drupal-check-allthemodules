<?php

namespace Drupal\Tests\sir_trevor\Unit;

use Doctrine\Common\Annotations\Annotation;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\Common\Annotations\DocParser;

trait AnnotationAsserter {
  /**
   * Provides an array of annotation class names that can be expected.
   * @return string[]
   */
  protected abstract function getAnnotationClassNames();

  /**
   * @param Annotation[] $expectedAnnotations
   * @param string $classToTest
   */
  protected function assertClassAnnotationsMatch(array $expectedAnnotations, $classToTest) {
    $annotations = $this->getClassAnnotations($classToTest);
    \PHPUnit_Framework_Assert::assertEquals($expectedAnnotations, $annotations);
  }

  /**
   * @return \Doctrine\Common\Annotations\DocParser
   */
  protected function createDocParser() {
    $docParser = new DocParser();

    foreach ($this->getAnnotationClassNames() as $class) {
      $annotationClass = new \ReflectionClass($class);
      AnnotationRegistry::registerFile($annotationClass->getFileName());
      $docParser->addNamespace($annotationClass->getNamespaceName());
    }

    return $docParser;
  }

  /**
   * @param string $classToTest
   * @return Annotation[]
   */
  protected function getClassAnnotations($classToTest) {
    $docParser = $this->createDocParser();
    $class = new \ReflectionClass($classToTest);
    $annotations = $docParser->parse($class->getDocComment());
    return $annotations;
  }

}
