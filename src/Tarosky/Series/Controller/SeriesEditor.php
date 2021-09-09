<?php

namespace Tarosky\Series\Controller;


use Tarosky\Series\Pattern\Singleton;

/**
 * Series editor.
 *
 * @package taro-series
 */
class SeriesEditor extends Singleton {

	/**
	 * @inheritDoc
	 */
	protected function init() {
		$post_type = taro_series_parent_post_type();
		add_action( 'add_meta_boxes', [ $this, 'register_meta_boxes' ] );
		add_action( 'save_post_' . $post_type, [ $this, 'save_post' ] );
		add_filter( 'manage_' . $post_type . '_posts_columns', [ $this, 'manage_columns' ] );
		add_action( 'manage_' . $post_type . '_posts_custom_column', [ $this, 'custom_column' ], 10, 2 );
	}


	/**
	 * Register meta box.
	 *
	 * @param string $post_type Post type to be a part of series.
	 */
	public function register_meta_boxes( $post_type ) {
		if ( taro_series_parent_post_type() !== $post_type ) {
			return;
		}
		add_meta_box( 'taro-series-meta', __( 'Series Meta', 'taro-series' ), [ $this, 'meta_box_meta' ], $post_type, 'side' );
		add_meta_box( 'taro-series-list', __( 'Articles in Series', 'taro-series' ), [ $this, 'meta_box_list' ], $post_type, 'advanced' );
	}

	/**
	 * Series meta box for list.
	 *
	 * @param \WP_Post $post Post object.
	 */
	public function meta_box_list( $post ) {
		wp_enqueue_script( 'taro-series-series-editor' );
		printf( '<div id="series-articles" data-post-id="%d"></div>', esc_attr( $post->ID ) );
	}

	/**
	 * Series meta box for meta information.
	 *
	 * @param \WP_Post $post Post object.
	 */
	public function meta_box_meta( $post ) {
		wp_nonce_field( 'update_series', '_taroseriesnonce' );
		?>
		<p style="padding-top: 10px; margin-bottom: 10px;">
			<label>
				<input name="series_is_finished" type="checkbox" value="1" <?php checked( taro_series_is_finished( $post ) ); ?> />
				<?php esc_html_e( 'This series is finished', 'taro-series' ); ?>
			</label>
		</p>
		<hr />
		<p style="margin: 10px 0;">
			<label>
				<?php esc_html_e( 'Finish At', 'taro-series' ); ?><br />
				<input name="series_finish_at" type="date" class="widefat" value="<?php echo esc_attr( taro_series_finish_at( $post ) ); ?>" /><br />
				<span style="display: block; margin-top: 5px;" class="description"><?php esc_html_e( 'Optional. If you need to announce the finish date of this series, please set this.', 'taro-series' ); ?></span>
			</label>
		</p>
		<hr />
		<p style="margin: 10px 0;">
			<label>
				<?php esc_html_e( 'Total Articles', 'taro-series' ); ?><br />
				<input name="series_total" type="number" class="widefat" value="<?php echo esc_attr( taro_series_total( $post ) ); ?>" />
				<span style="display: block; margin-top: 5px;" class="description"><?php esc_html_e( 'Optional. If you need to announce the total amount of articles preliminary, please set this.', 'taro-series' ); ?></span>
			</label>
		</p>
		<?php
	}

	/**
	 * Save psot meta.
	 *
	 * @param int $post_id Post ID.
	 */
	public function save_post( $post_id ) {
		if ( ! wp_verify_nonce( filter_input( INPUT_POST, '_taroseriesnonce' ), 'update_series' ) ) {
			return;
		}
		foreach ( [ 'total', 'is_finished', 'finish_at' ] as $key ) {
			update_post_meta( $post_id, '_series_' . $key, filter_input( INPUT_POST, 'series_' . $key ) );
		}
	}

	/**
	 * Add custom columns.
	 *
	 * @param string[] $columns Columns names.
	 * @return string[]
	 */
	public function manage_columns( $columns ) {
		$new_columns = [];
		foreach ( $columns as $key => $label ) {
			$new_columns[ $key ] = $label;
			if ( 'title' === $key ) {
				$new_columns['series'] = __( 'Series', 'taro-series' );
			}
		}
		return $new_columns;
	}

	/**
	 * Render custom columns.
	 *
	 * @param string $column  Column name.
	 * @param int    $post_id Post id.
	 */
	public function custom_column( $column, $post_id ) {
		switch ( $column ) {
			case 'series':
				printf(
					'%s/%s',
					number_format( taro_series_count( $post_id, 'any' ) ),
					taro_series_total( $post_id ) ?: 'NaN'
				);
				break;
		}
	}
}
