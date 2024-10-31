<?php
/*
Plugin Name: Plugin Docs
Plugin URI: https://wordpress.org/plugins/plugin-docs
Version: 1.0.9
Description: Add notes to your plugins so you can document why you needed each one
Author: James Low
Author URI: http://jameslow.com
*/

class PluginDocs {
	private static $docs = null;
	
	static function getDocs() {
		return self::$docs ? self::$docs : self::$docs = json_decode(get_option('plugin_docs'));
	}
	static function addHooks() {
		add_option('plugin_docs', '[{"file":"plugin-docs/plugin-docs.php","notes":"This plugin generates this notes interface."}]', false, false);
		add_action('admin_enqueue_scripts', array('PluginDocs', 'loadScripts'));
		add_filter( 'plugin_row_meta', array('PluginDocs', 'pluginMeta'), 10, 2 );
		if (is_admin()) {
			add_action('wp_ajax_save_plugin_docs', array('PluginDocs', 'saveDocs'));
		}
	}
	static function saveDocs() {
		$docs = self::getDocs();
		$file = wp_unslash(sanitize_text_field($_POST['file']));
		$notes = wp_unslash(sanitize_textarea_field($_POST['notes']));
		$found = false;
		for ($i=0; $i<count($docs); $i++) {
			$plugin = $docs[$i];
			if ($plugin->file == $file) {
				$docs[$i] = array('file' => $file, 'notes' => $notes);
				$found = true;
				break;
			}
		}
		if (!$found) {
			$docs[] = array('file' => $file, 'notes' => $notes);
		}
		update_option('plugin_docs', json_encode($docs));
		echo '1';
		wp_die();
	}
	static function loadScripts() {
		wp_enqueue_script('jquery');
		wp_enqueue_script('plugin-docs-js', plugins_url(null,__FILE__).'/plugin-docs.js');
		wp_enqueue_style('plugin-docs-css', plugins_url(null,__FILE__).'/plugin-docs.css');
	}
	static function pluginMeta($links, $file) {
		$docs = self::getDocs();
		$html = '';
		foreach ($docs as $plugin) {
			if ($plugin->file == $file) {
				$html = htmlspecialchars($plugin->notes);
			}
		}
		$new_links = array(
			'plugin-docs' => '<span class="plugin_docs" data="'.htmlspecialchars($file).'"><textarea placeholder="Notes...">'.$html.'</textarea><div><button onclick="savePluginDocs(this); return false;">Save</button></div></span>'
		);
		$links = array_merge( $links, $new_links );
		return $links;
	}
}

PluginDocs::addHooks();