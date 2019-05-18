
Custom Tokens Plus
==================
Author
* Rik de Boer, www.drupal.org/u/rdeboer
Commissioned by
* Darren Kelly, www.drupal.org/u/webel

What does it do?
----------------
Adds the option to use arguments on customer-defined tokens, as defined by the
the Custom Tokens module 
<a href="http://drupal.org/project/token_custom">Custom Tokens</a>

For instance, using Custom Tokens you may already have a token like this:

  [custom:company_logo]

This token would generate an HTML snippet of your company's logo, of a 
particular size, as a clickable hyperlink. Maybe it has the company slogan next
to it, too.

After enabling the Custom Tokens Plus module, you can extend this token 
with arguments, so you can specify variants for, say, the logo height and the
file to use for the logo image.

Rather than having to maintain multiple, near-identical versions of the above
token, you capture the variants via arguments (parameters), like so:

  [custom:company_logo{50px,comp1.jpg}]
  
  [custom:company_logo{100px,comp2a.jpg}]
  
  [custom:company_logo{200px,comp2b.jpg}]

When you specify the replacement text to use for the company_logo token, you
embed amongst the fixed parts of the text these special codes %1, %2... These
refer to the actual arguments to be passed to the token, when invoked as above.

Example:

  <span class="logo">
    <a href="/about-us"><img style="height:%1" src="/sites/default/%2"/></a>
    <strong>MyComp: the best - stuff the rest</strong>
  </span>
  
What else does it do?
---------------------
1) There are a number of enhancements to the the Custom Tokens list page:

a) Each token comes with a Clone option. This is handy when creating similar 
tokens. Clone an existing custom token, then make changes to the clone and save.

b) Sorting of the custom tokens list can be configured to be by type first, then
by machine name, making for a page that is better organised and easier to read.

c) The the number of rows shown (page size) when paging through the custom
token list can be set by the administrator. 
   
All of the above may be configured by the administrator on the configuration
page, /admin/config/content/token-custom-plus-settings

2) With the download of the Custom Tokens Plus module comes the submodule Node 
Link Token. It defines a replacement token that expands out as a hyperlink to a 
piece of content on your site. Like so: [node:link{321}], where 321 is the node 
ID. More info in token_custom_plus/modules/token_node_link/README.md


Development sponsored by:
* GreenSoft Australia, greensoftaustralia.com
* Darren Kelly, Webel IT, webel.com.au

  This minimally maintained module was developed for internal company use.
  It is offered to the Drupal community in the hope it may also be of use to
  others. Feature requests are not currently accepted.

Installation
------------
1. Download the Custom Token Plus module from drupal.org and unzip it into the 
   /modules directory on your Drupal site. 

2. Enable Custom Tokens Plus. You will also need to install the Token and Custom
   Tokens modules. The Token Filter module comes highly recommended. It allows
   you to embed tokens in formatted text fields and text areas, like the body
   of a piece of content.

3. Assign permissions "administer custom tokens" and "administer custom token 
   types" to eligible roles. The modules Custom Tokens and Custom Tokens Plus
   share the same permissions
   
4. When entering Custom Tokens we recommend you do NOT use an editor and also
   switch any of the Text Formatters that may add output to what you type, such
   as the infamous adding of <p> and <br> tags. We suggest you visit
   /admin/config/content/formats and press "Add text format" to create a 
   "Custom Token Editor" text format that has NO Text Editor and none of the
   filters enabled. Then use this text format on the Content text area where
   you add or edit Custom Tokens.
   
 5 If you wish to use the token Clone function on the custom tokens page,
   admin/structure/token-custom, then you must install the Prepopulate module,
   www.drupal.org/project/prepopulate

Creating or extending tokens with arguments
-------------------------------------------
The process is the same as for Custom Tokens, but what you enter in the fields
is slightly different

1. Go to "Structure" -> "Custom tokens"

2. Press "Add Token" or, if you wish to extend an existing token, "Edit" or
   "Clone" (to the right of the token in question).
   
3. The Custom Tokens form displays. Follow the instructions that appear with
   each of the fields on the form.
   
 FAQ
 ---
 Q1: Can I invoke the same taken multiple times in the same piece of content,
 with either the same or different argument values?
 A1: Yes!
 
 Q2: Can I nest my tokens, i.e. can I put in the Content area of a token an
 invocation of another token?
 A2: You certainly can!
 
 Q3: Can I use a token invocation as an argument, e.g. 
     [custom:mytok{[custom:othertok]}]
 A3: You silly bugger! Why would you want to do that? This is not possible,
     because the Token module does not support plain tokens nested this way,
     let alone tokens with arguments.
