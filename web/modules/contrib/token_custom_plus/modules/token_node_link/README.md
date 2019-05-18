
Node Link Token
===============
Author
* Rik de Boer  (https://www.drupal.org/u/rdeboer)
Commissioned by
* Darren Kelly (https://www.drupal.org/u/webel)

What does it do?
----------------
Introduces the node:link token that will expand as a link to a piece of content
on your site.
You specify the content via its node ID, as an argument to the node:link token:

  [node:link{598}]
  
During token replacement this will expand to a hyperlink like this:

  <a href="http://yoursite.com/node/598">Content title</a>
  
You may optionally specify a title override as a second argument:

  [node:link{598,This is my title override}]
  
which expands to

  <a href="http://yoursite.com/node/598">This is my title override</a>


Development sponsored by:
* GreenSoft Australia
  <a href="https://www.greensoftaustralia.com">GreenSoft Australia</a>
  <a href="https://webel.com.au">Webel IT Australia</a>.
  
* Inspired by: https://www.drupal.org/project/link_node


Installation & Use
------------------
1. Node Link Token is a submodule in the Custom Token Plus download. If you 
   don't have Custom Token Plus on your system yet, download it from drupal.org
   and unzip it into the /modules directory on your Drupal site. 

2. Enable Node Link Token. You will be prompted to also enable, the Token, 
   Custom Tokens and Custom Token Plus modules. The Token Filter module is
   highly recommended. It allows you to embed node:link tokens in formatted text
   fields and text areas.

3  Use the token like other tokens, e.g. in the formatted text area of the 
   content body.
