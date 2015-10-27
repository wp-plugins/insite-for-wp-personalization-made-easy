<?php
require_once plugin_dir_path( __FILE__ ) . 'config/config.php';
$dev_config = plugin_dir_path( __FILE__ ) . 'config/config-dev.php';

if (file_exists($dev_config)) {
    include_once $dev_config;
}

class Insite_Admin
{
    /**
     * Construct function for add menu and ajax route
     */
    function __construct() {
        add_action( 'admin_menu', array( $this, 'menu' ) );
        add_action( 'wp_ajax_insite_update_js', array( $this, 'updateJs' ) );
        add_action( 'wp_ajax_insite_get_site_pages', array( $this, 'getSitePages' ) );
        add_action( 'wp_ajax_insite_update_shortcode', array( $this, 'addShortCodeWidget' ) );
        add_action( 'wp_ajax_insite_delete_shortcode', array( $this, 'deleteShortCodeWidget' ) );
        add_action( 'wp_ajax_insite_delete_shortcodes', array( $this, 'cleanShortCodes' ) );
        add_action( 'wp_ajax_insite_cleanup', array( $this, 'cleanup' ) );
        add_action( 'wp_ajax_insite_connect_account', array( $this, 'connectAccount' ) );


    }

    /**
     * Add menu and pages
     */
    public function menu () {

        add_menu_page( 'inSite', 'inSite', 'read', 'insite', array( $this, 'insitePage' ), plugins_url( "assets/images/v.png", __FILE__ ) );
        add_submenu_page( 'insite', 'inSite Library', 'inSite Library', 'read', 'insite', array( $this, 'insitePage' ));
        add_submenu_page( 'insite', 'My inSites', 'My inSites', 'read','insite-my', array( $this, 'dashboardPage' ));
        add_submenu_page( 'insite', 'My Profile', 'My Profile', 'read','insite-profile', array( $this, 'dashboardPage' ));
        add_submenu_page( 'insite', 'Stats', 'Stats', 'read', 'insite-stats', array( $this, 'dashboardPage'));
        add_submenu_page( 'insite', 'FAQ', 'FAQ', 'read', 'insite-faq', array( $this, 'dashboardPage'));
        
       
    }

    public function initInsitePlugin () {
        $this->initToken();
        $this->initSite();
    }

    public function initToken () {
        global $sso_token;
        global $api_token;

        $api_token = get_option('insite_api_token');

        if (!$api_token)  {
            $api_token = $this->createUser();
        }

        $headers = array(
            'Content-Type' => 'application/json',
            'IO-API-AUTHENTICATE' => $api_token
        );

        $response = $this->getInsiteServerAPI('partner/auth/sso/token','',$headers);
        $urlparam = json_decode($response['body'])->url_parameter;
        $sso_token = $urlparam->name.'='. $urlparam->value;
    }

    public function initPage () {
        $this->addScriptsAndStyles();
    }

    public function initSite () {
        global $api_token;

        if (!get_option('insite_site_id')) {
            $site_url = get_site_url();
            $theme_name = $this->getThemeName();

            $headers = array(
                'Content-Type' => 'application/json',
                'IO-API-AUTHENTICATE' => $api_token
            );

            $body =  array (
                'value' =>  $site_url,
                'themeName' => $theme_name
            );

            $response = $this->postInsiteServerAPI('insite/sites',json_encode($body),$headers);

            if ( is_wp_error( $response ) ) {
                 echo $response->get_error_message();
            } else {
                $siteId = json_decode($response['body'])->alias;

                if ($siteId) {
                    update_option('insite_site_id',$siteId);
                }
            }
        }
    }

    public function dashboardPage () {
        global $insiteConfig;
        global $sso_token;

        $this->initInsitePlugin();
        $this->initPage();

        $map = array('insite-my' => '/dashboard/home/insites', 'insite-profile' => '/dashboard/home/profile', 'insite-stats' => '/dashboard/home/stats', 'insite-faq' => '/dashboard/home/faq');

        $dashboardPath = $map[$_GET['page']];

        if (isset($_GET['path'])){
            $path = $_GET['path'];
        } else {
            $path = $dashboardPath;
        }

        include('sections/dashboard.php');
    }

