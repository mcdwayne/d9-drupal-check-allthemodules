<?php

namespace Drupal\dbg;

/**
 * Generates a tree output.
 */
class Debug {

  /**
   * Unique array recursion indicator.
   */
  const ARRAY_RECURSION = 'DbG|was#here--YTGaEib579Toz2HA';

  /**
   * Output setting for plain text.
   */
  const OUTPUT_PLAIN = 0;

  /**
   * Output setting for HTML format.
   */
  const OUTPUT_HTML = 1;

  /**
   * The passed element for debugging.
   *
   * @var mixed
   */
  private $element;

  /**
   * The name of the variable.
   *
   * @var string
   */
  private $variableName;

  /**
   * Maximum recursion depth.
   *
   * @var int
   */
  private $depth;

  /**
   * The name of the file from witch the Debug was called.
   *
   * @var string
   */
  private $fileName;

  /**
   * The name of the file where the debugger was ran.
   *
   * @var string
   */
  private $file;

  /**
   * The line in the file from where the debugger was run.
   *
   * @var int
   */
  private $line;

  /**
   * Whether to show the object's function or not.
   *
   * @var bool
   */
  private $showFunctions;

  /**
   * Whether this DBG is called already or not.
   *
   * @var bool
   */
  private $shown;

  /**
   * Object fingerprints.
   *
   * @var array
   */
  private $fingerprints;

  /**
   * Output type.
   *
   * @var string
   */
  private $outputType;

  /**
   * Group for the messages.
   *
   * @var string
   */
  private $group;

  /**
   * Initializes Debug object.
   *
   * @param mixed $element
   *   The element which needs to be walked through, can be anything.
   * @param string $group
   *   Groups multiple debug messages together.
   */
  public function __construct($element, string $group = '_none') {
    $dg = debug_backtrace();
    $dg = $dg[1];
    $this->group = $group;

    // Get file and variable name.
    if (strpos($dg['file'], 'eval()') === FALSE) {
      $dg['file'] = str_replace('\\', '/', $dg['file']);
      $this->fileName = substr($dg['file'], strrpos($dg['file'], '/') + 1);
      $file = file($dg['file']);
      $this->variableName = $file[$dg['line'] - 1];
    }
    else {
      $this->fileName = 'eval_code';
    }

    // Get variable name, works correctly if only one call is made in one line.
    $matches = '';
    preg_match('/dbg.*\(.*(\$[a-zA-Z_\x7f-\xff\-\>]*).*\)/i', $this->variableName, $matches);
    $this->variableName = isset($matches[1]) ? $matches[1] : "...";

    $this->element = $element;
    $this->depth = 255;
    $this->file = $dg['file'];
    $this->line = $dg['line'];
    $this->shown = FALSE;
    $this->fingerprints = [];
    $this->outputType = self::OUTPUT_HTML;
    $this->showFunctions = TRUE;
  }

  /**
   * Destructor.
   */
  public function __destruct() {
    // Show debug message if it wasn't shown until destruction.
    if (!$this->shown) {
      try {
        $this->show();
      }
      catch (\ReflectionException $e) {
        error_log($e->getMessage(), 'exception');
      }
    }
  }

  /**
   * Set output type.
   *
   * @param int $type
   *   The type of the output.
   *
   * @return $this
   *   The current object.
   *
   * @throws \Exception
   *   An exception will be thrown if the type is not valid.
   */
  public function setOutput(int $type): Debug {
    if (!in_array($type, [self::OUTPUT_PLAIN, self::OUTPUT_HTML])) {
      throw new \Exception('Invalid type.');
    }
    $this->outputType = $type;
    return $this;
  }

  /**
   * Sets the maximum recursion depth.
   *
   * @param int $depth
   *   The maximum depth of the recursion.
   *
   * @throws \Exception
   *   If the $depth variable not numeric.
   *
   * @return $this
   *   The current object.
   */
  public function setMaxDepth(int $depth): Debug {
    $this->depth = $depth;
    return $this;
  }

