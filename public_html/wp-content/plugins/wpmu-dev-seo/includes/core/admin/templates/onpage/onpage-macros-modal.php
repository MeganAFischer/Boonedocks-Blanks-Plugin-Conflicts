<?php
/**
 * Template: Onpage Macros Modal.
 *
 * @package Smartcrwal
 */

namespace SmartCrawl;

use SmartCrawl\Admin\Settings\Onpage;

$macros = array(
	'General'                   => Onpage::get_general_macros(),
	'Posts'                     => Onpage::get_singular_macros(),
	'Taxonomy Archives'         => Onpage::get_term_macros(),
	'Author Archives'           => Onpage::get_author_macros(),
	'Date Archives'             => Onpage::get_date_macros(),
	'Custom Post Type Archives' => Onpage::get_pt_archive_macros(),
	'Search'                    => Onpage::get_search_macros(),
	'BuddyPress Profiles'       => Onpage::get_bp_profile_macros(),
	'BuddyPress Groups'         => Onpage::get_bp_group_macros(),
); ?>

<div class="wds-conditional">
	<p>
		<select title="">
			<?php foreach ( $macros as $macro_type => $type_macros ) : ?>
				<option value="<?php echo esc_attr( $macro_type ); ?>">
					<?php echo esc_html( $macro_type ); ?>
				</option>
			<?php endforeach; ?>
		</select>
	</p>

	<?php foreach ( $macros as $macro_type => $type_macros ) : ?>
		<div class="wds-conditional-inside" data-conditional-val="<?php echo esc_attr( $macro_type ); ?>">
			<div id="wds-show-supported-macros">
				<table class="sui-table">
					<thead>
					<tr>
						<th><?php esc_html_e( 'Macro', 'wds' ); ?></th>
						<th><?php esc_html_e( 'Gets Replaced By', 'wds' ); ?></th>
					</tr>
					</thead>
					<tfoot>
					<tr>
						<th><?php esc_html_e( 'Title', 'wds' ); ?></th>
						<th><?php esc_html_e( 'Gets Replaced By', 'wds' ); ?></th>
					</tr>
					</tfoot>
					<tbody>

					<?php foreach ( $type_macros as $macro => $label ) { ?>
						<tr>
							<td><?php echo esc_html( $macro ); ?></td>
							<td><?php echo esc_html( $label ); ?></td>
						</tr>
					<?php } ?>

					</tbody>
				</table>
			</div>
		</div>
	<?php endforeach; ?>
</div>