Paragraphs React
==========
The base idea of this module is to allow content editors to be able to generate a set
of ReactJS components by inserting JSX directly into a paragraph widget, providing a quick way to generate a
ReactJS page from a paragraph field, each paragraph is treated as a particular ReactJS component called "ParagraphsReact".

BabelJS (https://babeljs.io/) is used for generate transpiled ReactJS code (by default).
ReactDOM & ReactJS libraries are loaded (by default) using unkpg (https://unpkg.com/), but you can
choose also to load those libraries on your own.
The only dependence of this project is the paragraph (https://www.drupal.org/project/paragraphs)
contributed module so you can use this module also on a shared hosting.

For themers :
  - you can override paragraphs-react.html.twig for managing the output of the page
  - you can override paragraphs-react-component.html.twig for changing the output of the components
  - you can override paragraphs-react-component-{paragraph_type}.html.twig for changing the output of components for a specific paragraph type

Author/Maintainer
======================
- Raffaele Chiocca <rafuel92 at ibuildings> http://www.ibuildings.it
