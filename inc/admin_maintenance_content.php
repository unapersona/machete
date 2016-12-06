<?php
if ( ! defined( 'MACHETE_ADMIN_INIT' ) ) exit;


if(!$machete_maintenance_settings = get_option('machete_maintenance_settings')){
	$machete_maintenance_settings = array(
		'page_id' => '',
		'site_status' => 'online',
		'token' => strtoupper(substr(MD5(rand()),0,12))
		);
	// default option values saved WITHOUT autoload
    update_option( 'machete_maintenance_settings', $machete_maintenance_settings, 'no' );

};

$machete_preview_base_url = home_url( '/?mct_preview=true');
if ($machete_maintenance_settings['page_id']) {
	$machete_preview_url = home_url( '/?mct_preview=true&mct_page_id=' . $machete_maintenance_settings['page_id'] );
} else {
	$machete_preview_url = $machete_preview_base_url;
}

$machete_magic_base_url = home_url( '/?mct_token=');
$machete_magic_url      = home_url( '/?mct_token=' . $machete_maintenance_settings['token']);




?>


<div class="wrap machete-wrap machete-section-wrap">
	<h1><?php _e('Coming Soon & Maintenance Mode','machete') ?></h1>

	<p class="tab-description"><?php _e('If you have to close yout website temporarly to the public, the navive WordPress maintenance mode falls short and most coming soon plugins are bulky, incomplete or expensive. Machete maintenance mode is light, simple and versatile.','machete') ?></p>
	<?php machete_admin_tabs('machete-maintenance'); ?>
	<!--<p class="tab-performance"><span><strong><i class="dashicons dashicons-clock"></i> <?php _e('Performance impact:','machete') ?></strong> <?php _e('This tool generates up to three static HTML files that are loaded via PHP on each pageview. When enabled, custom body content requires one aditional database request.','machete') ?></span></p>-->




<form id="mache-maintenance-options" action="" method="POST">

<?php wp_nonce_field( 'machete_save_maintenance' ); ?>
<input type="hidden" name="machete-maintenance-saved" value="true">

    <table class="form-table">

    	<tr>
		<th scope="row"><?php _e('Set site status','machete') ?></th>
		<td><fieldset>
			<label>
				<input name="site_status" value="online" type="radio"
				<?php checked($machete_maintenance_settings['site_status'],'online') ?>>
				<strong><?php _e('Online','machete') ?></strong> - <?php _e('WordPress works as usual','machete') ?>
			</label><br>

			<label>
				<input name="site_status" value="coming_soon" type="radio"
				<?php checked($machete_maintenance_settings['site_status'],'coming_soon') ?>>
				<strong><?php _e('Coming soon','machete') ?></strong> - <?php _e('Site closed. All pages have a meta robots noindex, nofollow','machete') ?>
			</label><br>
			<label>
				<input name="site_status" value="maintenance" type="radio" 
				<?php checked($machete_maintenance_settings['site_status'],'maintenance') ?>>
				<strong><?php _e('Maintenance','machete') ?></strong> - <?php _e('Site closed. All pages return 503 Service unavailable','machete') ?></label><br>
		</fieldset></td>
		</tr>

		 <tr valign="top"><th scope="row"><?php _e('Magic Link','machete') ?></th>
            <td>
            	<input type="hidden" name="token" id="token_fld" value="<?php echo $machete_maintenance_settings['token']; ?>">
                <a href="<?php echo $machete_magic_url; ?>" id="machete_magic_link"><?php echo $machete_magic_url; ?></a>
                <button name="change_token" id="change_token_btn" class="button action"><?php _e('change','machete') ?></button>
		<p class="description"><?php _e('You can use this link to grant anyone access to the website when it is in maintenance mode.','machete') ?></p>

            </td>
        </tr>

        <tr valign="top"><th scope="row"><?php _e('Choose a page for the content','machete') ?></th>
            <td>
                <select name="page_id" id="page_id_fld">
                	<option value=""><?php _e('Use default content','machete') ?></option>
                    <?php
                    if( $pages = get_pages() ){
                        foreach( $pages as $page ){
                            echo '<option value="' . $page->ID . '" ' . selected( $page->ID, $machete_maintenance_settings['page_id'] ) . '>' . $page->post_title . '</option>';
                        }
                    }
                    ?>
                </select>

                <a href="<?php echo $machete_preview_url ?>" target="machete_preview" id="preview_maintenance_btn" class="button action"><?php _e('Preview','machete') ?></a>
                
            </td>
        </tr>
        <tr valign="top"><th scope="row"><?php _e('Customize maintenance page','machete') ?></th>
            <td>

            <p class="description"><?php printf(__('You can customize the maintenance page CSS using the %sAnalytics & Code tab','machete'),
            '<a href="'.admin_url('admin.php?page=machete-utils').'">') ?></a></p>
            <p class="description"><?php _e('For your reference, this is the HTML used to render the Maintenance page:','machete') ?></p>
            <pre style="color: #00f; font-weight: bold;">&lt;html&gt;
  &lt;head&gt;
    &lt;title&gt;<span style="color: #000;">[<?php _e('title of the selected page','machete') ?>]</span>&lt;/title&gt;
    <span style="color: #000;">[&hellip;]</span>
  &lt;/head&gt;
  &lt;body <span style="color: #c00;">id</span>=<span style="color: #f0f;">"maintenance_page"</span>&gt;
    &lt;div <span style="color: #c00;">id</span>=<span style="color: #f0f;">"content"</span>&gt;
      <span style="color: #000;">[<?php _e('content of the selected page','machete') ?>]</span>
    &lt;/div&gt;
  &lt;/body&gt;
&lt;/html&gt;</pre>
            </td>

        </tr>
    </table>
   
<?php submit_button(); ?>

</form>

		

</div>


<script>

(function($){

	var machete_preview_base_url = '<?php echo $machete_preview_url ?>';
	var machete_magic_base_url   = '<?php echo $machete_magic_base_url ?>';

	var random_token = function(){
		var chrs = '0123456789ABCDEF';
		var token = '';
		for (var i = 0, n = 12; i < n; i++){
			token += chrs.substr(Math.round(Math.random()*15),1);
		}
		return token;
	}
	
	$('#change_token_btn').click(function(e){
		
		if (confirm('<?php _e('Are you sure you want to change the magic token?','machete') ?>')){
			var new_token = random_token();
			var new_magic_url = machete_magic_base_url + new_token;

			$('#machete_magic_link').attr('href',new_magic_url).html(new_magic_url);
			$('#token_fld').val(new_token);
		}
		e.preventDefault();
		return;
	});

	$('#page_id_fld').change(function(e){
		var content_page_id = '';
		if (content_page_id = $('#page_id_fld option:selected').val()){
			
			$('#preview_maintenance_btn').attr('href',machete_preview_base_url+'&mct_page_id='+content_page_id);
			console.log(content_page_id);
		}else{
			$('#preview_maintenance_btn').attr('href', machete_preview_base_url);
			console.log('vacío');
		}
			
	});

	$('#mache-maintenance-options').submit(function( e ) {
	
	});
})(jQuery);


</script>
