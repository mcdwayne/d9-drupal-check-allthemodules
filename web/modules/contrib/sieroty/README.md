CONTENTS OF THIS FILE
---------------------
   
 * Introduction
 * Requirements
 * Installation
 * Configuration
 
INTRODUCTION
------------
<p>There's a rule in Polish typography that forbids 
placing one-letter conjunctions ("a", "i", "w") on the 
end of the line.This module adds a text filter that 
inserts `&nbsp;` after each such cnojunction in order 
to prevent them from being rendered on the end of a line.<p>
   
REQUIREMENTS
------------
None.

INSTALLATION
------------

<code>composer require 'drupal/sieroty:1.x-dev'</code>

CONFIGURATION
------------
<ol>
<li>Go to: <b>Configuration > Content authoring > 
Text formats and editors</b></li>
<li>Add Text formatter. <i>(optional)</i></li>
<li>Click Sieroty Text Filter in settings.</li>
</ol>
