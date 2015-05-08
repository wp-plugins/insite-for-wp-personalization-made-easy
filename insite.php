<?php
/**
* Plugin Name: inSite for WP: personalization made easy
* Plugin URI: http://insite.io
* Description: inSites are smart, personalized recipes that automatically CHANGE your website at pre determined TRIGGER points (such as Time, Location or Visits etc) to create a richer, more engaged and relevant visitor experience that drives greater conversion.
* Version: 1.0
* Author: Duda
* Author URI: http://www.dudamobile.com
* License: GPLv2 or later
* License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/

// require the admin class
require_once plugin_dir_path( __FILE__ ) . 'admin.php';

class InSite_Plugin {
    /**
     * Construct function for register and create admin
     */
    function __construct() {
        register_activation_hook(__FILE__, array($this, 'install'));
        register_uninstall_hook(    __FILE__, array( '$this', 'uninstall' ) );


        add_action('get_header', array($this, 'add_grid_style'));
        add_action('wp_enqueue_scripts', array($this, 'writeInsiteJsHeader'), 1000000);
        add_action('wp_enqueue_scripts', array($this, 'registerPluginScript'));
        add_action('wp_footer', array($this, 'writeInsiteJsFooter'), 1000000);
        add_filter ('the_content', array($this, 'addPostMarker'), 1000000);

        if( is_admin() ) {
            new Insite_Admin();
        }
    }

    public function add_grid_style() {
        // Register the style like this for a plugin:
        wp_register_style( 'insite-grid-style', 'https://insite.s3.amazonaws.com/io-editor/css/io-grid.css' );

        // For either a plugin or a theme, you can then enqueue the style:
        wp_enqueue_style( 'insite-grid-style' );
    }

    /**
     * Install when activate the plugin
     */
    public function install() {
        update_option('insite_js_version', '1');
    }

    /**
     * Clean when uninstalling the plugin
     */
    public function uninstall() {
        delete_option('insite_js_version');
        delete_option('insite_site_id');
        delete_option('insite_api_token');
        delete_option('insite_shortcodes');
    }

    /**
     * Add insite js file to page head
     */
    public function registerPluginScript() {
        global $insiteConfig;

        if (isset($_GET['noinsite'])) {
            return;
        }

        $insiteVersion = get_option('insite_js_version');

        if (get_option('insite_site_id')) {
           wp_register_script('insite', $insiteConfig['insiteRTScriptBase'] . '/s-' . get_option('insite_site_id') . '/io-script.js' , null, $insiteVersion);
           wp_enqueue_script('insite');
        }
    }

    public function writeInsiteJsHeader() {
        echo '<script>';
        echo 'window.INSITE = window.INSITE || {};';
        echo $this->getPageIdJs();
        echo '</script>';
    }

    public function writeInsiteJsFooter() {
        echo '<script>';
        echo 'window.INSITE = window.INSITE || {};';
        echo $this->getShortCodesJs();
        echo '</script>';
    }

    /**
    * Print the shortcode widgets to the end of the body encoded in base64.
    * The runtime script will decode the markup and will inject it to the right container
    */
    public function getShortCodesJs() {
        $js = 'INSITE.shortCodes = {';
        $shortcodes = get_option( 'insite_shortcodes' );

        if(!empty($shortcodes)) {
            foreach ($shortcodes as $key => $val) {
                $js = $js . '"' . $val['insite_id'] .'" : "' . base64_encode(do_shortcode($val['insite_code'])) . '" , ';
            }
        }

        $js = $js . '};';

        return $js;
    }

    public function getPageIdJs () {
        global $post;

        $current_page = '';

        if (is_home()) {
            $current_page = '__home__';
        } else if ($post) {
            $current_page = get_option('page_on_front') ==  $post->ID ? '__home__' : $post->post_name;
        }

        return 'window.INSITE.currentPage = "' . $current_page . '";';
    }

    public function addPostMarker($content) {
        $content = '<div class="io-post-marker" style="display:none"></div>' . $content;
        return $content;
    }
}

// initiate the plugin
new InSite_Plugin();
