# Drupal Processing.js Module

Provides an input filter and template for rendering [processing.js](http://processingjs.org) sketches.

## Requirements

* The [Processing.js](http://processingjs.org/download) JavaScript library.

## Installation

### 1. Download and extract processing.js as a Drupal library

* Example: `/libraries/processing/processing.min.js`

### 3. Configure the processing.js path.

* Confirm that the location that you installed processing.js matches the module's configuration page.

### 2. Configure a text format to use the Processing input filter

There are additional configuration options for the input filter. See below.

Note that processing.js filter is not compatible with "Correct Faulty HTML", and you should remove this core filter from the text format you use.
It is also important to check compatibility of other filters, and re-arrange processing.js to work with them.
For example, the "Line Break Converter" filter should be weighted *below* processing.js filter.

## Configuration Explanation

Each option may be configured for each text format as well as global options used when rendering a sketch manually.

#### Render Mode

1. Source first: The source code will be displayed along with a button to render the sketch.
2. Render first: The sketch will be rendered along with a button to display the source code.
3. Render only: The sketch will only be rendered (useful for embedding processing sketches as blocks).

#### Blacklist

1. Processing.js includes functions that manipulate the DOM. It is recommended that you restrict functions such as print, println, status and param if you allow arbitrary code sketches by users.
2. You can restrict any function listed in the [Processing.js Reference](http://processingjs.org/reference).
3. Any line containing restricted code will be commented out.

## Usage

### Embed processing sketches in content as a content author

Write Processing.js code within [processing] [/processing] tags. Nodes, blocks, comments, etc... may contain as many [processing] tags as you want.

### Embed processing sketches in a template or theme directly

It is possible to manually inject the code into templates. For example, at the top of a page.

1. Load the "processing/processing" library in `#attached` somewhere so that processing.js is loaded, but drupal.processing does not.
2. Use the Processing.js markup as normal.
