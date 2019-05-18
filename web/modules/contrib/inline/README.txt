
-- SUMMARY --

Inline module allows users to embed pieces of content into other text content,
by using "macro tags" to establish a reference to the content to embed,
including parameters to control its formatting.

Inline module provides a macro system and generic API for handling macro tags
within other strings/text content.  These macros are processed and rendered into
embedded (rich) HTML content.

By default, the macro system plugs into the 'view' event of text fields and
alters the output of them.  The API is not limited to that use-case, it is the
default integration only.

The macro processing is intentionally not implemented as an input filter, since
macros are not limited to filtered input using a text format, and also, since
Drupal's filter system is architecturally not designed to support implicit and
explicit context as well as maintaining dependencies and references to other
resources/content in the system.

The macro system is also intentionally not based on the token system, since the
token system was never architecturally designed nor intended to support token
replacements within HTML content, handling access, invalidation of cached
embedded content, tracking and maintaining which content is embedded where
(Drupal is a CMS after all), and most importantly, allowing to specify explicit
parameters for how an embedded content should be formatted.

The base/default integration can roughly be explained as follows:

- Inline module implements hook_field_attach_view_alter() and others.

- It builds a basic $context of:
    $context = array('field' => $field, 'entity' => $entity, ...)

- It calls module_invoke_all('inline_macro_context', &$context), allowing other
  modules to enhance the macro context.

- It parses the text content to find all macros (using a standardized
  serialization syntax) and determine the module/plugin that owns each macro.

- It calls the render callback for each macro in the respective owning
  module/plugin, passing the $context that has been set up.

- It calls drupal_alter('inline_macro', $macro, $context) to allow modules to
  alter the rendered result.  Other modules (such as Edit module) may check
  whether the user has access to edit the referenced resource/field, and e.g.,
  wrap or annotate the rendered embedded content in markup that allows the
  frontend to determine whether it is editable.


For a full description visit the project page:
  http://drupal.org/project/inline
Bug reports, feature suggestions and latest developments:
  http://drupal.org/project/issues/inline


-- INSTALLATION --

* Install as usual, for further information see
  http://drupal.org/documentation/install/modules-themes/modules-8


-- CONFIGURATION --

* @todo


-- USAGE --

@todo Rewrite this after deciding on the new standard serialization format.

* For more details on using the module, see the help text by clicking:
  /filter/tips/1#filter-inline after you install it.

  [upload|file=#|title=Custom title text]
  or
  [upload|file=filename.ext|title=Any title]

  You can specify the file you want to display in two ways:
  - specifying #, which will display the #th uploaded file
  - specifying filename

  If the file is not found, a 'NOT FOUND' message will be output.

  Specifying the file by number can cause problems if files are deleted or
  changed. In this case, specifying by name is recommended.

  You can also specify a title for the file, by using an optional 'title=Title'
  parameter. In this case, it will be used as a title for the file link or as
  an ALT tag for an image. If no title is specified, the file name is used as
  title.


-- EXAMPLES --

* @todo


-- CONTACT --

Current maintainers:
* Daniel F. Kudwien (sun) - http://drupal.org/user/54136
* Javier Castro (javier.alejandro.castro) - http://drupal.org/user/482562

Previous maintainers:
* Richard Archer (Richard Archer) - http://www.juggernaut.com.au
* Matteo Ferrari (matteo) - webmaster@cantincoro.org

This project has been sponsored by:
* UNLEASHED MIND
  Specialized in consulting and planning of Drupal powered sites, UNLEASHED
  MIND offers installation, development, theming, customization, and hosting
  to get you started. Visit http://www.unleashedmind.com for more information.

