Node read time
Reading time is a module that provides an extra field for content types,
which displays to the users the time it will take for them to read a node.
This field takes into consideration all the textfields part of the content type,
plus entity reference revision fields like Paragraphs, Custom blocks etc.
It also comes with a configuration page, where you can:
- activate the reading time field for specific content types;
- set the "words per minute" value, which is part of the
calculation of the reading time;

Also, the module provides twig template, which can be easily modified
in your custom theme.
