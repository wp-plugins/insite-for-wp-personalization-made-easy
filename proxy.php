<?php
require_once plugin_dir_path( __FILE__ ) . 'config/config.php';
$dev_config = plugin_dir_path( __FILE__ ) . 'config/config-dev.php';

if (file_exists($dev_config)) {
    include_once $dev_config;
}

class InSite_Proxy {
    const PROXY_MODE_PARAM = 'io-proxy';
    const INJECT_SCRIPT_PARAM = 'io-script';
    const PROXY_LINK_TYPES = 'author_feed_link,author_link,day_link,get_comment_author_link,month_link,page_link,post_link,post_type_link,the_permalink,year_link,tag_link,term_link';
    const PROXY_KEEP_PARAMS = 'noinsite,base,jsinbody';

    /**
     * Construct function for register and create admin
     */
    function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'getProxyModeHeaderJs'), 1000000);

        $link_types = explode(',', self::PROXY_LINK_TYPES);

        foreach ($link_types as $type) {
            add_filter($type, 'addParams');
        }

        function addParams($url) {
            if (isset($_GET[InSite_Proxy::PROXY_MODE_PARAM])) {
                $url = add_query_arg( InSite_Proxy::PROXY_MODE_PARAM, $_GET[InSite_Proxy::PROXY_MODE_PARAM], $url );
            }

            if (isset($_GET[InSite_Proxy::INJECT_SCRIPT_PARAM])) {
                $url = add_query_arg( InSite_Proxy::INJECT_SCRIPT_PARAM, $_GET[InSite_Proxy::INJECT_SCRIPT_PARAM], $url );
            }

            $keep_params = explode(',', InSite_Proxy::PROXY_KEEP_PARAMS);

            foreach ($keep_params as $param) {
                if (isset($_GET[$param])) {
                    $url = add_query_arg( $param, $_GET[$param], $url );
                }
            }

            return $url;
        }

        add_filter('show_admin_bar', '__return_false');
        add_action('wp_enqueue_scripts', array($this, 'injectProxyScripts'), 1000000);
        add_action('wp_enqueue_scripts', array($this, 'injectScriptsFromParam'), 1000000);
    }

    public function injectProxyScripts () {
        wp_enqueue_script( 'insite-crosser', 'https://insite.s3.amazonaws.com/io-plugin/crosser.js' );
        wp_enqueue_script( 'insite-proxy', plugins_url( 'assets/js/insite-proxy.js', __FILE__ ), null, null, true );
    }

    public function injectScriptsFromParam () {
        if (!isset($_GET[self::INJECT_SCRIPT_PARAM])) {
            return;
        }

        $scripts_types = explode(',', $_GET[InSite_Proxy::INJECT_SCRIPT_PARAM]);

        $scripts = $this->getServerScripts($scripts_types);

        if (!is_array($scripts)) {
            return;
        }

        foreach ($scripts as $script) {
            wp_enqueue_script( $script->name, $script->src, null, null, $script->location == 'footer' );
        }
    }

    public function getServerScripts ($scripts_types) {
        global $insiteConfig;

        $service_url = $insiteConfig['insiteAPIServerBase'] . '/server/api/anon/scripts?script=' . implode('&script=', $scripts_types);
        $response = wp_remote_get($service_url);
        $data = json_decode($response['body']);

        if (is_object($data) && is_array($data->scripts)) {
            return $data->scripts;
        }

        return array();
    }

    public function getProxyModeHeaderJs () {
        global $insiteConfig;

        echo '<script>';
        echo 'window.insiteProxyData = window.insiteProxy || {};';
        echo 'window.insiteProxyData.serverOrigin = location.protocol + "' . $insiteConfig['insiteUIServerBase'] . '";';
        echo 'window.insiteProxyData.siteBaseUrl = "' . get_bloginfo('url') . '"';
        echo '</script>';
    }
}
