# Termcase #

The Termcase module gives site administrators the option to specify case-formatting on the terms within a vocabulary.

It prevents users to use different cases on terms. With termcase you can make sure all terms in a vocabulary begin with an uppercase or that they are all formatted to lowercase.

There are five options available:

  1. No formatting
  2. Ucfirst: convert the first character of the term to uppercase
  3. Lowercase: convert all characters of the term to lowercase
  4. Uppercase: convert all characters of the term to uppercase
  5. Propercase: convert the first character of earch word to uppercase

You can define these settings per vocabulary. Apart from the included formatting options, you can use a hook in your own module to alter the terms just before they are saved, so if needed you can add your own formatting on top of the already applied formatting. The module also enables you to update all terms in an existing vocabulary.

**REQUIREMENTS:**

Taxonomy core module.

**CONFIGURATION:**

When the module is enabled, Termcase settings should appear on Vocabulary edit page.
