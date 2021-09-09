/*!
 * Series editor helper.
 *
 * @package taro-series
 * @handle taro-series-series-editor
 * @deps wp-element, wp-components, wp-api-fetch, wp-i18n, wp-data
 */

const { Component, render } = wp.element;
const { __ } = wp.i18n;
const { Spinner, Button, Modal, TextControl } = wp.components;
const { apiFetch } = wp;
const { dispatch } = wp.data;

const message = ( content, status ) => {
	dispatch( 'core/notices' ).createNotice( status, content, {
		type: 'snackbar',
		isDismissible: true,
		explicitDismiss: false,
	} );
}

const listStyle = {
	borderBottom: '1px solid #ddd',
	padding: '10px',
};

const labelStyle = {
	display: 'inline-block',
	padding: '2px 3px',
	backgroundColor: '#eee',
	marginLeft: '10px',
	borderRadius: '3px',
};

const linkStyle = { marginRight: '10px' };

class SearchItem extends Component {

	constructor( props ) {
		super( props );
		this.state = {
			loading: false,
		};
	}

	add( post ) {
		this.setState( { loading: true }, () => {
			apiFetch( {
				path: `taro-series/v1/series/${this.props.seriesId}`,
				method: 'post',
				data: {
					post_id: post.id
				}
			} ).then( () => {
				this.props.onAdd( post );
			} ).catch( ( res ) => {
				message( res.message, 'error' );
			} ).finally( () => {
				this.setState( {
					loading: false,
				} );
			} );
		} );
	}

	render() {
		const { loading } = this.state;
		const { post } = this.props;
		return (
			<li className="clearfix" style={ { borderBottom: '1px solid #eee', padding: '10px' } }>
				<Button style={ { float: 'right' } } isDefault isSmall isBusy={ loading } onClick={ () => {
					this.add( post );
				} }>{ __( 'Add', 'taro-series' ) }</Button>
				<p>
					<strong>{ post.title }</strong>
					<small style={ labelStyle }>{ post.postTypeLabel }</small>
					<small style={ labelStyle }>{ post.dateFormatted }</small>
				</p>
			</li>
		);
	}
}

class Articles extends Component {

	constructor( props ) {
		super( props );
		this.state = {
			posts: [],
			loading: true,
			results: [],
			resultCount: 0,
			searching: false,
			term: '',
		};
	}

	componentDidMount() {
		this.setState( {
			loading: true,
		}, () => {
			apiFetch( {
				path: `taro-series/v1/series/${this.props.seriesId}`,
				method: 'get',
			} ).then( ( res ) => {
				this.setState( {
					loading: false,
					posts: res.posts,
				} );
			} ).catch( ( res ) => {
				this.setState( {
					loading: false,
					posts: [],
				} );
			} );
		} );
	}

	search() {
		this.setState( {
			loading: true,
		}, () => {
			apiFetch( {
				path: `taro-series/v1/series/${this.props.seriesId}?s=${this.state.term}`,
				method: 'get',
			} ).then( ( res ) => {
				console.log( res );
				this.setState( {
					loading: false,
					resultCount: res.total,
					results: res.posts,
				} );
			} ).catch( ( res ) => {
				this.setState( {
					loading: false,
					results: [],
					resultCount: 0,
				}, () => {
					message( res.message, 'error' );
				} );
			} );
		} );
	}

	add( post ) {
		const { posts } = this.state;
		posts.push( post );
		posts.sort( ( a, b ) => {
			if ( a.date === b.date ) {
				return 0;
			} else {
				return a.date < b.date ? -1 : 1;
			}
		} )
		this.setState( {
			posts
		} );
	}

