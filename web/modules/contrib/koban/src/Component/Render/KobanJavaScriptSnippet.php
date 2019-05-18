<?php

namespace Drupal\koban\Component\Render;

use Drupal\Component\Render\MarkupInterface;

class KobanJavaScriptSnippet implements MarkupInterface {

    /**
     * The string to escape.
     *
     * @var string
     */
    protected $string;

    /**
     * Constructs an HtmlEscapedText object.
     *
     * @param string $string
     *   The string to escape. This value will be cast to a string.
     */
    public function __construct($string) {
        $this->string = (string) $string;
    }

    /**
     * {@inheritdoc}
     */
    public function __toString() {
        return $this->string;
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize() {
        return $this->__toString();
    }

}
