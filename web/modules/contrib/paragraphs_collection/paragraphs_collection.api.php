<?php

/**
 * @file
 * Hooks and documentation related to paragraphs_collection module.
 */

/**
 * @mainpage Paragraphs Collection API documentation.
 *
 * Paragraphs Collection provides multiple paragraph types and various
 * behavior plugins. See paragraphs_collection/README.md for more information.
 *
 * The module provides the following behavior plugins:
 *
 * @section behavior Behavior
 *
 * - @link grid_layout Grid Layout @endlink
 * - @link lockable Lockable @endlink
 * - @link style Style @endlink
 */

/**
 * @defgroup style Style configuration
 * @{
 * Each module can have its own styles for paragraphs defined a YAML based
 * configuration schema. Style definition can be discovered by prefixing config
 * by the name of module and end up filename with 'paragraphs.style.yml'.
 *
 * @code
 * paragraphs_collection_demo.paragraphs.style.yml
 * @endcode
 *
 * The structure of the YAML configuration file must have predefined structure,
 * which contains title, description and CSS libraries to be used to exact
 * style. The definition key "permission" is optional and if set to true, users
 * must have "use {style} style" permission to be able to use it.
 *
 * @code
 * title: 'Name of the style'
 * description: 'Defines different styles for paragraphs'
 * groups:
 *  - 'General Group'
 *  - 'Slideshow Group'
 * libraries:
 *  - 'paragraphs_collection_demo/style'
 * permission: true
 * @endcode
 * @}
 */

/**
 * @defgroup grid_layout Grid Layout configuration
 * @{
 * The Grid plugin has introduced a new type of YAML configuration:
 * paragraphs_collection.paragraphs.grid_layout.yml.
 *
 * It offers a way to define grid layouts that can be reused:
 *   - wrapper_classes: is applied to the Grid wrapping element.
 *   - columns: each element in the "columns" configuration array represents
 *     one column in the layout output. For example, if this configuration
 *     array has 3 elements, it would result in 3 columns in the output.
 *     Any number of classes is allowed per column.
 *   - libraries: specifies the path to the libraries.yml file where it is
 *     defined where to find the CSS rules for the class elements described
 *     in the "columns" configuration array.
 *
 * The attributes structure defined in the YAML configuration should be as
 * follows (still temporary):
 *
 * @code
 * title: 'Two columns layout'
 * description: 'Defines a two column layout with 8-4 widths.'
 * wrapper_classes:
 *  - 'paragraphs-behavior-grid-layout-row'
 * columns:
 *  - classes:
 *      - 'paragraphs-behavior-grid-layout-col-8'
 *  - classes:
 *      - 'paragraphs-behavior-grid-layout-col-4'
 * libraries:
 *  - 'paragraphs_collection/grid_layout'
 * @endcode
 *
 * @see paragraphs_collection.paragraphs.grid_layouts.yml
 * @}
 */
