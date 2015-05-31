<?php
/**
 * Plugin Name: Minimal Google Analytics
 * Description: The minimum code required to allow you to track user visits using Google Analytics. When enabled and configured it will add the Google Analytics script on every page. Can be configured under 'Settings'.
 * Version: 1.0.0
 * Requires at least: 4.2
 * Author: Dutchwise
 * Author URI: http://www.dutchwise.nl/
 * Text Domain: minga
 * Domain Path: /locale/
 * Network: true
 * License: MIT license (http://www.opensource.org/licenses/mit-license.php)
 */

include 'html.php';

class MinimalGoogleAnalytics {
	
	/**
	 * Sanitizes Google Analytics settings before saving.
	 *
	 * [enabled] => 0
	 * [tracking_id] => UA-XXXXXXXX-X
     * [anonymize_ip] => 0
	 *
	 * @param array $input
	 * @return array
	 */
	public function sanitizeGaSettings(array $input) {
		foreach($input as $key => &$value) {
			$value = stripslashes(strip_tags($value));
		}
		
		$fields = array('enabled', 'force_ssl', 'anonymize_ip', 'track_admin');
		
		foreach($fields as $field) {
			switch($field) {
				case 'enabled':
				case 'force_ssl':
				case 'anonymize_ip':
				case 'track_admin':
					$input[$field] = (int)!!$input[$field];	
					break;	
			}
		}
		
		return apply_filters('minga_sanitize_ga_options', $input);
	}
	
	/**
	 * Renders the Google Analytics admin settings page.
	 *
	 * @return void
	 */
	public function renderAdminSettingsPage() {
		$html = new HtmlHelper(false);
		
		echo $html->open('div', array('class' => 'wrap'));
		
		// start form
		echo $html->open('form', array(
			'action' => 'options.php',
			'method' => 'POST',
			'accept-charset' => get_bloginfo('charset'),
			'novalidate'
		));
		
		// form title
		echo $html->element('h2', __('Google Analytics', 'minga'));
		
		echo $html->single('br');
		
		// prepare form for settings (nonce, referer fields)
		settings_fields('google_analytics');
		
		// renders all settings sections of the specified page
		do_settings_sections('google_analytics');
		
		// renders the submit button
		submit_button();
		
		echo $html->close();
	}
	
	/**
	 * Renders the Google Analytics admin settings section.
	 *
	 * @param array $args 'id', 'title', 'callback'
	 * @return void
	 */
	public function renderAdminGaSettingsSection($args) {
		// do nothing
	}
	
	/**
	 * Renders the Google Analytics admin settings fields.
	 *
	 * @param array $args Unknown
	 * @return void
	 */
	public function renderAdminGaSettingField($args) {
		$options = get_option('google_analytics_settings');
		$html = new HtmlHelper();
		$atts = array();
		
		// if option does not exist, add to database
		if($options == '') {
			add_option('google_analytics_settings', array());
		}
		
		// make sure the required label_for and field arguments are present to render correctly
		if(!isset($args['label_for'], $args['field'])) {
			throw new InvalidArgumentException('add_settings_field incorrectly configured');
		}
		
		// define attributes each field should have
		$atts['id'] = $args['label_for'];
		$atts['name'] = "google_analytics_settings[{$args['field']}]";
		
		// render html based on which field needs to be rendered
		switch($args['field']) {
			case 'enabled':
			case 'anonymize_ip':
			case 'force_ssl':
			case 'track_admin':
				$atts['type'] = 'checkbox';
				$atts['value'] = '1';
				
				$html->single('input', array(
					'id' => $atts['id'] . '_hidden',
					'type' => 'hidden',
					'value' => 0
				) + $atts);
				
				if(isset($options[$args['field']]) && $options[$args['field']]) {
					$atts['checked'] = 'checked';
				}
				
				$html->single('input', $atts);				
				break;
			case 'tracking_id':
				$atts['type'] = 'text';
				$atts['placeholder'] = 'UA-XXXXXXXX-X';
				
				if(isset($options[$args['field']])) {
					$atts['value'] = $options[$args['field']];
				}
				
				$html->single('input', $atts);				
				break;
		}
		
		$html->close();
		
		echo $html;
	}
	
