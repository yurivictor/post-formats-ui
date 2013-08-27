<div class="post-format-change">
	<span class="icon <?php echo esc_attr( $post_format ); ?>"></span>
	<?php if( isset( $all_post_formats[$post_format]['icon'] ) ): ?>
		<i class="<?php echo $all_post_formats[$post_format]['icon']; ?>"></i>
	<?php endif; ?>
	<span class="post-format-description"><?php echo $all_post_formats[$post_format]['description']; ?></span> 
	<a href="#"><?php _e('Change format'); ?></a>
</div>