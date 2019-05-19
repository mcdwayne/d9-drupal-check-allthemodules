<?php

namespace Drupal\sir_trevor;

use Psr\Log\LoggerInterface;

class IconSvgMerger implements IconSvgMergerInterface {

  /** @var \Psr\Log\LoggerInterface */
  private $logger;

  public function __construct(LoggerInterface $logger) {
    $this->logger = $logger;
  }

  /**
   * @param string[] $fileNames
   * @return string
   */
  public function merge(array $fileNames) {
    $fileContents = $this->loadFileContents($fileNames);
    $xmlDocuments = $this->loadXmlDocuments($fileContents);
    return $this->mergeXmlDocuments($xmlDocuments);
  }

  /**
   * @param array $fileNames
   * @return array
   */
  private function loadFileContents(array $fileNames) {
    $fileContents = [];
    foreach ($fileNames as $fileName) {
      if (!file_exists($fileName)) {
        $this->logger->warning("{$fileName} does not exist.");
      }
      else {
        $fileContents[$fileName] = file_get_contents($fileName);
      }
    }
    return $fileContents;
  }

  /**
   * @param array $sources
   * @return \DOMDocument[]
   */
  private function loadXmlDocuments(array $sources) {
    $documents = [];

    foreach ($sources as $filename => $source) {
      $document = new \DOMDocument();
      try {
        if ($document->loadXML($source)) {
          $documents[] = $document;
        }
        else {
          $this->logger->warning("{$filename} does not contain valid xml.");
        }
      }
      catch (\Exception $e) {
        $this->logger->warning("{$filename} does not contain valid xml.");
      }
    }

    return $documents;
  }

  /**
   * @param \DOMDocument[] $xmlDocuments
   * @return string
   */
  private function mergeXmlDocuments(array $xmlDocuments) {
    $doc = new \DOMDocument();
    $doc->loadXML($this->getEmptySvgSource());
    $defsElement = $doc->getElementsByTagName('defs')->item(0);

    foreach ($xmlDocuments as $document) {
      $nodes = $document->getElementsByTagName('symbol');
      foreach ($nodes as $node) {
        $node = $doc->importNode($node, TRUE);
        $defsElement->appendChild($node);
      }
    }

    return $doc->saveXML($doc->documentElement);
  }

  /**
   * @return string
   */
  private function getEmptySvgSource() {
    return <<<'EMPTY_SVG'
<svg style="position: absolute; width: 0; height: 0;" width="0" height="0" version="1.1"
         xmlns="http://www.w3.org/2000/svg">
    <defs>
    </defs>
</svg>
EMPTY_SVG;
  }
}
