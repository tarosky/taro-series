<?php
/**
 * Item template.
 *
 * @package taro-series
 * @version 1.0.0
 */



?>

<li class="taro-series-toc-item<?php echo ( get_queried_object_id() === get_the_ID() ) ? ' current' : ''; ?>">
	<?php if ( 'publish' === get_post_status() ) : ?>
	<a class="taro-series-toc-item-link" href="<?php the_permalink(); ?>">
		<?php the_title(); ?>
	</a>
	<?php else : ?>
	<span class="taro-series-list-link scheduled">
		<?php
		// translators: %s is published date.
		printf( __( 'Coming Soon(%s)', 'taro-series' ), mysql2date( get_option( 'date_format' ), get_post()->post_date ) );
		?>
	</span>
	<?php endif; ?>
</li>