	/**
	 * Runs when the WordPress admin area is initialised.
	 *
	 * @return void
	 */
	public function onAdminInit() {
		register_setting('google_analytics', 'google_analytics_settings', array($this, 'sanitizeGaSettings'));
		
		add_settings_section(
			'ga_section',				// ID used to identify this section and with which to register options
			__( 'Tracking', 'minga' ),	// Title to be displayed on the administration page
			array($this, 'renderAdminGaSettingsSection'),
			'google_analytics'			// Page on which to add this section of options
		);
		
		// field names and labels
		$fields = array(
			'enabled' => __('Enable Google Analytics', 'minga'),
			'tracking_id' => __('Tracking ID', 'minga'),
			'force_ssl' => __('Force SSL', 'minga'),
			'anonymize_ip' => __('Enable anonymized IP', 'minga'),
			'track_admin' => __('Enable admin user tracking (outside CMS)', 'minga')
		);
		
		// register and render the fields using add_settings_field and the $fields array
		foreach($fields as $field => $label) {
			add_settings_field(
				"google_analytics_settings[{$field}]",	// ID used to identify the field throughout the theme
				$label,									// The label to the left of the option interface element
				array($this, 'renderAdminGaSettingField'),
				'google_analytics',						// The page on which this option will be displayed
				'ga_section',							// The name of the section to which this field belongs
				array(									// The array of arguments to pass to the callback.
					'field' => $field,
					'label_for' => $field
				)
			);
		}
	}
	
	/**
	 * Runs when the WordPress admin menus are initialised.
	 *
	 * @return void
	 */
	public function onAdminMenu() {
		// adds the email menu item to WordPress's main Settings menu
		add_options_page(
			__('Google Analytics Settings', 'minga'),
			__('Google Analytics', 'minga'),
			'manage_options', 
			'ga',
			array($this, 'renderAdminSettingsPage')
		);
	}
	
	/**
	 * Renders the Google Analytics tracking script.
	 *
	 * @return void
	 */
	public function renderGaScript() {
		$options = get_option('google_analytics_settings');
		
		// check if Google Analytics is enabled
		if(empty($options['enabled']) || empty($options['tracking_id'])) {
			return;
		}
		
		// check if is admin and should not track admin
		if(empty($options['track_admin']) && current_user_can('manage_options')) {
			return;
		}
		
		// create identifier for the script
		$id = 'google-analytics-script';
		
		// check if script was already added
		if(wp_script_is($id, 'done')) {
			return;
		}		
		
		// access global scripts (to mark the script as done later on)
		global $wp_scripts;
		
		$html = new HtmlHelper();
		$html->open('script', array('type' => 'text/javascript'));
        
		// append the script that includes ga.js
        $html->append("(function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
  (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
  m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
  })(window,document,'script','//www.google-analytics.com/analytics.js','ga');");
  
  		$html->append("ga('create', '{$options['tracking_id']}', 'auto');");
		
		if(!empty($options['force_ssl'])) {
			$html->append("ga('set', 'forceSSL', true);");
		}
		
		if(!empty($options['anonymize_ip'])) {
			$html->append("ga('set', 'anonymizeIp', true);");
		}
		
		$html->append("ga('send', 'pageview');");
		
		// close script tag
		$html->close();
		
		// render script
		echo $html->render();
		
		// mark the script as added
		$wp_scripts->done[] = $id;		
	}
	
	/**
	 * Class constructor
	 */
	public function __construct() {
		add_action('admin_menu', array($this, 'onAdminMenu'));
		add_action('admin_init', array($this, 'onAdminInit'));
		add_action('wp_head', array($this, 'renderGaScript'));
	}
	
}

new MinimalGoogleAnalytics;