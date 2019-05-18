INTRODUCTION
------------
This module includes the Blockquote Attribution plugin, an extension of the standard CKEditor
Blockquote button. This button inserts <figure> and <figcaption> markup around the <blockquote>
element. This is the standard way to indicate that a piece of content is quoted from another source.

See the explanation at https://alistapart.com/blog/post/more-thoughts-about-blockquotes-than-are-strictly-required

INSTALLATION
------------
1. Enable CKEditor Blockquote Attribution in Admin -> Extend.
2. Configure your WYSIWYG toolbar to include the button.
3. If the 'Limit allowed HTML tags' filter is enabled, add:
   '<figure class> <blockquote> <figcaption class>'
   to teh list of tags.

REQUIREMENTS
------------
CKEditor Module (Core)

USE
------------
Click the button, optionally with text to be included in the quote selected.
Below the blockquote, placeholder text will be inserted. Select this text and replace it
with the source attribution. The 'Source Work Title' text should be the title of the work
being quoted, as per the HTML 5 specification for the <cite> tag.

MAINTAINERS
------------
Alexander O'Neill (https://www.drupal.org/u/alxp)
