<?php
/**
 * Template for displaying Comments.
 *
 * @package Hellish Simplicity
 * @since Hellish Simplicity 1.1
 */


/**
 * Show pre comments navigation.
 */
function hellish_comments_navigation( $id = '' ) {
	if ( get_comment_pages_count() > 1 && get_option( 'page_comments' ) ) {
		?>
	<nav role="navigation" id="<?php echo esc_attr( $id ); ?>" class="site-navigation comment-navigation">
		<h1 class="screen-reader-text"><?php esc_html_e( 'Comment navigation', 'hellish-simplicity' ); ?></h1>
		<div class="nav-previous"><?php previous_comments_link( esc_html__( '&larr; Older Comments', 'hellish-simplicity' ) ); ?></div>
		<div class="nav-next"><?php next_comments_link( esc_html__( 'Newer Comments &rarr;', 'hellish-simplicity' ) ); ?></div>
	</nav><!-- #comment-nav-<?php echo absint( $id ); ?> .site-navigation .comment-navigation --><?php
	}
}


/**
 * Bail out now if the user needs to enter a password.
 */
if ( post_password_required() ) {
	return;
}
?>

<div id="comments" class="comments-area">

<?php

/**
 * Display the comments if any exist.
 */
if ( have_comments() ) { ?>
	<h2 class="comments-title"><?php
		printf(
			_nx(
				'One thought on &ldquo;%2$s&rdquo;',
				'%1$s thoughts on &ldquo;%2$s&rdquo;',
				get_comments_number(),
				'comments title',
				'hellish-simplicity'
			),
			number_format_i18n( get_comments_number() ),
			'<span>' . esc_html( get_the_title() ) . '</span>'
		);
	?></h2><?php

	hellish_comments_navigation( 'comment-nav-above' );
	?>

	<ol class="commentlist"><?php wp_list_comments(); ?></ol><!-- .commentlist --><?php

	hellish_comments_navigation( 'comment-nav-below' );

}

/**
 * If comments are closed, then leave a notice.
 */
if (
	! comments_open() &&
	'0' != get_comments_number() &&
	post_type_supports( get_post_type(), 'comments' )
) {
	echo '<p class="nocomments">' . esc_html__( 'Comments are closed.', 'hellish-simplicity' ) . '</p>';
}

/**
 * Display the main comment form.
 */
comment_form(
	array(
		'comment_notes_after' => '',
	)
);

?>

</div><!-- #comments .comments-area -->