  /**
   * Builds a sub structure for functions.
   *
   * @param string &$container
   *   Parent container.
   * @param object $object
   *   The object for which we can get the functions.
   * @param bool $root
   *   Whether if the parent element is the root or not.
   * @param int $depth
   *   The current depth in the tree.
   *
   * @return $this
   *   The current object.
   *
   * @throws \ReflectionException
   */
  private function getMethods(string &$container, $object, bool $root = FALSE, int $depth = 0): Debug {
    $reflection = new \ReflectionObject($object);
    $methods = $reflection->getMethods();
    if (($count = count($methods)) > 0) {
      if ($this->outputType == self::OUTPUT_HTML) {
        if ($root) {
          $level_type = "root";
        }
        else {
          $level_type = "child";
        }
        $container .= "<div class='{$level_type} container'><div class='inner'><span class='key'>Methods</span> <span class='type'>({$count})</span></div>";
      }
      elseif ($this->outputType == self::OUTPUT_PLAIN) {
        $container .= "{$this->indention($depth - 1)} # Methods ({$count})" . PHP_EOL;
      }

      foreach ($methods as $method) {
        if ($method->isPrivate()) {
          $visibility = 'private';
        }
        elseif ($method->isProtected()) {
          $visibility = 'protected';
        }
        else {
          $visibility = 'public';
        }

        if ($this->outputType == self::OUTPUT_HTML) {
          $doc_comment = trim($this->processDocComment($reflection, $method));

          $has_no_children_class = '';
          if (empty($doc_comment)) {
            $has_no_children_class = ' no-children';
          }

          $container .= "<div class='child container{$has_no_children_class} visibility-{$visibility}'><div class='inner'><span class='visibility'>{$visibility}</span> <span class='type'>function</span> <span class='method-name'>{$method->getName()}()</span></div>";

          if (!empty($doc_comment)) {
            $container .= "<div class='child container no-children'><div class='inner'><pre>{$doc_comment}</pre></div></div>";
          }

          $container .= '</div>';
        }
        elseif ($this->outputType == self::OUTPUT_PLAIN) {
          $container .= "{$this->indention($depth - 2)} # {$visibility} function {$method->getname()}()" . PHP_EOL;
        }
      }

      if ($this->outputType == self::OUTPUT_HTML) {
        $container .= "</div>";
      }
    }
    return $this;
  }

  /**
   * Processes method's documentation comment.
   *
   * @param \ReflectionClass $class
   *   The current class on which the method exists.
   * @param \ReflectionMethod $method
   *   The method for which the comment needs to be processed.
   *
   * @return string
   *   The processed documentation comment.
   *
   * @throws \ReflectionException
   */
  private function processDocComment(\ReflectionClass $class, \ReflectionMethod $method): string {
    $string = preg_replace('/^ *\/?\*+\/? ?/im', '', $method->getDocComment());
    if (strpos($string, '{@inheritdoc}')) {
      $parent = $class->getParentClass();

      if ($parent !== FALSE && $parent->hasMethod($method->getName())) {
        $string = str_replace('{@inheritdoc}', $this->processDocComment($parent, $parent->getMethod($method->getName())), $string);
      }
      else {
        foreach ($class->getInterfaces() as $interface) {
          if ($interface->hasMethod($method->getName())) {
            $string = str_replace('{@inheritdoc}', $this->processDocComment($interface, $interface->getMethod($method->getName())), $string);
          }
        }
      }
    }
    return $string;
  }