    /**
     * Insite Page
     */
    public function insitePage () {
        global $insiteConfig;
        global $sso_token;
        global $api_token;

        $this->initInsitePlugin();
        $this->initPage();

        // get site pages
        $pagesToClient = $this->getRecPages();

        if (isset($_GET['path'])){
            $path = $_GET['path'];
        } else {
            $path = '/browser/home/browser';
        }

        include('sections/library.php');
    }

    public function createUser() {
        global $insiteConfig;

        $body =  '{}';
        $headers = array('Content-Type' => 'application/json');
        $response = $this->postInsiteServerAPI('anon/signup',$body,$headers);
        $api_token = json_decode($response['body'])->api_token;

        update_option('insite_api_token',$api_token);

        return $api_token;
    }

    public function postInsiteServerAPI($method_url,$body,$headers) {
        global $insiteConfig;

        $service_url = $insiteConfig['insiteAPIServerBase'].'/server/api/'.$method_url;
        $args = array(
            'body' => $body,
            'headers' => $headers
        );

        $response = wp_remote_post($service_url,$args);

        if ( is_wp_error($response) ) {
            print_r($response->get_error_message());
            die();
        }

        return $response;
    }

    public function getInsiteServerAPI($method_url,$body,$headers) {
        global $insiteConfig;

        $service_url = $insiteConfig['insiteAPIServerBase'].'/server/api/'.$method_url;
        $args = array(
            'body' => $body,
            'headers' => $headers
        );

        $response = wp_remote_get($service_url,$args);

        if ( is_wp_error($response) ) {
            print_r($response->get_error_message());
            die();
        }

        return $response;
    }

    public function getSitePages ($sub_pages) {
        die(json_encode($this->getRecPages()));
    }

    // Recursive function to get wp pages
    public function getRecPages($pages = null, $parentId = 0) {
        if (!$pages && $parentId == 0) {
            $args = array(
                'sort_order' => 'ASC',
                'sort_column' => 'post_title',
                'hierarchical' => 1,
                'exclude' => '',
                'include' => '',
                'meta_key' => '',
                'meta_value' => '',
                'authors' => '',
                'child_of' => 0,
                'parent' => -1,
                'exclude_tree' => '',
                'number' => '',
                'offset' => 0,
                'post_type' => 'page',
                'post_status' => 'publish'
            );

            $pages = get_pages($args);
        }

        $clientPages = array();

        foreach ($pages as $wpPage) {
            if ($wpPage->post_parent != $parentId) {
                continue;
            }

            $page = new stdClass();
            $page->id = $wpPage->ID;
            $page->is_home = $wpPage->ID == get_option('page_on_front');
            $page->name = $page->is_home ? '__home__' : $wpPage->post_name;
            $page->title = $wpPage->post_title;
            $page->parent = $wpPage->post_parent;
            $page->url = get_page_link($wpPage->ID, false);

            // check children
            if ($wpPage->ID !== 0) {
                $sub_pages = get_pages(array('parent' => $wpPage->ID));

                if ($sub_pages) {
                    $page->children = $this->getRecPages($sub_pages, $wpPage->ID);
                }
            }

            $clientPages[] = $page;
        }

        return $clientPages;
    }

    /**
     * Add a new shortcode to the options table
     * example for calling it: jQuery.post(ajaxurl, {"action": 'insite_update_shortcode','shortcode_id': 1234, 'shortcode_code':'[contact-form-7 id="13" title="Contact form 1"]'}, function(response) {
     *
     *       console.log('Got this from the server: ' + response);
     *   });
     */
    public function addShortCodeWidget() {
        global $wpdb;

        $id = $_POST['shortcode_id'];
        $code = $_POST['shortcode_code'];
        $code = str_replace('\"', '"', $code);
        $shortcode = array ( array ('insite_id' =>$id, 'insite_code'=>$code) , );

        if( !get_option( 'insite_shortcodes' ) ) {
            add_option('insite_shortcodes', $shortcode);
        } else {
            $arrays_shortcodes=get_option( 'insite_shortcodes' );
            foreach ($arrays_shortcodes as $key => $val) {
                if($id == $val['insite_id'])
                {
                    unset($arrays_shortcodes[$key]);
                }
            }

            $marge_array=array_merge($arrays_shortcodes,$shortcode);
            update_option('insite_shortcodes',$marge_array);
        }

        $return = do_shortcode($code);
        die($return);
    }

