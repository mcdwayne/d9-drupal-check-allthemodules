Field Formatter Template

Description
--------------------------
Field Formatter Template (FFT) allow you can easy create and select template for
any field Formatter.

Installation
--------------------------
1. Copy the entire fft directory the Drupal modules/contrib
directory or use Drush with drush dl fft.
2. Login as an administrator. Enable the module on the Modules page.

Usage
--------------------------
- Create your template, default Formatter template store in folder
'sites/all/formatter', you can change this folder at 'admin/config/content/fft'.
Formatter template create as normal twig template, example you create inline tags
template, create file with name 'fft-inline-tags.html.twig' in folder
'sites/all/formatter' open file and type:
<code>
{#Template Name: Inline Tags #}

{% set values = [] %}

{% for item in data %}
  {% set term = link(item.name.value, 'internal:/taxonomy/term/' ~ item.tid.value)|render %}
  {% set values = values|merge([term]) %}
{% endfor %}

<div>{{ values | join(' | ') | raw }}</div>
</code>
Now open "Manage Display" of a content type, chose any field and chose
"Formatter Template" you can config and select "Inline Tags". Your field
formatter will display with 'inline-tags.html.twig'. Variables you can use in
template is data and entity
- data: stored all data of selected field.
- entity: attached entity of field.
