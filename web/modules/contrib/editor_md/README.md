# Editor.md

> Powerful open source Markdown editor.

## Try out a demonstration!

<https://markdown.unicorn.fail>

## Requirements

- [Drupal Markdown] - The Markdown module for Drupal, version 8.x-2.0 or higher.
- [Editor.md] - The Editor.md library distribution files.


## Installation

- Install the Editor.md module as you would [normally install] any other
  contributed Drupal module.
- In the event your site is not using Composer, you must manually install
  [Editor.md] in `/libraries/editor.md`. Note: it is highly recommended that
  you install this module's [dedicated fork] instead of the upstream which is
  currently out of date.


## Configuration

1. Navigate to `Administration > Extend` and enable the module and its
   dependencies.
2. Navigate to `Administration > Configuration > Content Authoring > Text
   formats and editors`.
3. Either create a new text format or choose an existing one to `Configure`.
4. On the format's configuration page, you can select `Editor.md` in the text
   editor dropdown and then continue to configure the editor as you desire.
5. Click `Save Configuration` when done.


## Maintainers

- a wei (a65162) - https://www.drupal.org/u/a65162
- Mark Carver (markcarver) - https://www.drupal.org/u/markcarver

[Drupal Markdown]: https://www.drupal.org/project/markdown
[Editor.md]: https://pandao.github.io/editor.md/en.html
[dedicated fork]: https://github.com/unicorn-fail/editor.md
[normally install]: https://www.drupal.org/node/1897420
