<?php
/*
Plugin Name: Profiler QuickTally
Plugin URI: http://mediarealm.com.au/
Description: Display your Profiler LIVE Donations Tally on your website with easy to use shortcodes.
Version: 1.0.2
Author: Media Realm
Author URI: http://www.mediarealm.com.au/
*/

class PFQuickTally {
    
    private $settings = array();
    private $settings_optionname = "pfquicktally";
    private $settings_default = array(
        "xmlfeeds_recached" => 0,
        
        "basicxmlfeed_url" => "",
        "fullrapidxmlfeed_url" => "",
        "thanksxmlfeed_url" => "",
        "basicxmlfeed_data" => array(),
        "fullrapidxmlfeed_data" => array(),
        "thanksxmlfeed_data" => array(),

        "basicxmlfeed_fetched" => 0,
        "fullrapidxmlfeed_fetched" => 0,
        "thanksxmlfeed_fetched" => 0,
    );
    
    public function __construct() {
        // Get the data from the DB
        $this->settings = get_option($this->settings_optionname, $this->settings_default);
        
        // Create custom cron schedule
        add_filter('cron_schedules',                  array(&$this, 'cron_schedule'));
        
        // Hook for scheduled caching task
        add_action('pfquicktally_cachexml',           array(&$this, 'cacheXML'));
        
        // In case wp-cron isn't working, we also attempt to re-cache in the footer
        add_action('wp_footer',                       array(&$this, 'cacheLazy'));
        
        // Setup the various short-codes
        add_shortcode('pftally_dollarsgoal',          array(&$this, 'sc_dollarsgoal'));
        add_shortcode('pftally_dollarscurrent',       array(&$this, 'sc_dollarscurrent'));
        add_shortcode('pftally_dollarsremaining',     array(&$this, 'sc_dollarsremaining'));
        add_shortcode('pftally_dollarspercentage',    array(&$this, 'sc_dollarspercentage'));
        add_shortcode('pftally_comments',             array(&$this, 'sc_comments'));
        
        if(is_admin()) {
            // Create settings menu entry
            add_action('admin_menu',                  array(&$this, 'add_admin_menu'));
        }
    }
    
    public function install() {
        // Add scheduled task on plugin activation
        if (!wp_next_scheduled ('pfquicktally_cachexml')) {
            wp_schedule_event(time(), 'every-minute', 'pfquicktally_cachexml');
        }
    }
    
    public function uninstall() {
        // Remove scheduled task on plugin deactivation
        wp_clear_scheduled_hook('pfquicktally_cachexml');
    }
    
    public function cron_schedule($schedules) {
        $schedules['every-minute'] = array(
            'interval' => 60, // 1 minute in seconds
            'display'  => "Every Minute",
        );
        
        return $schedules;
    }
    
    public function updateSettings() {
        update_option($this->settings_optionname, $this->settings, true);
    }
    
    public function add_admin_menu() {
        add_options_page('Profiler QuickTally', 'Profiler QuickTally', 'manage_options', 'pfquicktally', array(&$this, 'admin_settings'));
    }
    
    public function cacheXML() {
        $updated = false;

        if(!empty($this->settings['basicxmlfeed_url'])) {
            $basicxml = file_get_contents($this->settings['basicxmlfeed_url']);
            if($basicxml !== false) {
                $this->settings["basicxmlfeed_data"] = json_decode(json_encode((array)simplexml_load_string($basicxml)), 1);
                $this->settings["basicxmlfeed_fetched"] = time();
                $updated = true;
            }
        }
        
        if(!empty($this->settings['fullrapidxmlfeed_url'])) {
            $fullrapidxml = file_get_contents($this->settings['fullrapidxmlfeed_url']);
            if($fullrapidxml !== false) {
                $this->settings["fullrapidxmlfeed_data"] = json_decode(json_encode((array)simplexml_load_string($fullrapidxml)), 1);
                $this->settings["fullrapidxmlfeed_fetched"] = time();
                $updated = true;
            }
        }
        
        if(!empty($this->settings['thanksxmlfeed_url'])) {
            $thanksxml = file_get_contents($this->settings['thanksxmlfeed_url']);
            if($thanksxml !== false) {
                $this->settings["thanksxmlfeed_data"] = json_decode(json_encode((array)simplexml_load_string($thanksxml)), 1);
                $this->settings["thanksxmlfeed_fetched"] = time();
                $updated = true;
            }
        }

        $this->settings["xmlfeeds_recached"] = time();
        $this->updateSettings();
    }
    
    public function cacheLazy() {
        // Check if we need to recache and then do it
        if($this->settings['xmlfeeds_recached'] <= time() - 70) {
            $this->cacheXML();
        }
    }
    
    public function numberToFloat($number) {
        return floatval(preg_replace('/[^\d.]/', '', $number));
    }
    
    public function numberHandling($inputNum, $atts) {
        // Generic number handling for shortcodes
        
        $a = shortcode_atts( array(
            'friendly' => 'true',
            'nearestdollar' => 'true',
            'dollarsign' => 'true',
         ), $atts );
         
        $val = $this->numberToFloat($inputNum);
        
        if($a['nearestdollar'] == 'true') {
            $decimals = 0;
        } else {
            $decimals = 2;
        }
        
        if($a['friendly'] == 'true') {
            $val = number_format($val, $decimals);
        }
        
        if($a['dollarsign'] == 'true') {
            $val = "$" . $val;
        }
        
        return $val;
        
    }
    
    public function sc_dollarsgoal($atts, $content = null) {
        $val = $this->numberHandling($this->settings['basicxmlfeed_data']['target'], $atts);
        return $val;
    }
    
