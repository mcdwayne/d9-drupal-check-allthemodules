Tag formats
===========

Storage
-------

<pre>
&lt;div data-chunk-type="admonition" 
     data-admonition-type="note"
     data-admonition-alignment="center"
     data-admonition-width="full"&gt;
This is the admonition content
&lt;/div&gt;
</pre>

Class names
----

Should be unique across namespace formed from union of all type,
width, and alignment class names.


Display
-------

<pre>
&lt;div class="admonition admonition-note admonition-center admonition-full"&gt;
 &lt;img class="admonition-icon"&gt;
 &lt;div class="admonition-content"&gt;Content...&lt;/div&gt;
&lt;/div&gt;
</pre>


Questions and tasks
---------

Attributes are things like data-admonition-alignment. Should these be
namespaced with the word admonition? Or just data-alignment?

Localize titles and labels in JS.

Use one copy of code, shared between PHP and JS.

Discovery. Discover admonition types from files in dirs: HTNL, CSS, JS, and PHP.

Change layout of dialog. 

When the admon plugin is added to the toolbar when editing a format, 
turn on the admonition filter (or maybe don't use the filter
mechanism, use a hook instead).

