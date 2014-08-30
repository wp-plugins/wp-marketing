<?php
/*

Plugin Name: WP Marketing
Plugin URI: http://WPMarketing.guru
Description: WP Marketing is a suite of high-converting tools that help you to engage your visitors, personalize customer connections, and boost your profits.
Version: 1.0.0
Contributors: dallas22ca
Author: Dallas Read
Author URI: http://www.DallasRead.com
Text Domain: wp-marketing
marketing, customer support, customer service, conversions, Call-To-Action, cta, hello bar, mailchimp, aweber, getresponse, subscribe, subscription, newsletter
Requires at least: 3.6
Tested up to: 3.9.2
Stable tag: trunk
License: MIT

Copyright (c) 2014 Dallas Read.

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.
*/

/*
ini_set("display_errors",1);
ini_set("display_startup_errors",1);
error_reporting(-1);
*/

class WPMarketing {
  public static $wpmarketing_instance;
	const version = "1.0.0";
	const db = 1.0;
	const debug = false;

  public static function init() {
    if ( is_null( self::$wpmarketing_instance ) ) { self::$wpmarketing_instance = new WPMarketing(); }
    return self::$wpmarketing_instance;
  }

  private function __construct() {
		define("WPMARKETING_ROOT", dirname(__FILE__));
		
    add_action( "admin_menu", array( $this, "menu_page" ) );
		add_action( "admin_init", array( $this, "admin_init" ) );
		add_action( "plugins_loaded", array( $this, "db_check" ) );
		add_action( "wp_enqueue_scripts", array( $this, "wp_enqueue_scripts") );
		add_action( "wp_head", array( $this, "wp_head" ) );
		
		add_action( "wp_ajax_mailchimp_lists", array( $this, "mailchimp_lists" ) );
		add_action( "wp_ajax_settings_update", array( $this, "settings_update" ) );
		add_action( "wp_ajax_unlock", array( $this, "unlock" ) );
		
		add_action( "wp_ajax_cta_update", array( $this, "cta_update" ) );
		add_action( "wp_ajax_cta_delete", array( $this, "cta_delete" ) );
		
		add_action( "wp_ajax_ctas_fetch_all", array( $this, "ctas_fetch_all" ) );
		add_action( "wp_ajax_cta_submit", array( $this, "cta_submit" ) );
		add_action( "wp_ajax_cta_fetch_responses", array( $this, "cta_fetch_responses" ) );
		
		add_action( "wp_ajax_nopriv_ctas_fetch_all", array( $this, "ctas_fetch_all" ) );
		add_action( "wp_ajax_nopriv_cta_submit", array( $this, "cta_submit" ) );
		add_action( "wp_ajax_nopriv_cta_fetch_responses", array( $this, "cta_fetch_responses" ) );
		
		register_activation_hook( __FILE__, array( $this, "db_check" ) );
    register_uninstall_hook( __FILE__, array( $this, "uninstall" ) );
  }
	
	public static function admin_init() {
		wp_register_style( "wpmarketing_admin", plugins_url("admin/css/style.min.css", __FILE__) );
		wp_enqueue_style( array( "wpmarketing_admin", "wp-color-picker" ) );

		if (WPMarketing::debug) {
			wp_register_script( "wpmarketing_admin", plugins_url("admin/js/script.js", __FILE__) );
			wp_register_script( "wpmarketing_handlebars", plugins_url("admin/js/vendor/handlebars.js", __FILE__) );
			wp_register_script( "wpmarketing_swag", plugins_url("admin/js/vendor/swag.min.js", __FILE__) );
			wp_register_script( "wpmarketing_serialize_json", plugins_url("admin/js/vendor/jquery.serializejson.min.js", __FILE__) );
			wp_enqueue_script( array( "jquery", "jquery-ui-sortable", "jquery-ui-datepicker", "wp-color-picker", "wpmarketing_swag", "wpmarketing_handlebars", "wpmarketing_serialize_json", "wpmarketing_admin" ) );
		} else {
			wp_register_script( "wpmarketing_admin", plugins_url("admin/js/script.min.js", __FILE__) );
			wp_enqueue_script( array( "jquery", "jquery-ui-sortable", "jquery-ui-datepicker", "wp-color-picker", "wpmarketing_admin" ) );
		}
	}
	
