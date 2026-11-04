<?php
/**
 * قالب دیدگاه‌ها.
 *
 * @package LamerdKhodro
 */

defined( 'ABSPATH' ) || exit;

if ( post_password_required() ) {
	return;
}
?>

<section id="comments" class="lk-comments">
	<?php if ( have_comments() ) : ?>
		<h2 class="lk-section__title" style="font-size:1.05rem">
			<?php
			$lk_count = (int) get_comments_number();
			echo esc_html(
				sprintf(
					/* translators: %s: comments count */
					_n( '%s دیدگاه', '%s دیدگاه', $lk_count, 'lamerd-khodro' ),
					lk_fa_digits( number_format_i18n( $lk_count ) )
				)
			);
			?>
		</h2>

		<ol style="list-style:none;padding:0;margin:0 0 24px">
			<?php
			wp_list_comments(
				array(
					'style'       => 'ol',
					'short_ping'  => true,
					'avatar_size' => 44,
					'callback'    => static function ( $comment, $args, $depth ) {
						?>
						<li id="comment-<?php comment_ID(); ?>" <?php comment_class( 'lk-comment' ); ?> style="padding-block:14px;border-block-end:1px solid var(--lk-line)">
							<article>
								<footer style="display:flex;align-items:center;gap:8px;margin-block-end:6px">
									<?php echo get_avatar( $comment, 36, '', '', array( 'style' => 'border-radius:50%' ) ); ?>
									<span style="font-weight:700"><?php comment_author(); ?></span>
									<span class="lk-muted" style="font-size:.75rem"><?php echo esc_html( lk_posted_on_fallback( (string) get_comment_date( 'Y-m-d H:i:s' ) ) ); ?></span>
								</footer>
								<div class="lk-prose" style="font-size:.875rem">
									<?php comment_text(); ?>
								</div>
								<?php
								comment_reply_link(
									array_merge(
										$args,
										array(
											'depth'     => $depth,
											'max_depth' => $args['max_depth'],
											'before'    => '<span style="font-size:.8125rem">',
											'after'     => '</span>',
										)
									)
								);
								?>
							</article>
						</li>
						<?php
					},
				)
			);
			?>
		</ol>

		<?php
		the_comments_pagination(
			array(
				'prev_text' => __( 'قبلی', 'lamerd-khodro' ),
				'next_text' => __( 'بعدی', 'lamerd-khodro' ),
				'class'     => 'lk-pagination',
			)
		);
		?>
	<?php endif; ?>

	<?php if ( ! comments_open() && get_comments_number() ) : ?>
		<p class="lk-muted"><?php esc_html_e( 'دیدگاه‌ها برای این مطلب بسته شده است.', 'lamerd-khodro' ); ?></p>
	<?php endif; ?>

	<?php
	comment_form(
		array(
			'class_form'          => 'lk-comment-form',
			'title_reply_before'  => '<h2 class="lk-section__title" style="font-size:1.05rem">',
			'title_reply_after'   => '</h2>',
			'class_submit'        => 'lk-btn lk-btn--primary',
			'comment_notes_before' => '<p class="lk-muted" style="font-size:.8125rem">' . esc_html__( 'نشانی ایمیل شما منتشر نخواهد شد.', 'lamerd-khodro' ) . '</p>',
		)
	);
	?>
</section>
