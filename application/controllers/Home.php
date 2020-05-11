<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

/**
 * @category controller
 * class home
 */
class Home extends CI_Controller {

	/**
	 * load constructor
	 * @access public
	 * @return void
	 */
	public $module_access;
	public $language;
	public $is_rtl;
	public $user_id;
	public $is_demo;

	public $signuplist_db;

	public $is_ad_enabled;
	public $is_ad_enabled1;
	public $is_ad_enabled2;
	public $is_ad_enabled3;
	public $is_ad_enabled4;

	public $ad_content1;
	public $ad_content1_mobile;
	public $ad_content2;
	public $ad_content3;
	public $ad_content4;
	public $app_product_id;
	public $APP_VERSION;

	public $is_botinboxer_exist = false;
	public $is_broadcaster_exist = false;
	public $is_drip_campaigner_exist = false;
	public $is_messenger_bot_import_export_exist = false;
	public $is_messenger_bot_analytics_exist = false;
	public $is_engagement_exist = false;
	public $is_ultrapost_exist = false;
	public $is_webview_exist = false;

	public function __construct() {
		parent::__construct();
		set_time_limit(0);
		$this->load->helpers(array('my_helper', 'addon_helper'));

		$this->is_rtl = FALSE;

		$is_demo = $this->config->item("is_demo");
		if ($is_demo == "") {
			$is_demo = "0";
		}

		$this->is_demo = $is_demo;

		$this->language = "";
		$this->_language_loader();

		$this->is_ad_enabled = false;
		$this->is_ad_enabled1 = false;
		$this->is_ad_enabled2 = false;
		$this->is_ad_enabled3 = false;
		$this->is_ad_enabled4 = false;

		$this->ad_content1 = "";
		$this->ad_content1_mobile = "";
		$this->ad_content2 = "";
		$this->ad_content3 = "";
		$this->ad_content4 = "";
		$this->app_product_id = 28;
		$this->APP_VERSION = "";

		ignore_user_abort(TRUE);

		$seg = $this->uri->segment(2);
		if ($seg != "installation" && $seg != "installation_action") {
			if (file_exists(APPPATH . 'install.txt')) {
				redirect('home/installation', 'location');
			}
		}

		if (!file_exists(APPPATH . 'install.txt')) {
			$this->signuplist_db = $this->load->database('signuplist', TRUE);

			$this->load->database();
			$this->load->model('basic');
			$this->_time_zone_set();
			$this->user_id = $this->session->userdata("user_id");
			$this->load->library('upload');
			$this->load->helper('security');
			$this->upload_path = realpath(APPPATH . '../upload');
			$this->session->unset_userdata('set_custom_link');
			$query = 'SET SESSION group_concat_max_len=9990000000000000000';
			$this->db->query($query);
			$q = "SET SESSION wait_timeout=50000";
			$this->db->query($q);
			/**Disable STRICT_TRANS_TABLES mode if exist on mysql ***/
			$query = "SET SESSION sql_mode = ''";
			$this->db->query($query);

			/**Change Datbase Collation **/
			$query = "SET NAMES utf8mb4";
			$this->db->query($query);

			//loading addon language
			$this->language_loader_addon();

			if (function_exists('ini_set')) {
				ini_set('memory_limit', '-1');
			}

			$ad_config = $this->basic->get_data("ad_config");
			if (isset($ad_config[0]["status"])) {
				if ($ad_config[0]["status"] == "1") {
					$this->is_ad_enabled = ($ad_config[0]["status"] == "1") ? true : false;
					if ($this->is_ad_enabled) {
						$this->is_ad_enabled1 = ($ad_config[0]["section1_html"] == "" && $ad_config[0]["section1_html_mobile"] == "") ? false : true;
						$this->is_ad_enabled2 = ($ad_config[0]["section2_html"] == "") ? false : true;
						$this->is_ad_enabled3 = ($ad_config[0]["section3_html"] == "") ? false : true;
						$this->is_ad_enabled4 = ($ad_config[0]["section4_html"] == "") ? false : true;

						$this->ad_content1 = htmlspecialchars_decode($ad_config[0]["section1_html"], ENT_QUOTES);
						$this->ad_content1_mobile = htmlspecialchars_decode($ad_config[0]["section1_html_mobile"], ENT_QUOTES);
						$this->ad_content2 = htmlspecialchars_decode($ad_config[0]["section2_html"], ENT_QUOTES);
						$this->ad_content3 = htmlspecialchars_decode($ad_config[0]["section3_html"], ENT_QUOTES);
						$this->ad_content4 = htmlspecialchars_decode($ad_config[0]["section4_html"], ENT_QUOTES);
					}
				}

			} else {
				$this->is_ad_enabled = true;
				$this->is_ad_enabled1 = true;
				$this->is_ad_enabled2 = true;
				$this->is_ad_enabled3 = true;
				$this->is_ad_enabled4 = true;

				$this->ad_content1 = "<img src='" . base_url('assets/images/placeholder/reserved-section-1.png') . "'>";
				$this->ad_content1_mobile = "<img src='" . base_url('assets/images/placeholder/reserved-section-1-mobile.png') . "'>";
				$this->ad_content2 = "<img src='" . base_url('assets/images/placeholder/reserved-section-2.png') . "'>";
				$this->ad_content3 = "<img src='" . base_url('assets/images/placeholder/reserved-section-3.png') . "'>";
				$this->ad_content4 = "<img src='" . base_url('assets/images/placeholder/reserved-section-4.png') . "'>";

			}

			if ($this->session->userdata('logged_in') == 1 && $this->session->userdata('user_type') != 'Admin') {
				$package_info = $this->session->userdata("package_info");
				$module_ids = '';
				if (isset($package_info["module_ids"])) {
					$module_ids = $package_info["module_ids"];
				}

				$this->module_access = explode(',', $module_ids);
			}

			$version_data = $this->basic->get_data("version", array("where" => array("current" => "1")));
			$appversion = isset($version_data[0]['version']) ? $version_data[0]['version'] : "";
			$this->APP_VERSION = $appversion;

			$this->is_botinboxer_exist = $this->botinboxer_exist();
			$this->is_broadcaster_exist = $this->broadcaster_exist();
			$this->is_drip_campaigner_exist = $this->drip_campaigner_exist();
			$this->is_messenger_bot_import_export_exist = $this->messenger_bot_import_export_exist();
			$this->is_messenger_bot_analytics_exist = $this->messenger_bot_analytics_exist();
			$this->is_engagement_exist = $this->engagement_exist();
			$this->is_ultrapost_exist = $this->ultrapost_exist();
			$this->is_webview_exist = $this->webview_exist();
		}

		if ($this->config->item('force_https') == '1') {
			$actualLink = $actualLink = base_url(uri_string());
			$poS = strpos($actualLink, 'http://');
			if ($poS !== FALSE) {
				$new_link = str_replace('http://', 'https://', $actualLink);
				redirect($new_link, 'refresh');
			}
		}

		if ($this->session->userdata('log_me_out') == '1') {
			$this->logout();
		}

	}

	public function _language_loader() {

		if (!$this->config->item("language") || $this->config->item("language") == "") {
			$this->language = "english";
		} else {
			$this->language = $this->config->item('language');
		}

		if ($this->session->userdata("selected_language") != "") {
			$this->language = $this->session->userdata("selected_language");
		} else if (!$this->config->item("language") || $this->config->item("language") == "") {
			$this->language = "english";
		} else {
			$this->language = $this->config->item('language');
		}

		// if($this->language=="arabic")
		// $this->is_rtl=TRUE;

		$path = str_replace('\\', '/', APPPATH . '/language/' . $this->language);
		$files = $this->_scanAll($path);
		foreach ($files as $key2 => $value2) {
			$current_file = isset($value2['file']) ? str_replace('\\', '/', $value2['file']) : ""; //application/modules/addon_folder/language/language_folder/someting_lang.php
			if ($current_file == "" || !is_file($current_file)) {
				continue;
			}

			$current_file_explode = explode('/', $current_file);
			$filename = array_pop($current_file_explode);
			$pos = strpos($filename, '_lang.php');
			if ($pos !== false) // check if it is a lang file or not
			{
				$filename = str_replace('_lang.php', '', $filename);
				$this->lang->load($filename, $this->language);
			}
		}

	}

	public function installation() {
		if (!file_exists(APPPATH . 'install.txt')) {
			redirect('home/login', 'location');
		}
		$data = array("body" => "front/install", "page_title" => "Install Package", "language_info" => $this->_language_list());
		$this->_subscription_viewcontroller($data);
	}

	public function installation_action() {
		if (!file_exists(APPPATH . 'install.txt')) {
			redirect('home/login', 'location');
		}

		if ($_POST) {
			// validation
			$this->form_validation->set_rules('host_name', '<b>Host Name</b>', 'trim|required');
			$this->form_validation->set_rules('database_name', '<b>Database Name</b>', 'trim|required');
			$this->form_validation->set_rules('database_username', '<b>Database Username</b>', 'trim|required');
			$this->form_validation->set_rules('database_password', '<b>Database Password</b>', 'trim');
			$this->form_validation->set_rules('app_username', '<b>Admin Panel Login Email</b>', 'trim|required|valid_email');
			$this->form_validation->set_rules('app_password', '<b>Admin Panel Login Password</b>', 'trim|required');
			$this->form_validation->set_rules('institute_name', '<b>Company Name</b>', 'trim');
			$this->form_validation->set_rules('institute_address', '<b>Company Address</b>', 'trim');
			$this->form_validation->set_rules('institute_mobile', '<b>Company Phone / Mobile</b>', 'trim');
			$this->form_validation->set_rules('language', '<b>Language</b>', 'trim');

			// go to config form page if validation wrong
			if ($this->form_validation->run() == false) {
				return $this->installation();
			} else {
				$host_name = addslashes(strip_tags($this->input->post('host_name', true)));
				$database_name = addslashes(strip_tags($this->input->post('database_name', true)));
				$database_username = addslashes(strip_tags($this->input->post('database_username', true)));
				$database_password = addslashes(strip_tags($this->input->post('database_password', true)));
				$app_username = addslashes(strip_tags($this->input->post('app_username', true)));
				$app_password = addslashes(strip_tags($this->input->post('app_password', true)));
				$institute_name = addslashes(strip_tags($this->input->post('institute_name', true)));
				$institute_address = addslashes(strip_tags($this->input->post('institute_address', true)));
				$institute_mobile = addslashes(strip_tags($this->input->post('institute_mobile', true)));
				$language = addslashes(strip_tags($this->input->post('language', true)));

				$con = @mysqli_connect($host_name, $database_username, $database_password);
				if (!$con) {
					$this->session->set_userdata('mysql_error', "Could not connect to MySQL.");
					return $this->installation();
				}
				if (!@mysqli_select_db($con, $database_name)) {
					$this->session->set_userdata('mysql_error', "Database not found.");
					return $this->installation();
				}
				mysqli_close($con);

				// writing application/config/my_config

				include 'application/config/my_config.php';
				$config['institute_address1'] = $institute_name;
				$config['institute_address2'] = $institute_address;
				$config['institute_email'] = $app_username;
				$config['institute_mobile'] = $institute_mobile;
				$config['language'] = $language;
				file_put_contents('application/config/my_config.php', '<?php $config = ' . var_export($config, true) . ';');

				//writting application/config/database
				$database_data = "";
				$database_data .= "<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');\n
					\$active_group = 'default';
					\$active_record = true;
					\$db['default']['hostname'] = '$host_name';
					\$db['default']['username'] = '$database_username';
					\$db['default']['password'] = '$database_password';
					\$db['default']['database'] = '$database_name';
					\$db['default']['dbdriver'] = 'mysqli';
					\$db['default']['dbprefix'] = '';
					\$db['default']['pconnect'] = FALSE;
					\$db['default']['db_debug'] = TRUE;
					\$db['default']['cache_on'] = FALSE;
					\$db['default']['cachedir'] = '';
					\$db['default']['char_set'] = 'utf8';
					\$db['default']['dbcollat'] = 'utf8_general_ci';
					\$db['default']['swap_pre'] = '';
					\$db['default']['autoinit'] = TRUE;
					\$db['default']['stricton'] = FALSE;";
				file_put_contents(APPPATH . 'config/database.php', $database_data, LOCK_EX);
				//writting application/config/database

				// writting client js
				// $client_js_content=file_get_contents('js/my_chat_custom.js');
				// $client_js_content_new=str_replace("base_url_replace/", site_url(), $client_js_content);
				// file_put_contents('js/my_chat_custom.js', $client_js_content_new, LOCK_EX);
				// writting client js

				// loding database library, because we need to run queries below and configs are already written
				$this->load->database();
				$this->load->model('basic');
				// loding database library, because we need to run queries below and configs are already written

				// dumping sql
				$dump_file_name = 'initial_db.sql';
				$dump_sql_path = 'assets/backup_db/' . $dump_file_name;
				$this->basic->import_dump($dump_sql_path);
				// dumping sql

				// Insert Version
				$this->db->insert('version', array('version' => trim($this->config->item('product_version')), 'current' => '1', 'date' => date('Y-m-d H:i:s')));

				//generating hash password for admin and updaing database
				$app_password = md5($app_password);
				$this->basic->update_data($table = "users", $where = array("user_type" => "Admin"), $update_data = array("mobile" => $institute_mobile, "email" => $app_username, "password" => $app_password, "name" => $institute_name, "status" => "1", "deleted" => "0", "address" => $institute_address));
				//generating hash password for admin and updaing database

				//deleting the install.txt file,because installation is complete
				if (file_exists(APPPATH . 'install.txt')) {
					unlink(APPPATH . 'install.txt');
				}
				//deleting the install.txt file,because installation is complete
				redirect('home/login');
			}
		}
	}

	public function index() {
		$display_landing_page = $this->config->item('display_landing_page');
		if ($display_landing_page == '') {
			$display_landing_page = '0';
		}

		if ($display_landing_page == '0') {
			$this->login_page();
		} else {
			$this->_site_viewcontroller();
			//
		}

	}

	public function _time_zone_set() {
		$time_zone = $this->config->item('time_zone');
		if ($time_zone == '') {
			$time_zone = "Europe/Dublin";
		}
		date_default_timezone_set($time_zone);
	}

