<?php
/**
 * Live Preview のデモデータ。
 *
 * シナリオ: 架空の料理ブログ。8 回連載「Sourdough from Scratch」（5 回公開済み・3 回予約投稿）と
 * 完結済みの 3 回連載「Weeknight Pasta」。着地は連載第 1 回（/sourdough-part-1/）で、
 * 本文の下に自動で付く目次（現在地・Coming Soon）を見せる。
 *
 * このファイルがマスター。編集したら次で blueprint.json に埋め込むこと:
 *   php <skill>/scripts/blueprint.php embed .
 */
require '/wordpress/wp-load.php';

// runPHP はログインユーザー無しで動く。権限チェックのある API（tax_input など）が黙って無視されるので、管理者として実行する。
wp_set_current_user( 1 );

// 着地ページのスラッグと、連載アーカイブ（/series/archive/{slug}）を使うため、パーマリンクを有効にする。
// update_option() だけでは $wp_rewrite が古い構造のままで、最後の flush が投稿のルールを書き出さない。
global $wp_rewrite;
$wp_rewrite->set_permalink_structure( '/%postname%/' );
update_option( 'blogname', 'Crumb & Crust' );
update_option( 'blogdescription', 'A home baking journal' );

// プラグイン設定: 投稿を連載に入れられるようにし、目次の表示を有効にする（カスタマイザーの値はオプションに保存される）。
update_option( 'taro_series_post_types', array( 'post' ) );
update_option( 'taro_series_index_in_post', '1' );
update_option( 'taro_series_index_in_series', '1' );
update_option( 'taro_series_include_scheduled_posts', '1' );
update_option( 'taro_series_order', 'ASC' );
update_option( 'taro_series_toc_title', 'Series: %s' );

$category = wp_insert_term( 'Baking', 'category' );
$cat_id   = is_wp_error( $category ) ? 1 : (int) $category['term_id'];

/**
 * 連載の親（series 投稿）を作る。
 *
 * @param string $title   Title.
 * @param string $slug    Slug.
 * @param string $content Content.
 * @param array  $meta    Series meta (total, is_finished, finish_at).
 * @return int
 */
$create_series = function ( $title, $slug, $content, $meta ) {
	$series_id = wp_insert_post( array(
		'post_type'    => taro_series_parent_post_type(),
		'post_status'  => 'publish',
		'post_title'   => $title,
		'post_name'    => $slug,
		'post_content' => $content,
	) );
	foreach ( $meta as $key => $value ) {
		update_post_meta( $series_id, '_series_' . $key, $value );
	}
	return $series_id;
};

/**
 * 連載の記事を作る。日付は現在時刻からの相対（日数）で、未来なら予約投稿にする。
 *
 * @param int    $series_id Series ID.
 * @param string $title     Title.
 * @param string $slug      Slug.
 * @param string $body      Body text.
 * @param int    $days      Days from now (negative = past).
 * @return int
 */
$create_article = function ( $series_id, $title, $slug, $body, $days ) use ( $cat_id ) {
	$timestamp = time() + $days * DAY_IN_SECONDS;
	$post_id   = wp_insert_post( array(
		'post_type'     => 'post',
		'post_status'   => 0 < $days ? 'future' : 'publish',
		'post_title'    => $title,
		'post_name'     => $slug,
		'post_content'  => "<!-- wp:paragraph -->\n<p>" . $body . "</p>\n<!-- /wp:paragraph -->",
		'post_date'     => wp_date( 'Y-m-d H:i:s', $timestamp ),
		'post_date_gmt' => gmdate( 'Y-m-d H:i:s', $timestamp ),
		'post_category' => array( $cat_id ),
	) );
	update_post_meta( $post_id, taro_series_meta_key(), $series_id );
	return $post_id;
};