  /**
   * Walk through the array or object tree.
   *
   * @param mixed $item
   *   The item to walk through on.
   * @param int $depth
   *   Maximum depth in the whole tree.
   * @param bool $root
   *   Tells the function whether it is a root or not, do not set.
   *
   * @return string
   *   A formatted view of the $item.
   *
   * @throws \ReflectionException
   */
  private function treeTraversal(&$item, int $depth, bool $root = TRUE): string {
    $container = '';

    // Convert object to array, to be able to walk through the private sections
    // too.
    if (is_object($item)) {
      $reflection = new \ReflectionObject($item);

      $constants = $reflection->getConstants();
      $constant_elements = [];
      foreach ($constants as $key => $constant) {
        $constant_elements['const ' . $key] = $constant;
      }
      $constants = NULL;

      $item_elements = $constant_elements + $reflection->getProperties();
    }
    else {
      $item_elements = $item;
    }

    foreach ($item_elements as $key => &$element) {
      if ($key === self::ARRAY_RECURSION) {
        continue;
      }

      $visibility = '';
      $static = '';
      if ($element instanceof \ReflectionProperty) {
        $element->setAccessible(TRUE);
        $name = $element->getName();
        // FIXME: Sometimes it still throws an error, let's ignore it for now.
        @$value = $element->getValue($item);

        // Determine property visibility.
        if ($element->isPrivate()) {
          $visibility = 'private';
        }
        elseif ($element->isProtected()) {
          $visibility = 'protected';
        }
        else {
          $visibility = 'public';
        }

        // Indicate if the property is static.
        if ($element->isStatic()) {
          if ($this->outputType == self::OUTPUT_HTML) {
            $static = ' <span class="static">static</span>';
          }
          else {
            $static = ' static';
          }
        }
      }
      else {
        $name = $key;
        $value = &$element;
      }

      if ($this->outputType == self::OUTPUT_HTML) {
        $classes = "class='";
        if ($root) {
          $classes .= "root container";
        }
        else {
          $classes .= "child container";
        }

        if ((!is_array($value) || (is_array($value) && count($value) == 0)) && !is_object($value)) {
          $classes .= " no-children";
        }

        if (!empty($visibility)) {
          $classes .= ' visibility-' . $visibility;
        }

        $classes .= "'";
        $container .= "<div {$classes}>";
      }

      if (is_object($item)) {
        if ($this->outputType == self::OUTPUT_HTML) {
          $container .= "<div class='inner'><span class='visibility'>{$visibility}</span>{$static} <span class='name'>{$name}</span> ";
        }
        else {
          $container .= $this->indention($depth) . "{$visibility}{$static} {$name}";
        }
      }
      else {
        if ($this->outputType == self::OUTPUT_HTML) {
          $container .= "<div class='inner'><span class='name'>{$name}</span> ";
        }
        else {
          $container .= $this->indention($depth) . "{$name} ";
        }
      }

      // Determine type.
      $container .= $this->getVariableType($value, FALSE) . PHP_EOL;

      if ($this->outputType == self::OUTPUT_HTML) {
        $container .= "</div>";
      }

      // List object functions.
      if (is_object($value) && $this->showFunctions) {
        $this->getMethods($container, $value, FALSE, $depth);
      }

      // Check for recursion.
      $recursion = FALSE;
      if ((is_array($value) && isset($value[self::ARRAY_RECURSION])) || (is_object($value) && in_array($this->getObjectFingerprint($value), $this->fingerprints))) {
        $recursion = TRUE;
      }

      if ($recursion) {
        if ($this->outputType == self::OUTPUT_HTML) {
          $container .= "<div class='child container no-children'><div class='inner'><span class='infinite'>&infin;</span> <span>RECURSION</span></div></div>";
        }
        elseif ($this->outputType == self::OUTPUT_PLAIN) {
          $container .= $this->indention($depth) . ' -- RECURSION -- ' . PHP_EOL;
        }
      }
      elseif ($depth && ((is_array($value) && count($value) > 0) || is_object($value) || $value instanceof \ArrayObject)) {
        if (is_object($value)) {
          $this->fingerprints[] = $this->getObjectFingerprint($value);
        }
        elseif (is_array($value)) {
          $value[self::ARRAY_RECURSION] = TRUE;
        }

        $container .= $this->treeTraversal($value, $depth - 1, FALSE);
      }

      if ($this->outputType == self::OUTPUT_HTML) {
        $container .= "</div>";
      }
    }

    return $container;
  }

