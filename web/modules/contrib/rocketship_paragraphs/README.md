# Rocketship Paragraphs

#### Libraries

You will need these JS libraries installed in your project:
- blazy: v. 1.8.x
- colorbox: v. 1.6.x
- slick: v. 1.8.x (do not use 1.9, it breaks things)

Follow the READMEs for those modules on how to properly download these 
libraries.

#### Configuration

**Go to:**  
`/admin/config/system/rocketship-paragraphs/settings`

Your options are:

**Enable default CSS:**

This loads structural CSS (basic layout such as columns where needed) + a generated CSS file which automatically applies your chosen colors (see 'color variants' explanation further below).  
It is recommended you do not use this if you are using one of the Rocketship themes or customized designs. In those cases, it is better to roll your own.  
If you do not use the Rocketship themes, you can start from the CSS files in the `css/examples`-folder.  
See mor info further below.

**Full-width Backgrounds:**

You can make the paragraph backgrounds stretch the width of your site. But only if your site has no sidebar and the page container doesn't have overflow:hidden on it

**Color variants:**

Here you can define sets of colors for your front-end theme to use. 
Each color set (or 'variant') consists of:
 - a background-color,
 - a foreground color (eg. for the text),
 - a link color
 - and a link hover color.  

When saving the config form:
 - it will generate a CSS file that can apply these colors to your paragraphs. This file is only loaded if you enable the default CSS.  
 - it will generate html-classes on the paragraph div, which you can use to add your own custom theming

Perhaps there are styling exceptions that have to be taken into account. In that case, do not enable the default CSS option and instead add theming for the variations you've added in your custom theme.  
 
You can also remove the variations and instead just declare them in a hook. If
you're already planning on creating custom theming, this may be preferential.

Combining is also possible, as the hook is a simple alter.

**Dummy CSS**

There are 2 dummy CSS-files included in `css/templates`. You can use those as a 
jumping-off point if you want to write your own, where you can replace colors and classes (names starting with 'replace_variant_') 
with your own values. 
It is recommended to also copy and use the style.layouts.css file, as it creates column structures that some of the paragraphs use.

#### Using the paragraph types.

When installing rocketship_paragraphs, it will add a whole lot of paragraph styles for you to use in your content types (or block types).

A lot of the theming and logic is tied to certain fields we've already set up,
either here or in Rocketship Core. So when adding paragraphs to a new content 
type or block, be sure to re-use the existing fields:
- field_header_paragraph: for header Paragraphs
- field_paragraphs: for all other Paragraphs

**Header Paragraphs:**  
Used in lieu of the standard title, these paragraphs add
background color, background image, title override (with support for limited
tags) for detail pages. Be sure to only allow paragraphs of this type for the
header paragraph field.  

**Paragraphs:**  
All other paragraphs are simply pre-made paragraphs. Be sure to 
exclude header paragraphs from these field instances.

**Recommended settings in 'Manage form display':**
- Widget: Paragraphs EXPERIMENTAL
- Add mode: select list
- Default paragraph type: Simple header, for the header paragraphs. Empty for the others.

**Color variants:**   
When adding or editing a the paragraph in a content or block type, you wou will see that you can set a background-color, using a colored preview (colored squares with an 'a' in them).  
Each of those squares matches a color variant that is provided in the config form.  
Simply select a color variant and save your page.  
If you have enabled the default CSS, you will see the colors applied to your paragraph in the front-end theme. Otherwise, you will need to style them yourself, based on the variant classes.
 
**An important note:**  
If you use the default CSS and you remove, or change names of color variants while they are already in use on one or more paragraphs, you will need to re-save the nodes. Otherwise the colors will no longer work. This is because the colors CSS is generated and won't match up with the html-classes anymore.


#### Workflow multilingual

Rocketship goes against the flow in this regard. We make the paragraph fields 
on nodes translatable and the paragraphs untranslatable. The advantage is 
that the client can select different paragraphs per translation. The downside
 is that we rely on extra Contrib, and that out of the box certain other
Contrib which extends or relies on Paragraphs might not work correctly.

So make sure the paragraphs themselves are NOT translatable, the fields we've
provided are already set as translatable and for the widget, use the Asymmetric
one.
