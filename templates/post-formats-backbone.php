<script type="text/template" id="format-type">
<div class="post-format-fields">
	<div class="field cf">
		<% _.each(inputs, function(input) { %>
				<% if( input.el === 'input' ) { %>
					<label class="block" for="<%= input.id %>"><%= input.label_name %></label>
					<input class="widefat" type="<%= input.type %>" name="<%= input.id %>" id="<%= input.id %>" <% if( formatType == cpffs.fs_info[0].fs_type ) { %> value="<%= cpffs.fs_info[0][input.id] %>" <% } %> />
				<% } %>
				<% if( input.el === 'textarea' ) { %>
					<label for="<%= input.id %>"><%= input.label_name %></label><br />
					<textarea class="widefat" name="<%= input.id %>" id="<%= input.id %>"><% if( formatType == cpffs.fs_info[0].fs_type ) { %><%= cpffs.fs_info[0][input.id] %><% } %></textarea>
				<% } %>
				<% if ( input.el === 'div' ) { %>
					<div data-format="<%= input.format %>" class="wp-format-media-holder hide-if-no-js"><a href="#" class="wp-format-media-select" data-choose="<%= input.data_choose %>" data-update="<%= input.data_update %>"><%= input.text %></a></div>
				<% } %>
		<% }); %>
	</div>
</div>

</script>

<div id="post-format-ui"></div>