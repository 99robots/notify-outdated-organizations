<h1><?php _e('Notify Outdated Organizations', self::$text_domain); ?></h1>

<!-- Notify Button -->

<p>
	<a href="<?php echo "tools.php?page=" . self::$tools_page . '&notify=true'; ?>"><input type="button" class="button" id="<?php self::$prefix; ?>notify" name="<?php self::$prefix; ?>notify" value="<?php _e('Notify all outdated Organizations', self::$text_domain); ?>" /></a><br/>
	<em><?php echo sprintf(__('Last Notifed on %s'), $updated_last); ?></em>
</p>

<!-- Category Filter -->

<!--
<p>
	<select id="<?php echo self::$prefix; ?>category" name="<?php echo self::$prefix; ?>category">
		<?php
		$categories = get_terms('org_category');

		foreach ($categories as $category) {?>
			<option value="<?php echo $category->name; ?>"><?php echo $category->name; ?></option>
		<?php } ?>
	</select>
</p>
-->

<div class="wrap">

	<h3><?php _e('Outdated Organizations', self::$text_domain); ?></h3>

	<?php
	$organizations = get_posts(array(
		'post_type' 		=> 'organization',
		'posts_per_page' 	=> -1,
		'post_status' 		=> 'publish',
		'date_query' => array(
			array(
				'column' 	=> 'post_modified_gmt', //post_modified_gmt
				'before'  	=> '1 year ago',
			),
		),
	));
	?>
	<div class="tablenav top">
		<div class="tablenav-pages one-page">
			<span class="displaying-num"><?php echo sprintf(__('%s Items'), count($organizations)); ?></span>
		</div>
	</div>

	<table class="wp-list-table widefat fixed posts">
		<thead>
			<th><?php _e('ID', self::$text_domain); ?></th>
			<th><?php _e('Title', self::$text_domain); ?></th>
			<th><?php _e('Email', self::$text_domain); ?></th>
			<th><?php _e('Category', self::$text_domain); ?></th>
			<th><?php _e('Last Updated', self::$text_domain); ?></th>
		</thead>
		<tfoot>
			<th><?php _e('ID', self::$text_domain); ?></th>
			<th><?php _e('Title', self::$text_domain); ?></th>
			<th><?php _e('Email', self::$text_domain); ?></th>
			<th><?php _e('Category', self::$text_domain); ?></th>
			<th><?php _e('Last Updated', self::$text_domain); ?></th>
		</tfoot>

		<tbody id="the-list">
		<?php foreach ($organizations as $organization) { ?>
			<tr>

				<td>
					<span><?php _e($organization->ID, self::$text_domain); ?></span>
				</td>

				<td>
					<a href="<?php echo get_permalink($organization->ID); ?>"><?php _e($organization->post_title, self::$text_domain); ?></a>
					<div class="row-actions">
						<span class="edit"><a href="post.php?post=<?php _e($organization->ID, self::$text_domain); ?>&action=edit"><?php _e("Edit", self::$text_domain); ?></a></span> |
						<span class="view"><a href="<?php echo get_permalink($organization->ID); ?>"><?php _e("View", self::$text_domain); ?></a></span>
					</div>
				</td>

				<td>
					<span><?php

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

						_e($email, self::$text_domain);
					 ?></span>
				</td>

				<td>
					<?php
					$categories = get_the_terms($organization->ID, 'org_category');

					if (isset($categories) && is_array($categories)) {
						foreach ($categories as $category) { ?>
							<div><?php _e($category->name, self::$text_domain); ?></div>
					<?php }
					} ?>
				</td>

				<td>
					<abbr><?php _e(date_format(date_create($organization->post_modified_gmt), "Y/m/d"), self::$text_domain); ?></abbr><br/>
					<span><?php _e("Modified", self::$text_domain); ?></span>
				</td>

			</tr>
		<?php } ?>
		</tbody>
	</table>

	<div class="tablenav bottom">
		<div class="tablenav-pages one-page">
			<span class="displaying-num"><?php echo sprintf(__('%s Items'), count($organizations)); ?></span>
		</div>
	</div>
</div>