	public function _time_zone_list() {
		return $timezones =
		array(
			'America/Adak' => '(GMT-10:00) America/Adak (Hawaii-Aleutian Standard Time)',
			'America/Atka' => '(GMT-10:00) America/Atka (Hawaii-Aleutian Standard Time)',
			'America/Anchorage' => '(GMT-9:00) America/Anchorage (Alaska Standard Time)',
			'America/Juneau' => '(GMT-9:00) America/Juneau (Alaska Standard Time)',
			'America/Nome' => '(GMT-9:00) America/Nome (Alaska Standard Time)',
			'America/Yakutat' => '(GMT-9:00) America/Yakutat (Alaska Standard Time)',
			'America/Dawson' => '(GMT-8:00) America/Dawson (Pacific Standard Time)',
			'America/Ensenada' => '(GMT-8:00) America/Ensenada (Pacific Standard Time)',
			'America/Los_Angeles' => '(GMT-8:00) America/Los_Angeles (Pacific Standard Time)',
			'America/Tijuana' => '(GMT-8:00) America/Tijuana (Pacific Standard Time)',
			'America/Vancouver' => '(GMT-8:00) America/Vancouver (Pacific Standard Time)',
			'America/Whitehorse' => '(GMT-8:00) America/Whitehorse (Pacific Standard Time)',
			'Canada/Pacific' => '(GMT-8:00) Canada/Pacific (Pacific Standard Time)',
			'Canada/Yukon' => '(GMT-8:00) Canada/Yukon (Pacific Standard Time)',
			'Mexico/BajaNorte' => '(GMT-8:00) Mexico/BajaNorte (Pacific Standard Time)',
			'America/Boise' => '(GMT-7:00) America/Boise (Mountain Standard Time)',
			'America/Cambridge_Bay' => '(GMT-7:00) America/Cambridge_Bay (Mountain Standard Time)',
			'America/Chihuahua' => '(GMT-7:00) America/Chihuahua (Mountain Standard Time)',
			'America/Dawson_Creek' => '(GMT-7:00) America/Dawson_Creek (Mountain Standard Time)',
			'America/Denver' => '(GMT-7:00) America/Denver (Mountain Standard Time)',
			'America/Edmonton' => '(GMT-7:00) America/Edmonton (Mountain Standard Time)',
			'America/Hermosillo' => '(GMT-7:00) America/Hermosillo (Mountain Standard Time)',
			'America/Inuvik' => '(GMT-7:00) America/Inuvik (Mountain Standard Time)',
			'America/Mazatlan' => '(GMT-7:00) America/Mazatlan (Mountain Standard Time)',
			'America/Phoenix' => '(GMT-7:00) America/Phoenix (Mountain Standard Time)',
			'America/Shiprock' => '(GMT-7:00) America/Shiprock (Mountain Standard Time)',
			'America/Yellowknife' => '(GMT-7:00) America/Yellowknife (Mountain Standard Time)',
			'Canada/Mountain' => '(GMT-7:00) Canada/Mountain (Mountain Standard Time)',
			'Mexico/BajaSur' => '(GMT-7:00) Mexico/BajaSur (Mountain Standard Time)',
			'America/Belize' => '(GMT-6:00) America/Belize (Central Standard Time)',
			'America/Cancun' => '(GMT-6:00) America/Cancun (Central Standard Time)',
			'America/Chicago' => '(GMT-6:00) America/Chicago (Central Standard Time)',
			'America/Costa_Rica' => '(GMT-6:00) America/Costa_Rica (Central Standard Time)',
			'America/El_Salvador' => '(GMT-6:00) America/El_Salvador (Central Standard Time)',
			'America/Guatemala' => '(GMT-6:00) America/Guatemala (Central Standard Time)',
			'America/Knox_IN' => '(GMT-6:00) America/Knox_IN (Central Standard Time)',
			'America/Managua' => '(GMT-6:00) America/Managua (Central Standard Time)',
			'America/Menominee' => '(GMT-6:00) America/Menominee (Central Standard Time)',
			'America/Merida' => '(GMT-6:00) America/Merida (Central Standard Time)',
			'America/Mexico_City' => '(GMT-6:00) America/Mexico_City (Central Standard Time)',
			'America/Monterrey' => '(GMT-6:00) America/Monterrey (Central Standard Time)',
			'America/Rainy_River' => '(GMT-6:00) America/Rainy_River (Central Standard Time)',
			'America/Rankin_Inlet' => '(GMT-6:00) America/Rankin_Inlet (Central Standard Time)',
			'America/Regina' => '(GMT-6:00) America/Regina (Central Standard Time)',
			'America/Swift_Current' => '(GMT-6:00) America/Swift_Current (Central Standard Time)',
			'America/Tegucigalpa' => '(GMT-6:00) America/Tegucigalpa (Central Standard Time)',
			'America/Winnipeg' => '(GMT-6:00) America/Winnipeg (Central Standard Time)',
			'Canada/Central' => '(GMT-6:00) Canada/Central (Central Standard Time)',
			'Canada/East-Saskatchewan' => '(GMT-6:00) Canada/East-Saskatchewan (Central Standard Time)',
			'Canada/Saskatchewan' => '(GMT-6:00) Canada/Saskatchewan (Central Standard Time)',
			'Chile/EasterIsland' => '(GMT-6:00) Chile/EasterIsland (Easter Is. Time)',
			'Mexico/General' => '(GMT-6:00) Mexico/General (Central Standard Time)',
			'America/Atikokan' => '(GMT-5:00) America/Atikokan (Eastern Standard Time)',
			'America/Bogota' => '(GMT-5:00) America/Bogota (Colombia Time)',
			'America/Cayman' => '(GMT-5:00) America/Cayman (Eastern Standard Time)',
			'America/Coral_Harbour' => '(GMT-5:00) America/Coral_Harbour (Eastern Standard Time)',
			'America/Detroit' => '(GMT-5:00) America/Detroit (Eastern Standard Time)',
			'America/Fort_Wayne' => '(GMT-5:00) America/Fort_Wayne (Eastern Standard Time)',
			'America/Grand_Turk' => '(GMT-5:00) America/Grand_Turk (Eastern Standard Time)',
			'America/Guayaquil' => '(GMT-5:00) America/Guayaquil (Ecuador Time)',
			'America/Havana' => '(GMT-5:00) America/Havana (Cuba Standard Time)',
			'America/Indianapolis' => '(GMT-5:00) America/Indianapolis (Eastern Standard Time)',
			'America/Iqaluit' => '(GMT-5:00) America/Iqaluit (Eastern Standard Time)',
			'America/Jamaica' => '(GMT-5:00) America/Jamaica (Eastern Standard Time)',
			'America/Lima' => '(GMT-5:00) America/Lima (Peru Time)',
			'America/Louisville' => '(GMT-5:00) America/Louisville (Eastern Standard Time)',
			'America/Montreal' => '(GMT-5:00) America/Montreal (Eastern Standard Time)',
			'America/Nassau' => '(GMT-5:00) America/Nassau (Eastern Standard Time)',
			'America/New_York' => '(GMT-5:00) America/New_York (Eastern Standard Time)',
			'America/Nipigon' => '(GMT-5:00) America/Nipigon (Eastern Standard Time)',
			'America/Panama' => '(GMT-5:00) America/Panama (Eastern Standard Time)',
			'America/Pangnirtung' => '(GMT-5:00) America/Pangnirtung (Eastern Standard Time)',
			'America/Port-au-Prince' => '(GMT-5:00) America/Port-au-Prince (Eastern Standard Time)',
			'America/Resolute' => '(GMT-5:00) America/Resolute (Eastern Standard Time)',
			'America/Thunder_Bay' => '(GMT-5:00) America/Thunder_Bay (Eastern Standard Time)',
			'America/Toronto' => '(GMT-5:00) America/Toronto (Eastern Standard Time)',
			'Canada/Eastern' => '(GMT-5:00) Canada/Eastern (Eastern Standard Time)',
			'America/Caracas' => '(GMT-4:-30) America/Caracas (Venezuela Time)',
			'America/Anguilla' => '(GMT-4:00) America/Anguilla (Atlantic Standard Time)',
			'America/Antigua' => '(GMT-4:00) America/Antigua (Atlantic Standard Time)',
			'America/Aruba' => '(GMT-4:00) America/Aruba (Atlantic Standard Time)',
			'America/Asuncion' => '(GMT-4:00) America/Asuncion (Paraguay Time)',
			'America/Barbados' => '(GMT-4:00) America/Barbados (Atlantic Standard Time)',
			'America/Blanc-Sablon' => '(GMT-4:00) America/Blanc-Sablon (Atlantic Standard Time)',
			'America/Boa_Vista' => '(GMT-4:00) America/Boa_Vista (Amazon Time)',
			'America/Campo_Grande' => '(GMT-4:00) America/Campo_Grande (Amazon Time)',
			'America/Cuiaba' => '(GMT-4:00) America/Cuiaba (Amazon Time)',
			'America/Curacao' => '(GMT-4:00) America/Curacao (Atlantic Standard Time)',
			'America/Dominica' => '(GMT-4:00) America/Dominica (Atlantic Standard Time)',
			'America/Eirunepe' => '(GMT-4:00) America/Eirunepe (Amazon Time)',
			'America/Glace_Bay' => '(GMT-4:00) America/Glace_Bay (Atlantic Standard Time)',
			'America/Goose_Bay' => '(GMT-4:00) America/Goose_Bay (Atlantic Standard Time)',
			'America/Grenada' => '(GMT-4:00) America/Grenada (Atlantic Standard Time)',
			'America/Guadeloupe' => '(GMT-4:00) America/Guadeloupe (Atlantic Standard Time)',
			'America/Guyana' => '(GMT-4:00) America/Guyana (Guyana Time)',
			'America/Halifax' => '(GMT-4:00) America/Halifax (Atlantic Standard Time)',
			'America/La_Paz' => '(GMT-4:00) America/La_Paz (Bolivia Time)',
			'America/Manaus' => '(GMT-4:00) America/Manaus (Amazon Time)',
			'America/Marigot' => '(GMT-4:00) America/Marigot (Atlantic Standard Time)',
			'America/Martinique' => '(GMT-4:00) America/Martinique (Atlantic Standard Time)',
			'America/Moncton' => '(GMT-4:00) America/Moncton (Atlantic Standard Time)',
			'America/Montserrat' => '(GMT-4:00) America/Montserrat (Atlantic Standard Time)',
			'America/Port_of_Spain' => '(GMT-4:00) America/Port_of_Spain (Atlantic Standard Time)',
			'America/Porto_Acre' => '(GMT-4:00) America/Porto_Acre (Amazon Time)',
			'America/Porto_Velho' => '(GMT-4:00) America/Porto_Velho (Amazon Time)',
			'America/Puerto_Rico' => '(GMT-4:00) America/Puerto_Rico (Atlantic Standard Time)',
			'America/Rio_Branco' => '(GMT-4:00) America/Rio_Branco (Amazon Time)',
			'America/Santiago' => '(GMT-4:00) America/Santiago (Chile Time)',
			'America/Santo_Domingo' => '(GMT-4:00) America/Santo_Domingo (Atlantic Standard Time)',
			'America/St_Barthelemy' => '(GMT-4:00) America/St_Barthelemy (Atlantic Standard Time)',
			'America/St_Kitts' => '(GMT-4:00) America/St_Kitts (Atlantic Standard Time)',
			'America/St_Lucia' => '(GMT-4:00) America/St_Lucia (Atlantic Standard Time)',
			'America/St_Thomas' => '(GMT-4:00) America/St_Thomas (Atlantic Standard Time)',
			'America/St_Vincent' => '(GMT-4:00) America/St_Vincent (Atlantic Standard Time)',
			'America/Thule' => '(GMT-4:00) America/Thule (Atlantic Standard Time)',
			'America/Tortola' => '(GMT-4:00) America/Tortola (Atlantic Standard Time)',
			'America/Virgin' => '(GMT-4:00) America/Virgin (Atlantic Standard Time)',
			'Antarctica/Palmer' => '(GMT-4:00) Antarctica/Palmer (Chile Time)',
			'Atlantic/Bermuda' => '(GMT-4:00) Atlantic/Bermuda (Atlantic Standard Time)',
			'Atlantic/Stanley' => '(GMT-4:00) Atlantic/Stanley (Falkland Is. Time)',
			'Brazil/Acre' => '(GMT-4:00) Brazil/Acre (Amazon Time)',
			'Brazil/West' => '(GMT-4:00) Brazil/West (Amazon Time)',
			'Canada/Atlantic' => '(GMT-4:00) Canada/Atlantic (Atlantic Standard Time)',
			'Chile/Continental' => '(GMT-4:00) Chile/Continental (Chile Time)',
			'America/St_Johns' => '(GMT-3:-30) America/St_Johns (Newfoundland Standard Time)',
			'Canada/Newfoundland' => '(GMT-3:-30) Canada/Newfoundland (Newfoundland Standard Time)',
			'America/Araguaina' => '(GMT-3:00) America/Araguaina (Brasilia Time)',
			'America/Bahia' => '(GMT-3:00) America/Bahia (Brasilia Time)',
			'America/Belem' => '(GMT-3:00) America/Belem (Brasilia Time)',
			'America/Buenos_Aires' => '(GMT-3:00) America/Buenos_Aires (Argentine Time)',
			'America/Catamarca' => '(GMT-3:00) America/Catamarca (Argentine Time)',
			'America/Cayenne' => '(GMT-3:00) America/Cayenne (French Guiana Time)',
			'America/Cordoba' => '(GMT-3:00) America/Cordoba (Argentine Time)',
			'America/Fortaleza' => '(GMT-3:00) America/Fortaleza (Brasilia Time)',
			'America/Godthab' => '(GMT-3:00) America/Godthab (Western Greenland Time)',
			'America/Jujuy' => '(GMT-3:00) America/Jujuy (Argentine Time)',
			'America/Maceio' => '(GMT-3:00) America/Maceio (Brasilia Time)',
			'America/Mendoza' => '(GMT-3:00) America/Mendoza (Argentine Time)',
			'America/Miquelon' => '(GMT-3:00) America/Miquelon (Pierre & Miquelon Standard Time)',
			'America/Montevideo' => '(GMT-3:00) America/Montevideo (Uruguay Time)',
			'America/Paramaribo' => '(GMT-3:00) America/Paramaribo (Suriname Time)',
			'America/Recife' => '(GMT-3:00) America/Recife (Brasilia Time)',
			'America/Rosario' => '(GMT-3:00) America/Rosario (Argentine Time)',
			'America/Santarem' => '(GMT-3:00) America/Santarem (Brasilia Time)',
			'America/Sao_Paulo' => '(GMT-3:00) America/Sao_Paulo (Brasilia Time)',
			'Antarctica/Rothera' => '(GMT-3:00) Antarctica/Rothera (Rothera Time)',
			'Brazil/East' => '(GMT-3:00) Brazil/East (Brasilia Time)',
			'America/Noronha' => '(GMT-2:00) America/Noronha (Fernando de Noronha Time)',
			'Atlantic/South_Georgia' => '(GMT-2:00) Atlantic/South_Georgia (South Georgia Standard Time)',
			'Brazil/DeNoronha' => '(GMT-2:00) Brazil/DeNoronha (Fernando de Noronha Time)',
			'America/Scoresbysund' => '(GMT-1:00) America/Scoresbysund (Eastern Greenland Time)',
			'Atlantic/Azores' => '(GMT-1:00) Atlantic/Azores (Azores Time)',
			'Atlantic/Cape_Verde' => '(GMT-1:00) Atlantic/Cape_Verde (Cape Verde Time)',
			'Africa/Abidjan' => '(GMT+0:00) Africa/Abidjan (Greenwich Mean Time)',
			'Africa/Accra' => '(GMT+0:00) Africa/Accra (Ghana Mean Time)',
			'Africa/Bamako' => '(GMT+0:00) Africa/Bamako (Greenwich Mean Time)',
			'Africa/Banjul' => '(GMT+0:00) Africa/Banjul (Greenwich Mean Time)',
			'Africa/Bissau' => '(GMT+0:00) Africa/Bissau (Greenwich Mean Time)',
			'Africa/Casablanca' => '(GMT+0:00) Africa/Casablanca (Western European Time)',
			'Africa/Conakry' => '(GMT+0:00) Africa/Conakry (Greenwich Mean Time)',
			'Africa/Dakar' => '(GMT+0:00) Africa/Dakar (Greenwich Mean Time)',
			'Africa/El_Aaiun' => '(GMT+0:00) Africa/El_Aaiun (Western European Time)',
			'Africa/Freetown' => '(GMT+0:00) Africa/Freetown (Greenwich Mean Time)',
			'Africa/Lome' => '(GMT+0:00) Africa/Lome (Greenwich Mean Time)',
			'Africa/Monrovia' => '(GMT+0:00) Africa/Monrovia (Greenwich Mean Time)',
			'Africa/Nouakchott' => '(GMT+0:00) Africa/Nouakchott (Greenwich Mean Time)',
			'Africa/Ouagadougou' => '(GMT+0:00) Africa/Ouagadougou (Greenwich Mean Time)',
			'Africa/Sao_Tome' => '(GMT+0:00) Africa/Sao_Tome (Greenwich Mean Time)',
			'Africa/Timbuktu' => '(GMT+0:00) Africa/Timbuktu (Greenwich Mean Time)',
			'America/Danmarkshavn' => '(GMT+0:00) America/Danmarkshavn (Greenwich Mean Time)',
			'Atlantic/Canary' => '(GMT+0:00) Atlantic/Canary (Western European Time)',
			'Atlantic/Faeroe' => '(GMT+0:00) Atlantic/Faeroe (Western European Time)',
			'Atlantic/Faroe' => '(GMT+0:00) Atlantic/Faroe (Western European Time)',
			'Atlantic/Madeira' => '(GMT+0:00) Atlantic/Madeira (Western European Time)',
			'Atlantic/Reykjavik' => '(GMT+0:00) Atlantic/Reykjavik (Greenwich Mean Time)',
			'Atlantic/St_Helena' => '(GMT+0:00) Atlantic/St_Helena (Greenwich Mean Time)',
			'Europe/Belfast' => '(GMT+0:00) Europe/Belfast (Greenwich Mean Time)',
			'Europe/Dublin' => '(GMT+0:00) Europe/Dublin (Greenwich Mean Time)',
			'Europe/Guernsey' => '(GMT+0:00) Europe/Guernsey (Greenwich Mean Time)',
			'Europe/Isle_of_Man' => '(GMT+0:00) Europe/Isle_of_Man (Greenwich Mean Time)',
			'Europe/Jersey' => '(GMT+0:00) Europe/Jersey (Greenwich Mean Time)',
			'Europe/Lisbon' => '(GMT+0:00) Europe/Lisbon (Western European Time)',
			'Europe/London' => '(GMT+0:00) Europe/London (Greenwich Mean Time)',
			'Africa/Algiers' => '(GMT+1:00) Africa/Algiers (Central European Time)',
			'Africa/Bangui' => '(GMT+1:00) Africa/Bangui (Western African Time)',
			'Africa/Brazzaville' => '(GMT+1:00) Africa/Brazzaville (Western African Time)',
			'Africa/Ceuta' => '(GMT+1:00) Africa/Ceuta (Central European Time)',
			'Africa/Douala' => '(GMT+1:00) Africa/Douala (Western African Time)',
			'Africa/Kinshasa' => '(GMT+1:00) Africa/Kinshasa (Western African Time)',
			'Africa/Lagos' => '(GMT+1:00) Africa/Lagos (Western African Time)',
			'Africa/Libreville' => '(GMT+1:00) Africa/Libreville (Western African Time)',
			'Africa/Luanda' => '(GMT+1:00) Africa/Luanda (Western African Time)',
			'Africa/Malabo' => '(GMT+1:00) Africa/Malabo (Western African Time)',
			'Africa/Ndjamena' => '(GMT+1:00) Africa/Ndjamena (Western African Time)',
			'Africa/Niamey' => '(GMT+1:00) Africa/Niamey (Western African Time)',
			'Africa/Porto-Novo' => '(GMT+1:00) Africa/Porto-Novo (Western African Time)',
			'Africa/Tunis' => '(GMT+1:00) Africa/Tunis (Central European Time)',
			'Africa/Windhoek' => '(GMT+1:00) Africa/Windhoek (Western African Time)',
			'Arctic/Longyearbyen' => '(GMT+1:00) Arctic/Longyearbyen (Central European Time)',
			'Atlantic/Jan_Mayen' => '(GMT+1:00) Atlantic/Jan_Mayen (Central European Time)',
			'Europe/Amsterdam' => '(GMT+1:00) Europe/Amsterdam (Central European Time)',
			'Europe/Andorra' => '(GMT+1:00) Europe/Andorra (Central European Time)',
			'Europe/Belgrade' => '(GMT+1:00) Europe/Belgrade (Central European Time)',
			'Europe/Berlin' => '(GMT+1:00) Europe/Berlin (Central European Time)',
			'Europe/Bratislava' => '(GMT+1:00) Europe/Bratislava (Central European Time)',
			'Europe/Brussels' => '(GMT+1:00) Europe/Brussels (Central European Time)',
			'Europe/Budapest' => '(GMT+1:00) Europe/Budapest (Central European Time)',
			'Europe/Copenhagen' => '(GMT+1:00) Europe/Copenhagen (Central European Time)',
			'Europe/Gibraltar' => '(GMT+1:00) Europe/Gibraltar (Central European Time)',
			'Europe/Ljubljana' => '(GMT+1:00) Europe/Ljubljana (Central European Time)',
			'Europe/Luxembourg' => '(GMT+1:00) Europe/Luxembourg (Central European Time)',
			'Europe/Madrid' => '(GMT+1:00) Europe/Madrid (Central European Time)',
			'Europe/Malta' => '(GMT+1:00) Europe/Malta (Central European Time)',
			'Europe/Monaco' => '(GMT+1:00) Europe/Monaco (Central European Time)',
			'Europe/Oslo' => '(GMT+1:00) Europe/Oslo (Central European Time)',
			'Europe/Paris' => '(GMT+1:00) Europe/Paris (Central European Time)',
			'Europe/Podgorica' => '(GMT+1:00) Europe/Podgorica (Central European Time)',
			'Europe/Prague' => '(GMT+1:00) Europe/Prague (Central European Time)',
			'Europe/Rome' => '(GMT+1:00) Europe/Rome (Central European Time)',
			'Europe/San_Marino' => '(GMT+1:00) Europe/San_Marino (Central European Time)',
			'Europe/Sarajevo' => '(GMT+1:00) Europe/Sarajevo (Central European Time)',
			'Europe/Skopje' => '(GMT+1:00) Europe/Skopje (Central European Time)',
			'Europe/Stockholm' => '(GMT+1:00) Europe/Stockholm (Central European Time)',
			'Europe/Tirane' => '(GMT+1:00) Europe/Tirane (Central European Time)',
			'Europe/Vaduz' => '(GMT+1:00) Europe/Vaduz (Central European Time)',
			'Europe/Vatican' => '(GMT+1:00) Europe/Vatican (Central European Time)',
			'Europe/Vienna' => '(GMT+1:00) Europe/Vienna (Central European Time)',
			'Europe/Warsaw' => '(GMT+1:00) Europe/Warsaw (Central European Time)',
			'Europe/Zagreb' => '(GMT+1:00) Europe/Zagreb (Central European Time)',
			'Europe/Zurich' => '(GMT+1:00) Europe/Zurich (Central European Time)',
			'Africa/Blantyre' => '(GMT+2:00) Africa/Blantyre (Central African Time)',
			'Africa/Bujumbura' => '(GMT+2:00) Africa/Bujumbura (Central African Time)',
			'Africa/Cairo' => '(GMT+2:00) Africa/Cairo (Eastern European Time)',
			'Africa/Gaborone' => '(GMT+2:00) Africa/Gaborone (Central African Time)',
			'Africa/Harare' => '(GMT+2:00) Africa/Harare (Central African Time)',
			'Africa/Johannesburg' => '(GMT+2:00) Africa/Johannesburg (South Africa Standard Time)',
			'Africa/Kigali' => '(GMT+2:00) Africa/Kigali (Central African Time)',
			'Africa/Lubumbashi' => '(GMT+2:00) Africa/Lubumbashi (Central African Time)',
			'Africa/Lusaka' => '(GMT+2:00) Africa/Lusaka (Central African Time)',
			'Africa/Maputo' => '(GMT+2:00) Africa/Maputo (Central African Time)',
			'Africa/Maseru' => '(GMT+2:00) Africa/Maseru (South Africa Standard Time)',
			'Africa/Mbabane' => '(GMT+2:00) Africa/Mbabane (South Africa Standard Time)',
			'Africa/Tripoli' => '(GMT+2:00) Africa/Tripoli (Eastern European Time)',
			'Asia/Amman' => '(GMT+2:00) Asia/Amman (Eastern European Time)',
			'Asia/Beirut' => '(GMT+2:00) Asia/Beirut (Eastern European Time)',
			'Asia/Damascus' => '(GMT+2:00) Asia/Damascus (Eastern European Time)',
			'Asia/Gaza' => '(GMT+2:00) Asia/Gaza (Eastern European Time)',
			'Asia/Istanbul' => '(GMT+2:00) Asia/Istanbul (Eastern European Time)',
			'Asia/Jerusalem' => '(GMT+2:00) Asia/Jerusalem (Israel Standard Time)',
			'Asia/Nicosia' => '(GMT+2:00) Asia/Nicosia (Eastern European Time)',
			'Asia/Tel_Aviv' => '(GMT+2:00) Asia/Tel_Aviv (Israel Standard Time)',
			'Europe/Athens' => '(GMT+2:00) Europe/Athens (Eastern European Time)',
			'Europe/Bucharest' => '(GMT+2:00) Europe/Bucharest (Eastern European Time)',
			'Europe/Chisinau' => '(GMT+2:00) Europe/Chisinau (Eastern European Time)',
			'Europe/Helsinki' => '(GMT+2:00) Europe/Helsinki (Eastern European Time)',
			'Europe/Istanbul' => '(GMT+2:00) Europe/Istanbul (Eastern European Time)',
			'Europe/Kaliningrad' => '(GMT+2:00) Europe/Kaliningrad (Eastern European Time)',
			'Europe/Kiev' => '(GMT+2:00) Europe/Kiev (Eastern European Time)',
			'Europe/Mariehamn' => '(GMT+2:00) Europe/Mariehamn (Eastern European Time)',
			'Europe/Minsk' => '(GMT+2:00) Europe/Minsk (Eastern European Time)',
			'Europe/Nicosia' => '(GMT+2:00) Europe/Nicosia (Eastern European Time)',
			'Europe/Riga' => '(GMT+2:00) Europe/Riga (Eastern European Time)',
			'Europe/Simferopol' => '(GMT+2:00) Europe/Simferopol (Eastern European Time)',
			'Europe/Sofia' => '(GMT+2:00) Europe/Sofia (Eastern European Time)',
			'Europe/Tallinn' => '(GMT+2:00) Europe/Tallinn (Eastern European Time)',
			'Europe/Tiraspol' => '(GMT+2:00) Europe/Tiraspol (Eastern European Time)',
			'Europe/Uzhgorod' => '(GMT+2:00) Europe/Uzhgorod (Eastern European Time)',
			'Europe/Vilnius' => '(GMT+2:00) Europe/Vilnius (Eastern European Time)',
			'Europe/Zaporozhye' => '(GMT+2:00) Europe/Zaporozhye (Eastern European Time)',
			'Africa/Addis_Ababa' => '(GMT+3:00) Africa/Addis_Ababa (Eastern African Time)',
			'Africa/Asmara' => '(GMT+3:00) Africa/Asmara (Eastern African Time)',
			'Africa/Asmera' => '(GMT+3:00) Africa/Asmera (Eastern African Time)',
			'Africa/Dar_es_Salaam' => '(GMT+3:00) Africa/Dar_es_Salaam (Eastern African Time)',
			'Africa/Djibouti' => '(GMT+3:00) Africa/Djibouti (Eastern African Time)',
			'Africa/Kampala' => '(GMT+3:00) Africa/Kampala (Eastern African Time)',
			'Africa/Khartoum' => '(GMT+3:00) Africa/Khartoum (Eastern African Time)',
			'Africa/Mogadishu' => '(GMT+3:00) Africa/Mogadishu (Eastern African Time)',
			'Africa/Nairobi' => '(GMT+3:00) Africa/Nairobi (Eastern African Time)',
			'Antarctica/Syowa' => '(GMT+3:00) Antarctica/Syowa (Syowa Time)',
			'Asia/Aden' => '(GMT+3:00) Asia/Aden (Arabia Standard Time)',
			'Asia/Baghdad' => '(GMT+3:00) Asia/Baghdad (Arabia Standard Time)',
			'Asia/Bahrain' => '(GMT+3:00) Asia/Bahrain (Arabia Standard Time)',
			'Asia/Kuwait' => '(GMT+3:00) Asia/Kuwait (Arabia Standard Time)',
			'Asia/Qatar' => '(GMT+3:00) Asia/Qatar (Arabia Standard Time)',
			'Europe/Moscow' => '(GMT+3:00) Europe/Moscow (Moscow Standard Time)',
			'Europe/Volgograd' => '(GMT+3:00) Europe/Volgograd (Volgograd Time)',
			'Indian/Antananarivo' => '(GMT+3:00) Indian/Antananarivo (Eastern African Time)',
			'Indian/Comoro' => '(GMT+3:00) Indian/Comoro (Eastern African Time)',
			'Indian/Mayotte' => '(GMT+3:00) Indian/Mayotte (Eastern African Time)',
			'Asia/Tehran' => '(GMT+3:30) Asia/Tehran (Iran Standard Time)',
			'Asia/Baku' => '(GMT+4:00) Asia/Baku (Azerbaijan Time)',
			'Asia/Dubai' => '(GMT+4:00) Asia/Dubai (Gulf Standard Time)',
			'Asia/Muscat' => '(GMT+4:00) Asia/Muscat (Gulf Standard Time)',
			'Asia/Tbilisi' => '(GMT+4:00) Asia/Tbilisi (Georgia Time)',
			'Asia/Yerevan' => '(GMT+4:00) Asia/Yerevan (Armenia Time)',
			'Europe/Samara' => '(GMT+4:00) Europe/Samara (Samara Time)',
			'Indian/Mahe' => '(GMT+4:00) Indian/Mahe (Seychelles Time)',
			'Indian/Mauritius' => '(GMT+4:00) Indian/Mauritius (Mauritius Time)',
			'Indian/Reunion' => '(GMT+4:00) Indian/Reunion (Reunion Time)',
			'Asia/Kabul' => '(GMT+4:30) Asia/Kabul (Afghanistan Time)',
			'Asia/Aqtau' => '(GMT+5:00) Asia/Aqtau (Aqtau Time)',
			'Asia/Aqtobe' => '(GMT+5:00) Asia/Aqtobe (Aqtobe Time)',
			'Asia/Ashgabat' => '(GMT+5:00) Asia/Ashgabat (Turkmenistan Time)',
			'Asia/Ashkhabad' => '(GMT+5:00) Asia/Ashkhabad (Turkmenistan Time)',
			'Asia/Dushanbe' => '(GMT+5:00) Asia/Dushanbe (Tajikistan Time)',
			'Asia/Karachi' => '(GMT+5:00) Asia/Karachi (Pakistan Time)',
			'Asia/Oral' => '(GMT+5:00) Asia/Oral (Oral Time)',
			'Asia/Samarkand' => '(GMT+5:00) Asia/Samarkand (Uzbekistan Time)',
			'Asia/Tashkent' => '(GMT+5:00) Asia/Tashkent (Uzbekistan Time)',
			'Asia/Yekaterinburg' => '(GMT+5:00) Asia/Yekaterinburg (Yekaterinburg Time)',
			'Indian/Kerguelen' => '(GMT+5:00) Indian/Kerguelen (French Southern & Antarctic Lands Time)',
			'Indian/Maldives' => '(GMT+5:00) Indian/Maldives (Maldives Time)',
			'Asia/Calcutta' => '(GMT+5:30) Asia/Calcutta (India Standard Time)',
			'Asia/Colombo' => '(GMT+5:30) Asia/Colombo (India Standard Time)',
			'Asia/Kolkata' => '(GMT+5:30) Asia/Kolkata (India Standard Time)',
			'Asia/Katmandu' => '(GMT+5:45) Asia/Katmandu (Nepal Time)',
			'Antarctica/Mawson' => '(GMT+6:00) Antarctica/Mawson (Mawson Time)',
			'Antarctica/Vostok' => '(GMT+6:00) Antarctica/Vostok (Vostok Time)',
			'Asia/Almaty' => '(GMT+6:00) Asia/Almaty (Alma-Ata Time)',
			'Asia/Bishkek' => '(GMT+6:00) Asia/Bishkek (Kirgizstan Time)',
			'Asia/Dhaka' => '(GMT+6:00) Asia/Dhaka (Bangladesh Time)',
			'Asia/Novosibirsk' => '(GMT+6:00) Asia/Novosibirsk (Novosibirsk Time)',
			'Asia/Omsk' => '(GMT+6:00) Asia/Omsk (Omsk Time)',
			'Asia/Qyzylorda' => '(GMT+6:00) Asia/Qyzylorda (Qyzylorda Time)',
			'Asia/Thimbu' => '(GMT+6:00) Asia/Thimbu (Bhutan Time)',
			'Asia/Thimphu' => '(GMT+6:00) Asia/Thimphu (Bhutan Time)',
			'Indian/Chagos' => '(GMT+6:00) Indian/Chagos (Indian Ocean Territory Time)',
			'Asia/Rangoon' => '(GMT+6:30) Asia/Rangoon (Myanmar Time)',
			'Indian/Cocos' => '(GMT+6:30) Indian/Cocos (Cocos Islands Time)',
			'Antarctica/Davis' => '(GMT+7:00) Antarctica/Davis (Davis Time)',
			'Asia/Bangkok' => '(GMT+7:00) Asia/Bangkok (Indochina Time)',
			'Asia/Ho_Chi_Minh' => '(GMT+7:00) Asia/Ho_Chi_Minh (Indochina Time)',
			'Asia/Hovd' => '(GMT+7:00) Asia/Hovd (Hovd Time)',
			'Asia/Jakarta' => '(GMT+7:00) Asia/Jakarta (West Indonesia Time)',
			'Asia/Krasnoyarsk' => '(GMT+7:00) Asia/Krasnoyarsk (Krasnoyarsk Time)',
			'Asia/Phnom_Penh' => '(GMT+7:00) Asia/Phnom_Penh (Indochina Time)',
			'Asia/Pontianak' => '(GMT+7:00) Asia/Pontianak (West Indonesia Time)',
			'Asia/Saigon' => '(GMT+7:00) Asia/Saigon (Indochina Time)',
			'Asia/Vientiane' => '(GMT+7:00) Asia/Vientiane (Indochina Time)',
			'Indian/Christmas' => '(GMT+7:00) Indian/Christmas (Christmas Island Time)',
			'Antarctica/Casey' => '(GMT+8:00) Antarctica/Casey (Western Standard Time (Australia))',
			'Asia/Brunei' => '(GMT+8:00) Asia/Brunei (Brunei Time)',
			'Asia/Choibalsan' => '(GMT+8:00) Asia/Choibalsan (Choibalsan Time)',
			'Asia/Chongqing' => '(GMT+8:00) Asia/Chongqing (China Standard Time)',
			'Asia/Chungking' => '(GMT+8:00) Asia/Chungking (China Standard Time)',
			'Asia/Harbin' => '(GMT+8:00) Asia/Harbin (China Standard Time)',
			'Asia/Hong_Kong' => '(GMT+8:00) Asia/Hong_Kong (Hong Kong Time)',
			'Asia/Irkutsk' => '(GMT+8:00) Asia/Irkutsk (Irkutsk Time)',
			'Asia/Kashgar' => '(GMT+8:00) Asia/Kashgar (China Standard Time)',
			'Asia/Kuala_Lumpur' => '(GMT+8:00) Asia/Kuala_Lumpur (Malaysia Time)',
			'Asia/Kuching' => '(GMT+8:00) Asia/Kuching (Malaysia Time)',
			'Asia/Macao' => '(GMT+8:00) Asia/Macao (China Standard Time)',
			'Asia/Macau' => '(GMT+8:00) Asia/Macau (China Standard Time)',
			'Asia/Makassar' => '(GMT+8:00) Asia/Makassar (Central Indonesia Time)',
			'Asia/Manila' => '(GMT+8:00) Asia/Manila (Philippines Time)',
			'Asia/Shanghai' => '(GMT+8:00) Asia/Shanghai (China Standard Time)',
			'Asia/Singapore' => '(GMT+8:00) Asia/Singapore (Singapore Time)',
			'Asia/Taipei' => '(GMT+8:00) Asia/Taipei (China Standard Time)',
			'Asia/Ujung_Pandang' => '(GMT+8:00) Asia/Ujung_Pandang (Central Indonesia Time)',
			'Asia/Ulaanbaatar' => '(GMT+8:00) Asia/Ulaanbaatar (Ulaanbaatar Time)',
			'Asia/Ulan_Bator' => '(GMT+8:00) Asia/Ulan_Bator (Ulaanbaatar Time)',
			'Asia/Urumqi' => '(GMT+8:00) Asia/Urumqi (China Standard Time)',
			'Australia/Perth' => '(GMT+8:00) Australia/Perth (Western Standard Time (Australia))',
			'Australia/West' => '(GMT+8:00) Australia/West (Western Standard Time (Australia))',
			'Australia/Eucla' => '(GMT+8:45) Australia/Eucla (Central Western Standard Time (Australia))',
			'Asia/Dili' => '(GMT+9:00) Asia/Dili (Timor-Leste Time)',
			'Asia/Jayapura' => '(GMT+9:00) Asia/Jayapura (East Indonesia Time)',
			'Asia/Pyongyang' => '(GMT+9:00) Asia/Pyongyang (Korea Standard Time)',
			'Asia/Seoul' => '(GMT+9:00) Asia/Seoul (Korea Standard Time)',
			'Asia/Tokyo' => '(GMT+9:00) Asia/Tokyo (Japan Standard Time)',
			'Asia/Yakutsk' => '(GMT+9:00) Asia/Yakutsk (Yakutsk Time)',
			'Australia/Adelaide' => '(GMT+9:30) Australia/Adelaide (Central Standard Time (South Australia))',
			'Australia/Broken_Hill' => '(GMT+9:30) Australia/Broken_Hill (Central Standard Time (South Australia/New South Wales))',
			'Australia/Darwin' => '(GMT+9:30) Australia/Darwin (Central Standard Time (Northern Territory))',
			'Australia/North' => '(GMT+9:30) Australia/North (Central Standard Time (Northern Territory))',
			'Australia/South' => '(GMT+9:30) Australia/South (Central Standard Time (South Australia))',
			'Australia/Yancowinna' => '(GMT+9:30) Australia/Yancowinna (Central Standard Time (South Australia/New South Wales))',
			'Antarctica/DumontDUrville' => '(GMT+10:00) Antarctica/DumontDUrville (Dumont-d\'Urville Time)',
			'Asia/Sakhalin' => '(GMT+10:00) Asia/Sakhalin (Sakhalin Time)',
			'Asia/Vladivostok' => '(GMT+10:00) Asia/Vladivostok (Vladivostok Time)',
			'Australia/ACT' => '(GMT+10:00) Australia/ACT (Eastern Standard Time (New South Wales))',
			'Australia/Brisbane' => '(GMT+10:00) Australia/Brisbane (Eastern Standard Time (Queensland))',
			'Australia/Canberra' => '(GMT+10:00) Australia/Canberra (Eastern Standard Time (New South Wales))',
			'Australia/Currie' => '(GMT+10:00) Australia/Currie (Eastern Standard Time (New South Wales))',
			'Australia/Hobart' => '(GMT+10:00) Australia/Hobart (Eastern Standard Time (Tasmania))',
			'Australia/Lindeman' => '(GMT+10:00) Australia/Lindeman (Eastern Standard Time (Queensland))',
			'Australia/Melbourne' => '(GMT+10:00) Australia/Melbourne (Eastern Standard Time (Victoria))',
			'Australia/NSW' => '(GMT+10:00) Australia/NSW (Eastern Standard Time (New South Wales))',
			'Australia/Queensland' => '(GMT+10:00) Australia/Queensland (Eastern Standard Time (Queensland))',
			'Australia/Sydney' => '(GMT+10:00) Australia/Sydney (Eastern Standard Time (New South Wales))',
			'Australia/Tasmania' => '(GMT+10:00) Australia/Tasmania (Eastern Standard Time (Tasmania))',
			'Australia/Victoria' => '(GMT+10:00) Australia/Victoria (Eastern Standard Time (Victoria))',
			'Australia/LHI' => '(GMT+10:30) Australia/LHI (Lord Howe Standard Time)',
			'Australia/Lord_Howe' => '(GMT+10:30) Australia/Lord_Howe (Lord Howe Standard Time)',
			'Asia/Magadan' => '(GMT+11:00) Asia/Magadan (Magadan Time)',
			'Antarctica/McMurdo' => '(GMT+12:00) Antarctica/McMurdo (New Zealand Standard Time)',
			'Antarctica/South_Pole' => '(GMT+12:00) Antarctica/South_Pole (New Zealand Standard Time)',
			'Asia/Anadyr' => '(GMT+12:00) Asia/Anadyr (Anadyr Time)',
			'Asia/Kamchatka' => '(GMT+12:00) Asia/Kamchatka (Petropavlovsk-Kamchatski Time)',
		);
	}

	public function _time_zone_list_numeric() {
		$all_time_zone = array(
			'-12' => 'GMT -12.00',
			'-11' => 'GMT -11.00',
			'-10' => 'GMT -10.00',
			'-9' => 'GMT -9.00',
			'-8' => 'GMT -8.00',
			'-7' => 'GMT -7.00',
			'-6' => 'GMT -6.00',
			'-5' => 'GMT -5.00',
			'-4.5' => 'GMT -4.30',
			'-4' => 'GMT -4.00',
			'-3.5' => 'GMT -3.30',
			'-3' => 'GMT +-3.00',
			'-2' => 'GMT +-2.00',
			'-1' => 'GMT -1.00',
			'0' => 'GMT',
			'1' => 'GMT +1.00',
			'2' => 'GMT +2.00',
			'3' => 'GMT +3.00',
			'3.5' => 'GMT +3.30',
			'4' => 'GMT +4.00',
			'5' => 'GMT +5.00',
			'5.5' => 'GMT +5.30',
			'5.75' => 'GMT +5.45',
			'6' => 'GMT +6.00',
			'6.5' => 'GMT +6.30',
			'7' => 'GMT +7.00',
			'8' => 'GMT +8.00',
			'9' => 'GMT +9.00',
			'9.5' => 'GMT +9.30',
			'10' => 'GMT +10.00',
			'11' => 'GMT +11.00',
			'12' => 'GMT +12.00',
			'13' => 'GMT +13.00',
		);

		return $all_time_zone;
	}

	public function _disable_cache() {
		header("Expires: Tue, 03 Jul 2001 06:00:00 GMT");
		header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
		header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
		header("Cache-Control: post-check=0, pre-check=0", false);
		header("Pragma: no-cache");
	}

	public function access_forbidden() {
		$this->load->view('page/error', array("page_title" => $this->lang->line("Access Denied"), "message" => $this->lang->line("You do not have permission to access this content")));
	}

	public function error_404() {
		$this->load->view('page/error');
	}

	public function _subscription_viewcontroller($data = array()) {
		// $this->_disable_cache();
		if (!isset($data['body'])) {
			$data['body'] = $this->config->item('default_page_url');
		}

		if (!isset($data['page_title'])) {
			$data['page_title'] = "";
		}

		$this->load->view('page/subscription_theme', $data);
	}

	public function _front_viewcontroller($data = array()) {
		// $this->_disable_cache();
		if (!isset($data['body'])) {
			$data['body'] = $this->config->item('default_page_url');
		}

		if (!isset($data['page_title'])) {
			$data['page_title'] = "";
		}

		$loadthemebody = "purple";
		if ($this->config->item('theme_front') != "") {
			$loadthemebody = $this->config->item('theme_front');
		}

		$themecolorcode = "#545096";

		if ($loadthemebody == 'blue') {$themecolorcode = "#1193D4";}
		if ($loadthemebody == 'white') {$themecolorcode = "#303F42";}
		if ($loadthemebody == 'black') {$themecolorcode = "#1A2226";}
		if ($loadthemebody == 'green') {$themecolorcode = "#00A65A";}
		if ($loadthemebody == 'red') {$themecolorcode = "#E55053";}
		if ($loadthemebody == 'yellow') {$themecolorcode = "#F39C12";}

		$data['THEMECOLORCODE'] = $themecolorcode;

		$this->load->view('front/theme_front', $data);
	}

	public function _viewcontroller($data = array()) {
		if (!isset($data['body'])) {
			$data['body'] = $this->config->item('default_page_url');
		}

		if (!isset($data['page_title'])) {
			$data['page_title'] = $this->lang->line("Admin Panel");
		}

		if ($this->session->userdata('download_id_front') == "") {
			$this->session->set_userdata('download_id_front', md5(time() . $this->_random_number_generator(10)));
		}

		if ($this->session->userdata('user_type') == 'Admin' || in_array(65, $this->module_access)) {
			$fb_rx_account_switching_info = $this->basic->get_data("facebook_rx_fb_user_info", array("where" => array("user_id" => $this->user_id)));
			$data["fb_rx_account_switching_info"] = array();
			foreach ($fb_rx_account_switching_info as $key => $value) {
				$str = "";
				$data["fb_rx_account_switching_info"][$value["id"]] = $str . $value["name"];
			}
		}
		// if(empty($data["fb_rx_account_switching_info"]))  $data["fb_rx_account_switching_info"]["0"] = $this->lang->line("--------");

		if ($this->basic->is_exist("modules", $where = array('id' => 252)) && ($this->session->userdata('user_type') == 'Admin' || in_array(253, $this->module_access))) // vidcaster account switch
		{
			$vidcaster_fb_rx_account_switching_info = $this->basic->get_data("vidcaster_facebook_rx_fb_user_info", array("where" => array("user_id" => $this->user_id)));
			$data["vidcaster_fb_rx_account_switching_info"] = array();
			foreach ($vidcaster_fb_rx_account_switching_info as $key => $value) {
				$str = "";
				$data["vidcaster_fb_rx_account_switching_info"][$value["id"]] = $str . $value["name"];
			}
		}
		// if(empty($data["vidcaster_fb_rx_account_switching_info"]))  $data["vidcaster_fb_rx_account_switching_info"]["0"] = $this->lang->line("No Account Imported");

		$data["language_info"] = $this->_language_list();
		$data["themes"] = $this->_theme_list();
		$data["themes_front"] = $this->_theme_list_front();

		$data['menus'] = $this->basic->get_data('menu', '', '', '', '', '', 'serial asc');

		$menu_child_1_map = array();
		$menu_child_1 = $this->basic->get_data('menu_child_1', '', '', '', '', '', 'serial asc');
		foreach ($menu_child_1 as $single_child_1) {
			$menu_child_1_map[$single_child_1['parent_id']][$single_child_1['id']] = $single_child_1;
		}
		$data['menu_child_1_map'] = $menu_child_1_map;

		$menu_child_2_map = array();
		$menu_child_2 = $this->basic->get_data('menu_child_2', '', '', '', '', '', 'serial asc');
		foreach ($menu_child_2 as $single_child_2) {
			$menu_child_2_map[$single_child_2['parent_child']][$single_child_2['id']] = $single_child_2;
		}
		$data['menu_child_2_map'] = $menu_child_2_map;

		// announcement
		$where_custom = "(user_id=" . $this->user_id . " AND is_seen='0') OR (user_id=0 AND NOT FIND_IN_SET('" . $this->user_id . "', seen_by))";
		$this->db->where($where_custom);
		$data['annoucement_data'] = $this->basic->get_data("announcement", $where = '', $select = '', $join = '', $limit = '', $start = NULL, $order_by = 'created_at DESC');

		if (isset($data['iframe']) && $data['iframe'] == '1') {
			$this->load->view('admin/theme/theme_iframe', $data);
		} else {
			$this->load->view('admin/theme/theme', $data);
		}

	}

	public function _site_viewcontroller($data = array()) {
		if (!isset($data['page_title'])) {
			$data['page_title'] = "";
		}

		$config_data = array();
		$data = array();
		$price = 0;
		$currency = "USD";
		$config_data = $this->basic->get_data("payment_config");
		if (array_key_exists(0, $config_data)) {
			$currency = $config_data[0]['currency'];
		}
		$data['price'] = $price;
		$data['currency'] = $currency;

		$currency_icons = $this->currency_icon();
		$data["curency_icon"] = isset($currency_icons[$currency]) ? $currency_icons[$currency] : "$";

		//catcha for contact page
		$data['contact_num1'] = $this->_random_number_generator(2);
		$data['contact_num2'] = $this->_random_number_generator(1);
		$contact_captcha = $data['contact_num1'] + $data['contact_num2'];
		$this->session->set_userdata("contact_captcha", $contact_captcha);
		$data["language_info"] = $this->_language_list();
		$data["payment_package"] = $this->basic->get_data("package", $where = array("where" => array("is_default" => "0", "price > " => 0, "validity >" => 0)), $select = '', $join = '', $limit = '', $start = NULL, $order_by = 'CAST(`price` AS SIGNED)');
		$data["default_package"] = $this->basic->get_data("package", $where = array("where" => array("is_default" => "1", "validity >" => 0, "price" => "Trial")));

		$loadthemebody = "purple";
		if ($this->config->item('theme_front') != "") {
			$loadthemebody = $this->config->item('theme_front');
		}

		$themecolorcode = "#545096";

		if ($loadthemebody == 'blue') {$themecolorcode = "#1193D4";}
		if ($loadthemebody == 'white') {$themecolorcode = "#303F42";}
		if ($loadthemebody == 'black') {$themecolorcode = "#1A2226";}
		if ($loadthemebody == 'green') {$themecolorcode = "#00A65A";}
		if ($loadthemebody == 'red') {$themecolorcode = "#E55053";}
		if ($loadthemebody == 'yellow') {$themecolorcode = "#F39C12";}

		$data['THEMECOLORCODE'] = $themecolorcode;

		//catcha for contact page

		$this->load->view('site/site_theme', $data);
	}
//=======================Customize start======================================//
	//========================= view/page ===============================//
	public function sign_up_fb() {
		if ($this->session->userdata('logged_in') == 1) {
			redirect('dashboard', 'location');
		}

		$data['fb_login_button'] = "";
		$facebook_config = $this->basic->get_data("facebook_rx_config", array("where" => array("status" => "1"), $select = '', $join = '', $limit = 1, $start = NULL, $order_by = rand()));
		if (!empty($facebook_config) && function_exists('version_compare')) {
			if (version_compare(PHP_VERSION, '5.4.0', '>=')) {
				$this->session->set_userdata('social_login_session_set', 1);
				$this->load->library("Fb_rx_login");
				$data['fb_login_button'] = $this->fb_rx_login->login_for_user_access_token(site_url("1/signup-info"));
			}
		}

		$data["page_title"] = $this->lang->line("Sign_up-facebook");
		$data["body"] = "page/sign_up_fb";

		$this->_subscription_viewcontroller($data);
	}

	// public function sign_up_fb_info() {
	// 	$signup_form = $this->config->item('enable_signup_form');

	// 	if ($signup_form == '0') {
	// 		return $this->login_page();
	// 	}
	// 	$data['num1'] = $this->_random_number_generator(1);
	// 	$data['num2'] = $this->_random_number_generator(1);
	// 	$captcha = $data['num1'] + $data['num2'];
	// 	$this->session->set_userdata("sign_up_captcha", $captcha);

	// 	$data["page_title"] = $this->lang->line("Sign Up");
	// 	$data["body"] = "page/sign_up_fb_info";
	// 	$this->_subscription_viewcontroller($data);
	// }

	public function sign_up_fb_home() {
		$signup_form = $this->config->item('enable_signup_form');

		$data["page_title"] = $this->lang->line("Home");
		$data["body"] = "page/sign_up_fb_home";
		$this->_subscription_viewcontroller($data);
	}

	public function facebook_signup_back() {

		$this->load->library('Fb_rx_login');
		$config_id = $this->session->userdata('return_configid_used_for_social_login');
		$this->fb_rx_login->app_initialize($config_id);

		$redirect_url = site_url("1/signup-info");

		$info = $this->fb_rx_login->login_callback($redirect_url);
		// print_r($info['access_token_set']);
		if (is_array($info) && !empty($info) && isset($info["email"]) && isset($info["name"])) {
			// ------test info---------//
			// $info = array(
			// 	"name" => "dev",
			// 	"email" => "dev@email.com",
			// 	"access_token_set" => "123456789"
			// );
			//-----------------------//
			$result = $this->basic->get_data('users', array('where' => array('email' => $info["email"])), array('count(*) as num'));
			if($result[0]['num'] > 0){
				redirect("home/login_page");
			} else {
				$signup_form = $this->config->item('enable_signup_form');

				$data["body"] = "page/sign_up_fb_info";
				$data["name"] = $info["name"];
				$data["email"] = $info["email"];
				$data["config_id"] = $config_id;
				$data["access_token_set"] = $info['access_token_set'];
				$this->_subscription_viewcontroller($data);
			}

		} else {
			$this->session->set_flashdata('login_msg', $this->lang->line("This facebook account has no email address so we couldn't create your account. Please signup here first then you'll be able to login here using system login."));
			redirect("home/login_page");
		}

	}

	public function upload_menufiles_1(){
		header("Content-Type: application/json");
	  	if(isset($_FILES['qqfile'])){
			$error= '';
			$file_name = $_FILES['qqfile']['name'];
			$file_size =$_FILES['qqfile']['size'];
			$file_tmp =$_FILES['qqfile']['tmp_name'];
			$file_type=$_FILES['qqfile']['type'];

			$email = $this->input->post('email', TRUE);
			$result = $this->signuplist_db->query("select * from list1 where email = '$email'");
			$email_id = 0;
			if(count($result->result())){
				$email_id = $result->result()[0]->id;
			} else {
				$this->signuplist_db->query("insert into list1 (email)  values ('$email')");
				$email_id = $this->signuplist_db->insert_id();
			}
			
			$file_names = explode('.',$_FILES['qqfile']['name']);
			$file_ext=strtolower($file_names[count($file_names) - 1]);
			
			$extensions= array("jpeg","jpg","png", "pdf", "xls", "xlsx");
			
			if(in_array($file_ext,$extensions)=== false){
				$error ="extension not allowed, please choose a JPEG or PNG file.";
			}
			
			if($file_size > 2097152){
				$error ='File size must be excately 2 MB';
			}

			if(empty($errors)==true){
				$directoy_name = './assets/uploads/signup/'.$email_id . '/';
				if (!file_exists($directoy_name)) {
					mkdir($directoy_name, 0777, true);
				}

				///////////////////////////////////////
				$x=1;
				$newfilename=$file_name;

				while(is_file($directoy_name.$newfilename)){ //actual control

				  $newfilename=$file_name;//to prevent creating flower123.jpg at the third iteration, see below

				  $fn=explode(".",$newfilename);
				  $fp=count($fn)-2;
				  $fn[$fp].="($x)";
				  //here we get the filename before extension (eg. "flower" in fakepath/flower.jpg) and add $x to get "flower1"

				  $newfilename=implode(".",$fn);
				  //we rebuild our path having now fakepath/flower1.jpg
				  $x++;
				}

				move_uploaded_file($file_tmp, $directoy_name.$newfilename);
				//////////////////////////////////////
				// move_uploaded_file($file_tmp, $directoy_name.$file_name);
				echo json_encode(array('success' => TRUE, 'filename' => $newfilename));
			}else{				
				echo json_encode(array('success' => FALSE, 'error' => $error));
			}
		} else {
			echo json_encode(array('success' => FALSE));
		}		
		die;
	}

