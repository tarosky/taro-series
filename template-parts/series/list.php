<?php
/**
 * List template.
 *
 * @package taro-series
 * @version 1.0.0
 * @var array $args
 */

$args = wp_parse_args( $args, [
	'series'     => null,
	'title'      => '',
	'link'       => '',
	'link_label' => '',
] );

// Check series exists.
$query = taro_series_index_query( $args['series'] );
if ( ! $query || ! $query->have_posts() ) {
	return;
}
// Enqueue style if OK.
\Tarosky\Series\Customizer\StyleLoading::load_style();
?>
<nav class="taro-series-toc">
	<?php if ( $args['title'] ) : ?>
		<h2 class="taro-series-toc-title"><?php echo esc_html( apply_filters( 'taro_series_index_title', $args['title'], $query ) ); ?></h2>
	<?php endif; ?>
	<ol class="taro-series-toc-list">
		<?php
		while ( $query->have_posts() ) {
			$query->the_post();
			taro_series_template_part( 'template-parts/series/item', get_post_type() );
		}
		wp_reset_postdata();
		?>
	</ol>
	<?php if ( $query->found_posts > $query->post_count && $args['link'] ) : ?>
	<p class="taro-series-toc-link">
		<a class="taro-series-toc-link-button" href="<?php echo esc_url( $args['link'] ); ?>">
			<?php echo esc_html( $args['link_label'] ); ?>
		</a>
	</p>
	<?php endif; ?>
</nav>
