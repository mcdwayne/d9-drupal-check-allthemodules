// The editor creator to use.
import Plugin from '@ckeditor/ckeditor5-core/src/plugin';
import BalloonEditorBase from '@ckeditor/ckeditor5-editor-balloon/src/ballooneditor';

import Essentials from '@ckeditor/ckeditor5-essentials/src/essentials';
import Autoformat from '@ckeditor/ckeditor5-autoformat/src/autoformat';
import Bold from '@ckeditor/ckeditor5-basic-styles/src/bold';
import Italic from '@ckeditor/ckeditor5-basic-styles/src/italic';
import BlockQuote from '@ckeditor/ckeditor5-block-quote/src/blockquote';
import Heading from '@ckeditor/ckeditor5-heading/src/heading';
import List from '@ckeditor/ckeditor5-list/src/list';
import Paragraph from '@ckeditor/ckeditor5-paragraph/src/paragraph';
import Table from '@ckeditor/ckeditor5-table/src/table';
import TableToolbar from '@ckeditor/ckeditor5-table/src/tabletoolbar';
import Template from '@amazee/ckeditor5-template/src/template';
import Linkit from '@amazee/ckeditor5-drupal-linkit/src/linkit';
import DrupalMedia from '@amazee/ckeditor5-drupal-media/src/drupalmedia';
import BlockToolbar from '@ckeditor/ckeditor5-ui/src/toolbar/block/blocktoolbar';
import Validation from '@amazee/ckeditor5-template/src/validation';
import ButtonElement from '@amazee/ckeditor5-drupal-linkit/src/elements/buttonelement';
import TemplateEditing from '@amazee/ckeditor5-template/src/templateediting';
import RemoteControl from '@amazee/ckeditor5-template/src/remotecontrol';
import TabsElement from '@amazee/ckeditor5-template/src/elements/tabselement'
import GalleryElement from "@amazee/ckeditor5-template/src/elements/galleryelement";
import MergeEditing from "@amazee/ckeditor5-template/src/mergeediting";
import TextConstraintElement from "@amazee/ckeditor5-template/src/elements/textconstraintelement";

import Placeholder from '@amazee/editor-components/components/placeholder/placeholder';
import Media from '@amazee/editor-components/components/media/media';
import '@amazee/editor-components/components/container/container';
import '@amazee/editor-components/components/gallery/gallery';
import '@amazee/editor-components/components/tabs/tabs';
import '@amazee/editor-components/components/text_conflict/text_conflict';
import '@amazee/editor-components/components/text_conflict/text_conflict_option/text_conflict_option';
import '@amazee/editor-components/components/textfield/textfield';
import '@amazee/editor-components/components/button/button';

export default class SectionsEditor extends BalloonEditorBase { }

class PlaceholderConfig extends Plugin {
	init() {
		const templates = this.editor.config.get('templates');
		Placeholder.availableSections = Object.keys(templates)
			.map(id => ({ id, label: templates[id].label, icon: templates[id].icon }));
		Media.previewCallback = this.editor.config.get('drupalMediaRenderer').callback;
	}
}

// Plugins to include in the build.
SectionsEditor.builtinPlugins = [
	PlaceholderConfig,
	RemoteControl,
	Essentials,
	Autoformat,
	Bold,
	Italic,
	BlockQuote,
	Heading,
	List,
	Paragraph,
	Table,
	TableToolbar,
	BlockToolbar,
	Template,
	Linkit,
	TemplateEditing,
	DrupalMedia,
	Validation,
	ButtonElement,
	TabsElement,
	GalleryElement,
	MergeEditing,
  TextConstraintElement
];

// Editor configuration.
SectionsEditor.defaultConfig = {
	toolbar: {
		items: [
			'bold',
			'italic',
			'link',
			'bulletedList',
			'numberedList',
			'blockQuote',
			'insertTable',
			'undo',
			'redo'
		]
	},
	table: {
		contentToolbar: [
			'tableColumn',
			'tableRow',
			'mergeTableCells'
		]
	},
	blockToolbar: ['heading', 'insertTable'],
	// This value must be kept in sync with the language defined in webpack.config.js.
	language: 'en'
};

window.ckeditor5_sections_builds = window.ckeditor5_sections_builds || {};
window.ckeditor5_sections_builds['ckeditor5_sections/editor_build'] = SectionsEditor;