  /**
   * Get indention based on the current depth.
   *
   * @param int $currentDepth
   *   The current depth in the tree.
   *
   * @return string
   *   Indention by two spaces for each level.
   */
  private function indention(int $currentDepth): string {
    return str_repeat('  ', $this->getLevel($currentDepth));
  }

  /**
   * Gets the level based on the current depth.
   *
   * @param int $currentDepth
   *   The current depth in the tree.
   *
   * @return int
   *   Level calculated from the depth.
   */
  private function getLevel(int $currentDepth): int {
    return $this->depth - $currentDepth;
  }

  /**
   * Whether to show the functions in the tree or not.
   *
   * @param bool $show
   *   TRUE to show the functions (default) or FALSE to not show them.
   *
   * @return $this
   *   The current object.
   */
  public function showFunctions(bool $show): Debug {
    $this->showFunctions = $show;
    return $this;
  }

  /**
   * Calculate a fingerprint for the given object.
   *
   * @param \object|\ArrayObject $object
   *   Object for which the hash needs to be determined.
   *
   * @return string
   *   The calculated object hash for the object.
   */
  private function getObjectFingerprint($object): string {
    return spl_object_hash($object);
  }

  /**
   * Gets the type of the variable.
   *
   * @param mixed $variable
   *   The variable for which we want to determine the type.
   * @param bool $only_type
   *   Whether to return only the type or the value too.
   *
   * @return string
   *   Type or type with value.
   */
  private function getVariableType($variable, bool $only_type = TRUE): string {
    $is_type = FALSE;

    if (is_int($variable)) {
      $type = 'Integer';
    }
    elseif (is_float($variable)) {
      $type = 'Double';
    }
    elseif (is_string($variable)) {
      $len = strlen($variable);
      $type = "String, {$len} characters";
      $variable = $this->outputType == self::OUTPUT_HTML ? htmlspecialchars($variable, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') : $variable;
    }
    elseif (is_bool($variable)) {
      $type = 'Boolean';
    }
    elseif (is_array($variable)) {
      $type = 'Array, ' . count($variable) . ' items';
      $is_type = TRUE;
    }
    elseif (is_object($variable)) {
      $type = 'Object type of ' . get_class($variable);
      $is_type = TRUE;
    }
    elseif ($variable === NULL) {
      $type = 'NULL';
      $is_type = TRUE;
    }
    else {
      $type = 'Unknown';
    }

    if (!$only_type && !$is_type) {
      if ($this->outputType == self::OUTPUT_HTML) {
        return "<span class='type'>({$type})</span> {$this->htmlValue($variable)}";
      }
      elseif ($this->outputType == self::OUTPUT_PLAIN) {
        return "({$type}) {$this->plainValue($variable)}";
      }
    }

    return $type;
  }

  /**
   * Get plain value.
   *
   * @param mixed $value
   *   The value.
   *
   * @return string
   *   Plain value, bool converted to string.
   */
  private function plainValue($value): string {
    if (is_bool($value)) {
      $bool = $value ? 'TRUE' : 'FALSE';
      return $bool;
    }
    return $value;
  }

  /**
   * HTML Wrapper for the value.
   *
   * @param mixed $value
   *   The value which needs to be wrapped into HTML.
   *
   * @return string
   *   The wrapped string.
   */
  private function htmlValue($value): string {
    if (is_string($value) && strlen($value) > 160) {
      return "<div class='value long-string'>{$value}</div>";
    }
    elseif (is_bool($value)) {
      $bool = $value ? 'TRUE' : 'FALSE';
      return "<span class='value bool'>{$bool}</span>";
    }
    return "<span class='value'>{$value}</span>";
  }

  /**
   * Create the debug html for viewing.
   *
   * @param mixed $object_function
   *   Object function or variable.
   * @param mixed $tree
   *   The sub items of the main object/function.
   *
   * @return string
   *   Generated debug markup.
   */
  private function createDebugOutput($object_function, $tree = NULL): string {
    $type = $this->getVariableType($this->element);

    if (!is_null($tree)) {
      $variables = $object_function . $tree;
    }
    else {
      $object_function = $this->htmlValue($object_function);
      $variables = "<div class='root container no-children'><div class='inner'><span class='value'>{$object_function}</span></div></div>";
    }

    if ($this->outputType == self::OUTPUT_HTML) {
      return <<<EOT
<div class='debug-information'>
  <div class='container'>
    <div class='inner'>
      <span class='key'>{$this->variableName}</span>
      <span class='type'>{$type}</span>
      <div class='info'>"Called from <b>{$this->file}</b>, line <b>{$this->line}</b>"</div>
    </div>
    <div class='container'>{$variables}</div>
  </div>
</div>
EOT;
    }
    else {
      return <<<EOT
{$this->variableName} : {$type}
Called from {$this->file}, line {$this->line}

{$variables}
EOT;
    }
  }

  /**
   * Prints out the debug messages on the very top of the page.
   *
   * @return $this
   *   The current object.
   *
   * @throws \ReflectionException
   */
  public function show(): Debug {
    if (is_array($this->element) || is_object($this->element)) {
      // List object functions.
      $object_function = '';
      if (is_object($this->element) && $this->showFunctions) {
        $this->getMethods($object_function, $this->element, TRUE);
      }

      $tree = $this->treeTraversal($this->element, $this->depth);

      $_SESSION['dbg_information'][$this->group][] = $this->createDebugOutput($object_function, $tree);
    }
    else {
      $_SESSION['dbg_information'][$this->group][] = $this->createDebugOutput($this->element);
    }

    $this->shown = TRUE;
    return $this;
  }

  /**
   * Write debug information to file.
   *
   * @param string|null $dir
   *   Save file to a given location.
   *
   * @return \Drupal\dbg\Debug
   *   The current object.
   *
   * @throws \ReflectionException
   */
  public function writeToFile(?string $dir = NULL): Debug {
    // Get temp dir.
    $temp = ($dir == NULL ? file_directory_temp() : $dir) . '/DBG';

    // Check for DBG directory, if not exists create one.
    if (!is_dir($temp)) {
      mkdir($temp);
    }

    // Create a temp directory if not exists.
    $temp .= '/' . date('Ymd-his', time());
    if (!is_dir($temp)) {
      mkdir($temp);
    }

    if (is_array($this->element) || is_object($this->element)) {
      $object_function = '';
      if (is_object($this->element) && $this->showFunctions) {
        $this->getMethods($object_function, $this->element, TRUE);
      }
      $tree = $this->treeTraversal($this->element, $this->depth);
      $data = $this->createDebugOutput($object_function, $tree);
    }
    else {
      $data = $this->element;
    }

    $content = '';
    $ext = 'html';
    if ($this->outputType == self::OUTPUT_HTML) {
      // Get css file.
      $css = file_get_contents(getcwd() . '/' . drupal_get_path('module', 'dbg') . '/css/dbg.css');
      // Get JS files.
      $js = file_get_contents(getcwd() . '/' . drupal_get_path('module', 'dbg') . '/js/dbg.js');

      $content = <<<EOT
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <title>Debug Output</title>
    <style rel="stylesheet" type="text/css">{$css}</style>
    <script type="text/javascript">{$js}</script>
  </head>
  <body>
    {$data}
  </body>
</html>
EOT;
    }
    elseif ($this->outputType == self::OUTPUT_PLAIN) {
      $ext = 'txt';
      $content = $data;
    }

    $index = 0;
    while (TRUE) {
      $file = $temp . '/' . $this->fileName . ($index == 0 ? '' : '-' . $index) . ".{$ext}";

      if (!is_file($file)) {
        file_put_contents($file, $content);
        break;
      }

      $index++;
    }

    $this->shown = TRUE;
    return $this;
  }

}
