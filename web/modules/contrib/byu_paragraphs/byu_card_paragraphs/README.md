# BYU Card Paragraphs

This is a module that allows you to add the various BYU card components to your content types.

## Usage

Installing this module installs the regular BYU card component type with a paragraph that displays the card. It also installs another paragraph called "BYU Card Container," which is the paragraph you'll use to add cards to your content types. There are multiple extensions to this module which add support for other content types, and they will be available via the BYU card container paragraph, so you can add whatever card you would like. All you need to do is add a paragraph entity field to the desired content type, and have it reference the BYU card container paragraph. You won't need to have it reference the various card paragraphs, since the card container will already do that for you.

### BYU Card

The BYU card component only has two fields. It has a field for it's content, which is just a CKEditor field, and then a field for the classes. In the card content field, you will put everything you want to put in the card, including images, links, etc. The classes will define how the border is handled. See below for the available classes. When using multiple classes, separate them with a space.

## Styling

This goes over styling for the normal BYU card component. Styling for other card components are found in the respective READMEs of the sub-modules themselves.

### Classes

You will add these to the classes field. Separate multiple classes with a space.

* Borders
  * border-gray - Makes the card border gray.
  * border-navy - Makes the card border navy.
* Border Radius (Default is 0)
  * border-small - Changes the border radius to 10px.
  * border-large - Changes the border radius to 25px.

### Custom Styling

If you want to change the style of the way the cards display, you will need to add your own css. For example, if you want to change the widths of the cards so that they aren't all the same width, you will need to add some css like this:
```$xslt
byu-card:nth-of-type(2) {
    width: 38%;
}
byu-card:nth-of-type(1) {
    width: 60%;
}
```

### Flexbox Styling

If you wish to put the cards into a flexbox, you also need to add some css. All the cards are in a div with the class `field--name--field-byu-card-container`. So you'll start with this in your css.

```$xslt
.field--name--field-byu-card-container {
  display: flex;
  ...
}
```

All of the different byu cards will be in this field. For now we don't have a way of styling multiple card containers. If you have more than one container, this is the style you will use to style the containers separately:
```$xslt
.field--name--field-byu-card-container:nth-child(1) {
  display: flex;
  ...
}

.field--name--field-byu-card-container:nth-child(2) {
  display: flex;
  ...
}

...
```