// 連載 1: 進行中。8 回中 5 回が公開済み、残りは毎週公開予定。
$sourdough = $create_series(
	'Sourdough from Scratch',
	'sourdough-from-scratch',
	"<!-- wp:paragraph -->\n<p>An eight-part journey from a jar of flour and water to a crackling loaf. A new part every week.</p>\n<!-- /wp:paragraph -->",
	array(
		'total'       => 8,
		'is_finished' => '',
		'finish_at'   => wp_date( 'Y-m-d', time() + 20 * DAY_IN_SECONDS ),
	)
);
$parts     = array(
	array( 'Part 1: Why Bake Sourdough?', 'What wild yeast gives you that a packet of instant yeast cannot, and what you need to get started.' ),
	array( 'Part 2: Building Your Starter', 'Day-by-day notes on feeding a starter, from the first bubbles to a reliable rise.' ),
	array( 'Part 3: Flour, Water, Salt', 'Choosing flours and understanding hydration, with a simple 75% dough to begin with.' ),
	array( 'Part 4: Stretch and Fold', 'Building strength in the dough without kneading. Timing, temperature and what to look for.' ),
	array( 'Part 5: Shaping the Loaf', 'Pre-shaping, bench rest and final shaping for a tight, even boule.' ),
	array( 'Part 6: The Overnight Proof', 'Using the fridge to slow fermentation and fit baking into a busy week.' ),
	array( 'Part 7: Scoring and Baking', 'Blades, patterns and the Dutch oven method for a dramatic ear.' ),
	array( 'Part 8: Troubleshooting Your Crumb', 'Reading the crumb to fix dense, gummy or flat loaves.' ),
);
foreach ( $parts as $index => $part ) {
	// 第 1 回は 29 日前、以降は 7 日おき（-29, -22, -15, -8, -1, 6, 13, 20）。第 6 回以降は予約投稿になり「Coming Soon」として目次に出る。
	$create_article( $sourdough, $part[0], 'sourdough-part-' . ( $index + 1 ), $part[1], -29 + $index * 7 );
}

// 連載 2: 完結済み。連載ごとに目次が分かれることを見せる。
$pasta  = $create_series(
	'Weeknight Pasta',
	'weeknight-pasta',
	"<!-- wp:paragraph -->\n<p>Three pasta dinners you can make in under thirty minutes.</p>\n<!-- /wp:paragraph -->",
	array(
		'total'       => 3,
		'is_finished' => '1',
		'finish_at'   => '',
	)
);
$dishes = array(
	array( 'Weeknight Pasta #1: Cacio e Pepe', 'Three ingredients, one pan, and the trick to a glossy sauce that never clumps.' ),
	array( 'Weeknight Pasta #2: Lemon Ricotta Spaghetti', 'A bright, no-cook sauce that comes together while the water boils.' ),
	array( 'Weeknight Pasta #3: Sausage and Broccoli Rabe', 'Bitter greens, rich sausage and orecchiette for a complete meal in a bowl.' ),
);
foreach ( $dishes as $index => $dish ) {
	$create_article( $pasta, $dish[0], 'weeknight-pasta-' . ( $index + 1 ), $dish[1], -60 + $index * 3 );
}

// 連載に入っていない普通の記事も 1 件置き、目次が付かないことと対比する。
wp_insert_post( array(
	'post_type'     => 'post',
	'post_status'   => 'publish',
	'post_title'    => 'Our Favorite Kitchen Scale',
	'post_name'     => 'favorite-kitchen-scale',
	'post_content'  => "<!-- wp:paragraph -->\n<p>A standalone review. This post is not part of any series, so no table of contents appears below.</p>\n<!-- /wp:paragraph -->",
	'post_date'     => wp_date( 'Y-m-d H:i:s', time() - 3 * DAY_IN_SECONDS ),
	'post_category' => array( $cat_id ),
) );

// 不要な既定コンテンツを片付ける。
wp_delete_post( 1, true ); // Hello world!
wp_delete_post( 2, true ); // Sample Page

// 新しいパーマリンク構造と連載アーカイブのルールを反映する。
flush_rewrite_rules();