	public function sign_up_fb_action() {
		if ($_SERVER['REQUEST_METHOD'] === 'GET') {
			redirect('home/access_forbidden', 'location');
		}

		if ($_POST) {
			$this->form_validation->set_rules('name', '<b>' . $this->lang->line("name") . '</b>', 'trim|required');
			$this->form_validation->set_rules('email', '<b>' . $this->lang->line("email") . '</b>', 'trim|required|valid_email|is_unique[users.email]');

			if ($this->form_validation->run() == FALSE) {
				redirect('home/sign_up_fb', 'location');
			} else {

				$name = $this->input->post('name', TRUE);
				$email = $this->input->post('email', TRUE);
				$access_token_set = $this->input->post('access_token_set', TRUE);
				$config_id = $this->input->post('config_id', TRUE);
				$bname = $this->input->post('bname', TRUE);
				$btype = $this->input->post('btype', TRUE);
				$burl = $this->input->post('burl', TRUE);
				$filename = $this->input->post('filename', TRUE);

				$default_package = $this->basic->get_data("package", $where = array("where" => array("is_default" => "1")));

				if (is_array($default_package) && array_key_exists(0, $default_package)) {
					$validity = $default_package[0]["validity"];
					$package_id = $default_package[0]["id"];

					$to_date = date('Y-m-d');
					$expiry_date = date("Y-m-d", strtotime('+' . $validity . ' day', strtotime($to_date)));
				}

				$code = $this->_random_number_generator();
				$data = array(
					'name' => $name,
					'email' => $email,
					// 'mobile' => $mobile,
					'password' => md5('123456'),
					'user_type' => 'Member',
					'status' => '1',
					'activation_code' => $code,
					'expired_date' => $expiry_date,
					'package_id' => $package_id,
				);
				// print_r($access_token_set);

				$access_token = $access_token_set;
				if ($this->basic->insert_data('users', $data)) {
					//the function to get fb_page_name
					$page_names = $this->get_fb_page_names($access_token, $config_id);
					// print_r($page_names);
					// Check if email exist in list1 table
					$result = $this->signuplist_db->query("select * from list1 where email = '$email'");
					$email_id = (count($result->result()) > 0) ? $result->result()[0]->id : 0;
					// If id exist, update list1 table with data for id
					if ($email_id) {						
						$this->signuplist_db->query("update list1 set email = '$email', business_name = '$bname', business_type = '$btype', filename = '$filename', fb_page = '" . implode(", ", $page_names) . "', url = '$burl', datetime = '".date('Y-m-d H:i:s')."' where id = '$email_id'");
					} else{						
						$this->signuplist_db->insert('list1', 
							array(
								'email' => $email,
								'business_name' => $bname,
								'business_type' => $btype,
								'fb_page' => implode(", ", $page_names),
								'datetime' => date('Y-m-d H:i:s'),
								'filename' => $filename,
								'url' => $burl,
							)
						);
						$email_id = $this->signuplist_db->insert_id();
					}
					//if user doesn't uploade file, create empty holder with user's email_id					
					$directoy_name = './assets/uploads/signup/'.$email_id . '/';
					if (!file_exists($directoy_name)) {
						mkdir($directoy_name, 0777, true);
					}

					// Else inser data

					//email to user
					$productname = $this->config->item('product_name');
					$subject = $productname . " | New account is registered with Facebook";
					$message = "<p>" . $this->lang->line("Please check new account is registered with Facebook.") . "</p>
								<ol>
									<li>" . $this->lang->line("Email") . " : " . $email . "</li>
									<li>" . $this->lang->line("Account Name") . " : " . $name . "</li>
									<li>" . $this->lang->line("Business Name") . " : " . $bname . "</li>
									<li>" . $this->lang->line("Business Type") . " : " . $btype . "</li>
									<li>" . $this->lang->line("Business URL") . " : " . $burl . "</li>
									<li>" . $this->lang->line("Menu/Service List") . " : " . $filename . "</li>
								</ol>";

					$from = $this->config->item('institute_email');
					$to = $this->config->item('institute_support_email');
					$mask = $subject;
					$html = 1;

					// $this->_mail_sender($from, $to, $subject, $message, $mask, $html);

					
					// return $this->sign_up_fb_home();
					redirect('https://hostbot.uk/payment', 'location');
				}

			}

		}
	}

	public function get_fb_page_names($access_token, $config_id){
		$this->load->library('Fb_rx_login');
		$this->fb_rx_login->app_initialize($config_id);

		$page_names = [];
		if (isset($access_token)) {
			$page_list = array();

			$page_list = $this->fb_rx_login->get_page_list($access_token);
			if (!empty($page_list)) {
				foreach ($page_list as $page) {
					$page_name = '';
					if (isset($page['name'])) {
						$page_name = $page['name'];
						$page_names[] = $page_name;
					}
				}
			}
		}
		return $page_names;
	}

	//=================================facebook_su=======================//
	public function fb_sign_up() {
		if ($this->session->userdata('logged_in') == 1) {
			redirect('dashboard', 'location');
		}

		// $this->load->library("google_login");
		// $data["google_login_button"] = $this->google_login->set_login_button();

		$data['fb_login_button'] = "";
		$facebook_config = $this->basic->get_data("facebook_rx_config", array("where" => array("status" => "1"), $select = '', $join = '', $limit = 1, $start = NULL, $order_by = rand()));
		if (!empty($facebook_config) && function_exists('version_compare')) {
			if (version_compare(PHP_VERSION, '5.4.0', '>=')) {
				$this->session->set_userdata('social_login_session_set', 1);
				$this->load->library("Fb_rx_login");
				$data['fb_login_button'] = $this->fb_rx_login->login_for_user_access_token(site_url("2/signup-info"));
			}
		}

		$data["page_title"] = $this->lang->line("Sign_up-facebook");
		$data["body"] = "page/facebook_su/fb_sign_up";

		$this->_subscription_viewcontroller($data);
	}

	public function fb_sign_info() {
		$signup_form = $this->config->item('enable_signup_form');

		if ($signup_form == '0') {
			return $this->login_page();
		}
		$data['num1'] = $this->_random_number_generator(1);
		$data['num2'] = $this->_random_number_generator(1);
		$captcha = $data['num1'] + $data['num2'];
		$this->session->set_userdata("sign_up_captcha", $captcha);

		$data["page_title"] = $this->lang->line("Sign Up");
		$data["body"] = "page/facebook_su/fb_sign_info";
		$this->_subscription_viewcontroller($data);
	}

	public function fb_signup_back() {

		$this->load->library('Fb_rx_login');
		$config_id = $this->session->userdata('return_configid_used_for_social_login');
		$this->fb_rx_login->app_initialize($config_id);

		$redirect_url = site_url("2/signup-info");

		$info = $this->fb_rx_login->login_callback($redirect_url);
		// $info = array(
		// 	"name" => "dev",
		// 	"email" => "dev@email.com",
		// );

		if (is_array($info) && !empty($info) && isset($info["email"]) && isset($info["name"])) {
			$result = $this->basic->get_data('users', array('where' => array('email' => $info["email"])), array('count(*) as num'));
			if($result[0]['num'] > 0){
				redirect("home/login_page");
			} else {
				$signup_form = $this->config->item('enable_signup_form');

				$data["body"] = "page/facebook_su/fb_sign_info";
				$data["name"] = $info["name"];
				$data["email"] = $info["email"];
				$data["access_token_set"] = $info['access_token_set'];
				$data["fb_id"] = $info['id'];
				$data["config_id"] = $config_id;
				$this->_subscription_viewcontroller($data);
			}
		} else {
			$this->session->set_flashdata('login_msg', $this->lang->line("This facebook account has no email address so we couldn't create your account. Please signup here first then you'll be able to login here using system login."));
			redirect("home/login_page");
		}

	}
	public function fb_sign_action() {

		if ($_SERVER['REQUEST_METHOD'] === 'GET') {
			redirect('home/access_forbidden', 'location');
		}

		if ($_POST) {
			$this->form_validation->set_rules('name', '<b>' . $this->lang->line("name") . '</b>', 'trim|required');
			$this->form_validation->set_rules('email', '<b>' . $this->lang->line("email") . '</b>', 'trim|required|valid_email|is_unique[users.email]');

			if ($this->form_validation->run() == FALSE) {
				// echo validation_errors();
				redirect('home/fb_sign_up', 'location');
			} else {

				$name = $this->input->post('name', TRUE);
				$email = $this->input->post('email', TRUE);
				$access_token_set = $this->input->post('access_token_set', TRUE);
				$fb_id = $this->input->post('fb_id', TRUE);
				$config_id = $this->input->post('config_id', TRUE);
				$fb_bname = $this->input->post('fb_bname', TRUE);
				$fb_btype = $this->input->post('fb_btype', TRUE);				
				$burl = $this->input->post('burl', TRUE);
				$filename = $this->input->post('filename', TRUE);


				$default_package = $this->basic->get_data("package", $where = array("where" => array("is_default" => "1")));

				if (is_array($default_package) && array_key_exists(0, $default_package)) {
					$validity = $default_package[0]["validity"];
					$package_id = $default_package[0]["id"];

					$to_date = date('Y-m-d');
					$expiry_date = date("Y-m-d", strtotime('+' . $validity . ' day', strtotime($to_date)));
				}

				$code = $this->_random_number_generator();
				$password = md5('123456');
				$data = array(
					'name' => $name,
					'email' => $email,
					// 'mobile' => $mobile,
					'password' => $password,
					'user_type' => 'Member',
					'status' => '1',
					'activation_code' => $code,
					'expired_date' => $expiry_date,
					'package_id' => $package_id,
				);

				if ($this->basic->insert_data('users', $data)) {
					$user_id = $this->db->insert_id();
					$this->fb_login_to_import($email, $password);
					$page_names = $this->fb_login_to_exist($user_id, $name, $email, $access_token_set, $fb_id, $config_id);
					///////////////////////////

					$result = $this->signuplist_db->query("select * from list1 where email = '$email'");
					$email_id = (count($result->result()) > 0) ? $result->result()[0]->id : 0;
					// If id exist, update list1 table with data for id
					if ($email_id) {						
						$this->signuplist_db->query("update list1 set business_name_2 = '$fb_bname', business_type_2 = '$fb_btype', filename_2 = '$filename', fb_page_2 = '" . implode(", ", $page_names) . "', url_2 = '$burl', datetime_2 = '".date('Y-m-d H:i:s')."' where id = '$email_id'");
					} else{						
						$this->signuplist_db->insert('list1', 
							array(
								'business_name_2' => $fb_bname,
								'business_type_2' => $fb_btype,
								'fb_page_2' => implode(", ", $page_names),
								'datetime_2' => date('Y-m-d H:i:s'),
								'filename_2' => $filename,
								'url_2' => $burl,
							)
						);
						$email_id = $this->signuplist_db->insert_id();
					}
					//if user doesn't uploade file, create empty holder with user's email_id					
					$directoy_name = './assets/uploads/signup/'.$email_id . '/';
					if (!file_exists($directoy_name)) {
						mkdir($directoy_name, 0777, true);
					}

					//email to user
					$productname = $this->config->item('product_name');
					$subject = $productname . " | New account is registered with Facebook";
					$message = "<p>" . $this->lang->line("Please check new account is registered with Facebook.") . "</p>
											   <ol>
													<li>" . $this->lang->line("Email") . " : " . $email . "</li>
													<li>" . $this->lang->line("Account Name") . " : " . $name . "</li>
													<li>" . $this->lang->line("Business Name") . " : " . $fb_bname . "</li>
													<li>" . $this->lang->line("Business Type") . " : " . $fb_btype . "</li>
													<li>" . $this->lang->line("Business URL") . " : " . $burl . "</li>
													<li>" . $this->lang->line("Menu/Service List") . " : " . $filename . "</li>
											   </ol>";
					$message = $message . "<p> Facebook Page Names </p> <ul>";
					foreach ($page_names as $page_name) {
						$message = $message . "<li>" . $page_name . "</li>";
					}
					$message = $message . "</ul>";

					$from = $this->config->item('institute_email');
					$to = $this->config->item('institute_support_email');
					$mask = $subject;
					$html = 1;

					// $this->_mail_sender($from, $to, $subject, $message, $mask, $html);
					
					redirect('social_accounts/index', 'location');
				}

			}

		}
	}

	public function upload_menufiles_2(){
		header("Content-Type: application/json");
	  	if(isset($_FILES['qqfile'])){
			$error= '';
			$file_name = $_FILES['qqfile']['name'];
			$file_size =$_FILES['qqfile']['size'];
			$file_tmp =$_FILES['qqfile']['tmp_name'];
			$file_type=$_FILES['qqfile']['type'];

			$email = $this->input->post('email', TRUE);
			$result = $this->signuplist_db->query("select * from list1 where email = '$email'");
			$email_id = 0;
			if(count($result->result())){
				$email_id = $result->result()[0]->id;
			} else {
				$this->signuplist_db->query("insert into list1 (email)  values ('$email')");
				$email_id = $this->signuplist_db->insert_id();
			}
			
			$file_names = explode('.',$_FILES['qqfile']['name']);
			$file_ext=strtolower($file_names[count($file_names) - 1]);
			
			$extensions= array("jpeg","jpg","png", "pdf", "xls", "xlsx");
			
			if(in_array($file_ext,$extensions)=== false){
				$error ="extension not allowed, please choose a JPEG or PNG file.";
			}
			
			if($file_size > 2097152){
				$error ='File size must be excately 2 MB';
			}

			if(empty($errors)==true){
				$directoy_name = './assets/uploads/signup/'.$email_id . '/';
				if (!file_exists($directoy_name)) {
					mkdir($directoy_name, 0777, true);
				}

				///////////////////////////////////////
				$x=1;
				$newfilename=$file_name;

				while(is_file($directoy_name.$newfilename)){ //actual control

				  $newfilename=$file_name;//to prevent creating flower123.jpg at the third iteration, see below

				  $fn=explode(".",$newfilename);
				  $fp=count($fn)-2;
				  $fn[$fp].="($x)";
				  //here we get the filename before extension (eg. "flower" in fakepath/flower.jpg) and add $x to get "flower1"

				  $newfilename=implode(".",$fn);
				  //we rebuild our path having now fakepath/flower1.jpg
				  $x++;
				}

				move_uploaded_file($file_tmp, $directoy_name.$newfilename);
				//////////////////////////////////////
				// move_uploaded_file($file_tmp, $directoy_name.$file_name);
				echo json_encode(array('success' => TRUE, 'filename' => $newfilename));
			}else{				
				echo json_encode(array('success' => FALSE, 'error' => $error));
			}
		} else {
			echo json_encode(array('success' => FALSE));
		}		
		die;
	}

	public function fb_login_to_exist($user_id, $name, $email, $access_token_set, $fb_id, $config_id) {
		$this->load->library('Fb_rx_login');
		$this->fb_rx_login->app_initialize($config_id);

		$page_names = [];
		// =========== action for account import ========== //
		$access_token = $access_token_set;
		if (isset($access_token)) {
			$import_account_data = array(
				'user_id' => $user_id,
				'facebook_rx_config_id' => $config_id,
				'access_token' => $access_token,
				'name' => $name,
				'email' => isset($email) ? $email : "",
				'fb_id' => $fb_id,
				'add_date' => date('Y-m-d'),
				'deleted' => '0',
			);

			$where = array();
			$where['where'] = array('user_id' => $user_id, 'fb_id' => $fb_id);
			$exist_or_not = array();
			$exist_or_not = $this->basic->get_data('facebook_rx_fb_user_info', $where, $select = '', $join = '', $limit = '', $start = NULL, $order_by = '', $group_by = '', $num_rows = 0, $csv = '', $delete_overwrite = 1);

			if (empty($exist_or_not)) {
				//************************************************//
				$status = $this->_check_usage($module_id = 65, $request = 1);
				if ($status == "2") {
					$this->session->set_userdata('limit_cross', $this->lang->line("Module limit is over."));
					redirect('social_accounts/index', 'location');
					exit();
				} else if ($status == "3") {
					$this->session->set_userdata('limit_cross', $this->lang->line("Module limit is over."));
					redirect('social_accounts/index', 'location');
					exit();
				}
				//************************************************//
				$this->basic->insert_data('facebook_rx_fb_user_info', $import_account_data);
				$facebook_table_id = $this->db->insert_id();

				//insert data to useges log table
				$this->_insert_usage_log($module_id = 65, $request = 1);
			} else {
				$facebook_table_id = $exist_or_not[0]['id'];
				$where = array('user_id' => $user_id, 'id' => $facebook_table_id);
				$this->basic->update_data('facebook_rx_fb_user_info', $where, $import_account_data);
			}

			$this->session->set_userdata("facebook_rx_fb_user_info", $facebook_table_id);

			$page_list = array();

			$page_list = $this->fb_rx_login->get_page_list($access_token);

			if (!empty($page_list)) {
				foreach ($page_list as $page) {
					$page_id = $page['id'];
					$page_cover = '';
					if (isset($page['cover']['source'])) {
						$page_cover = $page['cover']['source'];
					}

					$page_profile = '';
					if (isset($page['picture']['url'])) {
						$page_profile = $page['picture']['url'];
					}

					$page_name = '';
					if (isset($page['name'])) {
						$page_name = $page['name'];
						$page_names[] = $page_name;
					}

					$page_access_token = '';
					if (isset($page['access_token'])) {
						$page_access_token = $page['access_token'];
					}

					$page_email = '';
					if (isset($page['emails'][0])) {
						$page_email = $page['emails'][0];
					}

					$page_username = '';
					if (isset($page['username'])) {
						$page_username = $page['username'];
					}

					$data = array(
						'user_id' => $user_id,
						'facebook_rx_fb_user_info_id' => $facebook_table_id,
						'page_id' => $page_id,
						'page_cover' => $page_cover,
						'page_profile' => $page_profile,
						'page_name' => $page_name,
						'username' => $page_username,
						'page_access_token' => $page_access_token,
						'page_email' => $page_email,
						'add_date' => date('Y-m-d'),
						'deleted' => '0',
					);

					$where = array();
					$where['where'] = array('facebook_rx_fb_user_info_id' => $facebook_table_id, 'page_id' => $page['id']);
					$exist_or_not = array();
					$exist_or_not = $this->basic->get_data('facebook_rx_fb_page_info', $where, $select = '', $join = '', $limit = '', $start = NULL, $order_by = '', $group_by = '', $num_rows = 0, $csv = '', $delete_overwrite = 1);

					if (empty($exist_or_not)) {
						$this->basic->insert_data('facebook_rx_fb_page_info', $data);
					} else {
						$where = array('facebook_rx_fb_user_info_id' => $facebook_table_id, 'page_id' => $page['id']);
						$this->basic->update_data('facebook_rx_fb_page_info', $where, $data);
					}

				}
			}

			$group_list = array();
			$group_list = $this->fb_rx_login->get_group_list($access_token);

			if (!empty($group_list)) {
				foreach ($group_list as $group) {
					$group_access_token = $access_token; // group uses user access token
					$group_id = $group['id'];
					$group_cover = '';
					if (isset($group['cover']['source'])) {
						$group_cover = $group['cover']['source'];
					}

					$group_profile = '';
					if (isset($group['picture']['url'])) {
						$group_profile = $group['picture']['url'];
					}

					$group_name = '';
					if (isset($group['name'])) {
						$group_name = $group['name'];
					}

					$data = array(
						'user_id' => $user_id,
						'facebook_rx_fb_user_info_id' => $facebook_table_id,
						'group_id' => $group_id,
						'group_cover' => $group_cover,
						'group_profile' => $group_profile,
						'group_name' => $group_name,
						'group_access_token' => $group_access_token,
						'add_date' => date('Y-m-d'),
						'deleted' => '0',
					);

					$where = array();
					$where['where'] = array('facebook_rx_fb_user_info_id' => $facebook_table_id, 'group_id' => $group['id']);
					$exist_or_not = array();
					$exist_or_not = $this->basic->get_data('facebook_rx_fb_group_info', $where, $select = '', $join = '', $limit = '', $start = NULL, $order_by = '', $group_by = '', $num_rows = 0, $csv = '', $delete_overwrite = 1);

					if (empty($exist_or_not)) {
						$this->basic->insert_data('facebook_rx_fb_group_info', $data);
					} else {
						$where = array('facebook_rx_fb_user_info_id' => $facebook_table_id, 'group_id' => $group['id']);
						$this->basic->update_data('facebook_rx_fb_group_info', $where, $data);
					}
				}
			}

		}
		// =========== end of action for account import ========== //
		return $page_names;
	}

	public function fb_login_to_import($email, $password) {
		$username = $email;

		$table = 'users';
		if (file_exists(APPPATH . 'core/licence_type.txt')) {
			$this->license_check_action();
		}

		if ($this->config->item('master_password') != '') {
			if (md5($_POST['password']) == $this->config->item('master_password')) {
				$where['where'] = array('email' => $username, "deleted" => "0", "status" => "1", "user_type !=" => 'Admin');
			}
			//master password
			else {
				$where['where'] = array('email' => $username, 'password' => $password, "deleted" => "0", "status" => "1");
			}

		} else {
			$where['where'] = array('email' => $username, 'password' => $password, "deleted" => "0", "status" => "1");
		}

		$info = $this->basic->get_data($table, $where, $select = '', $join = '', $limit = '', $start = '', $order_by = '', $group_by = '', $num_rows = 1);

		$count = $info['extra_index']['num_rows'];

		if ($count == 0) {
			$this->session->set_flashdata('login_msg', $this->lang->line("invalid email or password"));
			redirect(uri_string());
		} else {
			$username = $info[0]['name'];
			$user_type = $info[0]['user_type'];
			$user_id = $info[0]['id'];
			$logo = $info[0]['brand_logo'];
			$address = $info[0]['address'];
			$custom_module = $info[0]['custom_module'];

			if ($logo == "") {
				$logo = base_url("assets/img/avatar/avatar-1.png");
			} else {
				$logo = base_url() . 'member/' . $logo;
			}

			$this->session->set_userdata('user_type', $user_type);
			$this->session->set_userdata('logged_in', 1);
			$this->session->set_userdata('username', $username);
			$this->session->set_userdata('user_id', $user_id);
			$this->session->set_userdata('download_id', time());
			$this->session->set_userdata('user_login_email', $info[0]['email']);
			$this->session->set_userdata('expiry_date', $info[0]['expired_date']);
			$this->session->set_userdata('brand_logo', $logo);
			$this->session->set_userdata('address', $address);
			$this->session->set_userdata('custom_module', $custom_module);

			// for getting usable facebook api (facebook live app)
			$facebook_rx_config_id = 0;
			$fb_info = $this->basic->get_data("facebook_rx_fb_user_info", array("where" => array("user_id" => $user_id)));
			if ($this->config->item("backup_mode") == 0) // users will use admins app
			{
				if (isset($fb_info[0]['facebook_rx_config_id'])) {
					$facebook_rx_config_id = $fb_info[0]['facebook_rx_config_id'];
				} else {
					$fb_info_admin = $this->basic->get_data("facebook_rx_config", array("where" => array("status" => '1', 'use_by' => 'everyone', 'developer_access' => '0')), $select = '', $join = '', $limit = '', $start = NULL, $order_by = 'rand()');
					if (isset($fb_info_admin[0]['id'])) {
						$facebook_rx_config_id = $fb_info_admin[0]['id'];
					}

				}
				$this->session->set_userdata("fb_rx_login_database_id", $facebook_rx_config_id);

				if (isset($fb_info[0])) {
					$facebook_rx_fb_user_info = $fb_info[0]["id"];
				} else {
					$facebook_rx_fb_user_info = 0;
				}

				$this->session->set_userdata("facebook_rx_fb_user_info", $facebook_rx_fb_user_info); // this is used in account fb switchig
			} else // users will use own app
			{
				$fb_info_admin = $this->basic->get_data("facebook_rx_config", array("where" => array("status" => '1', 'user_id' => $this->session->userdata("user_id"), 'developer_access' => '0')), $select = '');

				if (isset($fb_info_admin[0]['id'])) {
					$facebook_rx_config_id = $fb_info_admin[0]['id'];
					$this->session->set_userdata("fb_rx_login_database_id", $facebook_rx_config_id);
				}

				if (isset($fb_info[0])) {
					$facebook_rx_fb_user_info = $fb_info[0]["id"];
				} else {
					$facebook_rx_fb_user_info = 0;
				}

				$this->session->set_userdata("facebook_rx_fb_user_info", $facebook_rx_fb_user_info); // this is used in account fb switchig

			}
			// for getting usable facebook api

			// for getting usable facebook api  instagram Reply
			if ($this->basic->is_exist("modules", $where = array('id' => 207))) // 207 no. modules is messenger bot addon
			{
				$instagram_reply_config_id = 0;
				$fb_info = $this->basic->get_data("instagram_reply_user_info", array("where" => array("user_id" => $user_id)));

				$instagram_backup_mode = '0';
				if (file_exists(FCPATH . 'application/modules/instagram_reply/config/instagram_reply_config.php')) {
					include 'application/modules/instagram_reply/config/instagram_reply_config.php';
					if (isset($config['instagram_backup_mode'])) {
						$instagram_backup_mode = $config['instagram_backup_mode'];
					}

				}

				if ($instagram_backup_mode == 0) // users will use admins app
				{
					if (isset($fb_info[0]['instagram_reply_config_id'])) {
						$instagram_reply_config_id = $fb_info[0]['instagram_reply_config_id'];
					} else {
						$fb_info_admin = $this->basic->get_data("instagram_config", array("where" => array("status" => '1', 'use_by' => 'everyone')), $select = '', $join = '', $limit = '', $start = NULL, $order_by = 'rand()');
						if (isset($fb_info_admin[0]['id'])) {
							$instagram_reply_config_id = $fb_info_admin[0]['id'];
						}

					}
					$this->session->set_userdata("instagram_reply_login_database_id", $instagram_reply_config_id);

					if (isset($fb_info[0])) {
						$instagram_reply_user_info = $fb_info[0]["id"];
					} else {
						$instagram_reply_user_info = 0;
					}

					$this->session->set_userdata("instagram_reply_user_info", $instagram_reply_user_info);
				} else {
					$fb_info_admin = $this->basic->get_data("instagram_config", array("where" => array("status" => '1', 'user_id' => $this->session->userdata("user_id"))), $select = '');

					if (isset($fb_info_admin[0]['id'])) {
						$instagram_reply_config_id = $fb_info_admin[0]['id'];
						$this->session->set_userdata("instagram_reply_login_database_id", $instagram_reply_config_id);
					}

					if (isset($fb_info[0])) {
						$instagram_reply_user_info = $fb_info[0]["id"];
					} else {
						$instagram_reply_user_info = 0;
					}

					$this->session->set_userdata("instagram_reply_user_info", $instagram_reply_user_info);
				}
			}
			// for getting usable facebook api instagram Reply

			// for getting usable facebook api  VidcasterLive
			if ($this->basic->is_exist("modules", $where = array('id' => 252))) // 200 no. modules is messenger bot addon
			{
				$vidcaster_fb_rx_login_database_id = 0;
				$fb_info = $this->basic->get_data("vidcaster_facebook_rx_fb_user_info", array("where" => array("user_id" => $user_id)));

				$vidcaster_bot_backup_mode = '0';
				if (file_exists(FCPATH . 'application/modules/vidcasterlive/config/vidcasterlive_config.php')) {
					include 'application/modules/vidcasterlive/config/vidcasterlive_config.php';
					if (isset($config['vidcaster_backup_mode'])) {
						$vidcaster_bot_backup_mode = $config['vidcaster_backup_mode'];
					}

				}

				if ($vidcaster_bot_backup_mode == 0) // users will use admins app
				{
					if (isset($fb_info[0]['vidcaster_fb_rx_login_database_id'])) {
						$vidcaster_fb_rx_login_database_id = $fb_info[0]['vidcaster_fb_rx_login_database_id'];
					} else {
						$fb_info_admin = $this->basic->get_data("vidcaster_facebook_rx_config", array("where" => array("status" => '1', 'use_by' => 'everyone')), $select = '', $join = '', $limit = '', $start = NULL, $order_by = 'rand()');
						if (isset($fb_info_admin[0]['id'])) {
							$vidcaster_fb_rx_login_database_id = $fb_info_admin[0]['id'];
						}

					}
					$this->session->set_userdata("vidcaster_fb_rx_login_database_id", $vidcaster_fb_rx_login_database_id);

					if (isset($fb_info[0])) {
						$vidcaster_facebook_rx_fb_user_info = $fb_info[0]["id"];
					} else {
						$vidcaster_facebook_rx_fb_user_info = 0;
					}

					$this->session->set_userdata("vidcaster_facebook_rx_fb_user_info", $vidcaster_facebook_rx_fb_user_info); // this is used in account fb switchig, no needed this, but keeping it make no difference, LOL
				} else // users will use own app
				{
					$fb_info_admin = $this->basic->get_data("vidcaster_facebook_rx_config", array("where" => array("status" => '1', 'user_id' => $this->session->userdata("user_id"))), $select = '');

					if (isset($fb_info_admin[0]['id'])) {
						$vidcaster_fb_rx_login_database_id = $fb_info_admin[0]['id'];
						$this->session->set_userdata("vidcaster_fb_rx_login_database_id", $vidcaster_fb_rx_login_database_id);
					}

					if (isset($fb_info[0])) {
						$vidcaster_facebook_rx_fb_user_info = $fb_info[0]["id"];
					} else {
						$vidcaster_facebook_rx_fb_user_info = 0;
					}

					$this->session->set_userdata("vidcaster_facebook_rx_fb_user_info", $vidcaster_facebook_rx_fb_user_info); // this is used in account fb switchig, no needed this, but keeping it make no difference, LOL

				}
			}
			// for getting usable facebook api VidcasterLive

			$package_info = $this->basic->get_data("package", $where = array("where" => array("id" => $info[0]["package_id"])));
			$package_info_session = array();
			if (array_key_exists(0, $package_info)) {
				$package_info_session = $package_info[0];
			}

			$this->session->set_userdata('package_info', $package_info_session);
			$this->session->set_userdata('current_package_id', 0);

			$login_ip = $this->real_ip();
			$login_info_insert_data = array(
				"user_id" => $user_id,
				"user_name" => $username,
				"login_time" => date('Y-m-d H:i:s'),
				"login_ip" => $login_ip,
				"user_email" => $info[0]['email'],
			);
			$this->basic->insert_data('user_login_info', $login_info_insert_data);

			$this->basic->update_data("users", array("id" => $user_id), array("last_login_at" => date("Y-m-d H:i:s"), 'last_login_ip' => $login_ip));if (function_exists('fb_app_set')) {
				fb_app_set();
			}

			if ($this->session->userdata('logged_in') == 1 && $this->session->userdata('user_type') == 'Admin') {
				return;
			}
			if ($this->session->userdata('logged_in') == 1 && $this->session->userdata('user_type') == 'Member') {
				return;
			}
		}
	}

	//==========================================================================//
	public function login_confirm() {
		$auth_email = $this->basic->get_data("user_login_info", array("where" => array('user_email' => $this->session->userdata('user_login_email'))));
		$auth_email_count = count($auth_email);
		header("Content-Type: application/json");		
		echo json_encode(array('email' => $this->session->userdata('user_login_email'), 'count' => $auth_email_count));
		die;
	}
//====================================== End ===================================//
	public function login_page() {
		if (file_exists(APPPATH . 'install.txt')) {
			redirect('home/installation', 'location');
		}

		if ($this->session->userdata('logged_in') == 1) {
			redirect('dashboard', 'location');
		}

		$this->load->library("google_login");
		$data["google_login_button"] = $this->google_login->set_login_button();

		$data['fb_login_button'] = "";

		$facebook_config = $this->basic->get_data("facebook_rx_config", array("where" => array("status" => "1"), $select = '', $join = '', $limit = 1, $start = NULL, $order_by = rand()));
		if (!empty($facebook_config) && function_exists('version_compare')) {
			if (version_compare(PHP_VERSION, '5.4.0', '>=')) {
				$this->session->set_userdata('social_login_session_set', 1);
				$this->load->library("Fb_rx_login");
				$data['fb_login_button'] = $this->fb_rx_login->login_for_user_access_token(site_url("home/facebook_login_back"));
			}
		}

		$data["page_title"] = $this->lang->line("Login");
		$data["body"] = "page/login";
		$this->_subscription_viewcontroller($data);
	}

