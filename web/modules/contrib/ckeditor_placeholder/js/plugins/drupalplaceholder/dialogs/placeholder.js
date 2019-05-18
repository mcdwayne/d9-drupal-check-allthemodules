
/**
 * @license Copyright (c) 2003-2017, CKSource - Frederico Knabben. All rights reserved.
 * For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-oss-license
 */

/**
 * @fileOverview Definition for placeholder plugin dialog.
 *
 */

'use strict';

CKEDITOR.dialog.add( 'drupalplaceholder', function( editor ) {
	var generalLabel = Drupal.t('General'),
		validNameRegex = /^[^\[\]<>]+$/;

	return {
		title: Drupal.t('Placeholder Properties'),
		minWidth: 300,
		minHeight: 80,
		contents: [
			{
				id: 'info',
				label: generalLabel,
				title: generalLabel,
				elements: [
					// Dialog window UI elements.
					{
						id: 'name',
						type: 'text',
						style: 'width: 100%;',
						label: Drupal.t('Placeholder Name'),
						'default': '',
						required: true,
						validate: CKEDITOR.dialog.validate.regex( validNameRegex, Drupal.t('The placeholder can not be empty and can not contain any of following characters: [, ], <, >') ),
						setup: function( widget ) {
							this.setValue( widget.data.name );
						},
						commit: function( widget ) {
							widget.setData( 'name', this.getValue() );
						}
					}
				]
			}
		]
	};
} );
