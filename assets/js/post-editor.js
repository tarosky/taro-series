/*!
 * Post editor helper
 *
 * @package taro-series
 * @handle taro-series-post-editor
 * @deps wp-element, wp-components, wp-api-fetch, wp-i18n
 */

const { Component, render } = wp.element;
const { Spinner, RadioControl, TextControl, Button } = wp.components;
const { __ } = wp.i18n;
const { apiFetch } = wp;

const postCaches = {};



class SeriesRender extends Component {

	constructor(prop) {
		super(prop);
		this.state = {
			loading: false,
			post: null,
		};
		this.fetching = false;
	}

	componentDidMount() {
		if ( ! this.state.post && 0 < this.props.postId ) {
			this.fetch();
		}
	}

	componentDidUpdate() {
		this.fetch();
	}

	fetch() {
		if ( this.fetching ) {
			return; // Do nothing.
		}
		const { postId, postType } = this.props;
		this.fetching = true;
		if ( postId ) {
			this.setState( {
				loading: true,
			}, () => {
				apiFetch( {
					path: `taro-series/v1/available/${this.props.postType}?p=${postId}`,
				} ).then( ( res ) => {
					this.setState( {
						loading: false,
						post: res[0],
					}, () => {
						this.fetching = false;
					} );
				} ).catch( () => {
					this.setState( {
						loading: false,
						post: null,
					}, () => {
						this.fetching = false;
					} );
				} );
			});
		} else {
			this.setState( {
				post: null,
				loading: false,
			}, () => {
				this.fetching = false;
			} );
		}
	}

	render() {
		const { loading, post } = this.state;
		const { onChange } = this.props;
		const objects = [];
		let link = false;
		let title = '';
		if ( post ) {
			link  = post.edit_link;
			title = post.title;
		} else if ( 0 < this.props.postId ) {
			title = __( 'Loading...', 'taro-series' );
		} else {
			title = __( 'Not Set', 'taro-series' );
		}
		const style = {
			display: 'block',
			margin: '10px 0',
			padding: '5px',
			fontWeight: 'bold',
		};
		return (
			<>
				<div className="taro-series-selector-item">
					{ loading && (
						<Spinner />
					) }
					{ link ? (
						<>
							<a style={ style } href={ link } target="_blank" rel="noopener noreferrer">{ title }</a><br />
							<Button isSmall isDestructive onClick={ () => {
								onChange( 0 );
							}}>{ __( 'Leave Out', 'taro-series' ) }</Button>
						</>
					) : (
						<span style={ style } className="taro-series-link taro-series-link-invalid">{ title }</span>
					) }
				</div>
			</>
		);
	}
}

class SeriesChooser extends Component {

	constructor(props) {
		super(props);
		this.state = {
			loading: false,
			posts: [],
			orderby: 'DESC',
			s: '',
		};
	}

	calculate( posts = null ) {
		const { postId } = this.props;
		if ( null === posts || ! posts.length ) {
			return 0;
		}
		return ( posts.filter( ( p ) => ( p.id === postId ) ).length ) ? postId : 0;
	}

	componentDidMount() {
		this.fetch();
	}

	fetch() {
		this.setState( { loading: true }, () => {
			apiFetch( {
				path: `taro-series/v1/available/${this.props.postType}?s=${this.state.s}`,
			} ).then( ( res ) => {
				this.setState( {
					loading: false,
					posts: res,
				} );
			} ).catch( () => {
				this.setState( {
					loading: false,
					posts: [],
				} );
			} );
		} );
	}

	render() {
		const { postId, onChange } = this.props;
		const { loading, posts, s } = this.state;
		const result = [];
		if ( loading ) {
			result.push( <Spinner /> );
		}
		if ( posts.length ) {
			const currentValue = this.calculate( posts );
			const options = [];
			if ( ! currentValue ) {
				options.push( {
					value: 0,
					label: __( 'No Change', 'taro-series' ),
				} );
			}
			posts.forEach( ( p ) => {
				options.push( {
					value: parseInt( p.id, 10 ),
					label: p.title,
				} );
			} )
			result.push(
				<RadioControl label={ __( 'Select Series assigned to', 'taro-series' ) } selected={ currentValue } onChange={ onChange } options={ options } />
			);
		} else if ( loading ) {
			result.push(
				<p className="description">{ __( 'Loading...', 'taro-series' ) }</p>
			);
		} else {
			result.push(
				<p className="description">{ __( 'No series found matches criteria.', 'taro-series' ) }</p>
			);
		}
		result.push(
			<>
				<hr />
				<TextControl label={ __( 'Search Series', 'taro-series' ) } value={ s } onChange={ ( newS ) => this.setState( { s: newS } ) } />
				<Button isSmall isDefault onClick={ () => {
					this.fetch();
				} }>{ __( 'Filter', 'taro-series' ) }</Button>
			</>
		);
		return result;
	}
}

class SeriesSelector extends Component {

	constructor(props) {
		super(props);
		this.state = {
			postId: parseInt( props.postId, 10 ),
		};
	}

	render() {
		const { postId } = this.state;
		const onChange = ( newPostId ) => {
			this.setState( {
				postId: parseInt( newPostId, 10 ),
			} );
		};
		return (
			<>
				<input type="hidden" name="taro-series-parent" value={ postId } />
				<SeriesRender postId={ postId } postType={ this.props.postType } onChange={ ( newPostId ) => onChange( newPostId ) } />
				<hr />
				<SeriesChooser postId={ postId } postType={ this.props.postType } onChange={ ( newPostId ) => onChange( newPostId ) } />
			</>
		);
	}
}

// If meta box exists.
const metaBox = document.getElementById( 'taro-series-selector' );
if ( metaBox ) {
	render( <SeriesSelector postId={ metaBox.dataset.postId } postType={ metaBox.dataset.postType} />, metaBox );
}