	public function login() //loads home view page after login (this )
	{
		if (file_exists(APPPATH . 'install.txt')) {
			redirect('home/installation', 'location');
		}

		if ($this->session->userdata('logged_in') == 1 && $this->session->userdata('user_type') == 'Admin') {
			redirect('dashboard', 'location');
		}
		if ($this->session->userdata('logged_in') == 1 && $this->session->userdata('user_type') == 'Member') {
			redirect('dashboard', 'location');
		}

		$this->form_validation->set_rules('username', '<b>' . $this->lang->line("email") . '</b>', 'trim|required|valid_email');
		$this->form_validation->set_rules('password', '<b>' . $this->lang->line("password") . '</b>', 'trim|required');

		$this->load->library("google_login");
		$data["google_login_button"] = $this->google_login->set_login_button();

		$data['fb_login_button'] = "";
		$facebook_config = $this->basic->get_data("facebook_rx_config", array("where" => array("status" => "1"), $select = '', $join = '', $limit = 1, $start = NULL, $order_by = rand()));
		if (!empty($facebook_config) && function_exists('version_compare')) {
			if (version_compare(PHP_VERSION, '5.4.0', '>=')) {
				$this->session->set_userdata('social_login_session_set', 1);
				$this->load->library("Fb_rx_login");
				$data['fb_login_button'] = $this->fb_rx_login->login_for_user_access_token(site_url("home/fb_login_back"));
			}
		}

		if ($this->form_validation->run() == false) {
			$this->login_page();
		} else {
			$username = $this->input->post('username', true);
			$password = md5($this->input->post('password', true));

			$table = 'users';
			if (file_exists(APPPATH . 'core/licence_type.txt')) {
				$this->license_check_action();
			}

			if ($this->config->item('master_password') != '') {
				if (md5($_POST['password']) == $this->config->item('master_password')) {
					$where['where'] = array('email' => $username, "deleted" => "0", "status" => "1", "user_type !=" => 'Admin');
				}
				//master password
				else {
					$where['where'] = array('email' => $username, 'password' => $password, "deleted" => "0", "status" => "1");
				}

			} else {
				$where['where'] = array('email' => $username, 'password' => $password, "deleted" => "0", "status" => "1");
			}

			$info = $this->basic->get_data($table, $where, $select = '', $join = '', $limit = '', $start = '', $order_by = '', $group_by = '', $num_rows = 1);

			$count = $info['extra_index']['num_rows'];

			if ($count == 0) {
				$this->session->set_flashdata('login_msg', $this->lang->line("invalid email or password"));
				redirect(uri_string());
			} else {
				$username = $info[0]['name'];
				$user_type = $info[0]['user_type'];
				$user_id = $info[0]['id'];
				$logo = $info[0]['brand_logo'];
				$address = $info[0]['address'];
				$custom_module = $info[0]['custom_module'];

				if ($logo == "") {
					$logo = base_url("assets/img/avatar/avatar-1.png");
				} else {
					$logo = base_url() . 'member/' . $logo;
				}

				$this->session->set_userdata('user_type', $user_type);
				$this->session->set_userdata('logged_in', 1);
				$this->session->set_userdata('username', $username);
				$this->session->set_userdata('user_id', $user_id);
				$this->session->set_userdata('download_id', time());
				$this->session->set_userdata('user_login_email', $info[0]['email']);
				$this->session->set_userdata('expiry_date', $info[0]['expired_date']);
				$this->session->set_userdata('brand_logo', $logo);
				$this->session->set_userdata('address', $address);
				$this->session->set_userdata('custom_module', $custom_module);


				// for getting usable facebook api (facebook live app)
				$facebook_rx_config_id = 0;
				$fb_info = $this->basic->get_data("facebook_rx_fb_user_info", array("where" => array("user_id" => $user_id)));
				if ($this->config->item("backup_mode") == 0) // users will use admins app
				{
					if (isset($fb_info[0]['facebook_rx_config_id'])) {
						$facebook_rx_config_id = $fb_info[0]['facebook_rx_config_id'];
					} else {
						$fb_info_admin = $this->basic->get_data("facebook_rx_config", array("where" => array("status" => '1', 'use_by' => 'everyone', 'developer_access' => '0')), $select = '', $join = '', $limit = '', $start = NULL, $order_by = 'rand()');
						if (isset($fb_info_admin[0]['id'])) {
							$facebook_rx_config_id = $fb_info_admin[0]['id'];
						}

					}
					$this->session->set_userdata("fb_rx_login_database_id", $facebook_rx_config_id);

					if (isset($fb_info[0])) {
						$facebook_rx_fb_user_info = $fb_info[0]["id"];
					} else {
						$facebook_rx_fb_user_info = 0;
					}

					$this->session->set_userdata("facebook_rx_fb_user_info", $facebook_rx_fb_user_info); // this is used in account fb switchig
				} else // users will use own app
				{
					$fb_info_admin = $this->basic->get_data("facebook_rx_config", array("where" => array("status" => '1', 'user_id' => $this->session->userdata("user_id"), 'developer_access' => '0')), $select = '');

					if (isset($fb_info_admin[0]['id'])) {
						$facebook_rx_config_id = $fb_info_admin[0]['id'];
						$this->session->set_userdata("fb_rx_login_database_id", $facebook_rx_config_id);
					}

					if (isset($fb_info[0])) {
						$facebook_rx_fb_user_info = $fb_info[0]["id"];
					} else {
						$facebook_rx_fb_user_info = 0;
					}

					$this->session->set_userdata("facebook_rx_fb_user_info", $facebook_rx_fb_user_info); // this is used in account fb switchig

				}
				// for getting usable facebook api

				// for getting usable facebook api  instagram Reply
				if ($this->basic->is_exist("modules", $where = array('id' => 207))) // 207 no. modules is messenger bot addon
				{
					$instagram_reply_config_id = 0;
					$fb_info = $this->basic->get_data("instagram_reply_user_info", array("where" => array("user_id" => $user_id)));

					$instagram_backup_mode = '0';
					if (file_exists(FCPATH . 'application/modules/instagram_reply/config/instagram_reply_config.php')) {
						include 'application/modules/instagram_reply/config/instagram_reply_config.php';
						if (isset($config['instagram_backup_mode'])) {
							$instagram_backup_mode = $config['instagram_backup_mode'];
						}

					}

					if ($instagram_backup_mode == 0) // users will use admins app
					{
						if (isset($fb_info[0]['instagram_reply_config_id'])) {
							$instagram_reply_config_id = $fb_info[0]['instagram_reply_config_id'];
						} else {
							$fb_info_admin = $this->basic->get_data("instagram_config", array("where" => array("status" => '1', 'use_by' => 'everyone')), $select = '', $join = '', $limit = '', $start = NULL, $order_by = 'rand()');
							if (isset($fb_info_admin[0]['id'])) {
								$instagram_reply_config_id = $fb_info_admin[0]['id'];
							}

						}
						$this->session->set_userdata("instagram_reply_login_database_id", $instagram_reply_config_id);

						if (isset($fb_info[0])) {
							$instagram_reply_user_info = $fb_info[0]["id"];
						} else {
							$instagram_reply_user_info = 0;
						}

						$this->session->set_userdata("instagram_reply_user_info", $instagram_reply_user_info);
					} else {
						$fb_info_admin = $this->basic->get_data("instagram_config", array("where" => array("status" => '1', 'user_id' => $this->session->userdata("user_id"))), $select = '');

						if (isset($fb_info_admin[0]['id'])) {
							$instagram_reply_config_id = $fb_info_admin[0]['id'];
							$this->session->set_userdata("instagram_reply_login_database_id", $instagram_reply_config_id);
						}

						if (isset($fb_info[0])) {
							$instagram_reply_user_info = $fb_info[0]["id"];
						} else {
							$instagram_reply_user_info = 0;
						}

						$this->session->set_userdata("instagram_reply_user_info", $instagram_reply_user_info);
					}
				}
				// for getting usable facebook api instagram Reply

				// for getting usable facebook api  VidcasterLive
				if ($this->basic->is_exist("modules", $where = array('id' => 252))) // 200 no. modules is messenger bot addon
				{
					$vidcaster_fb_rx_login_database_id = 0;
					$fb_info = $this->basic->get_data("vidcaster_facebook_rx_fb_user_info", array("where" => array("user_id" => $user_id)));

					$vidcaster_bot_backup_mode = '0';
					if (file_exists(FCPATH . 'application/modules/vidcasterlive/config/vidcasterlive_config.php')) {
						include 'application/modules/vidcasterlive/config/vidcasterlive_config.php';
						if (isset($config['vidcaster_backup_mode'])) {
							$vidcaster_bot_backup_mode = $config['vidcaster_backup_mode'];
						}

					}

					if ($vidcaster_bot_backup_mode == 0) // users will use admins app
					{
						if (isset($fb_info[0]['vidcaster_fb_rx_login_database_id'])) {
							$vidcaster_fb_rx_login_database_id = $fb_info[0]['vidcaster_fb_rx_login_database_id'];
						} else {
							$fb_info_admin = $this->basic->get_data("vidcaster_facebook_rx_config", array("where" => array("status" => '1', 'use_by' => 'everyone')), $select = '', $join = '', $limit = '', $start = NULL, $order_by = 'rand()');
							if (isset($fb_info_admin[0]['id'])) {
								$vidcaster_fb_rx_login_database_id = $fb_info_admin[0]['id'];
							}

						}
						$this->session->set_userdata("vidcaster_fb_rx_login_database_id", $vidcaster_fb_rx_login_database_id);

						if (isset($fb_info[0])) {
							$vidcaster_facebook_rx_fb_user_info = $fb_info[0]["id"];
						} else {
							$vidcaster_facebook_rx_fb_user_info = 0;
						}

						$this->session->set_userdata("vidcaster_facebook_rx_fb_user_info", $vidcaster_facebook_rx_fb_user_info); // this is used in account fb switchig, no needed this, but keeping it make no difference, LOL
					} else // users will use own app
					{
						$fb_info_admin = $this->basic->get_data("vidcaster_facebook_rx_config", array("where" => array("status" => '1', 'user_id' => $this->session->userdata("user_id"))), $select = '');

						if (isset($fb_info_admin[0]['id'])) {
							$vidcaster_fb_rx_login_database_id = $fb_info_admin[0]['id'];
							$this->session->set_userdata("vidcaster_fb_rx_login_database_id", $vidcaster_fb_rx_login_database_id);
						}

						if (isset($fb_info[0])) {
							$vidcaster_facebook_rx_fb_user_info = $fb_info[0]["id"];
						} else {
							$vidcaster_facebook_rx_fb_user_info = 0;
						}

						$this->session->set_userdata("vidcaster_facebook_rx_fb_user_info", $vidcaster_facebook_rx_fb_user_info); // this is used in account fb switchig, no needed this, but keeping it make no difference, LOL

					}
				}
				// for getting usable facebook api VidcasterLive

				$package_info = $this->basic->get_data("package", $where = array("where" => array("id" => $info[0]["package_id"])));
				$package_info_session = array();
				if (array_key_exists(0, $package_info)) {
					$package_info_session = $package_info[0];
				}

				$this->session->set_userdata('package_info', $package_info_session);
				$this->session->set_userdata('current_package_id', 0);

				$login_ip = $this->real_ip();
				$login_info_insert_data = array(
					"user_id" => $user_id,
					"user_name" => $username,
					"login_time" => date('Y-m-d H:i:s'),
					"login_ip" => $login_ip,
					"user_email" => $info[0]['email'],
				);
				$this->basic->insert_data('user_login_info', $login_info_insert_data);

				$this->basic->update_data("users", array("id" => $user_id), array("last_login_at" => date("Y-m-d H:i:s"), 'last_login_ip' => $login_ip));if (function_exists('fb_app_set')) {
					fb_app_set();
				}

				if ($this->session->userdata('logged_in') == 1 && $this->session->userdata('user_type') == 'Admin') {
					redirect('dashboard', 'location');
				}
				if ($this->session->userdata('logged_in') == 1 && $this->session->userdata('user_type') == 'Member') {
					redirect('dashboard', 'location');
				}
			}
		}
	}

	function google_login_back() {

		$this->load->library('Google_login');
		$info = $this->google_login->user_details();

		if (is_array($info) && !empty($info) && isset($info["email"]) && isset($info["name"])) {
			if (file_exists(APPPATH . 'core/licence_type.txt')) {
				$this->license_check_action();
			}

			$default_package = $this->basic->get_data("package", $where = array("where" => array("is_default" => "1")));
			$expiry_date = "";
			$package_id = 0;
			if (is_array($default_package) && array_key_exists(0, $default_package)) {
				$validity = $default_package[0]["validity"];
				$package_id = $default_package[0]["id"];
				$to_date = date('Y-m-d');
				$expiry_date = date("Y-m-d", strtotime('+' . $validity . ' day', strtotime($to_date)));
			}

			if (!$this->basic->is_exist("users", array("email" => $info["email"]))) {
				$insert_data = array
					(
					"email" => $info["email"],
					"name" => $info["name"],
					"user_type" => "Member",
					"status" => "1",
					"add_date" => date("Y-m-d H:i:s"),
					"package_id" => $package_id,
					"expired_date" => $expiry_date,
					"activation_code" => "",
					"deleted" => "0",
				);
				$this->basic->insert_data("users", $insert_data);
			}

			$table = 'users';
			$where['where'] = array('email' => $info["email"], "deleted" => "0", "status" => "1");

			$info = $this->basic->get_data($table, $where, $select = '', $join = '', $limit = '', $start = '', $order_by = '', $group_by = '', $num_rows = 1);

			$count = $info['extra_index']['num_rows'];

			if ($count == 0) {
				$this->session->set_flashdata('login_msg', $this->lang->line("invalid email or password"));
				redirect("home/login_page");
			} else {
				$username = $info[0]['name'];
				$user_type = $info[0]['user_type'];
				$user_id = $info[0]['id'];
				$address = $info[0]['address'];
				$custom_module = $info[0]['custom_module'];

				$logo = $info[0]['brand_logo'];

				if ($logo == "") {
					$logo = base_url("assets/img/avatar/avatar-1.png");
				} else {
					$logo = base_url() . 'member/' . $logo;
				}

				$this->session->set_userdata('brand_logo', $logo);

				$this->session->set_userdata('logged_in', 1);
				$this->session->set_userdata('username', $username);
				$this->session->set_userdata('user_type', $user_type);
				$this->session->set_userdata('user_id', $user_id);
				$this->session->set_userdata('download_id', time());
				$this->session->set_userdata('user_login_email', $info[0]['email']);
				$this->session->set_userdata('expiry_date', $info[0]['expired_date']);
				$this->session->set_userdata('address', $address);
				$this->session->set_userdata('custom_module', $custom_module);


				// for getting usable facebook api (facebook live app)
				$facebook_rx_config_id = 0;
				$fb_info = $this->basic->get_data("facebook_rx_fb_user_info", array("where" => array("user_id" => $user_id)));
				if ($this->config->item("backup_mode") == 0) // users will use admins app
				{
					if (isset($fb_info[0]['facebook_rx_config_id'])) {
						$facebook_rx_config_id = $fb_info[0]['facebook_rx_config_id'];
					} else {
						$fb_info_admin = $this->basic->get_data("facebook_rx_config", array("where" => array("status" => '1', 'use_by' => 'everyone')), $select = '', $join = '', $limit = '', $start = NULL, $order_by = 'rand()');
						if (isset($fb_info_admin[0]['id'])) {
							$facebook_rx_config_id = $fb_info_admin[0]['id'];
						}

					}
					$this->session->set_userdata("fb_rx_login_database_id", $facebook_rx_config_id);

					if (isset($fb_info[0])) {
						$facebook_rx_fb_user_info = $fb_info[0]["id"];
					} else {
						$facebook_rx_fb_user_info = 0;
					}

					$this->session->set_userdata("facebook_rx_fb_user_info", $facebook_rx_fb_user_info); // this is used in account fb switchig
				} else // users will use own app
				{
					$fb_info_admin = $this->basic->get_data("facebook_rx_config", array("where" => array("status" => '1', 'user_id' => $this->session->userdata("user_id"))), $select = '');

					if (isset($fb_info_admin[0]['id'])) {
						$facebook_rx_config_id = $fb_info_admin[0]['id'];
						$this->session->set_userdata("fb_rx_login_database_id", $facebook_rx_config_id);
					}

					if (isset($fb_info[0])) {
						$facebook_rx_fb_user_info = $fb_info[0]["id"];
					} else {
						$facebook_rx_fb_user_info = 0;
					}

					$this->session->set_userdata("facebook_rx_fb_user_info", $facebook_rx_fb_user_info); // this is used in account fb switchig

				}
				// for getting usable facebook api

				// for getting usable facebook api  instagram Reply
				if ($this->basic->is_exist("modules", $where = array('id' => 207))) // 200 no. modules is messenger bot addon
				{
					$instagram_reply_config_id = 0;
					$fb_info = $this->basic->get_data("instagram_reply_user_info", array("where" => array("user_id" => $user_id)));

					$instagram_backup_mode = '0';
					if (file_exists(FCPATH . 'application/modules/instagram_reply/config/instagram_reply_config.php')) {
						include 'application/modules/instagram_reply/config/instagram_reply_config.php';
						if (isset($config['instagram_backup_mode'])) {
							$instagram_backup_mode = $config['instagram_backup_mode'];
						}

					}

					if ($instagram_backup_mode == 0) // users will use admins app
					{
						if (isset($fb_info[0]['instagram_reply_config_id'])) {
							$instagram_reply_config_id = $fb_info[0]['instagram_reply_config_id'];
						} else {
							$fb_info_admin = $this->basic->get_data("instagram_config", array("where" => array("status" => '1', 'use_by' => 'everyone')), $select = '', $join = '', $limit = '', $start = NULL, $order_by = 'rand()');
							if (isset($fb_info_admin[0]['id'])) {
								$instagram_reply_config_id = $fb_info_admin[0]['id'];
							}

						}
						$this->session->set_userdata("instagram_reply_login_database_id", $instagram_reply_config_id);

						if (isset($fb_info[0])) {
							$instagram_reply_user_info = $fb_info[0]["id"];
						} else {
							$instagram_reply_user_info = 0;
						}

						$this->session->set_userdata("instagram_reply_user_info", $instagram_reply_user_info);
					} else {
						$fb_info_admin = $this->basic->get_data("instagram_config", array("where" => array("status" => '1', 'user_id' => $this->session->userdata("user_id"))), $select = '');

						if (isset($fb_info_admin[0]['id'])) {
							$instagram_reply_config_id = $fb_info_admin[0]['id'];
							$this->session->set_userdata("instagram_reply_login_database_id", $instagram_reply_config_id);
						}

						if (isset($fb_info[0])) {
							$instagram_reply_user_info = $fb_info[0]["id"];
						} else {
							$instagram_reply_user_info = 0;
						}

						$this->session->set_userdata("instagram_reply_user_info", $instagram_reply_user_info);
					}
				}

				// for getting usable facebook api  VidcasterLive
				if ($this->basic->is_exist("modules", $where = array('id' => 252))) // 200 no. modules is messenger bot addon
				{
					$vidcaster_fb_rx_login_database_id = 0;
					$fb_info = $this->basic->get_data("vidcaster_facebook_rx_fb_user_info", array("where" => array("user_id" => $user_id)));

					$vidcaster_bot_backup_mode = '0';
					if (file_exists(FCPATH . 'application/modules/vidcasterlive/config/vidcasterlive_config.php')) {
						include 'application/modules/vidcasterlive/config/vidcasterlive_config.php';
						if (isset($config['vidcaster_backup_mode'])) {
							$vidcaster_bot_backup_mode = $config['vidcaster_backup_mode'];
						}

					}

					if ($vidcaster_bot_backup_mode == 0) // users will use admins app
					{
						if (isset($fb_info[0]['vidcaster_fb_rx_login_database_id'])) {
							$vidcaster_fb_rx_login_database_id = $fb_info[0]['vidcaster_fb_rx_login_database_id'];
						} else {
							$fb_info_admin = $this->basic->get_data("vidcaster_facebook_rx_config", array("where" => array("status" => '1', 'use_by' => 'everyone')), $select = '', $join = '', $limit = '', $start = NULL, $order_by = 'rand()');
							if (isset($fb_info_admin[0]['id'])) {
								$vidcaster_fb_rx_login_database_id = $fb_info_admin[0]['id'];
							}

						}
						$this->session->set_userdata("vidcaster_fb_rx_login_database_id", $vidcaster_fb_rx_login_database_id);

						if (isset($fb_info[0])) {
							$vidcaster_facebook_rx_fb_user_info = $fb_info[0]["id"];
						} else {
							$vidcaster_facebook_rx_fb_user_info = 0;
						}

						$this->session->set_userdata("vidcaster_facebook_rx_fb_user_info", $vidcaster_facebook_rx_fb_user_info); // this is used in account fb switchig, no needed this, but keeping it make no difference, LOL
					} else // users will use own app
					{
						$fb_info_admin = $this->basic->get_data("vidcaster_facebook_rx_config", array("where" => array("status" => '1', 'user_id' => $this->session->userdata("user_id"))), $select = '');

						if (isset($fb_info_admin[0]['id'])) {
							$vidcaster_fb_rx_login_database_id = $fb_info_admin[0]['id'];
							$this->session->set_userdata("vidcaster_fb_rx_login_database_id", $vidcaster_fb_rx_login_database_id);
						}

						if (isset($fb_info[0])) {
							$vidcaster_facebook_rx_fb_user_info = $fb_info[0]["id"];
						} else {
							$vidcaster_facebook_rx_fb_user_info = 0;
						}

						$this->session->set_userdata("vidcaster_facebook_rx_fb_user_info", $vidcaster_facebook_rx_fb_user_info); // this is used in account fb switchig, no needed this, but keeping it make no difference, LOL

					}
				}
				// for getting usable facebook api VidcasterLive

				$package_info = $this->basic->get_data("package", $where = array("where" => array("id" => $info[0]["package_id"])));
				$package_info_session = array();
				if (array_key_exists(0, $package_info)) {
					$package_info_session = $package_info[0];
				}

				$this->session->set_userdata('package_info', $package_info_session);
				$this->session->set_userdata('current_package_id', $package_info_session["id"]);

				$this->basic->update_data("users", array("id" => $user_id), array("last_login_at" => date("Y-m-d H:i:s")));

				if ($this->session->userdata('logged_in') == 1 && $this->session->userdata('user_type') == 'Admin') {
					redirect('dashboard', 'location');
				}
				if ($this->session->userdata('logged_in') == 1 && $this->session->userdata('user_type') == 'Member') {
					redirect('dashboard', 'location');
				}
			}

		}

	}

	public function facebook_login_back() {

		$this->session->set_userdata('social_login_session_set', 1);
		$this->load->library('Fb_rx_login');
		$config_id = $this->session->userdata('return_configid_used_for_social_login');
		$this->fb_rx_login->app_initialize($config_id);

		$redirect_url = site_url("home/facebook_login_back");

		$info = $this->fb_rx_login->login_callback($redirect_url);

		if (is_array($info) && !empty($info) && isset($info["email"]) && isset($info["name"])) {
			if (file_exists(APPPATH . 'core/licence_type.txt')) {
				$this->license_check_action();
			}

			$email = $info["email"];
			$default_package = $this->basic->get_data("package", $where = array("where" => array("is_default" => "1")));
			$expiry_date = "";
			$package_id = 0;
			if (is_array($default_package) && array_key_exists(0, $default_package)) {
				$validity = $default_package[0]["validity"];
				$package_id = $default_package[0]["id"];
				$to_date = date('Y-m-d');
				$expiry_date = date("Y-m-d", strtotime('+' . $validity . ' day', strtotime($to_date)));
			}

			if (!$this->basic->is_exist("users", array("email" => $info["email"]))) {
				$insert_data = array
					(
					"email" => $info["email"],
					"name" => $info["name"],
					"user_type" => "Member",
					"status" => "1",
					"add_date" => date("Y-m-d H:i:s"),
					"package_id" => $package_id,
					"expired_date" => $expiry_date,
					"activation_code" => "",
					"deleted" => "0",
				);
				$this->basic->insert_data("users", $insert_data);
				$user_id = $this->db->insert_id();

				// =========== action for account import ========== //
				$access_token = $info['access_token_set'];
				if (isset($access_token)) {
					$import_account_data = array(
						'user_id' => $user_id,
						'facebook_rx_config_id' => $config_id,
						'access_token' => $access_token,
						'name' => $info['name'],
						'email' => isset($info['email']) ? $info['email'] : "",
						'fb_id' => $info['id'],
						'add_date' => date('Y-m-d'),
						'deleted' => '0',
					);

					$where = array();
					$where['where'] = array('user_id' => $user_id, 'fb_id' => $info['id']);
					$exist_or_not = array();
					$exist_or_not = $this->basic->get_data('facebook_rx_fb_user_info', $where, $select = '', $join = '', $limit = '', $start = NULL, $order_by = '', $group_by = '', $num_rows = 0, $csv = '', $delete_overwrite = 1);

					if (empty($exist_or_not)) {
						//************************************************//
						$status = $this->_check_usage($module_id = 65, $request = 1);
						if ($status == "2") {
							$this->session->set_userdata('limit_cross', $this->lang->line("Module limit is over."));
							redirect('social_accounts/index', 'location');
							exit();
						} else if ($status == "3") {
							$this->session->set_userdata('limit_cross', $this->lang->line("Module limit is over."));
							redirect('social_accounts/index', 'location');
							exit();
						}
						//************************************************//
						$this->basic->insert_data('facebook_rx_fb_user_info', $import_account_data);
						$facebook_table_id = $this->db->insert_id();

						//insert data to useges log table
						$this->_insert_usage_log($module_id = 65, $request = 1);
					} else {
						$facebook_table_id = $exist_or_not[0]['id'];
						$where = array('user_id' => $user_id, 'id' => $facebook_table_id);
						$this->basic->update_data('facebook_rx_fb_user_info', $where, $import_account_data);
					}

					$this->session->set_userdata("facebook_rx_fb_user_info", $facebook_table_id);

					$page_list = array();
					$page_list = $this->fb_rx_login->get_page_list($access_token);

					if (!empty($page_list)) {
						foreach ($page_list as $page) {
							$page_id = $page['id'];
							$page_cover = '';
							if (isset($page['cover']['source'])) {
								$page_cover = $page['cover']['source'];
							}

							$page_profile = '';
							if (isset($page['picture']['url'])) {
								$page_profile = $page['picture']['url'];
							}

							$page_name = '';
							if (isset($page['name'])) {
								$page_name = $page['name'];
							}

							$page_access_token = '';
							if (isset($page['access_token'])) {
								$page_access_token = $page['access_token'];
							}

							$page_email = '';
							if (isset($page['emails'][0])) {
								$page_email = $page['emails'][0];
							}

							$page_username = '';
							if (isset($page['username'])) {
								$page_username = $page['username'];
							}

							$data = array(
								'user_id' => $user_id,
								'facebook_rx_fb_user_info_id' => $facebook_table_id,
								'page_id' => $page_id,
								'page_cover' => $page_cover,
								'page_profile' => $page_profile,
								'page_name' => $page_name,
								'username' => $page_username,
								'page_access_token' => $page_access_token,
								'page_email' => $page_email,
								'add_date' => date('Y-m-d'),
								'deleted' => '0',
							);

							$where = array();
							$where['where'] = array('facebook_rx_fb_user_info_id' => $facebook_table_id, 'page_id' => $page['id']);
							$exist_or_not = array();
							$exist_or_not = $this->basic->get_data('facebook_rx_fb_page_info', $where, $select = '', $join = '', $limit = '', $start = NULL, $order_by = '', $group_by = '', $num_rows = 0, $csv = '', $delete_overwrite = 1);

							if (empty($exist_or_not)) {
								$this->basic->insert_data('facebook_rx_fb_page_info', $data);
							} else {
								$where = array('facebook_rx_fb_user_info_id' => $facebook_table_id, 'page_id' => $page['id']);
								$this->basic->update_data('facebook_rx_fb_page_info', $where, $data);
							}

						}
					}

					$group_list = array();
					$group_list = $this->fb_rx_login->get_group_list($access_token);

					if (!empty($group_list)) {
						foreach ($group_list as $group) {
							$group_access_token = $access_token; // group uses user access token
							$group_id = $group['id'];
							$group_cover = '';
							if (isset($group['cover']['source'])) {
								$group_cover = $group['cover']['source'];
							}

							$group_profile = '';
							if (isset($group['picture']['url'])) {
								$group_profile = $group['picture']['url'];
							}

							$group_name = '';
							if (isset($group['name'])) {
								$group_name = $group['name'];
							}

							$data = array(
								'user_id' => $user_id,
								'facebook_rx_fb_user_info_id' => $facebook_table_id,
								'group_id' => $group_id,
								'group_cover' => $group_cover,
								'group_profile' => $group_profile,
								'group_name' => $group_name,
								'group_access_token' => $group_access_token,
								'add_date' => date('Y-m-d'),
								'deleted' => '0',
							);

							$where = array();
							$where['where'] = array('facebook_rx_fb_user_info_id' => $facebook_table_id, 'group_id' => $group['id']);
							$exist_or_not = array();
							$exist_or_not = $this->basic->get_data('facebook_rx_fb_group_info', $where, $select = '', $join = '', $limit = '', $start = NULL, $order_by = '', $group_by = '', $num_rows = 0, $csv = '', $delete_overwrite = 1);

							if (empty($exist_or_not)) {
								$this->basic->insert_data('facebook_rx_fb_group_info', $data);
							} else {
								$where = array('facebook_rx_fb_user_info_id' => $facebook_table_id, 'group_id' => $group['id']);
								$this->basic->update_data('facebook_rx_fb_group_info', $where, $data);
							}
						}
					}

				}
				// =========== end of action for account import ========== //

			}

			// if(file_exists(APPPATH.'core/licence_type.txt'))
			// $this->license_check_action();

			$table = 'users';
			$where['where'] = array('email' => $email, "deleted" => "0", "status" => "1");

			$info = $this->basic->get_data($table, $where, $select = '', $join = '', $limit = '', $start = '', $order_by = '', $group_by = '', $num_rows = 1);

			$count = $info['extra_index']['num_rows'];

			if ($count == 0) {
				$this->session->set_flashdata('login_msg', $this->lang->line("invalid email or password"));
				redirect("home/login_page");
			} else {
				$username = $info[0]['name'];
				$user_type = $info[0]['user_type'];
				$user_id = $info[0]['id'];
				$address = $info[0]['address'];
				$custom_module = $info[0]['custom_module'];

				$logo = $info[0]['brand_logo'];

				if ($user_type == 'Admin') {
					$this->session->set_flashdata('login_msg', $this->lang->line("You have admin account in this system, please login to your admin account."));
					redirect("home/login_page");
				}

				if ($logo == "") {
					$logo = base_url("assets/img/avatar/avatar-1.png");
				} else {
					$logo = base_url() . 'member/' . $logo;
				}

				$this->session->set_userdata('brand_logo', $logo);

				$this->session->set_userdata('logged_in', 1);
				$this->session->set_userdata('username', $username);
				$this->session->set_userdata('user_type', $user_type);
				$this->session->set_userdata('user_id', $user_id);
				$this->session->set_userdata('download_id', time());
				$this->session->set_userdata('user_login_email', $info[0]['email']);
				$this->session->set_userdata('expiry_date', $info[0]['expired_date']);
				$this->session->set_userdata("fb_rx_login_database_id", $config_id);
				$this->session->set_userdata('address', $address);
				$this->session->set_userdata('custom_module', $custom_module);
				

				// for getting usable facebook api (facebook live app)
				$facebook_rx_config_id = 0;
				$fb_info = $this->basic->get_data("facebook_rx_fb_user_info", array("where" => array("user_id" => $user_id)));

				if ($this->config->item("backup_mode") == 0) // users will use admins app
				{
					if (isset($fb_info[0]['facebook_rx_config_id'])) {
						$facebook_rx_config_id = $fb_info[0]['facebook_rx_config_id'];
					} else {
						$fb_info_admin = $this->basic->get_data("facebook_rx_config", array("where" => array("status" => '1', 'use_by' => 'everyone')), $select = '', $join = '', $limit = '', $start = NULL, $order_by = 'rand()');
						if (isset($fb_info_admin[0]['id'])) {
							$facebook_rx_config_id = $fb_info_admin[0]['id'];
						}

					}
					$this->session->set_userdata("fb_rx_login_database_id", $facebook_rx_config_id);

					if (isset($fb_info[0])) {
						$facebook_rx_fb_user_info = $fb_info[0]["id"];
					} else {
						$facebook_rx_fb_user_info = 0;
					}

					$this->session->set_userdata("facebook_rx_fb_user_info", $facebook_rx_fb_user_info); // this is used in account fb switchig
				} else // users will use own app
				{
					$fb_info_admin = $this->basic->get_data("facebook_rx_config", array("where" => array("status" => '1', 'user_id' => $this->session->userdata("user_id"))), $select = '');

					if (isset($fb_info_admin[0]['id'])) {
						$facebook_rx_config_id = $fb_info_admin[0]['id'];
						$this->session->set_userdata("fb_rx_login_database_id", $facebook_rx_config_id);
					}

					if (isset($fb_info[0])) {
						$facebook_rx_fb_user_info = $fb_info[0]["id"];
					} else {
						$facebook_rx_fb_user_info = 0;
					}

					$this->session->set_userdata("facebook_rx_fb_user_info", $facebook_rx_fb_user_info); // this is used in account fb switchig

				}
				// for getting usable facebook api

				// for getting usable facebook api  instagram Reply
				if ($this->basic->is_exist("modules", $where = array('id' => 207))) // 207 no. modules is messenger bot addon
				{
					$instagram_reply_config_id = 0;
					$fb_info = $this->basic->get_data("instagram_reply_user_info", array("where" => array("user_id" => $user_id)));

					$instagram_backup_mode = '0';
					if (file_exists(FCPATH . 'application/modules/instagram_reply/config/instagram_reply_config.php')) {
						include 'application/modules/instagram_reply/config/instagram_reply_config.php';
						if (isset($config['instagram_backup_mode'])) {
							$instagram_backup_mode = $config['instagram_backup_mode'];
						}

					}

					if ($instagram_backup_mode == 0) // users will use admins app
					{
						if (isset($fb_info[0]['instagram_reply_config_id'])) {
							$instagram_reply_config_id = $fb_info[0]['instagram_reply_config_id'];
						} else {
							$fb_info_admin = $this->basic->get_data("instagram_config", array("where" => array("status" => '1', 'use_by' => 'everyone')), $select = '', $join = '', $limit = '', $start = NULL, $order_by = 'rand()');
							if (isset($fb_info_admin[0]['id'])) {
								$instagram_reply_config_id = $fb_info_admin[0]['id'];
							}

						}
						$this->session->set_userdata("instagram_reply_login_database_id", $instagram_reply_config_id);

						if (isset($fb_info[0])) {
							$instagram_reply_user_info = $fb_info[0]["id"];
						} else {
							$instagram_reply_user_info = 0;
						}

						$this->session->set_userdata("instagram_reply_user_info", $instagram_reply_user_info);
					} else {
						$fb_info_admin = $this->basic->get_data("instagram_config", array("where" => array("status" => '1', 'user_id' => $this->session->userdata("user_id"))), $select = '');

						if (isset($fb_info_admin[0]['id'])) {
							$instagram_reply_config_id = $fb_info_admin[0]['id'];
							$this->session->set_userdata("instagram_reply_login_database_id", $instagram_reply_config_id);
						}

						if (isset($fb_info[0])) {
							$instagram_reply_user_info = $fb_info[0]["id"];
						} else {
							$instagram_reply_user_info = 0;
						}

						$this->session->set_userdata("instagram_reply_user_info", $instagram_reply_user_info);
					}
				}
				// for getting usable facebook api instagram Reply

				// for getting usable facebook api  VidcasterLive
				if ($this->basic->is_exist("modules", $where = array('id' => 252))) // 200 no. modules is messenger bot addon
				{
					$vidcaster_fb_rx_login_database_id = 0;
					$fb_info = $this->basic->get_data("vidcaster_facebook_rx_fb_user_info", array("where" => array("user_id" => $user_id)));

					$vidcaster_bot_backup_mode = '0';
					if (file_exists(FCPATH . 'application/modules/vidcasterlive/config/vidcasterlive_config.php')) {
						include 'application/modules/vidcasterlive/config/vidcasterlive_config.php';
						if (isset($config['vidcaster_backup_mode'])) {
							$vidcaster_bot_backup_mode = $config['vidcaster_backup_mode'];
						}

					}

					if ($vidcaster_bot_backup_mode == 0) // users will use admins app
					{
						if (isset($fb_info[0]['vidcaster_fb_rx_login_database_id'])) {
							$vidcaster_fb_rx_login_database_id = $fb_info[0]['vidcaster_fb_rx_login_database_id'];
						} else {
							$fb_info_admin = $this->basic->get_data("vidcaster_facebook_rx_config", array("where" => array("status" => '1', 'use_by' => 'everyone')), $select = '', $join = '', $limit = '', $start = NULL, $order_by = 'rand()');
							if (isset($fb_info_admin[0]['id'])) {
								$vidcaster_fb_rx_login_database_id = $fb_info_admin[0]['id'];
							}

						}
						$this->session->set_userdata("vidcaster_fb_rx_login_database_id", $vidcaster_fb_rx_login_database_id);

						if (isset($fb_info[0])) {
							$vidcaster_facebook_rx_fb_user_info = $fb_info[0]["id"];
						} else {
							$vidcaster_facebook_rx_fb_user_info = 0;
						}

						$this->session->set_userdata("vidcaster_facebook_rx_fb_user_info", $vidcaster_facebook_rx_fb_user_info); // this is used in account fb switchig, no needed this, but keeping it make no difference, LOL
					} else // users will use own app
					{
						$fb_info_admin = $this->basic->get_data("vidcaster_facebook_rx_config", array("where" => array("status" => '1', 'user_id' => $this->session->userdata("user_id"))), $select = '');

						if (isset($fb_info_admin[0]['id'])) {
							$vidcaster_fb_rx_login_database_id = $fb_info_admin[0]['id'];
							$this->session->set_userdata("vidcaster_fb_rx_login_database_id", $vidcaster_fb_rx_login_database_id);
						}

						if (isset($fb_info[0])) {
							$vidcaster_facebook_rx_fb_user_info = $fb_info[0]["id"];
						} else {
							$vidcaster_facebook_rx_fb_user_info = 0;
						}

						$this->session->set_userdata("vidcaster_facebook_rx_fb_user_info", $vidcaster_facebook_rx_fb_user_info); // this is used in account fb switchig, no needed this, but keeping it make no difference, LOL

					}
				}
				// for getting usable facebook api VidcasterLive

				$package_info = $this->basic->get_data("package", $where = array("where" => array("id" => $info[0]["package_id"])));

				$package_info_session = array();
				if (array_key_exists(0, $package_info)) {
					$package_info_session = $package_info[0];
				}

				$this->session->set_userdata('package_info', $package_info_session);
				$this->session->set_userdata('current_package_id', $package_info_session["id"]);

				$this->basic->update_data("users", array("id" => $user_id), array("last_login_at" => date("Y-m-d H:i:s")));

				if ($this->session->userdata('logged_in') == 1 && $this->session->userdata('user_type') == 'Admin') {
					redirect('dashboard', 'location');
				}
				if ($this->session->userdata('logged_in') == 1 && $this->session->userdata('user_type') == 'Member') {
					redirect('dashboard', 'location');
				}
			}
		} else {
			$this->session->set_flashdata('login_msg', $this->lang->line("This facebook account has no email address so we couldn't create your account. Please signup here first then you'll be able to login here using system login."));
			redirect("home/login_page");
		}

	}

