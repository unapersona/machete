<?php
if ( ! defined( 'ABSPATH' ) ) exit;


class machete_utils_module extends machete_module {

	function __construct(){
		$this->init( array(
			'slug' => 'utils',
			'title' => __('Analytics & Code','machete'),
			'full_title' => __('Analytics and Custom Code','machete'),
			'description' => __('Google Analytics tracking code manager and a simple editor to insert HTML, CSS and JS snippets or site verification tags.','machete'),
			//'is_active' => true,
			//'has_config' => true,
			//'can_be_disabled' => true,
			// 'role' => 'manage_options'
			)
		);
		$this->default_settings = array(
			'tracking_id' => '',
			'tracking_format' => 'none',
			'tacking_anonymize' => 0,
			'track_wpcf7' => 0,
			'alfonso_content_injection_method' => 'manual'
			);
	}

	public function admin(){
		$this->read_settings();
		if (isset($_POST['machete-utils-saved'])){
  			check_admin_referer( 'machete_save_utils' );
			$this->save_settings( $_POST );
		}
		add_action( 'admin_menu', array(&$this, 'register_sub_menu') );
	}

	protected function save_settings( $options = array(), $silent = false) {

		/*
		tracking_id
		tracking_format: standard, machete, none
		header_content
		footer_content
		*/
		$settings = $this->read_settings();
		$header_content = '';
		
		if (!is_dir(MACHETE_DATA_PATH)){
			if(!@mkdir(MACHETE_DATA_PATH)){
				if (!$silent) $this->notice( sprintf( __( 'Error creating data dir %s please check file permissions', 'machete' ), MACHETE_DATA_PATH), 'error');
				return false;
			}
		}

		if(!empty($options['tracking_id'])){

			if(!preg_match('/^ua-\d{4,9}-\d{1,4}$/i', strval( $options['tracking_id'] ))){
				// invalid Analytics Tracking ID
				// http://code.google.com/apis/analytics/docs/concepts/gaConceptsAccounts.html#webProperty
				if (!$silent) $this->notice( __( 'That doesn\'t look like a valid Google Analytics tracking ID', 'machete' ), 'warning');
				return false;
			}
			$settings['tracking_id'] = $options['tracking_id'];

			if( !in_array( $options['tracking_format'], array('standard','machete','none') )){
				// I don't know that tracking format
				if (!$silent) $this->notice( __( 'Something went wrong. Unknown tracking code format requested.', 'machete' ), 'warning');
				return false;
			}
			$settings['tracking_format'] = $options['tracking_format'];

			if ( isset( $options['tacking_anonymize'] )){
				$anonymizeIp = ',{anonymizeIp: true}';
				$settings['tacking_anonymize'] = 1;
			}else{
				$anonymizeIp = '';
				$settings['tacking_anonymize'] = 0;
			}

			if ( isset( $options['track_wpcf7'] )){
				$settings['track_wpcf7'] = 1;
				$track_wpcf7 = @file_get_contents($this->path.'templates/wpcf7.tpl.min.js');
			}else{
				$track_wpcf7 = '';
				$settings['track_wpcf7'] = 0;
			}

			// let's generate the Google Analytics tracking code
			if($settings['tracking_format'] == 'machete'){
				$header_content .= 'if (!navigator.userAgent || ('."\n";
				$header_content .= '  (navigator.userAgent.indexOf("Speed Insights") == -1) &&'."\n";
				$header_content .= '  (navigator.userAgent.indexOf("Googlebot") == -1)'."\n";
				$header_content .= ')) {'."\n";
			}
			if($settings['tracking_format'] != 'none'){
				
				$js_replaces = array(
					'{{anonymizeIp}}' => $anonymizeIp,
					'{{tracking_id}}' => $options['tracking_id'],
					'{{track_wpcf7}}' => $track_wpcf7
				);
				$header_content .= str_replace(
					array_keys($js_replaces),
					array_values($js_replaces),
					@file_get_contents($this->path.'templates/analytics.tpl.js')
				);
			}
			if($settings['tracking_format'] == 'machete'){
				$header_content .= '}'."\n";
			}
			if($settings['tracking_format'] != 'none'){
				$header_content = "<script>\n".$header_content."</script>\n<!-- Machete Header -->\n";
			}
		}else{
			$settings['tracking_id'] = '';
			$settings['tracking_format'] = 'none';
		}

		if(!empty($options['header_content'])){
			$header_content .= stripslashes(wptexturize($options['header_content']));
		}

		if(!empty($header_content)){
			file_put_contents(MACHETE_DATA_PATH.'header.html', $header_content, LOCK_EX);
		}else{
			if (file_exists(MACHETE_DATA_PATH.'header.html')){
				unlink(MACHETE_DATA_PATH.'header.html');
			}
		}


		if(
			isset($options['alfonso_content_injection_method']) &&
			in_array($options['alfonso_content_injection_method'], array('auto', 'manual'))
		){
			$settings['alfonso_content_injection_method'] = $options['alfonso_content_injection_method'];
		}else{
			$settings['alfonso_content_injection_method'] = $this->default_settings['alfonso_content_injection_method'];
		}

		if(!empty($options['alfonso_content'])){
			$alfonso_content = stripslashes(wptexturize($options['alfonso_content']));
			file_put_contents(MACHETE_DATA_PATH.'body.html', $alfonso_content, LOCK_EX);
		}else{
			if (file_exists(MACHETE_DATA_PATH.'body.html')){
				unlink(MACHETE_DATA_PATH.'body.html');
			}
		}


		if(!empty($options['footer_content'])){
			$footer_content = stripslashes(wptexturize($options['footer_content']));
			file_put_contents(MACHETE_DATA_PATH.'footer.html', $footer_content, LOCK_EX);
		}else{
			if (file_exists(MACHETE_DATA_PATH.'footer.html')){
				unlink(MACHETE_DATA_PATH.'footer.html');
			}
		}
		
		if ($this->is_equal_array($this->settings, $settings)){
			// no removes && no adds
			// ToDo: check for changes in the other sections
			//       give "no changes" notice only if no changes at all
			//if (!$silent) $this->save_no_changes_notice();
			return true;
		}	


		// option saved WITHOUT autoload
		if(update_option( 'machete_utils_settings', $settings, 'no' )){
			$this->settings = $settings;
			if (!$silent) $this->save_success_notice();
			return true;
		}else{
			if (!$silent) $this->save_error_notice();
			return false;
		}

	}

