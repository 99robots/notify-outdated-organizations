<?php
/*
Plugin Name: Notify Outdated Organizations
plugin URI:
Description: Notifies the outdated organizations (over 1 year of no updates).
version: 1.0
Author: Stratiq
Author URI: http://stratiq.com
License: GPL2
*/

/**
 * Global Definitions
 */

/* Plugin Name */

if (!defined('NOTIFY_OUTDATED_ORGANIZATIONS_PLUGIN_NAME'))
    define('NOTIFY_OUTDATED_ORGANIZATIONS_PLUGIN_NAME', trim(dirname(plugin_basename(__FILE__)), '/'));

/* Plugin directory */

if (!defined('NOTIFY_OUTDATED_ORGANIZATIONS_PLUGIN_DIR'))
    define('NOTIFY_OUTDATED_ORGANIZATIONS_PLUGIN_DIR', WP_PLUGIN_DIR . '/' . NOTIFY_OUTDATED_ORGANIZATIONS_PLUGIN_NAME);

/* Plugin url */

if (!defined('NOTIFY_OUTDATED_ORGANIZATIONS_PLUGIN_URL'))
    define('NOTIFY_OUTDATED_ORGANIZATIONS_PLUGIN_URL', WP_PLUGIN_URL . '/' . NOTIFY_OUTDATED_ORGANIZATIONS_PLUGIN_NAME);

/* Plugin verison */

if (!defined('NOTIFY_OUTDATED_ORGANIZATIONS_VERSION_NUM'))
    define('NOTIFY_OUTDATED_ORGANIZATIONS_VERSION_NUM', '1.0.0');


/**
 * Activatation / Deactivation
 */

register_activation_hook( __FILE__, array('NotifyOutdatedOrganizations', 'register_activation'));

/**
 * Hooks / Filter
 */

add_action('init', array('NotifyOutdatedOrganizations', 'load_textdomain'));
add_action('admin_menu', array('NotifyOutdatedOrganizations', 'menu_page'));

$plugin = plugin_basename(__FILE__);
add_filter("plugin_action_links_$plugin", array('NotifyOutdatedOrganizations', 'plugin_links'));

/**
 *  NotifyOutdatedOrganizations main class
 *
 * @since 1.0.0
 * @using Wordpress 3.8
 */

class NotifyOutdatedOrganizations {

	/* Properties */

	private static $text_domain = 'notify-outdated-organizations';

	private static $prefix = 'notify_outdated_organizations_';

	private static $tools_page = 'notify-outdated-organizations-admin-tools';

	private static $default = array(
		'updated_last'	=> '',
	);

	/**
	 * Load the text domain
	 *
	 * @since 1.0.0
	 */
	static function load_textdomain() {
		load_plugin_textdomain(self::$text_domain, false, NOTIFY_OUTDATED_ORGANIZATIONS_PLUGIN_DIR . '/languages');
	}

	/**
	 * Hooks to 'register_activation_hook'
	 *
	 * @since 1.0.0
	 */
	static function register_activation() {

		/* Check if multisite, if so then save as site option */

		if (is_multisite()) {
			add_site_option(self::$prefix . 'version', NOTIFY_OUTDATED_ORGANIZATIONS_VERSION_NUM);
		} else {
			add_option(self::$prefix . 'version', NOTIFY_OUTDATED_ORGANIZATIONS_VERSION_NUM);
		}
	}

	/**
	 * Hooks to 'plugin_action_links_' filter
	 *
	 * @since 1.0.0
	 */
	static function plugin_links($links) {
		$tools_link = '<a href="tools.php?page=' . self::$tools_page . '">Tools</a>';
		array_unshift($links, $tools_link);
		return $links;
	}

