<?php
/**
 * Item template.
 *
 * @package taro-series
 * @version 1.0.0
 */

?>

<li class="taro-series-list-item">
	<?php if ( 'publish' === get_post_status() ) : ?>
	<a class="taro-series-list-link<?php echo is_single( get_the_ID() ) ? ' current' : ''; ?>" href="<?php the_permalink(); ?>">
		<?php the_title(); ?>
	</a>
	<?php else : ?>
	<span class="taro-series-list-link scheduled">
		<?php
		// translators: %s is publish date.
		printf( __( 'Coming Soon(%s)', 'taro-series' ), mysql2date( get_option( 'date_format' ), get_post()->post_date ) );
		?>
	</span>
	<?php endif; ?>
</li>