    public function sc_dollarscurrent($atts, $content = null) {
        $val = $this->numberHandling($this->settings['basicxmlfeed_data']['main_tally'], $atts);
        return $val;
    }
    
    public function sc_dollarsremaining($atts, $content = null) {
        $current = $this->numberToFloat($this->settings['basicxmlfeed_data']['main_tally']);
        $target = $this->numberToFloat($this->settings['basicxmlfeed_data']['target']);
        $remaining = $target - $current;
        
        if($remaining <= 0) {
            $remaining = 0;
        }
        
        $val = $this->numberHandling($remaining, $atts);
        return $val;
    }
    
    public function sc_dollarspercentage($atts, $content = null) {
        
        $a = shortcode_atts( array(
            'round' => '0',
            'percentagesign' => 'true',
         ), $atts );
        
        $current = $this->numberToFloat($this->settings['basicxmlfeed_data']['main_tally']);
        $target = $this->numberToFloat($this->settings['basicxmlfeed_data']['target']);
        
        $percentage = round($current / $target * 100, $a['round']);
        
        if($a['percentagesign'] == "true") {
            $percentage .= "%";
        }
        
        return $percentage;
    }

    public function sc_comments($atts, $content = null) {
        // Outputs one or more comments as HTML
        // It'll also accept comments via a global variable $pfquicktally_comments
        
        global $pfquicktally_comments;

        $a = shortcode_atts( array(
            'limit' => '5',
            'random' => 'true',
         ), $atts );
        
        $comments = array();

        if(isset($pfquicktally_comments) && is_array($pfquicktally_comments)) {
            $this->settings['thanksxmlfeed_data']['client'] = array_merge($this->settings['thanksxmlfeed_data']['client'], $pfquicktally_comments);
        }

        foreach($this->settings['thanksxmlfeed_data']['client'] as $key => $val) {
            if($val['comment'] !== array()) {
                $text = (String)$val['comment'];
            }
            
            if(isset($text) && !empty($text)) {
                $html = '<div class="pfcomment"><span class="pfcomment_text">'.$text.'</span> <span class="pfcomment_details">'.$val['name'].', '.ucwords(strtolower($val['suburb'])).'</span></div>' . "\n";
                if(!in_array($html, $comments)) {
                    $comments[] = $html;
                }
            }

            
        }
        
        if($a['random'] == "true") {
            shuffle($comments);
        }

        $comments = array_slice($comments, 0, $a['limit']);

        return implode("", $comments);

    }
    
    
    
    public function admin_settings() {
        // Render and process the admin setup screen
        
        if(isset($_POST['submit'])) {
            // Save settings
            $this->settings['basicxmlfeed_url'] = $_POST['pf_basicxmlfeed'];
            $this->settings['fullrapidxmlfeed_url'] = $_POST['pf_fullrapidxmlfeed'];
            $this->settings['thanksxmlfeed_url'] = $_POST['pf_thanksxmlfeed'];
            $this->updateSettings();
            $this->cacheXML();
        }
        
        // Render the form
        echo '
        <form action="?page='.$_GET['page'].'" method="POST">
            <h1>Profiler QuickTally</h1>
            <h2>Configuration</h2>';
        
        echo '<table class="cdpapp_module" width="100%">';
        
        echo '<tr><th width="20%">Profiler Basic XML Feed:</th>';
        echo '<td><input name="pf_basicxmlfeed" value="'.$this->settings['basicxmlfeed_url'].'"></td>';
        echo '<td>e.g. https://prod.profiler.net.au/Profiler/se_pf_DATABASENAME.xml</td>';
        echo '</tr>';
        
        echo '<tr><th width="20%">Profiler Full RAPID XML Feed:</th>';
        echo '<td><input name="pf_fullrapidxmlfeed" value="'.$this->settings['fullrapidxmlfeed_url'].'"></td>';
        echo '<td>e.g. https://prod.profiler.net.au/Profiler/rapid_pf_DATABASENAME.xml</td>';
        echo '</tr>';
        
        echo '<tr><th width="20%">Profiler Thanks XML Feed:</th>';
        echo '<td><input name="pf_thanksxmlfeed" value="'.$this->settings['thanksxmlfeed_url'].'"></td>';
        echo '<td>e.g. https://prod.profiler.net.au/Profiler/se_thanks_pf_DATABASENAME.xml</td>';
        echo '</tr>';
        
        echo '</table>';
        
        echo '<input type="submit" class="button button-primary" name="submit" value="Save Settings" style="margin-bottom: 100px;" />';
        echo "</form>";
        
        echo 'Last Attempted Recache: ' . date("Y-m-d H:i:s e", $this->settings["xmlfeeds_recached"]);
        echo 'Last Attempted Recache: ' . date("Y-m-d H:i:s e", $this->settings["xmlfeeds_recached"]) . "<br />";
        echo 'Basic XML Last Fetched: ' . date("Y-m-d H:i:s e", $this->settings["basicxmlfeed_fetched"]) . "<br />";
        echo 'Full RAPID XML Last Fetched: ' . date("Y-m-d H:i:s e", $this->settings["fullrapidxmlfeed_fetched"]) . "<br />";
        echo 'Thanks XML Last Fetched: ' . date("Y-m-d H:i:s e", $this->settings["thanksxmlfeed_fetched"]) . "<br />";


    }
    
    
}

new PFQuickTally();

register_activation_hook( __FILE__,     array('PFQuickTally', 'install'));
register_deactivation_hook(__FILE__,    array('PFQuickTally', 'uninstall'));