	remove( postId ) {
		this.setState( { loading: false }, () => {
			apiFetch( {
				path: `taro-series/v1/series/${this.props.seriesId}?post_id=${postId}`,
				method: 'delete',
			} ).then( ( response ) => {
				// Successfully removed.
				this.setState( {
					loading: false,
					posts: this.state.posts.filter( ( post ) => {
						return post.id !== postId;
					} )
				}, () => {
					// translators: %d is post id.
					message( sprintf( __( '#%d is removed from the articles of this series.', 'taro-series' ), postId ), 'success' );
				} );
			} ).catch( ( response ) => {
				this.setState( {
					loading: false,
				}, () => {
					message( response.message, 'error' );
				} );
			} );
		} );
	}

	finishSearch() {
		this.setState( {
			searching: false,
			results: [],
			resultCount: 0,
			term: '',
		} )
	}

	render() {
		const { searching, loading, posts, results, term, resultCount } = this.state;
		return (
			<>
				{ loading && (
					<>
						<Spinner />
						<p className="description">{ __( 'Loading...', 'taro-series' ) }</p>
					</>
				) }

				{ ( ! loading && 0 < posts.length ) && (
					<ol className="taro-series-list" style={ { margin: '0 0 20px' } }>
						{ posts.map( ( post ) => {
							return (
								<li style={ listStyle } className="taro-series-item"  key={ post.id }>
									<p className="taro-series-item-title">
										<strong>{ post.title }</strong>
										<small style={ labelStyle }>{post.statusLabel}</small>
										<small style={ labelStyle }>{ post.postTypeLabel }</small>
										<small style={ labelStyle }>{post.dateFormatted}</small>
									</p>
									<p>
										<a style={ linkStyle } className="components-button is-small is-tertiary" href={ post.editLink } target="_blank" rel="noopener noreferrer">{ __( 'Edit', 'taro-series' ) }</a>
										<a style={ linkStyle } className="components-button is-small is-tertiary" href={ post.link } target="_blank" rel="noopener noreferrer">{ __( 'View', 'taro-series' ) }</a>
										<Button isDestructive isSmall isTertiary onClick={ () => {
											this.remove( post.id );
										} }>{ __( 'Remove from this series', 'taro-series' ) }</Button>
									</p>
								</li>
							);
						} ) }
					</ol>
				) }

				{ ( ! loading && 1 > posts.length ) && (
					<>
						<p className="description">{ __( 'No post is assigned in this series.', 'taro-series' ) }</p>
						<hr style={ { margin: '10px 0' } } />
					</>
				) }
				<Button isDefault isBusy={ searching } onClick={ () => {
					this.setState( { searching: true } );
				} }>{ __( 'Add New Article', 'taro-series' ) }</Button>

				{ searching && (
					<Modal title={ __( 'Add Article', 'taro-series' ) } onRequestClose={ () => this.finishSearch() }>
						<TextControl value={ term } onChange={ ( newTerm ) => this.setState( { term: newTerm } ) } placeholder={ __( 'Type and search.', 'taro-series' ) } />
						<Button isDefault onClick={ () => this.search() }>{ __( 'Search', 'taro-series' ) }</Button>
						<hr style={ { margin: '10px 0' } } />
						{ loading && <Spinner /> }
						{ ( 0 < results.length ) ? (
							<>
								<p>{ sprintf( __( 'Found posts: %d', 'taro-series' ), resultCount ) }</p>
								<ol style={ { borderTop: '1px solid #eee' } }>
									{ results.map( ( post ) => {
										return (
											<SearchItem post={ post } key={ `search-${post.id}` } seriesId={ this.props.seriesId } onAdd={ ( p ) => this.add( p ) } />
										);
									} ) }
								</ol>
							</>
						) : (
							<p className="description">
								{ __( 'No post matches criteria. Type keyword and try search.', 'taro-series' ) }
							</p>
						) }
					</Modal>
				) }
			</>
		);
	}
}

const wrapper = document.getElementById( 'series-articles' );
if ( wrapper ) {
	render( <Articles seriesId={ wrapper.dataset.postId } />, wrapper );
}
