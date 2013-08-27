<script type="text/template" id="change-post-format">
	<span class="icon <%= formatType %>"></span> 
	<span class="post-format-description"><%= formatDescription %></span> 
	<a href="#"><?php _e('Change format'); ?></a>
</script>

<?php 
if( isset( $post_format_descriptions[$format_type] ) ) {
    $post_format_info =  $post_format_descriptions[$format_type];
    $post_format_description =  $post_format_info ['description'];
	if( isset( $post_format_info['icon'] ) )
		$icon = $post_format_info['icon'];
	else
		$icon = '';
}
else 
    $post_format_description = '';

?>

<div id="change-post-format-ui">
<?php if( isset( $format_type ) ): ?>
	<div class="post-format-change" style="display:block">
		<span class="icon <?php echo $format_type; ?>"></span><i class="<?php echo $icon; ?>"></i> 
		<span class="post-format-description"><?php echo $post_format_description; ?></span> 
		<a href="#"><?php _e('Change format'); ?></a>
	</div>
<?php endif; ?>
</div>

<div class="post-format-options cf" <?php if( isset( $format_type ) ) { ?> style="display: none" <?php } ?>>
	<?php echo $post_format_options; ?>
</div>
