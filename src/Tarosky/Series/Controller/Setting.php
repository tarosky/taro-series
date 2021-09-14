<?php

namespace Tarosky\Series\Controller;


use Tarosky\Series\Pattern\Singleton;

/**
 * Setting controller.
 */
class Setting extends Singleton {

	/**
	 * Init functions.
	 */
	protected function init() {
		add_action( 'admin_init', [ $this, 'admin_setting' ] );
	}

	/**
	 * Register setting.
	 */
	public function admin_setting() {
		// If Ajax action, stop.
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			return;
		}
		// Register setting.
		add_settings_section( 'taro-series', __( 'Series Setting', 'taro-series' ), function() {
			printf(
				'<p class="description">%s</p>',
				esc_html__( 'In this section, you can define how series works in your site.', 'taro-series' )
			);
		}, 'writing' );
		// Register fields.
		add_settings_field( 'taro_series_post_types', __( 'Post Type', 'taro-series' ), function() {
			$post_types = get_post_types( [ 'public' => true ], OBJECT );
			$post_types = apply_filters( 'taro_series_post_types_choices', array_values( array_filter( $post_types, function( \WP_Post_Type $post_type ) {
				return ! in_array( $post_type->name, [ 'attachment', taro_series_parent_post_type() ], true );
			} ) ) );
			$value      = (array) get_option( 'taro_series_post_types', [] );
			foreach ( $post_types as $post_type ) {
				printf(
					'<label style="display: inline-block; margin: 0 1em 1em 0;"><input type="checkbox" name="taro_series_post_types[]" value="%s" %s /> %s</label>',
					esc_attr( $post_type->name ),
					checked( in_array( $post_type->name, $value, true ), true, false ),
					esc_html( $post_type->label )
				);
			}
			// Description.
			printf(
				'<p class="description">%s</p>',
				esc_html__( 'The post types checked above can be a part of series.', 'taro-series' )
			);
			// If predefined, display.
			$predefined = taro_series_post_types_predefined();
			if ( ! empty( $predefined ) ) {
				printf(
					'<p><strong>%s</strong> %s %s</p>',
					esc_html__( 'Notice:', 'taro-series' ),
					esc_html__( 'Post types are programatticaly pre-defined. The setting above will be omitted.', 'taro-series' ),
					implode( ', ', array_map( function( $pre ) {
						return sprintf( '<code>%s</code>', esc_html( $pre ) );
					}, $predefined ) )
				);
			}
		}, 'writing', 'taro-series' );
		register_setting( 'writing', 'taro_series_post_types' );
	}
}
