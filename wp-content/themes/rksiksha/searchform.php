<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<form role="search" method="get" class="rks-blog__searchform" action="<?php echo esc_url( home_url( '/' ) ); ?>">
	<label class="screen-reader-text" for="rks-search-<?php echo esc_attr( uniqid() ); ?>"><?php esc_html_e( 'Search for:', 'rksiksha' ); ?></label>
	<input type="search" id="rks-search-<?php echo esc_attr( uniqid() ); ?>" name="s" placeholder="<?php esc_attr_e( 'Search...', 'rksiksha' ); ?>" value="<?php echo esc_attr( get_search_query() ); ?>" />
	<button type="submit" aria-label="Search">→</button>
</form>
