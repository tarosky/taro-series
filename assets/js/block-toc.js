/*!
 * Toc Block
 *
 * @package taro-series
 * @handle taro-series-toc
 * @deps wp-i18n, wp-components, wp-blocks, wp-block-editor, wp-server-side-render, wp-compose, wp-data

 */

/*global TaroSeriesTocVars: false */

const { registerBlockType } = wp.blocks;
const { __ } = wp.i18n;
const { InspectorControls } = wp.blockEditor;
const { PanelBody, TextControl, SelectControl, Placeholder } = wp.components;
const { serverSideRender: ServerSideRender } = wp;

const { name, series } = TaroSeriesTocVars;

const SeriesSelector = ( props ) => {
	return (
		<SelectControl label={ __( 'Series', 'taro-series' ) } options={ series } value={ props.value } onChange={ ( newValue ) => props.onChange( newValue ) } />
	);
};

registerBlockType( name, {

	title: __( 'Series TOC', 'taro-taxonomy-blocks' ),

	icon: 'book-alt',

	category: 'widgets',

	keywords: [ 'series' ],

	attributes: TaroSeriesTocVars.attributes,

	description: __( 'Display the TOC of series.', 'taro-series' ),

	edit( { attributes, setAttributes } ) {
		// translators: %s is placeholder to be kept.
		const help = __( '%s wil be replaced with the series title. %0 means no title.', 'taro-series' );
		return (
			<>
				<InspectorControls>
					<PanelBody defaultOpen={ true } title={ __( 'Taxonomy Setting', 'taro-taxonomy-blocks' ) } >
						<TextControl label={ __( 'TOC Title', 'taro-series' ) } value={ attributes.title }
							onChange={ ( title ) => setAttributes( { title } ) }
							placeholder={ /* translators: %s is series title */ __( 'TOC of "%s"', 'taro-series' ) }
							help={ help } />
						<SeriesSelector value={ attributes.series_id } onChange={ ( series_id ) => setAttributes( { series_id: parseInt( series_id, 10 ) } ) } />
					</PanelBody>
				</InspectorControls>
				<div className="taro-series-toc-editor" style={ { 'pointer-events': 'none' } }>
					<ServerSideRender block={ name } attributes={ attributes } />
				</div>
			</>
		);
	},

	save() {
		return null;
	},
} );