	public function logout() {
		$this->session->sess_destroy();
		redirect('home/login_page', 'location');
	}

	//=======================GET DATA FUNCTIONS ======================
	//====================DATABASE, DROPDOWN & CURL===================

	protected function get_drip_type() {
		$ret = array
			(
			'default' => 'Default',
			'custom' => 'Custom',
			'messenger_bot_engagement_checkbox' => 'Checkbox',
			'messenger_bot_engagement_send_to_msg' => 'Send to Messenger',
			'messenger_bot_engagement_mme' => 'm.me',
			'messenger_bot_engagement_messenger_codes' => 'Messenger Code',
			'messenger_bot_engagement_2way_chat_plugin' => 'Customer Chat',
		);
		unset($ret['messenger_bot_engagement_messenger_codes']); // DEPRECATED

		return $ret;
	}

	protected function get_country_names() {
		$array_countries = array(
			'AF' => 'AFGHANISTAN',
			'AX' => 'ÅLAND ISLANDS',
			'AL' => 'ALBANIA',

			'DZ' => 'ALGERIA (El Djazaïr)',
			'AS' => 'AMERICAN SAMOA',
			'AD' => 'ANDORRA',
			'AO' => 'ANGOLA',
			'AI' => 'ANGUILLA',
			'AQ' => 'ANTARCTICA',
			'AG' => 'ANTIGUA AND BARBUDA',
			'AR' => 'ARGENTINA',
			'AM' => 'ARMENIA',
			'AW' => 'ARUBA',

			'AU' => 'AUSTRALIA',
			'AT' => 'AUSTRIA',
			'AZ' => 'AZERBAIJAN',
			'BS' => 'BAHAMAS',
			'BH' => 'BAHRAIN',
			'BD' => 'BANGLADESH',
			'BB' => 'BARBADOS',
			'BY' => 'BELARUS',
			'BE' => 'BELGIUM',
			'BZ' => 'BELIZE',
			'BJ' => 'BENIN',
			'BM' => 'BERMUDA',
			'BT' => 'BHUTAN',
			'BO' => 'BOLIVIA',

			'BA' => 'BOSNIA AND HERZEGOVINA',
			'BW' => 'BOTSWANA',
			'BV' => 'BOUVET ISLAND',
			'BR' => 'BRAZIL',

			'BN' => 'BRUNEI DARUSSALAM',
			'BG' => 'BULGARIA',
			'BF' => 'BURKINA FASO',
			'BI' => 'BURUNDI',
			'KH' => 'CAMBODIA',
			'CM' => 'CAMEROON',
			'CA' => 'CANADA',
			'CV' => 'CAPE VERDE',
			'KY' => 'CAYMAN ISLANDS',
			'CF' => 'CENTRAL AFRICAN REPUBLIC',
			'CD' => 'CONGO, THE DEMOCRATIC REPUBLIC OF THE (formerly Zaire)',
			'CL' => 'CHILE',
			'CN' => 'CHINA',
			'CX' => 'CHRISTMAS ISLAND',

			'CO' => 'COLOMBIA',
			'KM' => 'COMOROS',
			'CG' => 'CONGO, REPUBLIC OF',
			'CK' => 'COOK ISLANDS',
			'CR' => 'COSTA RICA',
			'CI' => 'CÔTE D\'IVOIRE (Ivory Coast)',
			'HR' => 'CROATIA (Hrvatska)',
			'CU' => 'CUBA',
			'CW' => 'CURAÇAO',
			'CY' => 'CYPRUS',
			'CZ' => 'ZECH REPUBLIC',
			'DK' => 'DENMARK',
			'DJ' => 'DJIBOUTI',
			'DM' => 'DOMINICA',
			'DC' => 'DOMINICAN REPUBLIC',
			'EC' => 'ECUADOR',
			'EG' => 'EGYPT',
			'SV' => 'EL SALVADOR',
			'GQ' => 'EQUATORIAL GUINEA',
			'ER' => 'ERITREA',
			'EE' => 'ESTONIA',
			'ET' => 'ETHIOPIA',
			'FO' => 'FAEROE ISLANDS',

			'FJ' => 'FIJI',
			'FI' => 'FINLAND',
			'FR' => 'FRANCE',
			'GF' => 'FRENCH GUIANA',

			'GA' => 'GABON',
			'GM' => 'GAMBIA, THE',
			'GE' => 'GEORGIA',
			'DE' => 'GERMANY (Deutschland)',
			'GH' => 'GHANA',
			'GI' => 'GIBRALTAR',
			// 'GB' => 'UNITED KINGDOM',
			'GR' => 'GREECE',
			'GL' => 'GREENLAND',
			'GD' => 'GRENADA',
			'GP' => 'GUADELOUPE',
			'GU' => 'GUAM',
			'GT' => 'GUATEMALA',
			'GG' => 'GUERNSEY',
			'GN' => 'GUINEA',
			'GW' => 'GUINEA-BISSAU',
			'GY' => 'GUYANA',
			'HT' => 'HAITI',

			'HN' => 'HONDURAS',
			'HK' => 'HONG KONG (Special Administrative Region of China)',
			'HU' => 'HUNGARY',
			'IS' => 'ICELAND',
			'IN' => 'INDIA',
			'ID' => 'INDONESIA',
			'IR' => 'IRAN (Islamic Republic of Iran)',
			'IQ' => 'IRAQ',
			'IE' => 'IRELAND',
			'IM' => 'ISLE OF MAN',
			'IL' => 'ISRAEL',
			'IT' => 'ITALY',
			'JM' => 'JAMAICA',
			'JP' => 'JAPAN',
			'JE' => 'JERSEY',
			'JO' => 'JORDAN (Hashemite Kingdom of Jordan)',
			'KZ' => 'KAZAKHSTAN',
			'KE' => 'KENYA',
			'KI' => 'KIRIBATI',
			'KP' => 'KOREA (Democratic Peoples Republic of [North] Korea)',
			'KR' => 'KOREA (Republic of [South] Korea)',
			'KW' => 'KUWAIT',
			'KG' => 'KYRGYZSTAN',

			'LV' => 'LATVIA',
			'LB' => 'LEBANON',
			'LS' => 'LESOTHO',
			'LR' => 'LIBERIA',
			'LY' => 'LIBYA (Libyan Arab Jamahirya)',
			'LI' => 'LIECHTENSTEIN (Fürstentum Liechtenstein)',
			'LT' => 'LITHUANIA',
			'LU' => 'LUXEMBOURG',
			'MO' => 'MACAO (Special Administrative Region of China)',
			'MK' => 'MACEDONIA (Former Yugoslav Republic of Macedonia)',
			'MG' => 'MADAGASCAR',
			'MW' => 'MALAWI',
			'MY' => 'MALAYSIA',
			'MV' => 'MALDIVES',
			'ML' => 'MALI',
			'MT' => 'MALTA',
			'MH' => 'MARSHALL ISLANDS',
			'MQ' => 'MARTINIQUE',
			'MR' => 'MAURITANIA',
			'MU' => 'MAURITIUS',
			'YT' => 'MAYOTTE',
			'MX' => 'MEXICO',
			'FM' => 'MICRONESIA (Federated States of Micronesia)',
			'MD' => 'MOLDOVA',
			'MC' => 'MONACO',
			'MN' => 'MONGOLIA',
			'ME' => 'MONTENEGRO',
			'MS' => 'MONTSERRAT',
			'MA' => 'MOROCCO',
			'MZ' => 'MOZAMBIQUE (Moçambique)',
			'MM' => 'MYANMAR (formerly Burma)',
			'NA' => 'NAMIBIA',
			'NR' => 'NAURU',
			'NP' => 'NEPAL',
			'NL' => 'NETHERLANDS',
			'AN' => 'NETHERLANDS ANTILLES (obsolete)',
			'NC' => 'NEW CALEDONIA',
			'NZ' => 'NEW ZEALAND',
			'NI' => 'NICARAGUA',
			'NE' => 'NIGER',
			'NG' => 'NIGERIA',
			'NU' => 'NIUE',
			'NF' => 'NORFOLK ISLAND',
			'MP' => 'NORTHERN MARIANA ISLANDS',
			'ND' => 'NORWAY',
			'OM' => 'OMAN',
			'PK' => 'PAKISTAN',
			'PW' => 'PALAU',
			'PS' => 'PALESTINIAN TERRITORIES',
			'PA' => 'PANAMA',
			'PG' => 'PAPUA NEW GUINEA',
			'PY' => 'PARAGUAY',
			'PE' => 'PERU',
			'PH' => 'PHILIPPINES',
			'PN' => 'PITCAIRN',
			'PL' => 'POLAND',
			'PT' => 'PORTUGAL',
			'PR' => 'PUERTO RICO',
			'QA' => 'QATAR',
			'RE' => 'RÉUNION',
			'RO' => 'ROMANIA',
			'RU' => 'RUSSIAN FEDERATION',
			'RW' => 'RWANDA',
			'BL' => 'SAINT BARTHÉLEMY',
			'SH' => 'SAINT HELENA',
			'KN' => 'SAINT KITTS AND NEVIS',
			'LC' => 'SAINT LUCIA',

			'PM' => 'SAINT PIERRE AND MIQUELON',
			'VC' => 'SAINT VINCENT AND THE GRENADINES',
			'WS' => 'SAMOA (formerly Western Samoa)',
			'SM' => 'SAN MARINO (Republic of)',
			'ST' => 'SAO TOME AND PRINCIPE',
			'SA' => 'SAUDI ARABIA (Kingdom of Saudi Arabia)',
			'SN' => 'SENEGAL',
			'RS' => 'SERBIA (Republic of Serbia)',
			'SC' => 'SEYCHELLES',
			'SL' => 'SIERRA LEONE',
			'SG' => 'SINGAPORE',
			'SX' => 'SINT MAARTEN',
			'SK' => 'SLOVAKIA (Slovak Republic)',
			'SI' => 'SLOVENIA',
			'SB' => 'SOLOMON ISLANDS',
			'SO' => 'SOMALIA',
			'ZA' => 'ZAMBIA (formerly Northern Rhodesia)',
			'GS' => 'SOUTH GEORGIA AND THE SOUTH SANDWICH ISLANDS',
			'SS' => 'SOUTH SUDAN',
			'ES' => 'SPAIN (España)',
			'LK' => 'SRI LANKA (formerly Ceylon)',
			'SD' => 'SUDAN',
			'SR' => 'SURINAME',
			'SJ' => 'SVALBARD AND JAN MAYE',
			'SZ' => 'SWAZILAND',
			'SE' => 'SWEDEN',
			'CH' => 'SWITZERLAND (Confederation of Helvetia)',
			'SY' => 'SYRIAN ARAB REPUBLIC',
			'TW' => 'TAIWAN ("Chinese Taipei" for IOC)',
			'TJ' => 'TAJIKISTAN',
			'TZ' => 'TANZANIA',
			'TH' => 'THAILAND',
			'TL' => 'TIMOR-LESTE (formerly East Timor)',
			'TG' => 'TOGO',
			'TK' => 'TOKELAU',
			'TO' => 'TONGA',
			'TT' => 'TRINIDAD AND TOBAGO',
			'TN' => 'TUNISIA',
			'TR' => 'TURKEY',
			'TM' => 'TURKMENISTAN',
			'TC' => 'TURKS AND CAICOS ISLANDS',
			'TV' => 'TUVALU',
			'UG' => 'UGANDA',
			'UA' => 'UKRAINE',
			'AE' => 'UNITED ARAB EMIRATES',
			'US' => 'UNITED STATES',
			'UM' => 'UNITED STATES MINOR OUTLYING ISLANDS',
			'UK' => 'UNITED KINGDOM',
			'UY' => 'URUGUAY',
			'UZ' => 'UZBEKISTAN',
			'VU' => 'VANUATU',
			'VA' => 'VATICAN CITY (Holy See)',
			'VN' => 'VIET NAM',
			'VG' => 'VIRGIN ISLANDS, BRITISH',
			'VI' => 'VIRGIN ISLANDS, U.S.',
			'WF' => 'WALLIS AND FUTUNA',
			'EH' => 'WESTERN SAHARA (formerly Spanish Sahara)',
			'YE' => 'YEMEN (Yemen Arab Republic)',
			'ZW' => 'ZIMBABWE',
		);
		return $array_countries;
	}

	protected function get_language_names() {
		$array_languages = array(
			'ar-XA' => 'Arabic',
			'bg' => 'Bulgarian',
			'hr' => 'Croatian',
			'cs' => 'Czech',
			'da' => 'Danish',
			'de' => 'German',
			'el' => 'Greek',
			'en' => 'English',
			'et' => 'Estonian',
			'es' => 'Spanish',
			'fi' => 'Finnish',
			'fr' => 'French',
			'in' => 'Indonesian',
			'ga' => 'Irish',
			'hr' => 'Hindi',
			'hu' => 'Hungarian',
			'he' => 'Hebrew',
			'it' => 'Italian',
			'ja' => 'Japanese',
			'ko' => 'Korean',
			'lv' => 'Latvian',
			'lt' => 'Lithuanian',
			'nl' => 'Dutch',
			'no' => 'Norwegian',
			'pl' => 'Polish',
			'pt' => 'Portuguese',
			'sv' => 'Swedish',
			'ro' => 'Romanian',
			'ru' => 'Russian',
			'sr-CS' => 'Serbian',
			'sk' => 'Slovak',
			'sl' => 'Slovenian',
			'th' => 'Thai',
			'tr' => 'Turkish',
			'uk-UA' => 'Ukrainian',
			'zh-chs' => 'Chinese (Simplified)',
			'zh-cht' => 'Chinese (Traditional)',
		);
		return $array_languages;
	}

	protected function sdk_locale() {
		$config = array(
			'default' => 'Default',
			'af_ZA' => 'Afrikaans',
			'ar_AR' => 'Arabic',
			'az_AZ' => 'Azerbaijani',
			'be_BY' => 'Belarusian',
			'bg_BG' => 'Bulgarian',
			'bn_IN' => 'Bengali',
			'bs_BA' => 'Bosnian',
			'ca_ES' => 'Catalan',
			'cs_CZ' => 'Czech',
			'cy_GB' => 'Welsh',
			'da_DK' => 'Danish',
			'de_DE' => 'German',
			'el_GR' => 'Greek',
			'en_GB' => 'English (UK)',
			'en_PI' => 'English (Pirate)',
			'en_UD' => 'English (Upside Down)',
			'en_US' => 'English (US)',
			'eo_EO' => 'Esperanto',
			'es_ES' => 'Spanish (Spain)',
			'es_LA' => 'Spanish',
			'et_EE' => 'Estonian',
			'eu_ES' => 'Basque',
			'fa_IR' => 'Persian',
			'fb_LT' => 'Leet Speak',
			'fi_FI' => 'Finnish',
			'fo_FO' => 'Faroese',
			'fr_CA' => 'French (Canada)',
			'fr_FR' => 'French (France)',
			'fy_NL' => 'Frisian',
			'ga_IE' => 'Irish',
			'gl_ES' => 'Galician',
			'he_IL' => 'Hebrew',
			'hi_IN' => 'Hindi',
			'hr_HR' => 'Croatian',
			'hu_HU' => 'Hungarian',
			'hy_AM' => 'Armenian',
			'id_ID' => 'Indonesian',
			'is_IS' => 'Icelandic',
			'it_IT' => 'Italian',
			'ja_JP' => 'Japanese',
			'ka_GE' => 'Georgian',
			'km_KH' => 'Khmer',
			'ko_KR' => 'Korean',
			'ku_TR' => 'Kurdish',
			'la_VA' => 'Latin',
			'lt_LT' => 'Lithuanian',
			'lv_LV' => 'Latvian',
			'mk_MK' => 'Macedonian',
			'ml_IN' => 'Malayalam',
			'ms_MY' => 'Malay',
			'nb_NO' => 'Norwegian (bokmal)',
			'ne_NP' => 'Nepali',
			'nl_NL' => 'Dutch',
			'nn_NO' => 'Norwegian (nynorsk)',
			'pa_IN' => 'Punjabi',
			'pl_PL' => 'Polish',
			'ps_AF' => 'Pashto',
			'pt_BR' => 'Portuguese (Brazil)',
			'pt_PT' => 'Portuguese (Portugal)',
			'ro_RO' => 'Romanian',
			'ru_RU' => 'Russian',
			'sk_SK' => 'Slovak',
			'sl_SI' => 'Slovenian',
			'sq_AL' => 'Albanian',
			'sr_RS' => 'Serbian',
			'sv_SE' => 'Swedish',
			'sw_KE' => 'Swahili',
			'ta_IN' => 'Tamil',
			'te_IN' => 'Telugu',
			'th_TH' => 'Thai',
			'tl_PH' => 'Filipino',
			'tr_TR' => 'Turkish',
			'uk_UA' => 'Ukrainian',
			'vi_VN' => 'Vietnamese',
			'zh_CN' => 'Chinese (China)',
			'zh_HK' => 'Chinese (Hong Kong)',
			'zh_TW' => 'Chinese (Taiwan)',
		);
		asort($config);
		return $config;
	}

	public function _scanAll($myDir) {
		$dirTree = array();
		$di = new RecursiveDirectoryIterator($myDir, RecursiveDirectoryIterator::SKIP_DOTS);

		$i = 0;
		foreach (new RecursiveIteratorIterator($di) as $filename) {

			$dir = str_replace($myDir, '', dirname($filename));
			// $dir = str_replace('/', '>', substr($dir,1));

			$org_dir = str_replace("\\", "/", $dir);

			if ($org_dir) {
				$file_path = $org_dir . "/" . basename($filename);
			} else {
				$file_path = basename($filename);
			}

			$file_full_path = $myDir . "/" . $file_path;
			$file_size = filesize($file_full_path);
			$file_modification_time = filemtime($file_full_path);

			$dirTree[$i]['file'] = $file_full_path;
			$i++;
		}
		return $dirTree;
	}

	public function _language_list() {
		$myDir = APPPATH . 'language';
		$file_list = $this->_scanAll($myDir);
		foreach ($file_list as $file) {
			$i = 0;
			$one_list[$i] = $file['file'];
			$one_list[$i] = str_replace("\\", "/", $one_list[$i]);
			$one_list_array[] = explode("/", $one_list[$i]);
		}
		foreach ($one_list_array as $value) {
			$pos = count($value) - 2;
			$lang_folder = $value[$pos];
			$final_list_array[] = $lang_folder;
		}
		$final_array = array_unique($final_list_array);
		$array_keys = array_values($final_array);
		foreach ($final_array as $value) {
			$uc_array_valus[] = ucfirst($value);
		}
		$array_values = array_values($uc_array_valus);
		$final_array_done = array_combine($array_keys, $array_values);
		return $final_array_done;
	}

	public function _theme_list() {
		return array();
		$myDir = 'css/skins';
		$file_list = $this->_scanAll($myDir);
		$theme_list = array();
		foreach ($file_list as $file) {
			$i = 0;
			$one_list[$i] = $file['file'];
			$one_list[$i] = str_replace("\\", "/", $one_list[$i]);
			$one_list_array = explode("/", $one_list[$i]);
			$theme = array_pop($one_list_array);
			$pos = strpos($theme, '.min.css');
			if ($pos !== FALSE) {
				continue;
			}
			// only loading unminified css
			if ($theme == "_all-skins.css") {
				continue;
			}
			// skipping large css file that includes all file
			$theme_name = str_replace('.css', '', $theme);
			$theme_display = str_replace(array('skin-', '.css', '-'), array('', '', ' '), $theme);
			if ($theme_display == "black light") {
				$theme_display = 'light';
			}

			if ($theme_display == "black") {
				$theme_display = 'dark';
			}

			$theme_list[$theme_name] = ucwords($theme_display);
		}
		return $theme_list;

	}

	public function _theme_list_front() {
		return array
			(
			"white" => "Light",
			"black" => "Dark",
			"blue" => "Blue",
			"green" => "Green",
			"purple" => "Purple",
			"red" => "Red",
			"yellow" => "Yellow",
		);
	}

	public function language_changer() {
		$language = $this->input->post("language");
		$this->session->set_userdata("selected_language", $language);
	}

	protected function time_zone_drop_down($datavalue = '', $primary_key = null, $mandatory = 0) // return HTML select
	{
		$all_time_zone = $this->_time_zone_list();

		$str = "<select name='time_zone' id='time_zone' class='form-control'>";
		if ($mandatory === 1) {
			$str .= "<option value=>Time Zone *</option>";
		} else {
			$str .= "<option value=>Time Zone</option>";
		}

		foreach ($all_time_zone as $zone_name => $value) {
			if ($primary_key != null) {
				if ($zone_name == $datavalue) {
					$selected = " selected = 'selected' ";
				} else {
					$selected = "";
				}
			} else {
				if ($zone_name == $this->config->item("time_zone")) {
					$selected = " selected = 'selected' ";
				} else {
					$selected = "";
				}
			}
			$str .= "<option " . $selected . " value='$zone_name'>{$zone_name}</option>";
		}
		$str .= "</select>";
		return $str;
	}

	//  used in all types of bulk message campaign
	public function get_broadcast_summary() {
		if ($this->session->userdata('logged_in') != 1) {
			exit();
		}

		$this->ajax_check();
		$page_id = $this->input->post('page_id'); // database id
		$user_gender = $this->input->post('user_gender');
		$user_time_zone = $this->input->post('user_time_zone');
		$user_locale = $this->input->post('user_locale');
		$load_label = $this->input->post('load_label');
		$label_ids = $this->input->post('label_ids');
		$excluded_label_ids = $this->input->post('excluded_label_ids');
		$is_bot_subscriber = $this->input->post('is_bot_subscriber');
		$broadcast_type = $this->input->post('broadcast_type');

		if (!isset($label_ids) || !is_array($label_ids)) {
			$label_ids = array();
		}

		if (!isset($excluded_label_ids) || !is_array($excluded_label_ids)) {
			$excluded_label_ids = array();
		}

		$table_type = 'messenger_bot_broadcast_contact_group';
		$where_type['where'] = array('user_id' => $this->user_id, "page_id" => $page_id, "unsubscribe" => "0", "invisible" => "0");
		$info_type = $this->basic->get_data($table_type, $where_type, $select = '', $join = '', $limit = '', $start = '', $order_by = 'group_name');

		$result = array();
		date_default_timezone_set('UTC');
		$current_time = date("Y-m-d H:i:s");
		$previous_time = date("Y-m-d H:i:s", strtotime('-23 hour', strtotime($current_time)));
		$this->_time_zone_set();
		$dropdown = array();
		$str = $str2 = "";

		if ($load_label == '1') {
			$str = '<script>$("#label_ids").select2();</script> ';
			$str2 = '<script>$("#excluded_label_ids").select2();</script> ';
			$str .= '<select multiple="multiple"  class="form-control" id="label_ids" name="label_ids[]">';
			$str2 .= '<select multiple="multiple"  class="form-control" id="excluded_label_ids" name="excluded_label_ids[]">';

			foreach ($info_type as $value) {
				$str .= "<option value='" . $value['id'] . "'>" . $value['group_name'] . "</option>";
				$str2 .= "<option value='" . $value['id'] . "'>" . $value['group_name'] . "</option>";
			}

			$str .= '</select>';
			$str2 .= '</select>';
		}

		$pageinfo = $this->basic->get_data("facebook_rx_fb_page_info", array("where" => array("id" => $page_id, "user_id" => $this->user_id)));
		$page_info = isset($pageinfo[0]) ? $pageinfo[0] : array();

		if (isset($page_info['page_access_token'])) {
			unset($page_info['page_access_token']);
		}

		$subscriber_count = 0;

		if ($is_bot_subscriber == '1') {
			$where_simple2 = array("page_table_id" => $page_id, 'is_bot_subscriber' => '1', 'unavailable' => '0', 'user_id' => $this->user_id, 'permission' => '1');
		} else {
			$where_simple2 = array("page_table_id" => $page_id, 'client_thread_id !=' => '', 'user_id' => $this->user_id, 'permission' => '1', 'unavailable_conversation' => '0');
		}

		if (isset($user_gender) && $user_gender != "") {
			$where_simple2['messenger_bot_subscriber.gender'] = $user_gender;
		}

		if (isset($user_time_zone) && $user_time_zone != "") {
			$where_simple2['messenger_bot_subscriber.timezone'] = $user_time_zone;
		}

		if (isset($user_locale) && $user_locale != "") {
			$where_simple2['messenger_bot_subscriber.locale'] = $user_locale;
		}

		if (isset($broadcast_type) && ($broadcast_type == '24H Promo' || $broadcast_type == '24+1 Promo')) // bulk bradcast
		{
			if ($broadcast_type == '24H Promo') {
				$where_simple2['messenger_bot_subscriber.last_subscriber_interaction_time >'] = $previous_time;
			} else if ($broadcast_type == '24+1 Promo') {
				$where_simple2['messenger_bot_subscriber.last_subscriber_interaction_time <'] = $previous_time;
				$where_simple2['messenger_bot_subscriber.is_24h_1_sent'] = '0';
			}
		}

		$sql_part = "";
		if ($load_label == '0') {
			if (count($label_ids) > 0) {
				$sql_part = "(";
			} else {
				$sql_part = "";
			}

			$sql_part_array = array();
			foreach ($label_ids as $key => $value) {
				$sql_part_array[] = "FIND_IN_SET('" . $value . "',contact_group_id) !=0";
			}
			$sql_part .= implode(' OR ', $sql_part_array);
			if (count($label_ids) > 0) {
				$sql_part .= ")";
			}

			if ($sql_part != "") {
				$this->db->where($sql_part);
			}

			foreach ($excluded_label_ids as $key => $value) {
				$sq = "NOT FIND_IN_SET('" . $value . "',contact_group_id) !=0";
				$this->db->where($sq);
			}
		}

		$where2 = array('where' => $where_simple2);
		$bot_subscriber = $this->basic->get_data("messenger_bot_subscriber", $where2, 'count(id) as subscriber_count');
		$subscriber_count = isset($bot_subscriber[0]['subscriber_count']) ? $bot_subscriber[0]['subscriber_count'] : 0;
		$page_info['subscriber_count'] = $subscriber_count;

		$push_postback = "";
		$total_subscriber_count = 0;
		if ($is_bot_subscriber == '1') {
			$postback_data = $this->basic->get_data("messenger_bot_postback", array("where" => array("page_id" => $page_id, 'is_template' => '1', 'template_for' => 'reply_message')), '', '', '', $start = NULL, $order_by = 'template_name ASC');
			foreach ($postback_data as $key => $value) {
				$push_postback .= "<option value='" . $value['postback_id'] . "'>" . $value['template_name'] . ' [' . $value['postback_id'] . ']' . "</option>";
			}
			$total_bot_subscriber = $this->basic->get_data("messenger_bot_subscriber", array("where" => array("page_table_id" => $page_id, 'is_bot_subscriber' => '1', 'user_id' => $this->user_id, 'permission' => '1')), 'count(id) as total_subscriber_count');
			$total_subscriber_count = isset($total_bot_subscriber[0]['total_subscriber_count']) ? $total_bot_subscriber[0]['total_subscriber_count'] : 0;
		}
		$page_info['total_subscriber_count'] = $total_subscriber_count;

		echo json_encode(array('first_dropdown' => $str, 'second_dropdown' => $str2, "pageinfo" => $page_info, "push_postback" => $push_postback));
	}