	public static function wp_enqueue_scripts() {
		wp_register_style( "ctajs", plugins_url("public/css/cta.min.css", __FILE__) );
		wp_register_style( "wpmarketing_frontend", plugins_url("public/css/style.min.css", __FILE__) );
		wp_enqueue_style( array( "wpmarketing_frontend", "ctajs" ) );
		
		wp_register_script( "wpmarketing_frontend", plugins_url("public/js/script.min.js", __FILE__) );
		wp_register_script( "ctajs", plugins_url("public/js/cta.min.js", __FILE__) );
		wp_enqueue_script( array( "jquery", "ctajs", "wpmarketing_frontend" ) );
	}
	
	public static function wp_head() {
		echo "<script type=\"text/javascript\">this.WPMW || (this.WPMW = {});
			WPMW.ajaxurl = '" . admin_url("admin-ajax.php") . "';
			WPMW.loader = '" . plugins_url("public/imgs/loading.gif", __FILE__) . "';
		</script>";
		
		if (WPMarketing::debug) {
			echo "<script type=\"text/javascript\">this.CTA || (this.CTA = []);CTA.push([\"debug\", true]);</script>";
		}
	}
  
  public static function menu_page() {
    add_menu_page( "WP Marketing", "Marketing", 7, "wpmarketing", array("WPMarketing", "admin_panel"), "dashicons-editor-expand", 25 );
  }
	
  public static function admin_panel() {
		global $wpdb;
		global $wpmarketing;
		global $just_activated;
		global $ctas_table;
		
    WPMarketing::parse_params();
    $wpmarketing = WPMarketing::settings();
		$ctas_table = $wpdb->prefix . "wpmarketing_ctas";
		
		if ($wpmarketing["activated"]) {
			require_once "admin/php/structure.php";
		} else {
			require_once "admin/php/activate.php";
		}
		
		echo "<script type=\"text/javascript\">this.WPMW || (this.WPMW = {}); WPMW.settings = " . json_encode($wpmarketing) . ";</script>";
  }
	
	public static function wp_footer() {
		global $wpmarketing;
		$wpmarketing = WPMarketing::settings();
		
		if (array_key_exists("convert_alert_status", $wpmarketing) && $wpmarketing["convert_alert_status"] == "on") {
			require_once WPMARKETING_ROOT . "/public/php/apps/convert_alert.php";
		}
	}
	
	public static function db_check() {
		global $wpdb;
		$ctas_table = $wpdb->prefix . "wpmarketing_ctas";
		$events_table = $wpdb->prefix . "wpmarketing_events";
		$visitors_table = $wpdb->prefix . "wpmarketing_visitors";
		
		if (get_option("wpmarketing_db_version") != WPMarketing::db) {
			$charset_collate = '';
			if ( ! empty( $wpdb->charset ) ) { $charset_collate = "DEFAULT CHARACTER SET {$wpdb->charset}"; }
			if ( ! empty( $wpdb->collate ) ) { $charset_collate .= " COLLATE {$wpdb->collate}"; }
			
			$ctas = "CREATE TABLE " . $ctas_table . " (
				id mediumint(9) NOT NULL AUTO_INCREMENT,
				name varchar(150) NOT NULL,
				data text NOT NULL,
				created_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
				updated_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
				PRIMARY KEY  (id)
			) $charset_collate;";
			
			$events = "CREATE TABLE " . $events_table . " (
				id mediumint(9) NOT NULL AUTO_INCREMENT,
				verb varchar(50) NOT NULL,
				description varchar(255),
				cta_id mediumint(9) NOT NULL,
				visitor_id mediumint(9) NOT NULL,
				data text NOT NULL,
				created_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
				PRIMARY KEY  (id),
				INDEX verb_idx (verb),
				INDEX visitor_idx (visitor_id),
				INDEX cta_idx (cta_id)
			) $charset_collate;";
			