	protected function import( $settings = array() ){

		$encoded_fields = array('header_content', 'alfonso_content', 'footer_content');

		foreach ($encoded_fields as $encoded_field){
			if (array_key_exists($encoded_field, $settings) && ! empty($settings[$encoded_field]) ){
				$settings[$encoded_field] = base64_decode($settings[$encoded_field]);
			}
		}
			
		if ( $this->save_settings($settings, true) ){
			return __('Settings succesfully restored from backup', 'machete') . "\n";
		}else{
			return __('Error restoring settings backup', 'machete') . "\n";
		}
	}

	protected function export(){

		$export = $this->settings;

		if($machete_header_content = @file_get_contents(MACHETE_DATA_PATH.'header.html')){

			$machete_header_content = explode('<!-- Machete Header -->', $machete_header_content);
			switch(count($machete_header_content)){
				case 1:
					$machete_header_content = $machete_header_content[0];
					break;
				case 2:
					$machete_header_content = $machete_header_content[1];
					break;
				default:
					$machete_header_content = implode('',array_slice($machete_header_content, 1));
			}
			$export['header_content'] = base64_encode($machete_header_content);
		}
		if (file_exists(MACHETE_DATA_PATH.'body.html')){
			$export['alfonso_content'] = base64_encode(file_get_contents(MACHETE_DATA_PATH.'body.html'));
		}

		if (file_exists(MACHETE_DATA_PATH.'footer.html')){
			$export['footer_content'] = base64_encode(file_get_contents(MACHETE_DATA_PATH.'footer.html'));
		}

		return $export;
	}

}
$machete->modules['utils'] = new machete_utils_module();