	/**
	 * Hooks to 'admin_menu'
	 *
	 * @since 1.0.0
	 */
	static function menu_page() {

	    /* Cast the first sub menu to the top menu */

	    $tools_page_load = add_submenu_page(
	    	'tools.php', 										// parent slug
	    	__('Notify Outdated Organizations', self::$text_domain), 						// Page title
	    	__('Notify Outdated Organizations', self::$text_domain), 						// Menu name
	    	'manage_options', 											// Capabilities
	    	self::$tools_page, 										// slug
	    	array('NotifyOutdatedOrganizations', 'tools_page')	// Callback function
	    );
	    add_action("admin_print_scripts-$tools_page_load", array('NotifyOutdatedOrganizations', 'include_admin_scripts'));
	}

	/**
	 * Hooks to 'admin_print_scripts-$page'
	 *
	 * @since 1.0.0
	 */
	static function include_admin_scripts() {

		/* CSS */

		wp_register_style(self::$prefix . 'tools_css', NOTIFY_OUTDATED_ORGANIZATIONS_PLUGIN_URL . '/css/tools.css');
		wp_enqueue_style(self::$prefix . 'tools_css');
	}

	/**
	 * Displays the HTML for the 'notify-outdated-organizations-admin-menu-settings' admin page
	 *
	 * @since 1.0.0
	 */
	static function tools_page() {

		$settings = get_option(self::$prefix . 'settings');

		/* Default values */

		if ($settings === false) {
			$settings = self::$default;
		}

		$updated_last = isset($settings['updated_last']) ? $settings['updated_last'] : '';

		if (isset($_GET['notify']) && $_GET['notify'] == 'true') {

			$settings['updated_last'] = date("Y/m/d h:m:s T");

			update_option(self::$prefix . 'settings', $settings);

			self::email_organizations();

			?>
			<script type="text/javascript">
				window.location = "<?php echo $_SERVER['PHP_SELF']?>?page=<?php echo self::$tools_page; ?>";
			</script>
			<?php
		}

		require('admin/tools.php');
	}

	/**
	 * Emails all organizations that have no updated their info in over a year
	 *
	 * @access public
	 * @static
	 * @return void
	 */
	static function email_organizations() {
		$organizations = get_posts(array(
			'post_type' 		=> 'organization',
			'posts_per_page' 	=> -1,
			'post_status' 		=> 'publish',
			'date_query' => array(
				array(
					'column' 	=> 'post_modified_gmt',
					'before'  	=> '1 year ago',
				),
			),
		));

		foreach ($organizations as $organization) {

			$email = '';

			// Loop through all contact emails

			$count = 0;

			$contact_email = get_post_meta($organization->ID, 'contacts_multi_' . $count . '_contact-email', true);

			while ($count < 15) {

				if ($contact_email != '') {
					$email = $contact_email;
					break;
				}

				$count++;

				$contact_email = get_post_meta($organization->ID, 'contacts_multi_' . $count . '_contact-email', true);
			}

			if ($email == '') {
				$email = get_post_meta($organization->ID, 'email', true);
			}

			$url = get_permalink($organization->ID);

			$submit_form = get_site_url() . '/submitedit-an-organization/';

			$subject = "Annual Update is now due for $organization->post_title";

			$message = "Community Answers is a free, non-profit, information and referral service located in the Greenwich Library. For almost 50 years, Community Answers has been the go-to source for everything Greenwich.  Our dynamic website gives you direct access to Greenwich Residents." . "\r\n\r\n";

			$message .= "It is time to check and update your Organization’s complimentary listing at communityanswers.org." . "\r\n\r\n";

			$message .= " - View your record - $url" . " " . "\r\n";

			$message .= " - To make changes please go our online submission form  - $submit_form " . " ";

			$message .= "\r\n\r\n" . "Community Answers strives to keep our information relevant and accurate, and we thank you for your assistance. If you have any questions, please contact us at cainformation@greenwichlibrary.org or call 203-622-7981.";

	    	$headers[] = 'From: Information Manager <cainformation@greenwichlibrary.org>';
			$headers[] = 'Cc: Janet Santen <cainformation@greenwichlibrary.org>';

			if (isset($email) && $email != '') {
				$result = wp_mail($email, $subject, $message, $headers);
				//error_log($result);
			}
		}
	}
}
?>