    /**
     * Remove a shortcode from the options table
     * example for calling it: jQuery.post(ajaxurl, {'action': 'insite_delete_shortcode','shortcode_id': 1234}, function(response) {
     *     console.log('Got this from the server: ' + response);
     *   });
     */
    public function deleteShortCodeWidget() {
        global $wpdb;

        $id = intval( $_POST['shortcode_id'] );
        $arrays_shortcodes = get_option('insite_shortcodes');

        if (!empty($arrays_shortcodes)) {
            foreach ($arrays_shortcodes as $key => $val) {
                if($id == $val['insite_id']) {
                    unset($arrays_shortcodes[$key]);
                }
            }
        }

        update_option('insite_shortcodes',$arrays_shortcodes);

        $return = 'Short code ' . $id . 'was removed';
        die($return);
    }

    /**
     * Clear all shortcodes from the options table
     * example for calling it: jQuery.post(ajaxurl, {'action': 'insite_delete_shortcodes'}, function(response) {
     *      console.log('Got this from the server: ' + response);
     *   });
     */
    public function cleanShortCodes() {
        delete_option('insite_shortcodes');

        $return = get_option( 'insite_shortcodes' );
        die($return);
    }

    /**
     * Clear all in isites
     *
     */
    public function cleanup() {
        delete_option('insite_js_version');
        delete_option('insite_site_id');
        delete_option('insite_api_token');
        delete_option('insite_shortcodes');
        die("cleanup");
    }

   /*
        Call server to connect installation to an existing account,
        given a temporary connect token.
    */
    public function connectAccount () {
        global $insiteConfig;

        $api_token = get_option('insite_api_token');

        $connectToken = $_POST['data']['connectToken'];

        $nextUrl = $_POST['data']['nextUrl'];

        $headers = array(
                'Content-Type' => 'application/json',
                'IO-API-AUTHENTICATE' => $api_token
        );

        $body =  array (
            'connectToken' =>  $connectToken
        );

        $response = $this->postInsiteServerAPI('insite/accounts/switch',json_encode($body),$headers);
        $res = json_decode($response['body']);
        update_option('insite_api_token',$res->apiToken);
        $urlparam = $res->ssoData->url_parameter;
        $ret_sso_token = $urlparam->name.'='. $urlparam->value;
        $nextUrlRet =   $insiteConfig['insiteUIServerBase'].'/server/login?'.$ret_sso_token.'&next='.urlencode($insiteConfig['insiteUIServerBase'].$nextUrl);
        $ret = array (
            'url' => $nextUrlRet
        );

        die(json_encode($ret));

    }

    public function addScriptsAndStyles() {
        wp_enqueue_script( 'insite-admin-crosser', 'https://insite.s3.amazonaws.com/io-plugin/crosser.js' );
        wp_enqueue_style( 'insite-admin', plugins_url( 'assets/css/admin.css', __FILE__ ) );
        wp_enqueue_script( 'insite-admin', plugins_url( 'assets/js/admin.js', __FILE__ ) );

    }

    public static function getPluginVersion() {
        $data = get_plugin_data( plugin_dir_path( __FILE__ ) . 'insite.php' );
        return $data['Version'];
    }

    public function getThemeName() {
        if (function_exists('wp_get_theme')) {
            $theme = wp_get_theme();
            return $theme->Name;
        } else if (function_exists('get_current_theme')) {
            return get_current_theme();
        }

        return null;
    }
}
