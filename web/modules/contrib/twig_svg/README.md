# twig_svg

1. Ensure your icon.svg file exists within the base theme, in the following
  directory 'your_theme/images/icons.svg'.

2. To include an SVG icon in a twig template, add the following in the relevant
html.twig file:

    {{ icon('icon-name') }}

Optionally, to add a title:
    {{ icon('icon-name', 'Icon title') }}

Optionally, to add classes:
    {{ icon('icon-name', '', ['extra-class', 'another-class']) }}

Optionally, to add a title and classes:
    {{ icon('icon-name', 'Icon title', ['extra-class', 'another-class']) }}

Credit to https://www.lullabot.com/articles/better-svg-sprite-reuse-in-drupal-8 for the fundamentals of this.
