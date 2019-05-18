<?php

/**
 * Class responsible for displaying a template files.
 */
class InstapageCmsPluginViewModel {

  /**
   * @var object Class instance.
   */
  private static $viewModel = null;

  /**
   * @var array Data that can be used inside of a template.
   */
  protected $templateData = array();

  /**
   * @var array Templates to fetch.
   */
  protected $templates = null;

  /**
   * Gets the class instance.
   */
  public static function getInstance() {

    if (self::$viewModel === null) {
      self::$viewModel = new InstapageCmsPluginViewModel();
    }

    return self::$viewModel;
  }

  /**
   * Class constructor.
   *
   * @param array $templates Templates to fetch.
   * @param array $attributes Attributes to pass to the templates.
   */
  public function __construct($templates = null, $attributes = null) {
    if ($attributes) {
      foreach ($attributes as $key => $value) {
        $this->templateData[$key] = $value;
      }
    }

    $this->templates = $templates;
  }

  /**
   * Initiates the templates. Sets the templates attributes.
   *
   * @param array $templates Templates to initiate.
   * @param array $attributes Attributes to pass to the templates.
   */
  public function init($templates = null, $attributes = null) {
    if ($attributes) {
      foreach ($attributes as $key => $value) {
        $this->templateData[$key] = $value;
      }
    }

    $this->templates = $templates;
  }

  /**
   * Magic method to set up a template attribute.
   *
   * @param string $name Name of the attribute.
   * @param mixed $value Value of the attribute.
   */
  public function __set($name, $value) {
    $this->templateData[$name] = $value;
  }

  /**
   * Information to the user how to fetch a template properly.
   *
   * @return string A message to the class user.
   */
  public function __toString() {
    return 'use $view->fetch() instead';
  }

  /**
   * Assigns a value as a template attribute.
   *
   * @param string $key Name of the attribute.
   * @param mixed $value Value of the attribute.
   *
   * @return object Template object.
   */
  public function assign($key, $value) {
    $this->templateData[$key] = $value;

    return $this;
  }

  /**
   * Renders the templates.
   *
   * @param array $templates. List of templates to render. If it's null, last user template will be rendered.
   *
   * @throws Excetion If $templates is null and no templates were used before.
   * @throws Exception If no template file is found.
   *
   * @return string Rendered template content.
   */
  public function fetch($templates = null) {
    $templates = $templates ? $templates : $this->templates;

    if (!$templates || empty($templates)) {
      throw new Exception("Templates can not be null.");
    }

    if (!is_array($templates)) {
      $templates = array($templates);
    }

    foreach ($templates as $template) {
      if (!file_exists($template)) {
        throw new Exception("Template {$template} not found.");
      }

      if ($this->templateData) {
        foreach ($this->templateData as $variableName => $variableValue) {
          $$variableName = $variableValue;
          unset($variableName);
          unset($variableValue);
        }
      }

      ob_start();
      include($template);
      $contents = ob_get_contents();
      ob_end_clean();
    }

    return $contents;
  }

  /**
   * Sets variables for the selected template and renders it.
   *
   * @param string $template Template to render.
   * @param array $variables Variables to be set as template attributes.
   *
   * @return string Rendered template content.
   */
  public static function get($template, $variables = null) {
    $view = new View($template);

    if ($variables) {
      foreach ($variables as $key => $value) {
        $view->$key = $value;
      }
    }

    return $view->fetch();
  }

  /**
   * Gets the template variable.
   *
   * @param string $template Template name.
   * @param array $variables Variables to get.
   *
   * @return array Template variables.
   */
  public static function _($template, $variables = null) {
    return self::get($template, $variables);
  }
}
