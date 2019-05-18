The no_field_markup module is a small module which removes the <div> elements
around a Field on output. In addition, it kicks away the label too.
You can control this possibility by each field.

Have a look of this example:

<div class="field field--name-field-firstname field--type-string field--label-above">
  <div class="field__label">Firstname</div>
  <div class="field__item">Dan</div>
</div>

is now:

Dan

----

You can control this possibility for each field in the field configuration "NO FIELD MARKUP" fieldset
at the end of the configuration page.