			$visitors = "CREATE TABLE " . $visitors_table . " (
				id mediumint(9) NOT NULL AUTO_INCREMENT,
				user_id mediumint(9) NOT NULL,
				email varchar(155) NOT NULL,
				data text NOT NULL,
				created_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
				updated_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
				PRIMARY KEY  (id),
				INDEX user_idx (user_id),
				INDEX email_idx (email)
			) $charset_collate;";
    
			require_once( ABSPATH . "wp-admin/includes/upgrade.php" );
			dbDelta( $ctas );
			dbDelta( $events );
			dbDelta( $visitors );
			update_option( "wpmarketing_db_version", WPMarketing::db );
		}
	}
  
  public static function uninstall() {
		// global $wpdb;
		// 
		// $ctas_table = $wpdb->prefix . "wpmarketing_ctas";
		// $events_table = $wpdb->prefix . "wpmarketing_events";
		// $visitors_table = $wpdb->prefix . "wpmarketing_visitors";
		// 
		// $wpdb->query("DROP TABLE IF EXISTS $ctas_table");
  	// $wpdb->query("DROP TABLE IF EXISTS $events_table");
  	// $wpdb->query("DROP TABLE IF EXISTS $visitors_table");
    // 
		// delete_option("wpmarketing_settings");
  }
  
  public static function settings($update = array()) {
		global $wpmarketing;
		
    if (empty($wpmarketing) || !empty($update)) {
			$settings = get_option("wpmarketing_settings");
			if ($settings == null) { $settings = array(); }
			
	    $defaults = array(
	      "version" => WPMarketing::version,
	      "db_version" => 0,
				"website" => $_SERVER["SERVER_NAME"],
	      "unlock_code" => "",
	      "subscriber_name" => "",
	      "subscriber_email" => "",
				"trial_end_at" => 0
	    );
			
			if (!empty($update) || $wpmarketing != $settings) {
				$wpmarketing = array_merge($defaults, $settings);
				$wpmarketing = array_merge($wpmarketing, $update);
				update_option("wpmarketing_settings", $wpmarketing);
			}
			
			$wpmarketing["activated"] = !(!isset($wpmarketing["subscriber_email"]) || $wpmarketing["subscriber_email"] == "");
			
			if (isset($wpmarketing["unlock_code"]) && $wpmarketing["unlock_code"] != "") {
				$wpmarketing["status"] = "unlocked";
			} else if (isset($wpmarketing["trial_end_at"]) && $wpmarketing["trial_end_at"] > time()) {
				$wpmarketing["status"] = "trialing";
			} else {
				$wpmarketing["status"] = "locked";
			}
		}
				
    return $wpmarketing;
  }
	
	public static function unlock() {
		$data = array( "success" => false );
		
    if (isset($_POST["unlock_code"])) {
			$unlock_code = trim($_POST["unlock_code"]);
      $request = new WP_Http;
      $result = $request->request("http://wpmarketing.guru/activation/unlock.php?unlock_code=" . $unlock_code);
      $response = json_decode($result["body"]);
			
			if ($response->success == 1) {
        $data = WPMarketing::settings( array( "unlock_code" => $unlock_code ) );
				$data["success"] = true;
      }
		}
		
    die(json_encode($data));
	}
  
  public static function parse_params() {
		global $wpmarketing;
		global $just_activated;
		
    if (isset($_POST["email"]) && is_email($_POST["email"]) && isset($_POST["name"])) {
			WPMarketing::settings( array(
				"subscriber_name" => trim($_POST["name"]),
				"subscriber_email" => sanitize_email(trim($_POST["email"]))
			) );
			$just_activated = true;
		}
  }
	
	public static function start_free_trial() {
		global $wpmarketing;
		$data = array( "success" => false );
		
    if ($wpmarketing["trial_end_at"] == 0) {
      $data = WPMarketing::settings( array( "trial_end_at" => strtotime("+7 day") ) );
			$data["success"] = true;
		}
		
    die(json_encode($data));
	}
	
	public static function remote_ip() {
		if (!empty($_SERVER["HTTP_CLIENT_IP"])) {
			$remote_ip = $_SERVER["HTTP_CLIENT_IP"];
		} elseif (!empty($_SERVER["HTTP_X_FORWARDED_FOR"])) {
			$remote_ip = $_SERVER["HTTP_X_FORWARDED_FOR"];
		} else {
			$remote_ip = $_SERVER["REMOTE_ADDR"];
		}
		if (inet_pton($remote_ip) === false) { $remote_ip = "0.0.0.0"; }
		return $remote_ip;
	}
	
	public static function request_path() {
	  $url = @( $_SERVER["HTTPS"] != 'on' ) ? 'http://' . $_SERVER["SERVER_NAME"] : 'https://' . $_SERVER["SERVER_NAME"];
	  $url .= ( $_SERVER["SERVER_PORT"] !== 80 ) ? ":" . $_SERVER["SERVER_PORT"] : "";
	  $url .= $_SERVER["REQUEST_URI"];
	  return $url;
	}
	
	public static function random_token() {
		return md5(uniqid(rand() * rand(), true));
	}
	
	public static function addslashes_deep($value) { 
		if ( is_array($value) ) { 
    	$value = array_map('addslashes_deep', $value); 
		} elseif ( is_object($value) ) { 
    	$vars = get_object_vars( $value ); 
    	foreach ($vars as $key=>$data) { 
				$value->{$key} = addslashes_deep( $data ); 
			}
		} else { 
	    $value = addslashes($value); 
		}
		
		return $value; 
	} 
	
	/*
		CTAS
	*/
	
	public static function cta_update() {
		global $wpdb;
		$ctas_table = $wpdb->prefix . "wpmarketing_ctas";
		$cta = array();
		unset($_POST["action"]);
		
		if (isset($_POST["data"]["name"])) {
			$name = sanitize_text_field($_POST["data"]["name"]);
			unset($_POST["data"]["name"]);
	
			$cta["name"] = $name;
			$cta["data"] = addslashes(json_encode($_POST));
      $cta["created_at"] = current_time("mysql");
      $cta["updated_at"] = current_time("mysql");
			
			if ($_POST["data"]["id"] != "") {
				$cta["id"] = $_POST["data"]["id"];
				$response = $wpdb->update( $ctas_table, $cta, array( "id" => $cta["id"]) );
			} else {
				$response = $wpdb->insert( $ctas_table, $cta );
				$cta["id"] = $wpdb->insert_id;
				$cta["new"] = true;
			}
		}
		
		$cta["name"] = stripslashes($cta["name"]);
		$cta["data"] = stripslashes_deep(json_decode(stripslashes_deep($cta["data"]))->data);
		$cta["success"] = isset($response);
		$cta["errors"] = $wpdb->last_error;
		die(json_encode($cta));
	}
	
	public static function ctas_fetch_all() {
		global $wpdb;
		$ctas_table = $wpdb->prefix . "wpmarketing_ctas";
		$ctas = $wpdb->get_results("SELECT * FROM $ctas_table ORDER BY updated_at DESC", ARRAY_A);
		
		foreach ($ctas as $key => $cta) {
			$ctas[$key]["data"] = stripslashes_deep(json_decode(stripslashes_deep($cta["data"]))->data);
			$ctas[$key]["name"] = stripslashes($ctas[$key]["name"]);
		}
		
		die(json_encode($ctas));
	}
	
	public static function cta_delete() {
		global $wpdb;
		$ctas_table = $wpdb->prefix . "wpmarketing_ctas";
		$data = array( "id" => $_POST["id"] );
		
		$response = $wpdb->delete( $ctas_table, $data );
		$data["success"] = $response != 0;
		
		die(json_encode($data));
	}
	
	public static function cta_submit() {
		global $wpmarketing;
		global $wpdb;
		$ctas_table = $wpdb->prefix . "wpmarketing_ctas";
		$events_table = $wpdb->prefix . "wpmarketing_events";
		$visitors_table = $wpdb->prefix . "wpmarketing_visitors";
		
		$submit = array(
			"verb" => "submit",
			"description" => "{{ contact.name }} submitted <a href=\"#!/ctas/{{ cta_id }}\">{{ cta_name }}</a> on <a href=\"{{ page.url }}\">{{ page.title }}</a>",
			"cta_id" => $_POST["cta_id"],
			"data" => isset($_POST["data"]) ? $_POST["data"] : array(),
			"created_at" => current_time("mysql")
		);
		
		if (isset($submit["data"]["email"])) { $email = $submit["data"]["email"]; }
		$cta = $wpdb->get_row("SELECT * FROM $ctas_table WHERE id = $submit[cta_id] LIMIT 1", ARRAY_A);
		
		if ($cta != null) {
			$submit["data"]["cta_name"] = $cta["name"];
			$current_user_id = get_current_user_id();
			$visitor = null;
			
			if ($current_user_id != 0) {
				$visitor = $wpdb->get_row("SELECT * FROM $visitors_table WHERE user_id = $current_user_id LIMIT 1", ARRAY_A);	
			}
			
			if ($visitor == null && isset($email)) {
				$visitor = $wpdb->get_row("SELECT * FROM $visitors_table WHERE email = '$email' LIMIT 1", ARRAY_A);	
			}
			
			if ($visitor == null) {
				$visitor_data_object = $submit["data"];
				unset($visitor_data_object["action"]);
				unset($visitor_data_object["redirect"]);
				$visitor = array(
					"user_id" => $current_user_id,
					"data" => addslashes(json_encode($visitor_data_object)),
					"created_at" => current_time("mysql"),
					"updated_at" => current_time("mysql")
				);
				if (isset($email)) { $visitor["email"] = $email; }
				
				if (isset($submit["data"])) {
					$wpdb->insert( $visitors_table, $visitor );
					$visitor["id"] = $wpdb->insert_id;
				}
			} else {
				$visitor["data"] = json_decode(stripslashes_deep($visitor["data"]), true);
				$visitor_data_object = array_merge($visitor["data"], $submit["data"]);
				unset($visitor_data_object["action"]);
				unset($visitor_data_object["redirect"]);
				$update = array( 
					"data" => addslashes(json_encode($visitor_data_object)),
					"updated_at" => current_time("mysql")
				);
				if (isset($email)) { $visitor["email"] = $email; $update["email"] = $email; }
				$wpdb->update( $visitors_table, $update, array( "id" => $visitor["id"] ) );
			}
			
			$submit["visitor_id"] = $visitor["id"];
			$submit["data"] = addslashes(json_encode($submit["data"]));
			$response = $wpdb->insert( $events_table, $submit );
			$submit["success"] = $response != false;
			
			if ($submit["success"]) {
				$submit["data"] = json_decode(stripslashes_deep($submit["data"]), true);
				$submit["cta"] = json_decode(stripslashes_deep($cta["data"]), true);
				$visitor["data"] = $visitor_data_object;
				$submit["visitor"] = $visitor;
				
				if (isset($email)) { //&& $wpmarketing["status"] == "unlocked"
					$submit["sync"] = array();
					
					if (isset($submit["cta"]["data"]["sync"]["mailchimp"]["list_id"])) {
						$mailchimp_list_id = $submit["cta"]["data"]["sync"]["mailchimp"]["list_id"];
						
						if ($mailchimp_list_id != "") {
							$submit["sync"]["mailchimp"] = WPMarketing::mailchimp_subscribe($visitor["data"], $mailchimp_list_id);
						}
					}
					
					if (isset($submit["cta"]["data"]["sync"]["aweber"]["list_id"])) {
						$aweber_list_id = $submit["cta"]["data"]["sync"]["aweber"]["list_id"];
						
						if ($aweber_list_id != "") {
							$submit["sync"]["aweber"] = WPMarketing::aweber_subscribe($visitor["data"], $aweber_list_id);
						}
					}
				}
			}
		}
		
		$submit["success"] = $response != false;
		if (!$submit["success"]) { $submit["error"] = $wpdb->last_error; }
		
		die(json_encode($submit));
	}
	
	public static function cta_fetch_responses() {
		global $wpdb;
		$events_table = $wpdb->prefix . "wpmarketing_events";
		$visitors_table = $wpdb->prefix . "wpmarketing_visitors";
		
		$data = array();
		$data["cta_id"] = $_POST["cta_id"];
		$data["start"] = date("Y-m-d H:i:s", strtotime($_POST["start"]));
		$data["finish"] = date("Y-m-d H:i:s", strtotime($_POST["finish"] . "+1 days"));
		
		$data["cta_sql"] = $wpdb->prepare(
			"SELECT * FROM $events_table WHERE verb = %s and cta_id = %s and created_at >= %s and created_at <= %s ORDER BY created_at DESC",
			"submit",
			$data["cta_id"],
			$data["start"],
			$data["finish"]
		);
		
		$data["responses"] = $wpdb->get_results( $data["cta_sql"], ARRAY_A);
		
		$data["visitor_ids"] = array_map(function($r) {
			return $r["visitor_id"];
		}, $data["responses"]);
		
		$data["visitors_sql"] = $wpdb->prepare("SELECT * FROM $visitors_table WHERE id IN(%s)", $data["visitor_ids"]);
		$data["visitors"] = $wpdb->get_results( $data["visitors_sql"], OBJECT_K);
		
		foreach ($data["responses"] as $key => $response) {
			$visitor = $data["visitors"][$response["visitor_id"]];
			$data["responses"][$key]["data"] = json_decode(stripslashes_deep($response["data"]));
			$data["responses"][$key]["visitor"] = json_decode(stripslashes_deep($visitor->data));
		}
		
		$data["count"] = count($data["responses"]);
		
		die(json_encode($data));
	}
	
	public static function aweber_subscribe($visitor, $list_id) {
		global $wpmarketing;
		$wpmarketing = WPMarketing::settings();
		$url = "http://www.aweber.com/scripts/addlead.pl";
		$data = array(
			"user-agent" => "WPMarketing Plugin",
			"timeout" => 10,
			"sslverify" => false,
			"headers" => array(
				"Content-Type" => "application/json"
			),
			"body" => array(
				"listname" => $list_id,
				"redirect" => $_SERVER["SERVER_NAME"],
				"meta_message" => "1",
				"meta_required" => "email",
				"submit" => "Subscribe"
			)
		);
		
		if (isset($visitor["name"])) { $data["body"]["name"] = $visitor["name"]; }
		if (isset($visitor["email"])) { $data["body"]["email"] = $visitor["email"]; }
		if (isset($visitor["first_name"])) { $data["body"]["custom first_name"] = $visitor["first_name"]; }
		if (isset($visitor["last_name"])) { $data["body"]["custom last_name"] = $visitor["last_name"]; }
		if (!isset($data["body"]["name"]) && isset($visitor["first_name"])) {
			$data["body"]["name"] = $visitor["first_name"];
			if (isset($visitor["last_name"])) { $data["body"]["name"] += " $visitor[last_name]"; }
		}
		
		$response = wp_remote_post($url, $data);
		
		if (WPMarketing::debug) {
			return $response;
		} else {
			return !is_wp_error($response);
		}
	}
	
	public static function mailchimp_subscribe($visitor, $list_id) {
		$post = array(
			"id" => $list_id,
			"email" => array( "email" => $visitor["email"] ),
			"send_welcome" => false,
			"email_type" => "html",
			"update_existing" => true,
			"replace_interests" => false,
			"double_optin" => false,
			"merge_vars" => array()
		);
		
		if (isset($visitor["email"])) { $post["merge_vars"]["EMAIL"] = $visitor["email"]; }
		if (isset($visitor["name"])) {
			$name = explode(" ", $visitor["name"]);
			$post["merge_vars"]["FNAME"] = $name[0];
			if (isset($name[1])) { $post["merge_vars"]["LNAME"] = $name[1]; }
		}
		if (isset($visitor["first_name"])) { $post["merge_vars"]["FNAME"] = $visitor["first_name"]; }
		if (isset($visitor["last_name"])) { $post["merge_vars"]["LNAME"] = $visitor["last_name"]; }
		if (isset($visitor["mobile"])) { $post["merge_vars"]["PHONE"] = $visitor["mobile"]; }
		
		return WPMarketing::mailchimp("lists/subscribe", $post);
	}
	
	public static function mailchimp_lists() {
		global $wpmarketing;
		$wpmarketing = WPMarketing::settings();
		$data = array( "success" => false );
		
		if (isset($wpmarketing["sync"]["mailchimp"]["api_key"]) && $wpmarketing["sync"]["mailchimp"]["api_key"] != "") {
			$response = WPMarketing::mailchimp("lists/list");
			$data["response"] = json_decode($response["body"]);
		
			if ($response["response"]["code"] == 200) {
				$data["success"] = true;
				$data["lists"] = $data["response"]->data;
			}
		} else {
			$data["success"] = true;
			$data["lists"] = array();
		}
		
		die(json_encode($data));
	}
	
	public static function mailchimp($path, $post = array()) {
		global $wpmarketing;
		$wpmarketing = WPMarketing::settings();
		
		if (isset($wpmarketing["sync"]["mailchimp"]["api_key"]) && $wpmarketing["sync"]["mailchimp"]["api_key"] != "") {
			$api_key = $wpmarketing["sync"]["mailchimp"]["api_key"];
			$data_center = explode("-", $api_key)[1];
			$api = "https://$data_center.api.mailchimp.com/2.0";
			$full_path = "$path.json";
			$url = "$api/$full_path";
			$post["apikey"] = $api_key;
		
			$data = array(
				"user-agent" => "WPMarketing Plugin",
				"timeout" => 10,
				"sslverify" => false,
				"headers" => array(
					"Content-Type" => "application/json"
				),
				"body" => json_encode($post)
			);
		
			$response = wp_remote_post($url, $data);
		} else {
			$response = array( "response" => array( "code" => 0 ));
		}

		return $response;
	}
	
	public static function settings_update() {
		$data = array( "success" => false );
    $data = WPMarketing::settings( $_POST["data"] );
		$data["success"] = true;
    die(json_encode($data));
	}
}

//delete_option("wpmarketing_settings");
WPMarketing::init();
	
?>