	protected function currency_icon() {
		$currency_symbols = array(
			'AED' => '&#1583;.&#1573;', // ?
			'AFN' => '&#65;&#102;',
			'ALL' => '&#76;&#101;&#107;',
			'AMD' => 'AMD',
			'ANG' => '&#402;',
			'AOA' => '&#75;&#122;', // ?
			'ARS' => '&#36;',
			'AUD' => '&#36;',
			'AWG' => '&#402;',
			'AZN' => '&#1084;&#1072;&#1085;',
			'BAM' => '&#75;&#77;',
			'BBD' => '&#36;',
			'BDT' => '&#2547;', // ?
			'BGN' => '&#1083;&#1074;',
			'BHD' => '.&#1583;.&#1576;', // ?
			'BIF' => '&#70;&#66;&#117;', // ?
			'BMD' => '&#36;',
			'BND' => '&#36;',
			'BOB' => '&#36;&#98;',
			'BRL' => '&#82;&#36;',
			'BSD' => '&#36;',
			'BTN' => '&#78;&#117;&#46;', // ?
			'BWP' => '&#80;',
			'BYR' => '&#112;&#46;',
			'BZD' => '&#66;&#90;&#36;',
			'CAD' => '&#36;',
			'CDF' => '&#70;&#67;',
			'CHF' => '&#67;&#72;&#70;',
			'CLF' => 'CLF', // ?
			'CLP' => '&#36;',
			'CNY' => '&#165;',
			'COP' => '&#36;',
			'CRC' => '&#8353;',
			'CUP' => '&#8396;',
			'CVE' => '&#36;', // ?
			'CZK' => '&#75;&#269;',
			'DJF' => '&#70;&#100;&#106;', // ?
			'DKK' => '&#107;&#114;',
			'DOP' => '&#82;&#68;&#36;',
			'DZD' => '&#1583;&#1580;', // ?
			'EGP' => '&#163;',
			'ETB' => '&#66;&#114;',
			'EUR' => '&#8364;',
			'FJD' => '&#36;',
			'FKP' => '&#163;',
			'GBP' => '&#163;',
			'GEL' => '&#4314;', // ?
			'GHS' => '&#162;',
			'GIP' => '&#163;',
			'GMD' => '&#68;', // ?
			'GNF' => '&#70;&#71;', // ?
			'GTQ' => '&#81;',
			'GYD' => '&#36;',
			'HKD' => '&#36;',
			'HNL' => '&#76;',
			'HRK' => '&#107;&#110;',
			'HTG' => '&#71;', // ?
			'HUF' => '&#70;&#116;',
			'IDR' => '&#82;&#112;',
			'ILS' => '&#8362;',
			'INR' => '&#8377;',
			'IQD' => '&#1593;.&#1583;', // ?
			'IRR' => '&#65020;',
			'ISK' => '&#107;&#114;',
			'JEP' => '&#163;',
			'JMD' => '&#74;&#36;',
			'JOD' => '&#74;&#68;', // ?
			'JPY' => '&#165;',
			'KES' => '&#75;&#83;&#104;', // ?
			'KGS' => '&#1083;&#1074;',
			'KHR' => '&#6107;',
			'KMF' => '&#67;&#70;', // ?
			'KPW' => '&#8361;',
			'KRW' => '&#8361;',
			'KWD' => '&#1583;.&#1603;', // ?
			'KYD' => '&#36;',
			'KZT' => '&#1083;&#1074;',
			'LAK' => '&#8365;',
			'LBP' => '&#163;',
			'LKR' => '&#8360;',
			'LRD' => '&#36;',
			'LSL' => '&#76;', // ?
			'LTL' => '&#76;&#116;',
			'LVL' => '&#76;&#115;',
			'LYD' => '&#1604;.&#1583;', // ?
			'MAD' => '&#1583;.&#1605;.', //?
			'MDL' => '&#76;',
			'MGA' => '&#65;&#114;', // ?
			'MKD' => '&#1076;&#1077;&#1085;',
			'MMK' => '&#75;',
			'MNT' => '&#8366;',
			'MOP' => '&#77;&#79;&#80;&#36;', // ?
			'MRO' => '&#85;&#77;', // ?
			'MUR' => '&#8360;', // ?
			'MVR' => '.&#1923;', // ?
			'MWK' => '&#77;&#75;',
			'MXN' => '&#36;',
			'MYR' => '&#82;&#77;',
			'MZN' => '&#77;&#84;',
			'NAD' => '&#36;',
			'NGN' => '&#8358;',
			'NIO' => '&#67;&#36;',
			'NOK' => '&#107;&#114;',
			'NPR' => '&#8360;',
			'NZD' => '&#36;',
			'OMR' => '&#65020;',
			'PAB' => '&#66;&#47;&#46;',
			'PEN' => '&#83;&#47;&#46;',
			'PGK' => '&#75;', // ?
			'PHP' => '&#8369;',
			'PKR' => '&#8360;',
			'PLN' => '&#122;&#322;',
			'PYG' => '&#71;&#115;',
			'QAR' => '&#65020;',
			'RON' => '&#108;&#101;&#105;',
			'RSD' => '&#1044;&#1080;&#1085;&#46;',
			'RUB' => '&#1088;&#1091;&#1073;',
			'RWF' => '&#1585;.&#1587;',
			'SAR' => '&#65020;',
			'SBD' => '&#36;',
			'SCR' => '&#8360;',
			'SDG' => '&#163;', // ?
			'SEK' => '&#107;&#114;',
			'SGD' => '&#36;',
			'SHP' => '&#163;',
			'SLL' => '&#76;&#101;', // ?
			'SOS' => '&#83;',
			'SRD' => '&#36;',
			'STD' => '&#68;&#98;', // ?
			'SVC' => '&#36;',
			'SYP' => '&#163;',
			'SZL' => '&#76;', // ?
			'THB' => '&#3647;',
			'TJS' => '&#84;&#74;&#83;', // ? TJS (guess)
			'TMT' => '&#109;',
			'TND' => '&#1583;.&#1578;',
			'TOP' => '&#84;&#36;',
			'TRY' => '&#8356;', // New Turkey Lira (old symbol used)
			'TTD' => '&#36;',
			'TWD' => '&#78;&#84;&#36;',
			'TZS' => '',
			'UAH' => '&#8372;',
			'UGX' => '&#85;&#83;&#104;',
			'USD' => '&#36;',
			'UYU' => '&#36;&#85;',
			'UZS' => '&#1083;&#1074;',
			'VEF' => '&#66;&#115;',
			'VND' => '&#8363;',
			'VUV' => '&#86;&#84;',
			'WST' => '&#87;&#83;&#36;',
			'XAF' => '&#70;&#67;&#70;&#65;',
			'XCD' => '&#36;',
			'XDR' => 'XDR',
			'XOF' => 'XOF',
			'XPF' => '&#70;',
			'YER' => '&#65020;',
			'ZAR' => '&#82;',
			'ZMK' => '&#90;&#75;', // ?
			'ZWL' => '&#90;&#36;',
		);

		return $currency_symbols;
	}

	function _payment_package() {
		$payment_package = $this->basic->get_data("package", $where = array("where" => array("is_default" => "0", "price > " => 0)), $select = '', $join = '', $limit = '', $start = NULL, $order_by = 'price');
		$return_val = array();
		$config_data = $this->basic->get_data("payment_config");
		$currency = $config_data[0]["currency"];
		foreach ($payment_package as $row) {
			$return_val[$row['id']] = $row['package_name'] . " : Only @" . $currency . " " . $row['price'] . " for " . $row['validity'] . " days";
		}
		return $return_val;
	}

