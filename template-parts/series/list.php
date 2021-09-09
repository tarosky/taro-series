<?php
/**
 * List template.
 *
 * @package taro-series
 * @version 1.0.0
 * @var array $args
 */

$query = taro_series_index_query( null, [
	'post_status' => [ 'publish', 'future' ],
] );
if ( ! $query || ! $query->have_posts() ) {
	return;
}
$args = wp_parse_args( $args, [
	'title' => '',
] );
?>
<nav class="taro-series">
	<?php if ( $args['title'] ) : ?>
		<h2 class="taro-series-title"><?php echo esc_html( apply_filters( 'taro_series_index_title', $args['title'], $query ) ); ?></h2>
	<?php endif; ?>
	<ol class="taro-series-list">
		<?php
		while ( $query->have_posts() ) {
			$query->the_post();
			taro_series_template_part( 'template-parts/series/item', get_post_type() );
		}
		wp_reset_postdata();
		?>
	</ol>
</nav>
