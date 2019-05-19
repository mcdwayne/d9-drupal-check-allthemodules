## Description

Some of the Filters and functions, backported from TFD7 theme engine and other things that can help a themer getting stuff done without to much hassle :) 
Other filters and functions are copied from other Twig extensions, mainly for our own convenience and to prevent having to enable a lot of Twig extensions in a project.
 
**All the filters are aware of Drupal Render array or Markup objects where needed.**
 

### Extra globals

 base_url  : the base URL of the site, including http or https (depending on the request being secured or not.)

### Filters

#### Wrap filter 
	`{{ content.title_field|wrap('h3') }}` 

Gives you
	`<h3>Title</h3>`

For those days you don't feel like creating a separate field template or use a GUI tool just to add a H3 around a field.

#### Truncate filter

A straight port of the [Twig Text Extension](http://twig.sensiolabs.org/doc/extensions/text.html#truncating-text) truncate filter

    {{ "Hello World!"|truncate(5) }}
    {{ "Hello World!"|truncate(7, true) }}
    {{ "Hello World!"|truncate(7,  false, "??") }}


### Functions

##### drupal_view()
Embed a view direct in your template. 

`{{ drupal_view(view_name,display_id) }}`
 
##### drupal_block()
Embed a block direct in your template. 

Build a viewable block
`{{ drupal_block(block_delta) }}` 

*Experimental feature, return the content of a lazybuild block*
`{{ drupal_block(block_delta,true) }}` 

### Debug features

#### Extended var dumping
Support for the [Vardumper module](https://www.drupal.org/project/vardumper), because that dumper is faster then kint (Kint contains to much logic for simply digging trough variables and that makes it slow....) 
      
      {{ dump(variable) }} 
      {{ vdm (variable) }} 

Overloads the default `{{dump}}` with the Vardumper version, or use `{{vpm}}` is you want to dump to the Message area. 

For convenience you can use `{{vdp}}` but that is essentially the same as doing `{{ dump(variable) }}` 
*TODO add support for the vdpb (to a dedicated block) and vdpw (to a watchdog logger) dumpers.* 

#### xdebug_break()

If xdebug is enabled on your environment you can use `{{ breakpoint() }}` to trigger an xdebug break, and inspect the variables in your xdebug client.

For convience the filename of the compiled template and the line of the xdebug_break() function is set to `$_xdebug_caller` variable

[Inspired by](https://github.com/ajgarlag/AjglBreakpointTwigExtension)AjglBreakpointTwigExtension


### Missing features? 

Feel free to do a request in the [issue queue](https://www.drupal.org/project/issues/tlh)

