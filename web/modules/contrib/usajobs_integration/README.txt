
Contents of this file
---------------------

 * Overview
 * Installation
 * Setup Tips
 * Overriding the Block Template


Overview
--------
This module creates a block which displays a job listings from USAJobs.gov
The module provides a configuration form which allows a user with permission
to set parameters used to query the USAJobs Search API. Results of this query
are output in a custom "USAJobs Listings" block, and also as JSON
at example.com/usajobs_integration/listings.json
for sites that wish to manipulate the results using Javascript.
More information about the USAJobs Search API:
https://developer.usajobs.gov/API-Reference


Installation
-----------

1. Place the usajobs_integration module in your modules directory.
2. Enable the usajobs_integration module at admin/modules.


Setup Tips
------------

1. Configure USAJobs API connection and filter settings
(admin/config/usajobs_integration)
3. Place the USAJobs Block in a block region (admin/structure/block) to display
on your site.
3. override template/block--usajobs-integration-block.html.twig in your theme
to customize block output


Overriding the Block Template
-----------------------------
To override the block template create a template in your theme

Example template in mytheme/templates/block--usajobs-integration-block.html

{% extends "block.html.twig" %}
{% block content %}
    <ol>
    {% for job in jobs %}
        <li>item: {{ loop.index }} {{ job.positionTitle }}</li>
    {% endfor %}
    </ol>
{% endblock %}
