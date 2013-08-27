<div class="error">
	<p>
	<?php printf( __( 'Your current WordPress is version %1$s, which may cause problems with the post formats plugin. The minimum required version is %2$s. Please, either <a href="%3$s">update WordPress</a>, or <a href="%4$s">deactivate the post formats plugin</a>.', 'liveblog' ), $wp_version, $min_version, admin_url( 'update-core.php' ), admin_url( 'plugins.php#post-formats-ui' ) ); ?>
	</p>
</div>