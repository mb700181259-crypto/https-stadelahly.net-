/**
 * Manchit ad block — a dynamic (server-rendered) Gutenberg block that outputs
 * the ad units assigned to a chosen location. No preview HTML is hardcoded; the
 * editor shows a placeholder and a location picker.
 *
 * @package Manchit
 */
( function ( blocks, element, blockEditor, components, i18n ) {
	'use strict';
	var el = element.createElement;
	var __ = i18n.__;
	var SelectControl = components.SelectControl;
	var useBlockProps = blockEditor.useBlockProps;

	var LOCATIONS = [
		{ value: 'in_content', label: __( 'داخل المقال', 'manchit' ) },
		{ value: 'before_content', label: __( 'قبل المحتوى', 'manchit' ) },
		{ value: 'after_content', label: __( 'بعد المحتوى', 'manchit' ) },
		{ value: 'sidebar_top', label: __( 'أعلى الشريط الجانبي', 'manchit' ) },
		{ value: 'archive_inline', label: __( 'داخل الأرشيف', 'manchit' ) },
		{ value: 'header', label: __( 'أسفل الهيدر', 'manchit' ) }
	];

	blocks.registerBlockType( 'manchit/ad', {
		title: __( 'إعلان Manchit', 'manchit' ),
		icon: 'megaphone',
		category: 'widgets',
		attributes: { location: { type: 'string', default: 'in_content' } },
		edit: function ( props ) {
			var loc = props.attributes.location;
			return el(
				'div',
				useBlockProps ? useBlockProps() : {},
				el( SelectControl, {
					label: __( 'موضع الإعلان', 'manchit' ),
					value: loc,
					options: LOCATIONS,
					onChange: function ( v ) { props.setAttributes( { location: v } ); }
				} ),
				el(
					'div',
					{ style: { padding: '1.2rem', textAlign: 'center', background: '#f6f7f7', border: '1px dashed #c3c4c7', borderRadius: '8px', color: '#787c82' } },
					__( 'مساحة إعلانية —', 'manchit' ) + ' ' + loc
				)
			);
		},
		save: function () { return null; } // dynamic (render_callback)
	} );
} )( window.wp.blocks, window.wp.element, window.wp.blockEditor, window.wp.components, window.wp.i18n );
