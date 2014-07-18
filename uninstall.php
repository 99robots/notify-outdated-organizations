<?php

	/* if uninstall not called from WordPress exit */

	if ( !defined( 'WP_UNINSTALL_PLUGIN' ) )
		exit ();

	/* Delete all existence of this plugin */

	$version = 'notify_outdated_organizations_version';

	if ( !is_multisite() ) {

		/* Delete blog option */

		delete_option($version);
	}

	else {

		/* Delete site option */

		delete_site_option($version);
	}
?>