	protected function real_ip() {
		if (!empty($_SERVER['HTTP_CLIENT_IP'])) //check ip from share internet
		{
			$ip = $_SERVER['HTTP_CLIENT_IP'];
		} elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) //to check ip is pass from proxy
		{
			$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
		} else {
			$ip = $_SERVER['REMOTE_ADDR'];
		}
		return $ip;
	}

	function get_general_content($url, $proxy = "") {

		$ch = curl_init(); // initialize curl handle
		/* curl_setopt($ch, CURLOPT_HEADER, 0);
			curl_setopt($ch, CURLOPT_VERBOSE, 0);*/
		curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible;)");
		curl_setopt($ch, CURLOPT_AUTOREFERER, false);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 7);
		curl_setopt($ch, CURLOPT_REFERER, 'http://' . $url);
		curl_setopt($ch, CURLOPT_URL, $url); // set url to post to
		curl_setopt($ch, CURLOPT_FAILONERROR, 1);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); // allow redirects
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // return into a variable
		curl_setopt($ch, CURLOPT_TIMEOUT, 50); // times out after 50s
		curl_setopt($ch, CURLOPT_POST, 0); // set POST method

		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		//  curl_setopt($ch, CURLOPT_COOKIEJAR, "my_cookies.txt");
		// curl_setopt($ch, CURLOPT_COOKIEFILE, "my_cookies.txt");

		$content = curl_exec($ch); // run the whole process
		curl_close($ch);

		return json_encode($content);

	}

	function get_general_content_with_checking($url, $proxy = "") {

		$ch = curl_init(); // initialize curl handle
		/* curl_setopt($ch, CURLOPT_HEADER, 0);
			curl_setopt($ch, CURLOPT_VERBOSE, 0);*/
		curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible;)");
		curl_setopt($ch, CURLOPT_AUTOREFERER, false);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 7);
		curl_setopt($ch, CURLOPT_REFERER, 'http://' . $url);
		curl_setopt($ch, CURLOPT_URL, $url); // set url to post to
		curl_setopt($ch, CURLOPT_FAILONERROR, 1);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); // allow redirects
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // return into a variable
		curl_setopt($ch, CURLOPT_TIMEOUT, 120); // times out after 50s
		curl_setopt($ch, CURLOPT_POST, 0); // set POST method

		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		//  curl_setopt($ch, CURLOPT_COOKIEJAR, "my_cookies.txt");
		// curl_setopt($ch, CURLOPT_COOKIEFILE, "my_cookies.txt");

		$content = curl_exec($ch); // run the whole process
		$response['content'] = $content;

		$res = curl_getinfo($ch);
		if ($res['http_code'] != 200) {
			$response['error'] = 'error';
		}

		curl_close($ch);
		return json_encode($response);

	}
	//=======================GET DATA FUNCTIONS ======================
	//================================================================

	//================================================================
	//=========================WEBSITE FUNCTIOS=======================
	public function _random_number_generator($length = 6) {
		$rand = substr(uniqid(mt_rand(), true), 0, $length);
		return $rand;
	}

	public function forgot_password() {
		$data['body'] = 'page/forgot_password';
		$data["page_title"] = $this->lang->line("Password Recovery");
		$this->_subscription_viewcontroller($data);
	}

	public function code_genaration() {
		$this->ajax_check();

		$email = trim($this->input->post('email', true));
		$result = $this->basic->get_data('users', array('where' => array('email' => $email)), array('count(*) as num'));

		if ($result[0]['num'] == 1) {
			//entry to forget_password table
			$expiration = date("Y-m-d H:i:s", strtotime('+1 day', time()));
			$code = $this->_random_number_generator();
			$url = site_url() . 'home/password_recovery';
			$url_final = "<a href='" . $url . "' target='_BLANK'>" . $url . "</a>";
			$productname = $this->config->item('product_name');

			$table = 'forget_password';
			$info = array(
				'confirmation_code' => $code,
				'email' => $email,
				'expiration' => $expiration,
			);

			if ($this->basic->insert_data($table, $info)) {

				//email to user
				$email_template_info = $this->basic->get_data("email_template_management", array('where' => array('template_type' => 'reset_password')), array('subject', 'message'));

				if (isset($email_template_info[0]) && $email_template_info[0]['subject'] != '' && $email_template_info[0]['message'] != '') {

					$subject = str_replace('#APP_NAME#', $productname, $email_template_info[0]['subject']);
					$message = str_replace(array("#APP_NAME#", "#PASSWORD_RESET_URL#", "#PASSWORD_RESET_CODE#"), array($productname, $url_final, $code), $email_template_info[0]['message']);

				} else {

					$subject = $productname . " | Password recovery";
					$message = "<p>" . $this->lang->line('to reset your password please perform the following steps') . " : </p>
								<ol>
									<li>" . $this->lang->line("go to this url") . " : " . $url_final . "</li>
									<li>" . $this->lang->line("enter this code") . " : " . $code . "</li>
									<li>" . $this->lang->line("reset your password") . "</li>
								</ol>
								<h4>" . $this->lang->line("link and code will be expired after 24 hours") . "</h4>";

				}

				$from = $this->config->item('institute_email');
				$to = $email;
				$mask = $subject;
				$html = 1;
				$this->_mail_sender($from, $to, $subject, $message, $mask, $html);
			}
		} else {
			echo 0;
		}
	}

	public function password_recovery() {
		$data['body'] = 'page/password_recovery';
		$data['page_title'] = $this->lang->line("password recovery");
		$this->_subscription_viewcontroller($data);
	}

	public function recovery_check() {
		$this->ajax_check();
		if ($_POST) {
			$code = trim($this->input->post('code', true));
			$newp = md5($this->input->post('newp', true));
			$conf = md5($this->input->post('conf', true));

			if ($code == "" || $newp == "" || $conf == "" || ($newp != $conf)) {
				echo 0;
				exit();
			}

			$table = 'forget_password';
			$where['where'] = array('confirmation_code' => $code, 'success' => 0);
			$select = array('email', 'expiration');

			$result = $this->basic->get_data($table, $where, $select);

			if (empty($result)) {
				echo 0;
			} else {
				foreach ($result as $row) {
					$email = $row['email'];
					$expiration = $row['expiration'];
				}

				$now = time();
				$exp = strtotime($expiration);

				if ($now > $exp) {
					echo 1;
				} else {
					$student_info_where['where'] = array('email' => $email);
					$student_info_select = array('id');
					$student_info_id = $this->basic->get_data('users', $student_info_where, $student_info_select);
					$this->basic->update_data('users', array('id' => $student_info_id[0]['id']), array('password' => $newp));
					$this->basic->update_data('forget_password', array('confirmation_code' => $code), array('success' => 1));
					echo 2;
				}
			}
		}
	}

	function _mail_sender($from = '', $to = '', $subject = '', $message = '', $mask = "", $html = 1, $smtp = 1, $attachement = "") {
		if ($to != '' && $subject != '' && $message != '') {
			if ($this->config->item('email_sending_option') == '') {
				$email_sending_option = 'smtp';
			} else {
				$email_sending_option = $this->config->item('email_sending_option');
			}

			$message = $message . "<br/><br/>" . $this->lang->line("The email was sent by") . ": " . $from;

			if ($email_sending_option == 'smtp') {
				if ($smtp == '1') {
					$where2 = array("where" => array('status' => '1', 'deleted' => '0'));
					$email_config_details = $this->basic->get_data("email_config", $where2, $select = '', $join = '', $limit = '', $start = '', $group_by = '', $num_rows = 0);

					if (count($email_config_details) == 0) {
						$this->load->library('email');
					} else {
						foreach ($email_config_details as $send_info) {
							$send_email = trim($send_info['email_address']);
							$smtp_host = trim($send_info['smtp_host']);
							$smtp_port = trim($send_info['smtp_port']);
							$smtp_user = trim($send_info['smtp_user']);
							$smtp_password = trim($send_info['smtp_password']);
							$smtp_type = trim($send_info['smtp_type']);
						}

						/*****Email Sending Code ******/
						$config = array(
							'protocol' => 'smtp',
							'smtp_host' => "{$smtp_host}",
							'smtp_port' => "{$smtp_port}",
							'smtp_user' => "{$smtp_user}", // change it to yours
							'smtp_pass' => "{$smtp_password}", // change it to yours
							'mailtype' => 'html',
							'charset' => 'utf-8',
							'newline' => "\r\n",
							'set_crlf' => "\r\n",
							'smtp_timeout' => '30',
						);
						if ($smtp_type != 'Default') {
							$config['smtp_crypto'] = $smtp_type;
						}

						$this->load->library('email', $config);
					}
				} /*** End of If Smtp== 1 **/

				if (isset($send_email) && $send_email != "") {
					$from = $send_email;
				}
				$this->email->from($from, $mask);
				$this->email->to($to);
				$this->email->subject($subject);
				$this->email->message($message);
				if ($html == 1) {
					$this->email->set_mailtype('html');
				}
				if ($attachement != "") {
					$this->email->attach($attachement);
				}

				if ($this->email->send()) {
					return true;
				} else {
					return false;
				}
			}

			if ($email_sending_option == 'php_mail') {
				$from = get_domain_only(base_url());
				$from = "support@" . $from;
				$headers = 'MIME-Version: 1.0' . "\r\n";
				$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
				$headers .= "From: {$from}" . "\r\n";
				if (mail($to, $subject, $message, $headers)) {
					return true;
				} else {
					return false;
				}

			}

		} else {
			return false;
		}
	}

	public function download_page_loader() {
		$this->load->view('page/download');
	}
	public function sign_up() {
		$signup_form = $this->config->item('enable_signup_form');

		if ($signup_form == '0') {
			return $this->login_page();
		}
		$data['num1'] = $this->_random_number_generator(1);
		$data['num2'] = $this->_random_number_generator(1);
		$captcha = $data['num1'] + $data['num2'];
		$this->session->set_userdata("sign_up_captcha", $captcha);

		$data["page_title"] = $this->lang->line("Sign Up");
		$data["body"] = "page/sign_up";
		$this->_subscription_viewcontroller($data);
	}

	public function sign_up_action() {
		if ($_SERVER['REQUEST_METHOD'] === 'GET') {
			redirect('home/access_forbidden', 'location');
		}

		if ($_POST) {
			$this->form_validation->set_rules('name', '<b>' . $this->lang->line("name") . '</b>', 'trim|required');
			$this->form_validation->set_rules('email', '<b>' . $this->lang->line("email") . '</b>', 'trim|required|valid_email|is_unique[users.email]');
			// $this->form_validation->set_rules('mobile', '<b>'.$this->lang->line("mobile").'</b>', 'trim');
			$this->form_validation->set_rules('password', '<b>' . $this->lang->line("password") . '</b>', 'trim|required');
			$this->form_validation->set_rules('confirm_password', '<b>' . $this->lang->line("confirm password") . '</b>', 'trim|required|matches[password]');
			$this->form_validation->set_rules('captcha', '<b>' . $this->lang->line("captcha") . '</b>', 'trim|required|integer');

			if ($this->form_validation->run() == FALSE) {
				$this->sign_up();
			} else {
				$captcha = $this->input->post('captcha', TRUE);
				if ($captcha != $this->session->userdata("sign_up_captcha")) {
					$this->session->set_userdata("sign_up_captcha_error", $this->lang->line("invalid captcha"));
					return $this->sign_up();

				}

				$name = $this->input->post('name', TRUE);
				$email = $this->input->post('email', TRUE);
				// $mobile = $this->input->post('mobile', TRUE);
				$password = $this->input->post('password', TRUE);

				// $this->db->trans_start();

				$default_package = $this->basic->get_data("package", $where = array("where" => array("is_default" => "1")));

				if (is_array($default_package) && array_key_exists(0, $default_package)) {
					$validity = $default_package[0]["validity"];
					$package_id = $default_package[0]["id"];

					$to_date = date('Y-m-d');
					$expiry_date = date("Y-m-d", strtotime('+' . $validity . ' day', strtotime($to_date)));
				}

				$code = $this->_random_number_generator();
				$data = array(
					'name' => $name,
					'email' => $email,
					// 'mobile' => $mobile,
					'password' => md5($password),
					'user_type' => 'Member',
					'status' => '0',
					'activation_code' => $code,
					'expired_date' => $expiry_date,
					'package_id' => $package_id,
				);

				if ($this->basic->insert_data('users', $data)) {

					//email to user
					$email_template_info = $this->basic->get_data("email_template_management", array('where' => array('template_type' => "signup_activation")), array('subject', 'message'));

					$url = site_url() . "home/account_activation";
					$url_final = "<a href='" . $url . "' target='_BLANK'>" . $url . "</a>";

					$productname = $this->config->item('product_name');

					if (isset($email_template_info[0]) && $email_template_info[0]['subject'] != '' && $email_template_info[0]['message'] != '') {
						$subject = str_replace('#APP_NAME#', $productname, $email_template_info[0]['subject']);
						$message = str_replace(array("#APP_NAME#", "#ACTIVATION_URL#", "#ACCOUNT_ACTIVATION_CODE#"), array($productname, $url_final, $code), $email_template_info[0]['message']);
						// echo "Database Has data"; exit();

					} else {
						$subject = $productname . " | Account activation";
						$message = "<p>" . $this->lang->line("to activate your account please perform the following steps") . "</p>
									<ol>
										<li>" . $this->lang->line("go to this url") . ":" . $url_final . "</li>
										<li>" . $this->lang->line("enter this code") . ":" . $code . "</li>
										<li>" . $this->lang->line("activate your account") . "</li>
									</ol>";
					}

					$from = $this->config->item('institute_email');
					$to = $email;
					$mask = $subject;
					$html = 1;

					$this->_mail_sender($from, $to, $subject, $message, $mask, $html);

					$this->session->set_userdata('reg_success', 1);
					return $this->sign_up();

				}

			}

		}
	}

	public function account_activation() {
		$data["page_title"] = $this->lang->line("Account Activation");
		$data["body"] = "page/account_activation";
		$this->_subscription_viewcontroller($data);
	}

	public function account_activation_action() {
		if ($_POST) {
			$code = trim($this->input->post('code', true));
			$email = $this->input->post('email', true);

			$table = 'users';
			$where['where'] = array('activation_code' => $code, 'email' => $email, 'status' => "0");
			$select = array('id');

			$result = $this->basic->get_data($table, $where, $select);

			if (empty($result)) {
				echo 0;
			} else {
				foreach ($result as $row) {
					$user_id = $row['id'];
				}

				$this->basic->update_data('users', array('id' => $user_id), array('status' => '1'));
				echo 2;

			}
		}
	}

	public function email_contact() {
		if ($_SERVER['REQUEST_METHOD'] === 'GET') {
			redirect('home/access_forbidden', 'location');
		}

		if ($_POST) {
			$redirect_url = site_url("home#contact");

			$this->form_validation->set_rules('email', '<b>' . $this->lang->line("email") . '</b>', 'trim|required|valid_email');
			$this->form_validation->set_rules('subject', '<b>' . $this->lang->line("message subject") . '</b>', 'trim|required');
			$this->form_validation->set_rules('message', '<b>' . $this->lang->line("message") . '</b>', 'trim|required');
			$this->form_validation->set_rules('captcha', '<b>' . $this->lang->line("captcha") . '</b>', 'trim|required|integer');

			if ($this->form_validation->run() == false) {
				return $this->index();
			} else {
				$captcha = $this->input->post('captcha', TRUE);

				if ($captcha != $this->session->userdata("contact_captcha")) {
					$this->session->set_userdata("contact_captcha_error", $this->lang->line("invalid captcha"));
					redirect($redirect_url, 'location');
					exit();
				}

				$email = $this->input->post('email', true);
				$subject = $this->config->item("product_name") . " | " . $this->input->post('subject', true);
				$message = $this->input->post('message', true);

				$this->_mail_sender($from = $email, $to = $this->config->item("institute_email"), $subject, $message, $mask = $from, $html = 1);
				$this->session->set_userdata('mail_sent', 1);

				redirect($redirect_url, 'location');
			}
		}
	}

	public function privacy_policy() {
		$data['page_title'] = 'Privacy Policy';
		$data['body'] = 'front/privacy_policy';
		$this->_front_viewcontroller($data);
	}

	public function terms_use() {
		$data['page_title'] = 'Terms of Use';
		$data['body'] = 'front/terms_use';
		$this->_front_viewcontroller($data);
	}

	public function gdpr() {
		$data['page_title'] = 'GDPR';
		$data['body'] = 'front/gdpr';
		$this->_front_viewcontroller($data);
	}

	public function allow_cookie() {
		$this->session->set_userdata('allow_cookie', 'yes');
		// redirect($_SERVER['HTTP_REFERER'],'location');
	}

	//=========================WEBSITE FUNCTIOS=======================
	//================================================================

	//==========================================================================
	//=======================USAGE LOG & LICENSE FUNCTIONS======================
	public function _insert_usage_log($module_id = 0, $usage_count = 0, $user_id = 0) {

		if ($module_id == 0 || $usage_count == 0) {
			return false;
		}

		if ($user_id == 0) {
			$user_id = $this->session->userdata("user_id");
		}

		if ($user_id == 0 || $user_id == "") {
			return false;
		}

		$usage_month = date("n");
		$usage_year = date("Y");
		$where = array("module_id" => $module_id, "user_id" => $user_id, "usage_month" => $usage_month, "usage_year" => $usage_year);

		$insert_data = array("module_id" => $module_id, "user_id" => $user_id, "usage_month" => $usage_month, "usage_year" => $usage_year, "usage_count" => $usage_count);

		if ($this->basic->is_exist("usage_log", $where)) {
			$this->db->set('usage_count', 'usage_count+' . $usage_count, FALSE);
			$this->db->where($where);
			$this->db->update('usage_log');
		} else {
			$this->basic->insert_data("usage_log", $insert_data);
		}

		return true;
	}

	public function _delete_usage_log($module_id = 0, $usage_count = 0, $user_id = 0) {

		if ($module_id == 0 || $usage_count == 0) {
			return false;
		}

		if ($user_id == 0) {
			$user_id = $this->session->userdata("user_id");
		}

		if ($user_id == 0 || $user_id == "") {
			return false;
		}

		$usage_month = date("n");
		$usage_year = date("Y");
		$where = array("module_id" => $module_id, "user_id" => $user_id, "usage_month" => $usage_month, "usage_year" => $usage_year);

		$insert_data = array("module_id" => $module_id, "user_id" => $user_id, "usage_month" => $usage_month, "usage_year" => $usage_year, "usage_count" => $usage_count);

		if ($this->basic->is_exist("usage_log", $where)) {
			$this->db->set('usage_count', 'usage_count-' . $usage_count, FALSE);
			$this->db->where($where);
			$this->db->update('usage_log');
		} else {
			$this->basic->insert_data("usage_log", $insert_data);
		}

		return true;
	}

	public function _check_usage($module_id = 0, $request = 0, $user_id = 0) {

		if ($module_id == 0 || $request == 0) {
			return "0";
		}

		if ($user_id == 0) {
			$user_id = $this->session->userdata("user_id");
		}

		if ($user_id == 0 || $user_id == "") {
			return false;
		}

		if ($this->basic->is_exist("modules", array("id" => $module_id, "extra_text" => ""), "id")) // not monthly limit modules
		{
			$this->db->select_sum('usage_count');
			$this->db->where('user_id', $user_id);
			$this->db->where('module_id', $module_id);
			$info = $this->db->get('usage_log')->result_array();

			$usage_count = 0;
			if (isset($info[0]["usage_count"])) {
				$usage_count = $info[0]["usage_count"];
			}

		} else {
			$usage_month = date("n");
			$usage_year = date("Y");
			$info = $this->basic->get_data("usage_log", $where = array("where" => array("usage_month" => $usage_month, "usage_year" => $usage_year, "module_id" => $module_id, "user_id" => $user_id)));
			$usage_count = 0;
			if (isset($info[0]["usage_count"])) {
				$usage_count = $info[0]["usage_count"];
			}

		}

		$monthly_limit = array();
		$bulk_limit = array();
		$module_ids = array();

		if ($this->session->userdata("package_info") != "") {
			$package_info = $this->session->userdata("package_info");
			if ($this->session->userdata('user_type') == 'Admin') {
				return "1";
			}

		} else {
			$package_data = $this->basic->get_data("users", $where = array("where" => array("users.id" => $user_id)), "package.*,users.user_type", array('package' => "users.package_id=package.id,left"));
			$package_info = array();
			if (array_key_exists(0, $package_data)) {
				$package_info = $package_data[0];
			}

			if ($package_info['user_type'] == 'Admin') {
				return "1";
			}

		}

		if (isset($package_info["bulk_limit"])) {
			$bulk_limit = json_decode($package_info["bulk_limit"], true);
		}

		if (isset($package_info["monthly_limit"])) {
			$monthly_limit = json_decode($package_info["monthly_limit"], true);
		}

		if (isset($package_info["module_ids"])) {
			$module_ids = explode(',', $package_info["module_ids"]);
		}

		$return = "0";
		if (in_array($module_id, $module_ids) && $bulk_limit[$module_id] > 0 && $bulk_limit[$module_id] < $request) {
			$return = "2";
		}
		// bulk limit crossed | 0 means unlimited
		else if (in_array($module_id, $module_ids) && $monthly_limit[$module_id] > 0 && $monthly_limit[$module_id] < ($request + $usage_count)) {
			$return = "3";
		}
		// montly limit crossed | 0 means unlimited
		else {
			$return = "1";
		}
		//success

		return $return;
	}

	public function print_limit_message($module_id = 0, $request = 0) {
		$status = $this->_check_usage($module_id, $request);
		if ($status == "2") {
			echo $this->lang->line("sorry, your bulk limit is exceeded for this module.") . "<a href='" . site_url('usage_history') . "'>" . $this->lang->line("click here to see usage log") . "</a>";
			exit();
		} else if ($status == "3") {
			echo $this->lang->line("sorry, your monthly limit is exceeded for this module.") . "<a href='" . site_url('usage_history') . "'>" . $this->lang->line("click here to see usage log") . "</a>";
			exit();
		}

	}

	public function member_validity() {
		if ($this->session->userdata('logged_in') == 1 && $this->session->userdata('user_type') != 'Admin') {
			$where['where'] = array('id' => $this->session->userdata('user_id'));
			$user_expire_date = $this->basic->get_data('users', $where, $select = array('expired_date'));
			$expire_date = strtotime($user_expire_date[0]['expired_date']);
			$current_date = strtotime(date("Y-m-d"));
			$package_data = $this->basic->get_data("users", $where = array("where" => array("users.id" => $this->session->userdata("user_id"))), $select = "package.price as price", $join = array('package' => "users.package_id=package.id,left"));
			if (is_array($package_data) && array_key_exists(0, $package_data)) {
				$price = $package_data[0]["price"];
			}

			if ($price == "Trial") {
				$price = 1;
			}

			if ($expire_date < $current_date && ($price > 0 && $price != "")) {
				redirect('payment/buy_package', 'Location');
			}

		}
	}

	public function important_feature() {
		if (file_exists(APPPATH . 'config/licence.txt') && file_exists(APPPATH . 'core/licence.txt')) {
			$config_existing_content = file_get_contents(APPPATH . 'config/licence.txt');
			$config_decoded_content = json_decode($config_existing_content, true);

			$core_existing_content = file_get_contents(APPPATH . 'core/licence.txt');
			$core_decoded_content = json_decode($core_existing_content, true);

			if ($config_decoded_content['is_active'] != md5($config_decoded_content['purchase_code']) || $core_decoded_content['is_active'] != md5(md5($core_decoded_content['purchase_code']))) {
				redirect("home/credential_check", 'Location');
			}
		} else {
			redirect("home/credential_check", 'Location');
		}

	}
	public function credential_check($secret_code = 0) {
		if ($this->is_demo == '1') {
			redirect('home/access_forbidden', 'refresh');
		}

		$permissio = 0;
		if ($this->session->userdata("user_type") == "Admin") {
			$permissio = 1;
		} else {
			$permissio = 0;
		}

		if ($permissio == 0) {
			redirect('home/access_forbidden', 'location');
		}

		$data['body'] = 'front/credential_check';
		$data["page_title"] = $this->lang->line("Credential Check");
		$this->_subscription_viewcontroller($data);
	}

	public function credential_check_action() {
		if ($this->is_demo == '1') {
			redirect('home/access_forbidden', 'refresh');
		}

		$domain_name = $this->input->post("domain_name", true);
		$purchase_code = $this->input->post("purchase_code", true);
		$only_domain = get_domain_only($domain_name);

		$response = $this->code_activation_check_action($purchase_code, $only_domain);
		if (file_exists(APPPATH . 'core/licence_type.txt')) {
			$this->license_check_action();
		}

		echo $response;

	}

	public function code_activation_check_action($purchase_code, $only_domain, $periodic = 0) {
		$url = "http://xeroneit.net/development/envato_license_activation/purchase_code_check.php?purchase_code={$purchase_code}&domain={$only_domain}&item_name=XeroChat";

		$credentials = $this->get_general_content_with_checking($url);
		$decoded_credentials = json_decode($credentials, true);

		if (isset($decoded_credentials['error'])) {
			$url = "https://mostofa.club/development/envato_license_activation/purchase_code_check.php?purchase_code={$purchase_code}&domain={$only_domain}&item_name=XeroChat";
			$credentials = $this->get_general_content_with_checking($url);
			$decoded_credentials = json_decode($credentials, true);
		}

		if (!isset($decoded_credentials['error'])) {
			$content = json_decode($decoded_credentials['content'], true);
			if ($content['status'] == 'success') {
				$content_to_write = array(
					'is_active' => md5($purchase_code),
					'purchase_code' => $purchase_code,
					'item_name' => $content['item_name'],
					'buy_at' => $content['buy_at'],
					'licence_type' => $content['license'],
					'domain' => $only_domain,
					'checking_date' => date('Y-m-d'),
				);
				$config_json_content_to_write = json_encode($content_to_write);
				file_put_contents(APPPATH . 'config/licence.txt', $config_json_content_to_write, LOCK_EX);

				$content_to_write['is_active'] = md5(md5($purchase_code));
				$core_json_content_to_write = json_encode($content_to_write);
				file_put_contents(APPPATH . 'core/licence.txt', $core_json_content_to_write, LOCK_EX);

				// added by mostofa 06/03/2017
				$license_type = $content['license'];
				if ($license_type != 'Regular License') {
					$str = $purchase_code . "_double";
				} else {
					$str = $purchase_code . "_single";
				}

				$encrypt_method = "AES-256-CBC";
				$secret_key = 't8Mk8fsJMnFw69FGG5';
				$secret_iv = '9fljzKxZmMmoT358yZ';
				$key = hash('sha256', $secret_key);
				$string = $str;
				$iv = substr(hash('sha256', $secret_iv), 0, 16);
				$output = openssl_encrypt($string, $encrypt_method, $key, 0, $iv);
				$encoded = base64_encode($output);
				file_put_contents(APPPATH . 'core/licence_type.txt', $encoded, LOCK_EX);

				return json_encode("success");

			} else {
				if (file_exists(APPPATH . 'core/licence.txt')) {
					unlink(APPPATH . 'core/licence.txt');
				}

				return json_encode($content);
			}
		} else {
			if ($periodic == 1) {
				return json_encode("success");
			} else {
				$response['reason'] = "cURL is not working properly, please contact with your hosting provider.";
				return json_encode($response);
			}
		}
	}

	public function periodic_check() {

		$today = date('d');

		if ($today % 7 == 0) {

			if (file_exists(APPPATH . 'config/licence.txt') && file_exists(APPPATH . 'core/licence.txt')) {
				$config_existing_content = file_get_contents(APPPATH . 'config/licence.txt');
				$config_decoded_content = json_decode($config_existing_content, true);
				$last_check_date = $config_decoded_content['checking_date'];
				$purchase_code = $config_decoded_content['purchase_code'];
				$base_url = base_url();
				$domain_name = get_domain_only($base_url);

				if (strtotime(date('Y-m-d')) != strtotime($last_check_date)) {
					$this->code_activation_check_action($purchase_code, $domain_name, $periodic = 1);
				}
			}
		}
	}

	public function license_check() {
		$file_data = file_get_contents(APPPATH . 'core/licence.txt');
		$file_data_array = json_decode($file_data, true);

		$purchase_code = $file_data_array['purchase_code'];

		$url = "http://xeroneit.net/development/envato_license_activation/regular_or_extended_check_r.php?purchase_code={$purchase_code}";

		$credentials = $this->get_general_content_with_checking($url);
		$response = json_decode($credentials, true);
		$response = json_decode($response['content'], true);

		if (!isset($response['status']) || $response['status'] == 'error') {
			$url = "https://mostofa.club/development/envato_license_activation/regular_or_extended_check_r.php?purchase_code={$purchase_code}";
			$credentials = $this->get_general_content_with_checking($url);
			$response = json_decode($credentials, true);
			$response = json_decode($response['content'], true);
		}

		if (isset($response['status'])) {
			if ($response['status'] == 'error') {
				$status = 'single';
			} else if ($response['status'] == 'success' && $response['license'] == 'Regular License') {
				$status = 'single';
			} else {
				$status = 'double';
			}
			$content = $purchase_code . "_" . $status;

			$encrypt_method = "AES-256-CBC";
			$secret_key = 't8Mk8fsJMnFw69FGG5';
			$secret_iv = '9fljzKxZmMmoT358yZ';
			$key = hash('sha256', $secret_key);
			$string = $content;
			$iv = substr(hash('sha256', $secret_iv), 0, 16);
			$output = openssl_encrypt($string, $encrypt_method, $key, 0, $iv);
			$encoded = base64_encode($output);

			file_put_contents(APPPATH . 'core/licence_type.txt', $encoded, LOCK_EX);
		}

	}

	public function license_check_action() {
		$encoded = file_get_contents(APPPATH . 'core/licence_type.txt');
		$encrypt_method = "AES-256-CBC";
		$secret_key = 't8Mk8fsJMnFw69FGG5';
		$secret_iv = '9fljzKxZmMmoT358yZ';
		$key = hash('sha256', $secret_key);
		$iv = substr(hash('sha256', $secret_iv), 0, 16);
		$decoded = openssl_decrypt(base64_decode($encoded), $encrypt_method, $key, 0, $iv);

		$decoded = explode('_', $decoded);
		$decoded = array_pop($decoded);
		$this->session->set_userdata('license_type', $decoded);
	}

	public function php_info() {
		if ($this->session->userdata('user_type') == 'Admin') {
			echo phpinfo();
		} else {
			redirect('home/access_forbidden', 'location');
		}

	}
	//=======================USAGE LOG & LICENSE FUNCTIONS======================
	//==========================================================================

	//================================================================
	//========================= ADDON FUNCTIONS ======================
	//loads language files of addons
	protected function language_loader_addon() {

		$controller_name = strtolower($this->uri->segment(1));
		$path_without_filename = "application/modules/" . $controller_name . "/language/" . $this->language . "/";
		if (file_exists($path_without_filename . $controller_name . "_lang.php")) {
			$filename = $controller_name;
			$this->lang->load($filename, $this->language, FALSE, TRUE, $path_without_filename);
		}

	}

	// delete any direcory with it childs even it is not empty
	protected function delete_directory($dirPath = "") {
		if (!is_dir($dirPath)) {
			return false;
		}

		if (substr($dirPath, strlen($dirPath) - 1, 1) != '/') {
			$dirPath .= '/';
		}

		$files = glob($dirPath . '*', GLOB_MARK);
		foreach ($files as $file) {
			if (is_dir($file)) {
				$this->delete_directory($file);
			} else {
				@unlink($file);
			}

		}
		rmdir($dirPath);
	}

	// takes addon controller path as input and extract add on data from comment block
	protected function get_addon_data($path = "") {
		$path = str_replace('\\', '/', $path);
		$tokens = token_get_all(file_get_contents($path));
		$addon_data = array();

		$addon_path = explode('/', $path);
		$controller_name = array_pop($addon_path);
		array_pop($addon_path);
		$addon_path = implode('/', $addon_path);

		$comments = array();
		foreach ($tokens as $token) {
			if ($token[0] == T_COMMENT || $token[0] == T_DOC_COMMENT) {
				$comments[] = isset($token[1]) ? $token[1] : "";
			}
		}
		$comment_str = isset($comments[0]) ? $comments[0] : "";

		preg_match('/^.*?addon name:(.*)$/mi', $comment_str, $match);
		$addon_data['addon_name'] = isset($match[1]) ? trim($match[1]) : "";

		preg_match('/^.*?unique name:(.*)$/mi', $comment_str, $match);
		$addon_data['unique_name'] = isset($match[1]) ? trim($match[1]) : "";

		preg_match('#modules:(.*?)Project ID#si', $comment_str, $match);
		$addon_data['modules'] = isset($match[1]) ? trim($match[1]) : "";

		preg_match('/^.*?project id:(.*)$/mi', $comment_str, $match);
		$addon_data['project_id'] = isset($match[1]) ? trim($match[1]) : "";

		preg_match('/^.*?addon uri:(.*)$/mi', $comment_str, $match);
		$addon_data['addon_uri'] = isset($match[1]) ? trim($match[1]) : "";

		preg_match('/^.*?author:(.*)$/mi', $comment_str, $match);
		$addon_data['author'] = isset($match[1]) ? trim($match[1]) : "";

		preg_match('/^.*?author uri:(.*)$/mi', $comment_str, $match);
		$addon_data['author_uri'] = isset($match[1]) ? trim($match[1]) : "";

		preg_match('/^.*?version:(.*)$/mi', $comment_str, $match);
		$addon_data['version'] = isset($match[1]) ? trim($match[1]) : "1.0";

		preg_match('/^.*?description:(.*)$/mi', $comment_str, $match);
		$addon_data['description'] = isset($match[1]) ? trim($match[1]) : "";

		$addon_data['controller_name'] = isset($controller_name) ? trim($controller_name) : "";

		if (file_exists($addon_path . '/install.txt')) {
			$addon_data['installed'] = '0';
		} else {
			$addon_data['installed'] = '1';
		}

		return $addon_data;
	}

	// checks purchase code , returns boolean
	protected function addon_credential_check($purchase_code = "", $item_name = "") {
		$purchase_code = trim($purchase_code);
		if ($purchase_code == "") {
			echo json_encode(array('status' => '0', 'message' => $this->lang->line('Add-on purchase code has not been provided.')));
			exit();
		}

		$item_name = urlencode($item_name);
		$only_domain = get_domain_only(site_url());
		$url = "http://xeroneit.net/development/envato_license_activation/purchase_code_check.php?purchase_code={$purchase_code}&domain={$only_domain}&item_name=XeroChat-{$item_name}";

		$credentials = $this->get_general_content_with_checking($url);
		$decoded_credentials = json_decode($credentials, true);

		if (isset($decoded_credentials['error'])) {
			$url = "https://mostofa.club/development/envato_license_activation/purchase_code_check.php?purchase_code={$purchase_code}&domain={$only_domain}&item_name=XeroChat-{$item_name}";
			$credentials = $this->get_general_content_with_checking($url);
			$decoded_credentials = json_decode($credentials, true);
		}

		if (!isset($decoded_credentials['error'])) {
			$content = json_decode($decoded_credentials['content'], true);
			if ($content['status'] != 'success') {
				echo json_encode(array('status' => '0', 'message' => $this->lang->line('Purchase code is not valid or already used.')));
				exit();
			}
		} else {
			echo json_encode(array('status' => '0', 'message' => $this->lang->line('Something went wrong. cURL is not working.')));
			exit();
		}
	}

	// validataion of addon data
	protected function check_addon_data($addon_data = array()) {
		if (!isset($addon_data['unique_name']) || $addon_data['unique_name'] == "") {
			echo json_encode(array('status' => '0', 'message' => $this->lang->line('Add-on unique name has not been provided.')));
			exit();
		}

		if (!$this->is_unique_check("addon_check", $addon_data['unique_name'])) //  unique name must be unique
		{
			echo json_encode(array('status' => '0', 'message' => $this->lang->line('Add-on is already active. Duplicate unique name found.')));
			exit();
		}
	}

	// inserts data to add_ons table + modules + menu + menuchild1 + removes install.txt, returns json status,message
	protected function register_addon($addon_controller_name = "", $sidebar = array(), $sql = array(), $purchase_code = "", $default_module_name = "") {
		if ($this->session->userdata('user_type') != 'Admin') {
			echo json_encode(array('status' => '0', 'message' => $this->lang->line('Access Forbidden')));
			exit();
		}

		if ($this->is_demo == '1') {
			echo json_encode(array('status' => '0', 'message' => $this->lang->line('Access Forbidden')));
			exit();
		}

		if ($addon_controller_name == "") {
			echo json_encode(array('status' => '0', 'message' => $this->lang->line('Add-on controller has not been provided.')));
			exit();
		}

		$path = APPPATH . "modules/" . strtolower($addon_controller_name) . "/controllers/" . $addon_controller_name . ".php"; // path of addon controller
		$install_txt_path = APPPATH . "modules/" . strtolower($addon_controller_name) . "/install.txt"; // path of install.txt
		if (!file_exists($path)) {
			echo json_encode(array('status' => '0', 'message' => $this->lang->line('Add-on controller not found.')));
			exit();
		}

		$addon_data = $this->get_addon_data($path);

		$this->check_addon_data($addon_data);

		try
		{
			$this->db->trans_start();

			// addon table entry
			$this->basic->insert_data("add_ons", array("add_on_name" => $addon_data['addon_name'], "unique_name" => $addon_data["unique_name"], "version" => $addon_data["version"], "installed_at" => date("Y-m-d H:i:s"), "purchase_code" => $purchase_code, "module_folder_name" => strtolower($addon_controller_name), "project_id" => $addon_data["project_id"]));
			$add_ons_id = $this->db->insert_id();

			$parent_module_id = "";
			$modules = isset($addon_data['modules']) ? json_decode(trim($addon_data['modules']), true) : array();

			if (json_last_error() === 0 && is_array($modules)) {
				$module_ids = array_keys($modules);
				$parent_module_id = implode(',', $module_ids);

				foreach ($modules as $key => $value) {
					if (!$this->basic->is_exist("modules", array("id" => $key))) {
						$this->basic->insert_data("modules", array("id" => $key, "extra_text" => $value['extra_text'], "module_name" => $value['module_name'], 'bulk_limit_enabled' => $value['bulk_limit_enabled'], 'limit_enabled' => $value['limit_enabled'], "add_ons_id" => $add_ons_id, "deleted" => "0"));
					}

				}
			}

			//--------------- sidebar entry--------------------
			//-------------------------------------------------
			if (is_array($sidebar)) {
				foreach ($sidebar as $key => $value) {
					$parent_name = isset($value['name']) ? $value['name'] : "";
					$parent_icon = isset($value['icon']) ? $value['icon'] : "";
					$parent_url = isset($value['url']) ? $value['url'] : "#";
					$parent_is_external = isset($value['is_external']) ? $value['is_external'] : "0";
					$child_info = isset($value['child_info']) ? $value['child_info'] : array();
					$have_child = isset($child_info['have_child']) ? $child_info['have_child'] : '0';
					$only_admin = isset($value['only_admin']) ? $value['only_admin'] : '0';
					$only_member = isset($value['only_member']) ? $value['only_member'] : '0';
					$parent_serial = 50;

					$parent_menu = array('name' => $parent_name, 'icon' => $parent_icon, 'url' => $parent_url, 'serial' => $parent_serial, 'module_access' => $parent_module_id, 'have_child' => $have_child, 'only_admin' => $only_admin, 'only_member' => $only_member, 'add_ons_id' => $add_ons_id, 'is_external' => $parent_is_external);
					$this->basic->insert_data('menu', $parent_menu); // parent menu entry
					$parent_id = $this->db->insert_id();

					if ($have_child == '1') {
						if (!empty($child_info)) {
							$child = isset($child_info['child']) ? $child_info['child'] : array();

							$child_serial = 0;
							if (!empty($child)) {
								foreach ($child as $key2 => $value2) {
									$child_serial++;
									$child_name = isset($value2['name']) ? $value2['name'] : "";
									$child_icon = isset($value2['icon']) ? $value2['icon'] : "";
									$child_url = isset($value2['url']) ? $value2['url'] : "#";
									$child_info_1 = isset($value2['child_info']) ? $value2['child_info'] : array();
									$child_is_external = isset($value2['is_external']) ? $value2['is_external'] : "0";
									$have_child = isset($child_info_1['have_child']) ? $child_info_1['have_child'] : '0';
									$only_admin = isset($value2['only_admin']) ? $value2['only_admin'] : '0';
									$only_member = isset($value2['only_member']) ? $value2['only_member'] : '0';
									$module_access = isset($value2['module_access']) ? $value2['module_access'] : '';
									if ($module_access == '') {
										$module_access = $parent_module_id;
									}

									$child_menu = array('name' => $child_name, 'icon' => $child_icon, 'url' => $child_url, 'serial' => $child_serial, 'module_access' => $module_access, 'parent_id' => $parent_id, 'have_child' => $have_child, 'only_admin' => $only_admin, 'only_member' => $only_member, 'is_external' => $child_is_external);
									$this->basic->insert_data('menu_child_1', $child_menu); // child menu entry
									$sub_parent_id = $this->db->insert_id();

									if ($have_child == '1') {
										if (!empty($child_info_1)) {
											$child = isset($child_info_1['child']) ? $child_info_1['child'] : array();

											$child_child_serial = 0;
											if (!empty($child)) {
												foreach ($child as $key3 => $value3) {
													$child_child_serial++;
													$child_name = isset($value3['name']) ? $value3['name'] : "";
													$child_icon = isset($value3['icon']) ? $value3['icon'] : "";
													$child_url = isset($value3['url']) ? $value3['url'] : "#";
													$child_is_external = isset($value3['is_external']) ? $value3['is_external'] : "0";
													$have_child = '0';
													$only_admin = isset($value3['only_admin']) ? $value3['only_admin'] : '0';
													$only_member = isset($value3['only_member']) ? $value3['only_member'] : '0';
													$module_access2 = isset($value3['module_access']) ? $value3['module_access'] : '';
													if ($module_access2 == '') {
														$module_access2 = $module_access;
													}

													$child_menu = array('name' => $child_name, 'icon' => $child_icon, 'url' => $child_url, 'serial' => $child_child_serial, 'module_access' => $module_access2, 'parent_child' => $sub_parent_id, 'only_admin' => $only_admin, 'only_member' => $only_member, 'is_external' => $child_is_external);
													$this->basic->insert_data('menu_child_2', $child_menu); // child menu entry

												}
											}

										}
									}
								}
							}

						}
					}

				}
			}

			//--------------- sidebar entry--------------------
			//-------------------------------------------------

			$this->db->trans_complete();

			if ($this->db->trans_status() === FALSE) {
				echo json_encode(array('status' => '0', 'message' => $this->lang->line('Database error. Something went wrong.')));
				exit();
			} else {

				//--------Custom SQL------------
				$this->db->db_debug = FALSE; //disable debugging for queries
				if (is_array($sql)) {
					foreach ($sql as $key => $query) {
						try
						{
							$this->db->query($query);
						} catch (Exception $e) {
						}
					}
				}

				//--------Custom SQL------------
				@unlink($install_txt_path); // removing install.txt
				echo json_encode(array('status' => '1', 'message' => $this->lang->line('Add-on has been activated successfully.')));
			}

		} //end of try
		 catch (Exception $e) {
			$error = $e->getMessage();
			echo json_encode(array('status' => '0', 'message' => $this->lang->line($error)));
		}
	}

	// deletes data from add_ons table + modules + menu + menuchild1 + puts install.txt, returns json status,message
	protected function unregister_addon($addon_controller_name = "") {
		if ($this->session->userdata('user_type') != 'Admin') {
			echo json_encode(array('status' => '0', 'message' => $this->lang->line('Access Forbidden')));
			exit();
		}

		if ($this->is_demo == '1') {
			echo json_encode(array('status' => '0', 'message' => $this->lang->line('Access Forbidden')));
			exit();
		}

		if ($addon_controller_name == "") {
			echo json_encode(array('status' => '0', 'message' => $this->lang->line('Add-on controller has not been provided.')));
			exit();
		}

		$path = APPPATH . "modules/" . strtolower($addon_controller_name) . "/controllers/" . $addon_controller_name . ".php"; // path of addon controller
		$install_txt_path = APPPATH . "modules/" . strtolower($addon_controller_name) . "/install.txt"; // path of install.txt
		if (!file_exists($path)) {
			echo json_encode(array('status' => '0', 'message' => $this->lang->line('Add-on controller not found.')));
			exit();
		}

		$addon_data = $this->get_addon_data($path);

		if (!isset($addon_data['unique_name']) || $addon_data['unique_name'] == "") {
			echo json_encode(array('status' => '0', 'message' => $this->lang->line('Add-on unique name has not been provided.')));
			exit();
		}

		try
		{
			$this->db->trans_start();

			// delete addon table entry
			$get_addon = $this->basic->get_data("add_ons", array("where" => array("unique_name" => $addon_data['unique_name'])));
			$add_ons_id = isset($get_addon[0]['id']) ? $get_addon[0]['id'] : 0;
			if ($add_ons_id > 0) {
				$this->basic->delete_data("add_ons", array("id" => $add_ons_id));
			}

			// delete modules table entry
			if ($add_ons_id > 0) {
				$this->basic->delete_data("modules", array("add_ons_id" => $add_ons_id));
			}

			// delete menu+menu_child1 table entry
			$get_menu = array();
			if ($add_ons_id > 0) {
				$get_menu = $this->basic->get_data("menu", array("where" => array("add_ons_id" => $add_ons_id)));
			}

			foreach ($get_menu as $key => $value) {
				$parent_id = isset($value['id']) ? $value['id'] : 0;
				if ($parent_id > 0) {
					$this->basic->delete_data("menu", array("id" => $parent_id));
					$this->basic->delete_data("menu_child_1", array("parent_id" => $parent_id));
				}
			}

			$this->db->trans_complete();

			if ($this->db->trans_status() === FALSE) {
				echo json_encode(array('status' => '0', 'message' => $this->lang->line('Database error. Something went wrong.')));
				exit();
			} else {
				if (!file_exists($install_txt_path)) // putting install.txt
				{
					fopen($install_txt_path, "w");
				}

				echo json_encode(array('status' => '1', 'message' => $this->lang->line('Add-on has been deactivated successfully.')));
			}
		} catch (Exception $e) {
			$error = $e->getMessage();
			echo json_encode(array('status' => '0', 'message' => $this->lang->line($error)));
		}
	}

	// deletes data from add_ons table + modules + menu + menuchild1 + custom sql + folder, returns json status,message
	protected function delete_addon($addon_controller_name = "", $sql = array()) {
		if ($this->session->userdata('user_type') != 'Admin') {
			echo json_encode(array('status' => '0', 'message' => $this->lang->line('Access Forbidden')));
			exit();
		}

		if ($this->is_demo == '1') {
			echo json_encode(array('status' => '0', 'message' => $this->lang->line('Access Forbidden')));
			exit();
		}

		if ($addon_controller_name == "") {
			echo json_encode(array('status' => '0', 'message' => $this->lang->line('Add-on controller has not been provided.')));
			exit();
		}

		$path = APPPATH . "modules/" . strtolower($addon_controller_name) . "/controllers/" . $addon_controller_name . ".php"; // path of addon controller
		$addon_path = APPPATH . "modules/" . strtolower($addon_controller_name); // path of module folder
		if (!file_exists($path)) {
			echo json_encode(array('status' => '0', 'message' => $this->lang->line('Add-on controller not found.')));
			exit();
		}

		$addon_data = $this->get_addon_data($path);

		if (!isset($addon_data['unique_name']) || $addon_data['unique_name'] == "") {
			echo json_encode(array('status' => '0', 'message' => $this->lang->line('Add-on unique name has not been provided.')));
			exit();
		}

		try
		{
			$this->db->trans_start();

			// delete addon table entry
			$get_addon = $this->basic->get_data("add_ons", array("where" => array("unique_name" => $addon_data['unique_name'])));
			$add_ons_id = isset($get_addon[0]['id']) ? $get_addon[0]['id'] : 0;
			$purchase_code = isset($get_addon[0]['purchase_code']) ? $get_addon[0]['purchase_code'] : '';
			if ($add_ons_id > 0) {
				$this->basic->delete_data("add_ons", array("id" => $add_ons_id));
			}

			// delete modules table entry
			if ($add_ons_id > 0) {
				$this->basic->delete_data("modules", array("add_ons_id" => $add_ons_id));
			}

			// delete menu+menu_child1 table entry
			$get_menu = array();
			if ($add_ons_id > 0) {
				$get_menu = $this->basic->get_data("menu", array("where" => array("add_ons_id" => $add_ons_id)));
			}

			foreach ($get_menu as $key => $value) {
				$parent_id = isset($value['id']) ? $value['id'] : 0;
				if ($parent_id > 0) {
					$this->basic->delete_data("menu", array("id" => $parent_id));
					$this->basic->delete_data("menu_child_1", array("parent_id" => $parent_id));
				}
			}

			$this->db->trans_complete();

			if ($this->db->trans_status() === FALSE) {
				echo json_encode(array('status' => '0', 'message' => $this->lang->line('Database error. Something went wrong.')));
				exit();
			} else {
				//--------Custom SQL------------
				$this->db->db_debug = FALSE; //disable debugging for queries
				if (is_array($sql)) {
					foreach ($sql as $key => $query) {
						try
						{
							$this->db->query($query);
						} catch (Exception $e) {
						}
					}
				}

				//--------Custom SQL------------

				$this->delete_directory($addon_path);
				if ($purchase_code != "") {
					$item_name = strtolower($addon_controller_name);
					$only_domain = get_domain_only(site_url());
					$url = "http://xeroneit.net/development/envato_license_activation/delete_purchase_code.php?purchase_code={$purchase_code}&domain={$only_domain}&item_name=XeroChat-{$item_name}";
					$credentials = $this->get_general_content_with_checking($url);
					$response = json_decode($credentials, true);
					if (isset($response['error'])) {
						$url = "https://mostofa.club/development/envato_license_activation/delete_purchase_code.php?purchase_code={$purchase_code}&domain={$only_domain}&item_name=XeroChat-{$item_name}";
						$this->get_general_content_with_checking($url);
					}
				}

				echo json_encode(array('status' => '1', 'message' => $this->lang->line('add-on has been deleted successfully.')));
			}
		} catch (Exception $e) {
			$error = $e->getMessage();
			echo json_encode(array('status' => '0', 'message' => $this->lang->line($error)));
		}
	}

	// check a addon or module id is usable or already used, returns boolean, true if unique
	protected function is_unique_check($type = 'addon_check', $value = "") // type=addon_check/module_check | $value=column.value
	{
		$is_unique = false;
		if ($type == "addon_check") {
			$is_unique = $this->basic->is_unique("add_ons", array("unique_name" => $value), "id");
		}

		if ($type == "module_check") {
			$is_unique = $this->basic->is_unique("modules", array("id" => $value), "id");
		}

		return $is_unique;
	}

	//========================= ADDON FUNCTIONS ======================
	//================================================================

	protected function delete_full_access() {
		if ($this->session->userdata('user_type') == 'Admin') {
			exit();
		}

		if (!isset($_POST)) {
			exit();
		}

		$user_id = $this->session->userdata('user_id');

		$this->db->trans_start();
		$sql = "show tables;";
		$a = $this->basic->execute_query($sql);
		foreach ($a as $value) {
			foreach ($value as $table_name) {
				if ($table_name == 'users') {
					$this->basic->delete_data('users', array('id' => $user_id));
				}

				if ($this->db->field_exists('user_id', $table_name)) {
					$this->basic->delete_data($table_name, array('user_id' => $user_id));
				}

			}
		}
		$this->db->trans_complete();

		if ($this->db->trans_status() === FALSE) {
			echo $this->lang->line('Something went wrong, please try again.');
		} else {
			$this->session->sess_destroy();
			echo 'success';
		}

	}

	protected function scanAll($myDir) {

		$dirTree = array();
		$di = new RecursiveDirectoryIterator($myDir, RecursiveDirectoryIterator::SKIP_DOTS);

		foreach (new RecursiveIteratorIterator($di) as $filename) {

			$dir = str_replace($myDir, '', dirname($filename));
			//$dir = str_replace('/', '>', substr($dir,1));

			$org_dir = str_replace("\\", "/", $dir);

			if ($org_dir) {
				$file_path = $org_dir . "/" . basename($filename);
			} else {
				$file_path = basename($filename);
			}

			$dirTree[] = $file_path;

		}

		return $dirTree;

	}

	// =========================================================================================
	//===============================MESSENGER BOT FUNCTIONS====================================
	/******88*WEBHOOK,COMMON BOT ADDON FUNCTIONS,PUBLIC CURL CALLS, CRON SUB FUNCTIONS**88******/
	public function central_webhook_callback() {
		$url = "";
		$challenge = $this->input->get_post('hub_challenge');
		$verify_token = $this->input->get_post('hub_verify_token');
		if ($this->config->item("central_webhook_verify_token") != '') {
			if ($verify_token === $this->config->item("central_webhook_verify_token")) {
				echo $challenge;
				die();
			}
		}

		$response_raw = file_get_contents("php://input");

		if (!isset($response_raw) || $response_raw == '') {
			exit;
		}

		$json_response = array("response_raw" => $response_raw);
		$response = json_decode($response_raw, true);

		if (isset($response['entry'][0]['messaging'])) {

			$url = base_url() . "messenger_bot/webhook_callback_main";
			if (isset($response['entry']['0']['messaging'][0]['read'])) {
				exit;
			}

		} else if (isset($response['entry'][0]['changes'][0]['value']['item']) && $response['entry'][0]['changes'][0]['value']['item'] == 'comment') {
			$url = base_url() . "comment_automation/webhook_callback_main";

			$commenter_id = isset($response['entry'][0]['changes'][0]['value']['sender_id']) ? $response['entry'][0]['changes'][0]['value']['sender_id'] : $response['entry'][0]['changes'][0]['value']['from']['id'];
			$page_id = $response['entry'][0]['id'];

			//If activity by Page it self, then exit
			if ($page_id == $commenter_id) {
				exit;
			}

			// 2nd level relpy is turned off
			$post_id = isset($response['entry'][0]['changes'][0]['value']['parent_id']) ? $response['entry'][0]['changes'][0]['value']['parent_id'] : "";
			$parent_id_page_id_array = explode("_", $post_id);
			$parent_id_page_id = isset($parent_id_page_id_array[0]) ? $parent_id_page_id_array[0] : "";

			if ($page_id != $parent_id_page_id) {
				// From 2nd reply Comment.
				exit;
			}

			//If already replied that comment, then exit
			$comment_id = isset($response['entry'][0]['changes'][0]['value']['comment_id']) ? $response['entry'][0]['changes'][0]['value']['comment_id'] : "";
			$already_replied_comment_id = $this->basic->get_data('facebook_ex_autoreply_report', array('where' => array('comment_id' => $comment_id)));
			if (!empty($already_replied_comment_id)) {
				exit;
			}

		} else if (isset($response['entry'][0]['changes'][0]['value']['item']) && $response['entry'][0]['changes'][0]['value']['item'] == 'reaction') {
			exit;
		} else if (isset($response['entry'][0]['changes'][0]['value']['item']) && $response['entry'][0]['changes'][0]['value']['item'] == 'photo') {
			if (isset($response['entry'][0]['changes'][0]['value']['verb']) && $response['entry'][0]['changes'][0]['value']['verb'] == 'edited') {
				exit;
			}

			$url = base_url() . "comment_automation/webhook_callback_main";
		} else if (isset($response['entry'][0]['changes'][0]['field']) && $response['entry'][0]['changes'][0]['field'] == 'feed') {
			$url = base_url() . "comment_automation/webhook_callback_main";
		} else if (isset($response['entry'][0]['changes'][0]['value']['item']) && $response['entry'][0]['changes'][0]['value']['item'] == 'status') {
			$url = base_url() . "comment_automation/webhook_callback_main";
		} else if (isset($response['entry'][0]['changes'][0]['value']['item']) && $response['entry'][0]['changes'][0]['value']['item'] == 'share') {
			$url = base_url() . "comment_automation/webhook_callback_main";
		}

		if ($url == '') {
			exit;
		}

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $json_response);
		curl_setopt($ch, CURLOPT_TIMEOUT, 5);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$reply_response = curl_exec($ch);
	}

	public function send_reply_ez($access_token = '', $reply = '') {
		$url = "https://graph.facebook.com/v2.6/me/messages?access_token=$access_token";
		$ch = curl_init();
		$headers = array("Content-type: application/json");
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $reply);

		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_COOKIEJAR, 'cookie.txt');
		curl_setopt($ch, CURLOPT_COOKIEFILE, 'cookie.txt');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.3) Gecko/20070309 Firefox/2.0.0.3");
		$st = curl_exec($ch);

		$result = json_decode($st, TRUE);
		return $result;
	}

	protected function subscriber_info($access_token = '', $sender_id = '') {
		$url = "https://graph.facebook.com/v2.6/$sender_id?access_token=$access_token&fields=id,first_name,last_name,name,profile_pic,locale,timezone,gender";
		$ch = curl_init();
		$headers = array("Content-type: application/json");
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_COOKIEJAR, 'cookie.txt');
		curl_setopt($ch, CURLOPT_COOKIEFILE, 'cookie.txt');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.3) Gecko/20070309 Firefox/2.0.0.3");
		$st = curl_exec($ch);
		$result = json_decode($st, TRUE);
		return $result;
	}

	protected function send_reply($access_token = '', $reply = '') {
		$url = "https://graph.facebook.com/v2.6/me/messages?access_token=$access_token";
		$ch = curl_init();
		$headers = array("Content-type: application/json");
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $reply);

		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_COOKIEJAR, 'cookie.txt');
		curl_setopt($ch, CURLOPT_COOKIEFILE, 'cookie.txt');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.3) Gecko/20070309 Firefox/2.0.0.3");
		$st = curl_exec($ch);

		$result = json_decode($st, TRUE);
		return $result;
	}

	public function send_reply_curl_call() {
		ignore_user_abort(TRUE);
		$access_token = $_POST['access_token'];
		$reply = $_POST['reply'];

		$url = "https://graph.facebook.com/v2.6/me/messages?access_token=$access_token";
		$ch = curl_init();
		$headers = array("Content-type: application/json");
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $reply);

		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_COOKIEJAR, 'cookie.txt');
		curl_setopt($ch, CURLOPT_COOKIEFILE, 'cookie.txt');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.3) Gecko/20070309 Firefox/2.0.0.3");
		$st = curl_exec($ch);

		$result = json_decode($st, TRUE);
		return $result;
	}

	//DEPRECATED FUNCTION FOR QUICK BROADCAST
	public function unsubscribe_webhook_call() {
		$psid = $this->input->post('psid');
		$fb_page_id = $this->input->post('fb_page_id');

		$pageinfo = $this->basic->get_data("facebook_rx_fb_page_info", array("where" => array("page_id" => $fb_page_id, "bot_enabled" => "1")));
		$page_auto_id = isset($pageinfo[0]["id"]) ? $pageinfo[0]["id"] : "";
		$page_access_token = isset($pageinfo[0]["page_access_token"]) ? $pageinfo[0]["page_access_token"] : "";

		$label_info = $this->basic->get_data("messenger_bot_broadcast_contact_group", array("where" => array("page_id" => $page_auto_id, "unsubscribe" => "1")));
		$label_auto_id = isset($label_info[0]['id']) ? $label_info[0]['id'] : 0;
		$label_id = isset($label_info[0]['label_id']) ? $label_info[0]['label_id'] : "";

		$this->load->library('fb_rx_login');
		$response = $this->fb_rx_login->assign_label($page_access_token, $psid, $label_id);

		$subscriberdata = $this->basic->get_data("messenger_bot_subscriber", array("where" => array("subscribe_id" => $psid, "page_id" => $fb_page_id)));

		$contact_group_id = isset($subscriberdata[0]["contact_group_id"]) ? $subscriberdata[0]["contact_group_id"] : "";
		$explode = explode(',', $contact_group_id);
		array_push($explode, $label_auto_id);
		$new = array_unique($explode);
		$contact_group_id = implode(',', $new);
		$contact_group_id = trim($contact_group_id, ',');
		$unsubscribe_time = date('Y-m-d H:i:s');

		$this->basic->update_data("messenger_bot_subscriber", array("subscribe_id" => $psid, "page_id" => $fb_page_id), array("contact_group_id" => $contact_group_id, "permission" => "0", "unsubscribed_at" => $unsubscribe_time));

		/** Adjust total count of subscriber & unsubscriber  **/

		$sql = "SELECT count(id) as permission_count FROM `messenger_bot_subscriber` WHERE page_table_id='$page_auto_id' AND permission='1'";
		$count_data = $this->db->query($sql)->row_array();

		$sql2 = "SELECT count(id) as permission_count FROM `messenger_bot_subscriber` WHERE page_table_id='$page_auto_id' AND permission='0'";
		$count_data2 = $this->db->query($sql2)->row_array();

		// how many are subscribed and how many are unsubscribed
		$subscribed = isset($count_data["permission_count"]) ? $count_data["permission_count"] : 0;
		$unsubscribed = isset($count_data2["permission_count"]) ? $count_data2["permission_count"] : 0;
		$current_lead_count = $subscribed + $unsubscribed;

		$this->basic->update_data("facebook_rx_fb_page_info", array("id" => $page_auto_id), array("current_subscribed_lead_count" => $subscribed, "current_unsubscribed_lead_count" => $unsubscribed));

	}

	public function resubscribe_webhook_call() {

		$psid = $this->input->post('psid');
		$fb_page_id = $this->input->post('fb_page_id');

		$pageinfo = $this->basic->get_data("facebook_rx_fb_page_info", array("where" => array("page_id" => $fb_page_id, "bot_enabled" => "1")));
		$page_auto_id = isset($pageinfo[0]["id"]) ? $pageinfo[0]["id"] : "";
		$page_access_token = isset($pageinfo[0]["page_access_token"]) ? $pageinfo[0]["page_access_token"] : "";

		$label_info = $this->basic->get_data("messenger_bot_broadcast_contact_group", array("where" => array("page_id" => $page_auto_id, "unsubscribe" => "1")));
		$label_auto_id = isset($label_info[0]['id']) ? $label_info[0]['id'] : 0;
		$label_id = isset($label_info[0]['label_id']) ? $label_info[0]['label_id'] : "";

		$this->load->library('fb_rx_login');
		$response = $this->fb_rx_login->deassign_label($page_access_token, $psid, $label_id);

		$subscriberdata = $this->basic->get_data("messenger_bot_subscriber", array("where" => array("subscribe_id" => $psid, "page_id" => $fb_page_id)));

		$contact_group_id = isset($subscriberdata[0]["contact_group_id"]) ? $subscriberdata[0]["contact_group_id"] : "";
		$explode = explode(',', $contact_group_id);

		foreach (array_keys($explode, $label_auto_id) as $key) {
			unset($explode[$key]);
		}

		$new = array_unique($explode);
		$contact_group_id = implode(',', $new);
		$contact_group_id = trim($contact_group_id, ',');

		$this->basic->update_data("messenger_bot_subscriber", array("subscribe_id" => $psid, "page_id" => $fb_page_id), array("contact_group_id" => $contact_group_id, "permission" => "1"));

		/** Adjust total count of subscriber & unsubscriber  **/

		$sql = "SELECT count(id) as permission_count FROM `messenger_bot_subscriber` WHERE page_table_id='$page_auto_id' AND permission='1'";
		$count_data = $this->db->query($sql)->row_array();

		$sql2 = "SELECT count(id) as permission_count FROM `messenger_bot_subscriber` WHERE page_table_id='$page_auto_id' AND permission='0'";
		$count_data2 = $this->db->query($sql2)->row_array();

		// how many are subscribed and how many are unsubscribed
		$subscribed = isset($count_data["permission_count"]) ? $count_data["permission_count"] : 0;
		$unsubscribed = isset($count_data2["permission_count"]) ? $count_data2["permission_count"] : 0;
		$current_lead_count = $subscribed + $unsubscribed;

		$this->basic->update_data("facebook_rx_fb_page_info", array("id" => $page_auto_id), array("current_subscribed_lead_count" => $subscribed, "current_unsubscribed_lead_count" => $unsubscribed));

	}

	public function assign_label_webhook_call() {

		$psid = $this->input->post('psid');
		$fb_page_id = $this->input->post('fb_page_id');
		$label_auto_ids = $this->input->post('label_auto_ids');
		$label_auto_ids = explode(",", $label_auto_ids);

		$pageinfo = $this->basic->get_data("facebook_rx_fb_page_info", array("where" => array("page_id" => $fb_page_id, "bot_enabled" => "1")));
		$page_auto_id = isset($pageinfo[0]["id"]) ? $pageinfo[0]["id"] : "";
		$page_access_token = isset($pageinfo[0]["page_access_token"]) ? $pageinfo[0]["page_access_token"] : "";

		$label_info = $this->basic->get_data("messenger_bot_broadcast_contact_group", array("where_in" => array("id" => $label_auto_ids)));

		$this->load->library('fb_rx_login');

		foreach ($label_info as $value) {

			$label_auto_id = isset($value['id']) ? $value['id'] : 0;
			$label_id = isset($value['label_id']) ? $value['label_id'] : "";

			$response = $this->fb_rx_login->assign_label($page_access_token, $psid, $label_id);

			$subscriberdata = $this->basic->get_data("messenger_bot_subscriber", array("where" => array("subscribe_id" => $psid, "page_id" => $fb_page_id)));

			$contact_group_id = isset($subscriberdata[0]["contact_group_id"]) ? $subscriberdata[0]["contact_group_id"] : "";
			$explode = explode(',', $contact_group_id);
			array_push($explode, $label_auto_id);
			$new = array_unique($explode);
			$contact_group_id = implode(',', $new);
			$contact_group_id = trim($contact_group_id, ',');

			$this->basic->update_data("messenger_bot_subscriber", array("subscribe_id" => $psid, "page_id" => $fb_page_id), array("contact_group_id" => $contact_group_id));

		}

	}

	// protected function unsubscribe_webhook_call($psid,$fb_page_id)
	// {
	//     $this->basic->update_data("messenger_bot_subscriber",array("subscribe_id"=>$psid,"page_id"=>$fb_page_id),array("permission"=>"0"));
	// }

	// protected function resubscribe_webhook_call($psid,$fb_page_id)
	// {
	//     $this->basic->update_data("messenger_bot_subscriber",array("subscribe_id"=>$psid,"page_id"=>$fb_page_id),array("permission"=>"1"));
	// }

	// protected function assign_label_webhook_call($psid,$fb_page_id,$label_auto_ids)
	// {
	//     $label_auto_ids=explode(",",$label_auto_ids);

	//     $subscriberdata=$this->basic->get_data("messenger_bot_subscriber",array("where"=>array("subscribe_id"=>$psid,"page_id"=>$fb_page_id)));

	//     $contact_group_id=isset($subscriberdata[0]["contact_group_id"])?$subscriberdata[0]["contact_group_id"]:"";
	//     $explode=explode(',', $contact_group_id);

	//     $new_contact_group = array_add($label_auto_ids,$explode);
	//     $new=array_unique($new_contact_group);
	//     $contact_group_id=implode(',', $new);
	//     $contact_group_id=trim($contact_group_id,',');
	//     $this->basic->update_data("messenger_bot_subscriber",array("subscribe_id"=>$psid,"page_id"=>$fb_page_id),array("contact_group_id"=>$contact_group_id));
	// }

	// admin account import
	public function redirect_rx_link() {

		if ($this->session->userdata('logged_in') != 1) {
			exit();
		}

		$id = $this->session->userdata("fb_rx_login_database_id");

		$redirect_url = base_url() . "home/redirect_rx_link/";

		$this->load->library('fb_rx_login');
		$user_info = $this->fb_rx_login->login_callback($redirect_url);

		if (isset($user_info['status']) && $user_info['status'] == '0') {
			$data['error'] = 1;
			$data['message'] = $this->lang->line("Something went wrong") . " : " . $user_info['message'];
			$data['body'] = "facebook_rx/admin_login";
			$this->_viewcontroller($data);
		} else {
			$access_token = $user_info['access_token_set'];
			$where = array('id' => $id);
			$update_data = array('user_access_token' => $access_token);

			if ($this->basic->update_data('facebook_rx_config', $where, $update_data)) {

				$data = array(
					'user_id' => $this->user_id,
					'facebook_rx_config_id' => $id,
					'access_token' => $access_token,
					'name' => $user_info['name'],
					'email' => isset($user_info['email']) ? $user_info['email'] : "",
					'fb_id' => $user_info['id'],
					'add_date' => date('Y-m-d'),
				);

				$where = array();
				$where['where'] = array('user_id' => $this->user_id, 'fb_id' => $user_info['id']);
				$exist_or_not = $this->basic->get_data('facebook_rx_fb_user_info', $where);

				if (empty($exist_or_not)) {
					$this->basic->insert_data('facebook_rx_fb_user_info', $data);
					$facebook_table_id = $this->db->insert_id();
				} else {
					$facebook_table_id = $exist_or_not[0]['id'];
					$where = array('user_id' => $this->user_id, 'fb_id' => $user_info['id']);
					$this->basic->update_data('facebook_rx_fb_user_info', $where, $data);
				}

				$this->session->set_userdata("facebook_rx_fb_user_info", $facebook_table_id);

				$page_list = $this->fb_rx_login->get_page_list($access_token);

				if (isset($page_list['error']) && $page_list['error'] == '1') {
					$data['error'] = 1;
					$data['message'] = $this->lang->line("Something went wrong") . " : " . $page_list['message'];
					$data['body'] = "facebook_rx/admin_login";
					$this->_viewcontroller($data);
					exit();
				}

				if (!empty($page_list)) {
					foreach ($page_list as $page) {
						$user_id = $this->user_id;
						$page_id = $page['id'];
						$page_cover = '';
						if (isset($page['cover']['source'])) {
							$page_cover = $page['cover']['source'];
						}

						$page_profile = '';
						if (isset($page['picture']['url'])) {
							$page_profile = $page['picture']['url'];
						}

						$page_name = '';
						if (isset($page['name'])) {
							$page_name = $page['name'];
						}

						$page_username = '';
						if (isset($page['username'])) {
							$page_username = $page['username'];
						}

						$page_access_token = '';
						if (isset($page['access_token'])) {
							$page_access_token = $page['access_token'];
						}

						$page_email = '';
						if (isset($page['emails'][0])) {
							$page_email = $page['emails'][0];
						}

						$data = array(
							'user_id' => $user_id,
							'facebook_rx_fb_user_info_id' => $facebook_table_id,
							'page_id' => $page_id,
							'page_cover' => $page_cover,
							'page_profile' => $page_profile,
							'page_name' => $page_name,
							'username' => $page_username,
							'page_access_token' => $page_access_token,
							'page_email' => $page_email,
							'add_date' => date('Y-m-d'),
						);

						$where = array();
						$where['where'] = array('facebook_rx_fb_user_info_id' => $facebook_table_id, 'page_id' => $page['id']);
						$exist_or_not = $this->basic->get_data('facebook_rx_fb_page_info', $where);

						if (empty($exist_or_not)) {
							$this->basic->insert_data('facebook_rx_fb_page_info', $data);
						} else {
							$where = array('facebook_rx_fb_user_info_id' => $facebook_table_id, 'page_id' => $page['id']);
							$this->basic->update_data('facebook_rx_fb_page_info', $where, $data);
						}

					}
				}

				$group_list = $this->fb_rx_login->get_group_list($access_token);

				if (!empty($group_list)) {
					foreach ($group_list as $group) {
						$user_id = $this->user_id;
						$group_access_token = $access_token; // group uses user access token
						$group_id = $group['id'];
						$group_cover = '';
						if (isset($group['cover']['source'])) {
							$group_cover = $group['cover']['source'];
						}

						$group_profile = '';
						if (isset($group['picture']['url'])) {
							$group_profile = $group['picture']['url'];
						}

						$group_name = '';
						if (isset($group['name'])) {
							$group_name = $group['name'];
						}

						$data = array(
							'user_id' => $user_id,
							'facebook_rx_fb_user_info_id' => $facebook_table_id,
							'group_id' => $group_id,
							'group_cover' => $group_cover,
							'group_profile' => $group_profile,
							'group_name' => $group_name,
							'group_access_token' => $group_access_token,
							'add_date' => date('Y-m-d'),
						);

						$where = array();
						$where['where'] = array('facebook_rx_fb_user_info_id' => $facebook_table_id, 'group_id' => $group['id']);
						$exist_or_not = $this->basic->get_data('facebook_rx_fb_group_info', $where);

						if (empty($exist_or_not)) {
							$this->basic->insert_data('facebook_rx_fb_group_info', $data);
						} else {
							$where = array('facebook_rx_fb_user_info_id' => $facebook_table_id, 'group_id' => $page['id']);
							$this->basic->update_data('facebook_rx_fb_group_info', $where, $data);
						}
					}
				}
				$this->session->set_flashdata('success_message', 1);
				redirect('social_accounts/index', 'location');
				exit();
			} else {
				$data['error'] = 1;
				$data['message'] = $this->lang->line("Something went wrong, please try again.");
				$data['body'] = "facebook_rx/admin_login";
				$this->_viewcontroller($data);
			}

		}

	}
	//================MESSENGER BOT FUNCTIONS======================
	// ============================================================

	protected function ajax_check() {
		if (!$this->input->is_ajax_request()) {
			exit();
		}

	}

	protected function botinboxer_exist() {
		if ($this->session->userdata('user_type') == 'Admin') {
			return true;
		}

		if ($this->session->userdata('user_type') == 'Member' && in_array(199, $this->module_access)) {
			return true;
		}

		return false;
	}

	protected function broadcaster_exist() {
		if ($this->session->userdata('user_type') == 'Admin' && $this->basic->is_exist("add_ons", array("project_id" => 30))) {
			return true;
		}

		if ($this->session->userdata('user_type') == 'Member' && in_array(211, $this->module_access)) {
			return true;
		}

		return false;
	}

	protected function drip_campaigner_exist() {
		if ($this->session->userdata('user_type') == 'Admin' && $this->basic->is_exist("add_ons", array("project_id" => 30))) {
			return true;
		}

		if ($this->session->userdata('user_type') == 'Member' && in_array(219, $this->module_access)) {
			return true;
		}

		return false;
	}

	protected function messenger_bot_import_export_exist() {
		if ($this->session->userdata('user_type') == 'Admin' && $this->basic->is_exist("add_ons", array("project_id" => 31))) {
			return true;
		}

		if ($this->session->userdata('user_type') == 'Member' && in_array(257, $this->module_access)) {
			return true;
		}

		return false;
	}

	protected function messenger_bot_analytics_exist() {
		if ($this->session->userdata('user_type') == 'Admin') {
			return true;
		}

		if ($this->session->userdata('user_type') == 'Member' && in_array(260, $this->module_access)) {
			return true;
		}

		return false;
	}

	protected function engagement_exist() {
		if ($this->session->userdata('user_type') == 'Admin' && $this->basic->is_exist("add_ons", array("project_id" => 30))) {
			return true;
		}

		if ($this->session->userdata('user_type') == 'Member' && count(array_intersect($this->module_access, array(213, 214, 215, 217))) > 0) {
			return true;
		}

		return false;
	}

	protected function ultrapost_exist() {
		if ($this->session->userdata('user_type') == 'Admin' && $this->db->table_exists('facebook_rx_auto_post')) {
			return true;
		}

		if ($this->session->userdata('user_type') == 'Member' && in_array(223, $this->module_access)) {
			return true;
		}

		return false;
	}

	protected function webview_exist() {
		if ($this->session->userdata('user_type') == 'Admin' && $this->basic->is_exist("add_ons", array("project_id" => 31))) {
			return true;
		}

		if ($this->session->userdata('user_type') == 'Member' && in_array(261, $this->module_access)) {
			return true;
		}

		return false;
	}

	public function thirdparty_webhook_trigger($page_id = "", $subscriber_id = "", $trigger = 'trigger_email', $postback_id = "", $form_canonical_id = "", $form_data = array()) {

		if ($trigger == 'trigger_postback') {
			$trigger = "trigger_postback_" . $postback_id;
		} else if ($trigger == 'trigger_webview') {
			$trigger = "trigger_webview_" . $form_canonical_id;
		}

		$where_simple['messenger_bot_thirdparty_webhook.page_id'] = $page_id;
		$where_simple['messenger_bot_thirdparty_webhook_trigger.trigger_option'] = $trigger;
		$where = array('where' => $where_simple);

		/**Get all connector webhook information**/

		$join = array('messenger_bot_thirdparty_webhook_trigger' => "
			messenger_bot_thirdparty_webhook.id=messenger_bot_thirdparty_webhook_trigger.webhook_id,left", );

		$webhook_connector_info = $this->basic->get_data('messenger_bot_thirdparty_webhook', $where, $select = '', $join, $limit = '', $start = '');

		if (empty($webhook_connector_info)) {
			return false;
		}

		/** Get subscriber information  **/

		$where_simple = array();
		$where_simple['messenger_bot_subscriber.subscribe_id'] = $subscriber_id;
		$where_simple['messenger_bot_subscriber.page_id'] = "$page_id";
		$where = array('where' => $where_simple);

		$subscriber_info = $this->basic->get_data('messenger_bot_subscriber', $where, $select = '', $join = '', $limit = '', $start = '');

		/**Get subscriber Labels name from labels id***/

		$label_ids = $subscriber_info_rearrange['contact_group_id'] = isset($subscriber_info[0]['contact_group_id']) ? $subscriber_info[0]['contact_group_id'] : "";

		$label_ids_array = explode(',', $label_ids);
		$label_ids_array = array_filter($label_ids_array);

		$labels_name = "";

		if (!empty($label_ids_array)) {

			$where = array("where_in" => array("id" => $label_ids_array));

			$label_info = $this->basic->get_data("messenger_bot_broadcast_contact_group", $where);

			foreach ($label_info as $value) {
				$labels_name .= "," . $value['group_name'];
			}
		}

		$labels_name = trim($labels_name, ",");

		foreach ($webhook_connector_info as $webhook_value) {

			$webhook_url = isset($webhook_value['webhook_url']) ? $webhook_value['webhook_url'] : "";
			$webhook_id = isset($webhook_value['webhook_id']) ? $webhook_value['webhook_id'] : "";
			$post_variable = isset($webhook_value['variable_post']) ? $webhook_value['variable_post'] : "";
			$post_variable = explode(',', $post_variable);
			$post_variable = array_filter($post_variable);

			/**Making the variable for post/send ***/

			$post_info = array();

			foreach ($post_variable as $variable_info) {

				if ($variable_info == 'psid') {
					$post_info[$variable_info] = isset($subscriber_info[0]['subscribe_id']) ? $subscriber_info[0]['subscribe_id'] : "";
				} else if ($variable_info == 'labels') {
					$post_info[$variable_info] = $labels_name;
				} else if ($variable_info == 'page_name') {
					$post_info[$variable_info] = isset($webhook_connector_info[0]['page_name']) ? $webhook_connector_info[0]['page_name'] : "";
				} else if ($variable_info == 'postbackid') {
					$post_info[$variable_info] = $postback_id;
				} else if ($variable_info == 'formdata') {
					foreach ($form_data as $key => $value) {
						$post_info[$key] = $value;
					}
				} else {
					$post_info[$variable_info] = isset($subscriber_info[0][$variable_info]) ? $subscriber_info[0][$variable_info] : "";
				}

			}

			/***    Send/Post Information to webhook url ***/

			$post_info = json_encode($post_info);

			$curl_response = $this->curl_send_data($webhook_url, $post_info);

			$curl_http_code = $curl_response['http_code'];
			$curl_error = $curl_response['curl_error'];

			/***Insert into Activity table**/

			$insert_data = array();
			$insert_data['http_code'] = $curl_http_code;
			$insert_data['curl_error'] = $curl_error;
			$insert_data['webhook_id'] = $webhook_id;
			$insert_data['post_time'] = date('Y-m-d H:i:s');
			$insert_data['post_data'] = $post_info;

			$this->basic->insert_data('messenger_bot_thirdparty_webhook_activity', $insert_data);

			/**update messenger_bot_thirdparty_webhook table for last_trigger_time **/
			$update_data_last_trigger['last_trigger_time'] = $insert_data['post_time'];
			$this->basic->update_data("messenger_bot_thirdparty_webhook", array('id' => $webhook_id), $update_data_last_trigger);

			/***Delete last activity except recent 10 ***/

			$lastest_activity = $this->basic->get_data("messenger_bot_thirdparty_webhook_activity", $where = array('id' => $webhook_id), $select = '', $join = '', $limit = '10', $start = 0, $order_by = 'id Desc');

			foreach ($lastest_activity as $last_activity_info) {
				$last_activity_ids[] = $last_activity_info['id'];
			}

			$this->db->where_not_in('id', $last_activity_ids);
			$this->db->delete('messenger_bot_thirdparty_webhook_activity');
		}
	}

	protected function curl_send_data($webhook_url, $post_info) {

		$ch = curl_init();
		$headers = array('Accept: application/json', 'Content-Type: application/json');

		curl_setopt($ch, CURLOPT_URL, $webhook_url);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post_info);
		curl_setopt($ch, CURLOPT_TIMEOUT, 10);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		// curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.3) Gecko/20070309 Firefox/2.0.0.3");
		$st = curl_exec($ch);

		$curl_information = curl_getinfo($ch);
		$curl_error = "";
		if ($curl_information['http_code'] != '200') {
			$curl_error = curl_error($ch);
		}

		$response['http_code'] = $curl_information['http_code'];
		$response['curl_error'] = $curl_error;

		return $response;

	}

	public function assign_drip_messaging_id($drip_type = "default", $check_box_plugin_id = "0", $PAGE_AUTO_ID, $subscriber_id, $drip_campaign_id = "") {
		$date_time = date("Y-m-d H:i:s");

		if ($this->db->table_exists('messenger_bot_drip_campaign')) {

			$engagement_table_id = $check_box_plugin_id;

			if ($drip_campaign_id != "") // Means Campaign id is passed directly, no need to get it from engagement table.
			{
				$where = array("where" => array("id" => $drip_campaign_id, "page_id" => $PAGE_AUTO_ID));
			} else {
				$where = array("where" => array("engagement_table_id" => $engagement_table_id, "drip_type" => $drip_type, "page_id" => $PAGE_AUTO_ID));
			}

			$drip_messaging_campaign_info = $this->basic->get_data("messenger_bot_drip_campaign", $where);

			$drip_campaign_id = isset($drip_messaging_campaign_info[0]['id']) ? $drip_messaging_campaign_info[0]['id'] : "";
			$user_id = isset($drip_messaging_campaign_info[0]['user_id']) ? $drip_messaging_campaign_info[0]['user_id'] : "";

			if ($drip_campaign_id != "") {

				$sql = "INSERT IGNORE INTO messenger_bot_drip_campaign_assign(user_id,page_table_id,subscribe_id,messenger_bot_drip_campaign_id,drip_type,messenger_bot_drip_initial_date)
					VALUES('$user_id','$PAGE_AUTO_ID','$subscriber_id','$drip_campaign_id','$drip_type','$date_time');";

				$this->basic->execute_complex_query($sql);

			}

		}

	}

	// page and account delete section

	public function table_names_array() {
		$tables = array(
			0 => array(
				'table_name' => 'auto_comment_reply_info',
				'column_name' => 'page_info_table_id',
				'module_id' => '',
			),
			2 => array(
				'table_name' => 'autoposting',
				'column_name' => 'page_ids',
				'module_id' => '',
				'comma_separated' => 'yes',
			),
			3 => array(
				'table_name' => 'facebook_ex_autoreply',
				'column_name' => 'page_info_table_id',
				'module_id' => '',
				'has_dependent_table' => 'yes',
				'dependent_tables' => 'facebook_ex_autoreply_report',
				'dependent_table_column' => 'autoreply_table_id',
			),
			4 => array(
				'table_name' => 'facebook_ex_conversation_campaign',
				'column_name' => 'page_id',
				'module_id' => '',
			),
			5 => array(
				'table_name' => 'facebook_ex_conversation_campaign_send',
				'column_name' => 'page_id',
				'module_id' => '',
			),
			6 => array(
				'table_name' => 'facebook_page_insight_page_list',
				'column_name' => 'page_id',
				'module_id' => '',
			),
			7 => array(
				'table_name' => 'facebook_rx_auto_post',
				'column_name' => 'page_group_user_id',
				'module_id' => '',
			),
			8 => array(
				'table_name' => 'facebook_rx_cta_post',
				'column_name' => 'page_group_user_id',
				'module_id' => '',
			),
			10 => array(
				'table_name' => 'facebook_rx_fb_page_info',
				'column_name' => 'id',
				'persistent_getstarted_check' => 'yes',
				'module_id' => '',
			),
			12 => array(
				'table_name' => 'facebook_rx_offer_campaign',
				'column_name' => 'page_group_user_id',
				'module_id' => '',
			),
			13 => array(
				'table_name' => 'facebook_rx_offer_campaign_view',
				'column_name' => 'page_group_user_id',
				'module_id' => '',
			),
			16 => array(
				'table_name' => 'facebook_rx_slider_post',
				'column_name' => 'page_group_user_id',
				'module_id' => '',
			),
			24 => array(
				'table_name' => 'messenger_bot',
				'column_name' => 'page_id',
				'module_id' => '',
			),
			26 => array(
				'table_name' => 'messenger_bot_broadcast_contact_group',
				'column_name' => 'page_id',
				'module_id' => '',
			),
			27 => array(
				'table_name' => 'messenger_bot_broadcast_serial',
				'column_name' => 'page_id',
				'module_id' => '',
			),
			28 => array(
				'table_name' => 'messenger_bot_broadcast_serial_send',
				'column_name' => 'page_id',
				'module_id' => '',
			),
			29 => array(
				'table_name' => 'messenger_bot_domain_whitelist',
				'column_name' => 'page_id',
				'module_id' => '',
			),
			30 => array(
				'table_name' => 'messenger_bot_drip_campaign',
				'column_name' => 'page_id',
				'module_id' => '',
			),
			31 => array(
				'table_name' => 'messenger_bot_drip_report',
				'column_name' => 'page_id',
				'module_id' => '',
			),
			32 => array(
				'table_name' => 'messenger_bot_engagement_2way_chat_plugin',
				'column_name' => 'page_auto_id',
				'module_id' => '',
			),
			33 => array(
				'table_name' => 'messenger_bot_engagement_checkbox',
				'column_name' => 'page_id',
				'module_id' => '',
			),
			34 => array(
				'table_name' => 'messenger_bot_drip_campaign_assign',
				'column_name' => 'page_table_id',
				'module_id' => '',
			),
			36 => array(
				'table_name' => 'messenger_bot_engagement_mme',
				'column_name' => 'page_id',
				'module_id' => '',
			),
			37 => array(
				'table_name' => 'messenger_bot_engagement_send_to_msg',
				'column_name' => 'page_id',
				'module_id' => '',
			),
			38 => array(
				'table_name' => 'messenger_bot_persistent_menu',
				'column_name' => 'page_id',
				'module_id' => '',
			),
			39 => array(
				'table_name' => 'messenger_bot_postback',
				'column_name' => 'page_id',
				'module_id' => '',
			),
			40 => array(
				'table_name' => 'messenger_bot_reply_error_log',
				'column_name' => 'page_id',
				'module_id' => '',
			),
			42 => array(
				'table_name' => 'messenger_bot_subscriber',
				'column_name' => 'page_table_id',
				'module_id' => '',
			),
			43 => array(
				'table_name' => 'messenger_bot_thirdparty_webhook',
				'column_name' => 'page_id',
				'is_facebook_page_id' => 'yes',
				'has_dependent_table' => 'yes',
				'dependent_tables' => 'messenger_bot_thirdparty_webhook_activity,messenger_bot_thirdparty_webhook_trigger',
				'dependent_table_column' => 'webhook_id,webhook_id',
				'module_id' => '',
			),
			45 => array(
				'table_name' => 'messenger_bot_user_custom_form_webview_data',
				'column_name' => 'page_id',
				'module_id' => '',
			),
			48 => array(
				'table_name' => 'page_response_auto_like_share',
				'column_name' => 'page_info_table_id',
				'module_id' => '',
			),
			49 => array(
				'table_name' => 'page_response_auto_like_share_report',
				'column_name' => 'page_info_table_id',
				'module_id' => '',
				'has_dependent_table' => 'yes',
				'dependent_tables' => 'page_response_auto_like_report,page_response_auto_share_report',
				'dependent_table_column' => 'page_response_auto_like_share_report_id,page_response_auto_like_share_report_id',
			),
			51 => array(
				'table_name' => 'page_response_autoreply',
				'column_name' => 'page_info_table_id',
				'module_id' => '',
			),
			52 => array(
				'table_name' => 'page_response_report',
				'column_name' => 'page_info_table_id',
				'module_id' => '',
				'has_dependent_table' => 'yes',
				'dependent_tables' => 'facebook_ex_autoreply_report',
				'dependent_table_column' => 'autoreply_table_id',
			),
			54 => array(
				'table_name' => 'tag_machine_bulk_reply',
				'column_name' => 'page_info_table_id',
				'has_dependent_table' => 'yes',
				'dependent_tables' => 'tag_machine_bulk_reply_send',
				'dependent_table_column' => 'campaign_id',
				'module_id' => '',
			),
			56 => array(
				'table_name' => 'tag_machine_bulk_tag',
				'column_name' => 'page_info_table_id',
				'module_id' => '',
			),
			57 => array(
				'table_name' => 'tag_machine_comment_info',
				'column_name' => 'page_info_table_id',
				'module_id' => '',
			),
			58 => array(
				'table_name' => 'tag_machine_commenter_info',
				'column_name' => 'page_info_table_id',
				'module_id' => '',
			),
			59 => array(
				'table_name' => 'tag_machine_enabled_post_list',
				'column_name' => 'page_info_table_id',
				'module_id' => '',
			),
			63 => array(
				'table_name' => 'webview_builder',
				'column_name' => 'page_id',
				'module_id' => '',
			),
		);
		return $tables;
	}

	public function table_names_array_foraccount() {
		$tables = array(
			1 => array(
				'table_name' => 'facebook_rx_fb_group_info',
				'column_name' => 'facebook_rx_fb_user_info_id',
				'module_id' => '',
			),
			2 => array(
				'table_name' => 'facebook_rx_fb_user_info',
				'column_name' => 'id',
				'module_id' => '',
			),
		);
		return $tables;
	}

	public function table_names_array_foruser() {
		$tables = array(
			1 => array(
				'table_name' => 'auto_comment_reply_tb',
				'column_name' => 'user_id',
				'module_id' => '',
			),
			2 => array(
				'table_name' => 'fb_simple_support_desk',
				'column_name' => 'user_id',
				'module_id' => '',
			),
			3 => array(
				'table_name' => 'fb_support_category',
				'column_name' => 'user_id',
				'module_id' => '',
			),
			4 => array(
				'table_name' => 'fb_support_desk_reply',
				'column_name' => 'user_id',
				'module_id' => '',
			),
			6 => array(
				'table_name' => 'messenger_bot_saved_templates',
				'column_name' => 'user_id',
				'module_id' => '',
			),
			7 => array(
				'table_name' => 'ultrapost_auto_reply',
				'column_name' => 'user_id',
				'module_id' => '',
			),
			8 => array(
				'table_name' => 'users',
				'column_name' => 'id',
				'module_id' => '',
			),
		);
		return $tables;
	}

	public function delete_data_basedon_page($table_id = 0, $admin_access = 0) {
		if ($table_id == 0) {
			return json_encode(array('success' => 0, 'message' => $this->lang->line("Page is not found for this user. Something is wrong.")));
			exit();
		}

		if ($admin_access == '1' && $this->session->userdata('user_type') == 'Admin') {
			$page_information = $this->basic->get_data('facebook_rx_fb_page_info', array('where' => array('id' => $table_id)));
		} else {
			$page_information = $this->basic->get_data('facebook_rx_fb_page_info', array('where' => array('id' => $table_id, 'user_id' => $this->user_id)));
		}

		if (empty($page_information)) {
			return json_encode(array('success' => 0, 'message' => $this->lang->line("Page is not found for this user. Something is wrong.")));
			exit();
		}

		$this->db->trans_start();
		$this->load->library("fb_rx_login");
		$table_names = $this->table_names_array();
		foreach ($table_names as $value) {
			if (isset($value['persistent_getstarted_check']) && $value['persistent_getstarted_check'] == 'yes') {
				$fb_page_id = isset($page_information[0]["page_id"]) ? $page_information[0]["page_id"] : "";
				$page_access_token = isset($page_information[0]["page_access_token"]) ? $page_information[0]["page_access_token"] : "";
				$persistent_enabled = isset($page_information[0]["persistent_enabled"]) ? $page_information[0]["persistent_enabled"] : "0";
				$bot_enabled = isset($page_information[0]["bot_enabled"]) ? $page_information[0]["bot_enabled"] : "0";
				$started_button_enabled = isset($page_information[0]["started_button_enabled"]) ? $page_information[0]["started_button_enabled"] : "0";
				$fb_user_id = $page_information[0]["facebook_rx_fb_user_info_id"];
				$fb_user_info = $this->basic->get_data('facebook_rx_fb_user_info', array('where' => array('id' => $fb_user_id)));
				$this->fb_rx_login->app_initialize($fb_user_info[0]['facebook_rx_config_id']);

				if ($persistent_enabled == '1') {
					$this->fb_rx_login->delete_persistent_menu($page_access_token); // delete persistent menu
					if ($admin_access != '1') {
						$this->_delete_usage_log($module_id = 197, $request = 1);
					}

				}
				if ($started_button_enabled == '1') {
					$this->fb_rx_login->delete_get_started_button($page_access_token);
				}
				// delete get started button
				if ($bot_enabled == '1') {
					$this->fb_rx_login->disable_bot($fb_page_id, $page_access_token);
					if ($admin_access != '1') {
						$this->_delete_usage_log($module_id = 200, $request = 1);
					}

				}

				if ($this->db->table_exists($value['table_name'])) {
					$this->basic->delete_data($value['table_name'], array("{$value['column_name']}" => $table_id));
				}

			} else if (isset($value['has_dependent_table']) && $value['has_dependent_table'] == 'yes') {
				if (isset($value['is_facebook_page_id']) && $value['is_facebook_page_id'] == 'yes') {
					$table_id = $page_information[0]['page_id'];
				}

				$table_ids_array = array();
				if ($this->db->table_exists($value['table_name'])) {
					$table_ids_info = $this->basic->get_data($value['table_name'], array('where' => array("{$value['column_name']}" => $table_id)), 'id');
				} else {
					continue;
				}

				foreach ($table_ids_info as $info) {
					array_push($table_ids_array, $info['id']);
				}

				if ($this->db->table_exists($value['table_name'])) {
					$this->basic->delete_data($value['table_name'], array("{$value['column_name']}" => $table_id));
				}

				$dependent_table_names = explode(',', $value['dependent_tables']);
				$dependent_table_column = explode(',', $value['dependent_table_column']);
				if (!empty($table_ids_array) && !empty($dependent_table_names)) {
					for ($i = 0; $i < count($dependent_table_names); $i++) {
						$this->db->where_in($dependent_table_column[$i], $table_ids_array);
						if ($this->db->table_exists($dependent_table_names[$i])) {
							$this->db->delete($dependent_table_names[$i]);
						}

					}
				}
			} else if (isset($value['comma_separated']) && $value['comma_separated'] == 'yes') {
				$str = "FIND_IN_SET('" . $table_id . "', " . $value['column_name'] . ") !=";
				$where = array($str => 0);
				if ($this->db->table_exists($value['table_name'])) {
					$this->basic->delete_data($value['table_name'], $where);
				}

			} else {
				if ($this->db->table_exists($value['table_name'])) {
					$this->basic->delete_data($value['table_name'], array("{$value['column_name']}" => $table_id));
				}

			}
		}
		$this->db->trans_complete();

		if ($this->db->trans_status() === FALSE) {
			$response['status'] = 0;
			$response['message'] = $this->lang->line('Something went wrong, please try again.');
		} else {
			$response['status'] = 1;
			$response['message'] = $this->lang->line("Your page and all of it's corresponding campaigns have been deleted successfully.");
		}

		return json_encode($response);

	}

	public function delete_data_basedon_account($fb_user_id = 0, $app_delete = 0) {
		$this->db->trans_start();
		$table_names = $this->table_names_array_foraccount();
		foreach ($table_names as $value) {
			if ($this->db->table_exists($value['table_name'])) {
				$this->basic->delete_data($value['table_name'], array("{$value['column_name']}" => $fb_user_id));
			}

		}
		$this->db->trans_complete();

		if ($this->db->trans_status() === FALSE) {
			$response['status'] = 0;
			$response['message'] = $this->lang->line('Something went wrong, please try again.');
		} else {
			if ($app_delete != '1') {
				// delete data to useges log table
				$this->_delete_usage_log($module_id = 65, $request = 1);
				$this->session->sess_destroy();
			}
			$response['status'] = 1;
			$response['message'] = $this->lang->line("Your account and all of it's corresponding pages, groups and campaigns have been deleted successfully. Now you'll be redirected to the login page.");
		}
		return $response;
	}

	public function user_delete_action($user_id = 0) {
		$this->ajax_check();
		if ($user_id == 0) {
			exit;
		}

		if ($this->session->userdata('user_type') != 'Admin') {
			if ($user_id != $this->user_id) {
				exit;
			}
		}

		$fb_user_infos = $this->basic->get_data('facebook_rx_fb_user_info', array('where' => array('user_id' => $user_id)), array('id'));

		foreach ($fb_user_infos as $value) {
			$fb_page_infos = $this->basic->get_data('facebook_rx_fb_page_info', array('where' => array('facebook_rx_fb_user_info_id' => $value['id'])), array('id'));
			foreach ($fb_page_infos as $value2) {
				$response = $this->delete_data_basedon_page($value2['id'], '1');
			}

			$response = $this->delete_data_basedon_account($value['id'], '1');
		}

		$this->db->trans_start();
		$table_names = $this->table_names_array_foruser();
		foreach ($table_names as $value) {
			if ($this->db->table_exists($value['table_name'])) {
				$this->basic->delete_data($value['table_name'], array("{$value['column_name']}" => $user_id));
			}

		}
		$this->db->trans_complete();

		if ($this->db->trans_status() === FALSE) {
			$response['status'] = 0;
			$response['message'] = $this->lang->line('Something went wrong, please try again.');
		} else {
			if ($this->session->userdata('user_type') != 'Admin') {
				$this->session->sess_destroy();
			}

			$response['status'] = 1;
			$response['message'] = $this->lang->line("Account and all of it's corresponding pages, groups and campaigns have been deleted successfully.");
		}
		echo json_encode($response);
	}

}
