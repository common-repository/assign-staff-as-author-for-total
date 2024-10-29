<?php
/**
 * Plugin Name:       Assign Staff as Author for Total
 * Plugin URI:        https://wordpress.org/plugins/assign-staff-as-author-for-total/
 * Description:       Assign staff members as the "author" for any page or post to be displayed in the post meta or author bio.
 * Version:           2.0
 * Requires at least: 6.3
 * Requires PHP:      7.0
 * Author:            WPExplorer
 * Author URI:        https://www.wpexplorer.com/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       assign-staff-as-author-for-total
 * Domain Path:       /languages/
 */

/*
Assign Staff as Author for Total is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 2 of the License, or
any later version.

Assign Staff as Author for Total is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with Assign Staff as Author for Total. If not, see https://www.gnu.org/licenses/gpl-2.0.html.
*/

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main Assign_Staff_Author_Total Class.
 */
if ( ! class_exists( 'Assign_Staff_Author_Total' ) ) {

	final class Assign_Staff_Author_Total {

		/**
		 * Assign_Staff_Author_Total constructor.
		 */
		public function __construct() {
			if ( is_admin() ) {
				add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), [ $this, 'on_plugin_action_links' ] );
				add_action( 'admin_init', [ $this, 'admin_init' ] );
				add_action( 'admin_menu', [ $this, 'on_admin_menu' ] );
				add_action( 'admin_enqueue_scripts', [ $this, 'quick_bulk_edit_scripts' ] );
				add_action( 'bulk_edit_custom_box', [ $this, 'quick_bulk_edit' ], 10, 2 );
				add_action( 'quick_edit_custom_box', [ $this, 'quick_bulk_edit' ], 10, 2 );
			} elseif ( ! is_admin() || wp_doing_ajax() ) {
				add_filter( 'pre_get_avatar', [ $this, 'filter_pre_get_avatar' ], 10, 3 );
				add_filter( 'the_author', [ $this, 'filter_the_author' ] );
				add_filter( 'author_link', [ $this, 'filter_author_link' ] );
				add_filter( 'get_the_author_description', [ $this, 'filter_get_the_author_description' ] );
				add_filter( 'wpex_get_user_social_links', [ $this, 'filter_wpex_get_user_social_links' ] );
			}
		}

		/**
		 * Adds a link to the settings page from the plugins dashboard.
		 */
		public function on_plugin_action_links( $links ) {
			if ( is_array( $links ) ) {
				$new_links = [
					'<a href="' . esc_url( admin_url( 'options-general.php?page=assign-staff-author-total' ) ) . '">' . esc_html__( 'Settings', 'assign-staff-as-author-for-total' ) . '</a>',
				];
				$links = array_merge( $new_links, $links );
			} 
			return $links;
		}

		/**
		 * Runs on the admin_init hook.
		 */
		public function admin_init(): void {
			$this->register_settings();
			$this->add_save_metabox();
			foreach ( $this->get_post_types() as $post_type ) {
				add_filter( "manage_{$post_type}_posts_columns", [ $this, 'add_admin_columns' ] );
				add_action( "manage_{$post_type}_posts_custom_column", [ $this, 'display_admin_columns' ], 10, 2 );
			}
		}

		/**
		 * Register plugin settings.
		 */
		public function register_settings(): void {
			register_setting(
				'assign_staff_author_total_settings',
				'assign_staff_author_total',
				[
					'type' => 'array',
					'sanitize_callback' => [ $this, 'save_settings' ],
				]
			);
			add_settings_section(
				'assign_staff_author_total_settings',
				false,
				false,
				'assign_staff_author_total'
			);
			add_settings_field(
				'assign_staff_author_total_post_types',
				esc_html__( 'Post Types', 'assign-staff-as-author-for-total' ),
				[ $this, 'render_setting_post_types' ],
				'assign_staff_author_total',
				'assign_staff_author_total_settings'
			);
			add_settings_field(
				'assign_staff_author_total_bio_length',
				esc_html__( 'Author Bio Description Length', 'assign-staff-as-author-for-total' ),
				[ $this, 'render_setting_bio_length' ],
				'assign_staff_author_total',
				'assign_staff_author_total_settings',
				[
					'label_for' => 'assign_staff_author_total-bio_length',
				]
			);
		}

		/**
		 * Runs on the admin_menu hook.
		 */
		public function on_admin_menu(): void {
			add_options_page(
				esc_html__( 'Assign Staff as Author for Total', 'assign-staff-as-author-for-total' ),
				esc_html__( 'Staff as Authors', 'assign-staff-as-author-for-total' ),
				'manage_options',
				'assign-staff-author-total',
				[ $this, 'render_settings_page' ]
			);
		}

		/**
		 * Sanitize settings when saved.
		 */
		public static function save_settings( $value ): array {
			$value = (array) $value;
			$new_value = [];
			if ( isset( $value['post_types'] ) && is_array( $value['post_types'] ) ) {
				$new_value['post_types'] = array_map( 'sanitize_text_field', $value['post_types'] );
			} else {
				$new_value['post_types'] = [ 'post' ];
			}
			if ( ! empty( $value['bio_length'] ) && is_numeric( $value['bio_length'] ) ) {
				$new_value['bio_length'] = (int) sanitize_text_field( $value['bio_length'] );
			}
			return $new_value;
		}

		/**
		 * Renders the admin settings page.
		 */
		public function render_settings_page(): void {
			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}

			// Fixes issues with https://core.trac.wordpress.org/ticket/21989
			if ( ! is_array( get_option( 'assign_staff_author_total' ) ) ) {
				add_option( 'assign_staff_author_total', [], '', false );
			}
			?>
			<div class="wrap">
				<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
				<form action="options.php" method="post"><?php
					settings_fields( 'assign_staff_author_total_settings' );
					do_settings_sections( 'assign_staff_author_total' );
					submit_button();
				?></form>
			</div>
			<?php
		}

		/**
		 * Renders the post types select setting.
		 */
		public function render_setting_post_types(): void {
			$value = $this->get_setting( 'post_types' );
			if ( ! is_array( $value ) ) {
				$value = $this->get_public_post_types();
			}
			?>
			<fieldset>
				<legend class="screen-reader-text"><?php esc_html_e( 'Select the post types that you want the staff author selector to be available for', 'assign-staff-as-author-for-total' ); ?></legend>
				<?php foreach ( $this->get_public_post_types() as $post_type ) { ?>
					<div>
					<input id="assign_staff_author_total-post-types-<?php echo sanitize_html_class( $post_type ); ?>" type="checkbox" name="assign_staff_author_total[post_types][]" value="<?php echo sanitize_key( $post_type ); ?>" <?php checked( in_array( $post_type, $value, true ) ); ?>>
					<label for="assign_staff_author_total-post-types-<?php echo sanitize_html_class( $post_type ); ?>"><?php
						echo esc_html( get_post_type_object( $post_type )->labels->name ?? $post_type );
					?></label>
					</div>
				<?php } ?>
				<p class="description"><?php esc_html_e( 'This setting controls which post types will display the option to assign your staff members to the post. If you have assigned staff members for a specific post type and then disable the post type here, those assignments will still be in place. The only way to unassign staff members is via post edit, quick edit or bulk edit.', 'assign-staff-as-author-for-total' ); ?></p>
			</fieldset>
			<?php
		}

		/**
		 * Render the bio length setting field.
		 */
		public function render_setting_bio_length(): void {
			$value = $this->get_setting( 'bio_length' );
			?>
			<input type="text" id="<?php echo esc_attr( $args['label_for'] ); ?>" name="assign_staff_author_total[bio_length]" value="<?php echo esc_attr( $value ); ?>" placeholder="40">
			<p class="description"><?php esc_html_e( 'When displaying the author bio, if the staff member doesn\'t have a custom excerpt the bio description will be automatically created from the post content. Enter how many words to trim the post content by. Enter -1 to display the full post content.', 'assign-staff-as-author-for-total' ); ?></p>
			<?php
		}

		/**
		 * Define new admin dashboard columns.
		 */
		public function add_admin_columns( $columns ): array {
			$staff_pt = get_post_type_object( 'staff' );
			$staff_label = $staff_pt->labels->name ?? esc_html__( 'Staff', 'assign-staff-as-author-for-total' );
			$columns['wpex_author_staff_id'] = sprintf( esc_html__( '%s Author', 'assign-staff-as-author-for-total' ), $staff_label );
			return $columns;
		}

		/**
		 * Display new admin dashboard columns.
		 */
		public function display_admin_columns( $column, $post_id ): void {
			switch ( $column ) {
				case 'wpex_author_staff_id':
					$author_id = absint( get_post_meta( $post_id, 'wpex_author_staff_id', true ) );
					if ( $author_id && (bool) get_post_status( $author_id ) ) {
						echo esc_html( get_the_title( $author_id ) );
					} else {
						echo '&mdash;';
					}
					echo '<input type="hidden" value="' . \esc_attr( $author_id ) . '" disabled>';
				break;
			}
		}

		/**
		 * Quick/Bulk edit scripts.
		 */
		public function quick_bulk_edit_scripts( $hook ): void {
			if ( 'edit.php' !== $hook ) {
				return;
			}

			$post_type = $_GET['post_type'] ?? 'post';

			if ( ! in_array( $post_type, $this->get_post_types(), true ) ) {
				return;
			}

			wp_enqueue_script(
				'assign-staff-as-author-for-total-quick-edit',
				trailingslashit( plugin_dir_url( __FILE__ ) ) . 'assets/js/quick-edit.js',
				[ 'jquery', 'inline-edit-post' ],
				'1.0',
				[
					'strategy' => 'defer',
					'in_footer' => true,
				]
			);
		}

		/**
		 * Custom quick/bulk edit fields.
		 */
		public function quick_bulk_edit( $column_name, $post_type ): void {
			if ( 'wpex_author_staff_id' !== $column_name || ! in_array( $post_type, $this->get_post_types(), true ) ) {
				return;
			}
			?>
			<fieldset class="inline-edit-col-right">
				<div class="inline-edit-col">
					<div class="inline-edit-group wp-clearfix">
						<label class="inline-edit-wpex_author_staff_id alignleft">
							<span class="title"><?php
								esc_html_e( 'Staff Author', 'assign-staff-as-author-for-total' );
							?></span>
							<select name="wpex_author_staff_id"><?php
								if ( 'bulk_edit_custom_box' === current_filter() ) {
									echo '<option value="">' . esc_html__( '— No Change —', 'assign-staff-as-author-for-total' ) . '</option>';
								} else {
									echo '<option value="">' . esc_html__( '— Select —', 'assign-staff-as-author-for-total' ) . '</option>';
								}
								foreach ( $this->get_staff_author_choices() as $value => $label ) {
									echo '<option value="' . esc_attr( $value ) . '">' . esc_html( $label ) . '</option>';
								} ?>
							?></select>
						</label>
					</div>
				</div>
			</fieldset>
			<?php
		}

		/**
		 * Hooks for adding and saving metaboxes.
		 */
		public function add_save_metabox(): void {
			foreach ( $this->get_post_types() as $post_type ) {
				add_action( "add_meta_boxes_{$post_type}", [ $this, 'add_meta_box' ] );
				add_action( "save_post_{$post_type}", [ $this, 'save_post' ], 10, 2 );
			}
		}

		/**
		 * Filter the author avatar.
		 */
		public function filter_pre_get_avatar( $html, $id_or_email, $args ) {
			if ( ! is_numeric( $id_or_email ) || ! function_exists( 'wpex_get_post_thumbnail' ) ) {
				return $html;
			}

			$id_or_email = (int) $id_or_email;
			$post_author = (int) get_post_field( 'post_author', get_the_ID() );

			if ( $post_author !== $id_or_email ) {
				return $html;
			}

			$staff_author = $this->get_staff_author();

			if ( ! $staff_author ) {
				return $html;
			}

			$staff_thumbnail = get_post_thumbnail_id( $staff_author );

			if ( ! $staff_thumbnail ) {
				return $html;
			}

			$class = [
				'avatar',
				'avatar-' . (int) $args['size'], 'photo'
			];

			$staff_avatar_args = [
				'attachment' => $staff_thumbnail,
				'size'       => 'wpex_custom',
				'width'      => $args['height'],
				'height'     => $args['width'],
				'alt'        => $args['alt'],
			];

			if ( ! empty( $args['class'] ) ) {
				if ( is_array( $args['class'] ) ) {
					$class = array_merge( $class, $args['class'] );
				} else {
					$class[] = $args['class'];
				}
			}

			$staff_avatar_args['class'] = $class;

			if ( ! empty( $args['extra_attr'] ) ) {
				$staff_avatar_args['attributes'] = array_map( 'esc_attr', $args['extra_attr'] );
			}

			$staff_avatar = wpex_get_post_thumbnail( $staff_avatar_args );

			if ( $staff_avatar ) {
				$html = $staff_avatar;
			}

			return $html;
		}

		/**
		 * Alter the author posts link.
		 */
		public function filter_the_author( $display_name ) {
			if ( $staff_author = $this->get_staff_author() ) {
				$display_name = esc_html( get_the_title( $staff_author ) );
			}
			return $display_name;
		}

		/**
		 * Alter the author link.
		 */
		public function filter_author_link( $link ) {
			if ( $staff_author = $this->get_staff_author() ) {
				$link = esc_url( get_permalink( $staff_author ) );
			}
			return $link;
		}

		/**
		 * Alter the author description.
		 */
		public function filter_get_the_author_description( $description ) {
			if ( $staff_author = $this->get_staff_author() ) {
				if ( function_exists( 'wpex_get_excerpt' ) ) {
					$description = wpex_get_excerpt( [
						'post_id' => $staff_author,
						'length'  => (int) $this->get_setting( 'bio_length', 40 ),
					] );
				} else {
					$description = get_the_excerpt( $staff_author );
				}
			}
			return $description;
		}

		/**
		 * Alter the author bio social links.
		 */
		public function filter_wpex_get_user_social_links( $links ) {
			if ( $staff_author = $this->get_staff_author() ) {
				if ( function_exists( 'wpex_get_staff_social' ) ) {
					return wpex_get_staff_social( [
						'post_id' => $staff_author,
						'display' => 'icons',
						'before'  => '<div class="author-bio-social wpex-clr">',
						'after'   => '</div>',
						'style'   => get_theme_mod( 'author_box_social_style', 'flat-color-round' ),
					] );
				} else {
					return;
				}
			}
			return $links;
		}

		/**
		 * Add new metabox to set staff author.
		 */
		public function add_meta_box(): void {
			add_meta_box(
				'wpex_staff_author_meta',
				esc_html__( 'Staff Author', 'assign-staff-as-author-for-total' ),
				[ $this, 'meta_box_callback' ],
				null,
				'side'
			);
		}

		/**
		 * Metabox callback.
		 */
		public function meta_box_callback( $post ) {
			$val = get_post_meta( $post->ID, 'wpex_author_staff_id', true );
			?>
			<label for="wpex_author_staff_id" class="screen-reader-text"><?php esc_html_e( 'Staff Author', 'assign-staff-as-author-for-total' ); ?></label>
			<select name="wpex_author_staff_id" id="wpex_author_staff_id">
				<option value=""><?php esc_html_e( '— Select —', 'assign-staff-as-author-for-total' ); ?></option>
				<?php foreach ( $this->get_staff_author_choices() as $value => $label ) {
					echo '<option value="' . esc_attr( $value ) . '" ' . selected( $val, absint( $value ), false ) . '>' . esc_html( $label ) . '</option>';
				} ?>
			</select>
			<?php wp_nonce_field( 'wpex_author_staff_id_nonce', 'wpex_author_staff_id_nonce' ); ?>
		<?php
		}

		/**
		 * Save meta field.
		 */
		public function save_post( $post_id, $post ) {
			// No need to modify meta during auto-save.
			if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
				return;
			}

			// Post Type check.
			$post_type = $_POST['post_type'] ?? 'post';

			if ( ! in_array( get_post_type( $post ), $this->get_post_types(), true ) ) {
				return;
			}

			// Check the user's permissions.
			if ( $post_type && 'page' === $post_type ) {
				if ( ! current_user_can( 'edit_page', $post_id ) ) {
					return;
				}
			} else {
				if ( ! current_user_can( 'edit_post', $post_id ) ) {
					return;
				}
			}

			$new_val = '';
			$delete_val = false;

			// Save inline edit.
			if ( isset( $_POST['_inline_edit'] )
				&& wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_inline_edit'] ) ), 'inlineeditnonce' )
			) {
				if ( isset( $_POST['wpex_author_staff_id'] ) ) {
					if ( ! empty( $_POST['wpex_author_staff_id'] ) ) {
						$new_val = $_POST['wpex_author_staff_id'];
					} else {
						$delete_val = true;
					}
				}
			}

			// Save bulk edit
			if ( isset( $_REQUEST['_wpnonce'] )
				&& wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) ), 'bulk-posts' )
			) {
				if ( isset( $_REQUEST['wpex_author_staff_id'] ) ) {
					if ( ! empty( $_REQUEST['wpex_author_staff_id'] ) ) {
						$new_val = $_REQUEST['wpex_author_staff_id'];
						if ( 'remove' === $new_val ) {
							$delete_val = true;
						}
					}
				}
			}

			// Standard post save.
			if ( isset( $_POST['wpex_author_staff_id_nonce'] )
				&& wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wpex_author_staff_id_nonce'] ) ), 'wpex_author_staff_id_nonce' )
				&& array_key_exists( 'wpex_author_staff_id', $_POST )
			) {
				if ( ! empty( $_POST['wpex_author_staff_id'] ) ) {
					$new_val = $_POST['wpex_author_staff_id'];
				} else {
					$delete_val = true;
				}
			}

			if ( $new_val ) {
				update_post_meta( $post_id, 'wpex_author_staff_id', absint( $new_val ) );
			} elseif ( $delete_val ) {
				delete_post_meta( $post_id, 'wpex_author_staff_id' );
			}
		}

		/**
		 * Return the staff author id.
		 */
		public function get_staff_author() {
			return get_post_meta( get_the_ID(), 'wpex_author_staff_id', true );
		}

		/**
		 * Returns array of post types to add the metabox to.
		 */
		public function get_post_types(): array {
			$post_types = $this->get_setting( 'post_types' );
			if ( ! is_array( $post_types ) ) {
				$post_types = $this->get_public_post_types();
			}
			return (array) apply_filters( 'assign_staff_author_total_post_types', $post_types );
		}

		/**
		 * Return all public post types.
		 */
		private function get_public_post_types() {
			$post_types = get_post_types( [
				'public'   => true,
				'_builtin' => false,
			] );
			array_push( $post_types, 'post', 'page' );
			unset( $post_types['staff'] );
			return $post_types;
		}

		/**
		 * Returns setting value.
		 */
		private function get_setting( string $id, $default = '' ) {
			return get_option( 'assign_staff_author_total' )[ $id ] ?? $default;
		}

		/**
		 * Returns array of staff authors.
		 */
		public function get_staff_author_choices(): array {
			$choices = [];
			$staff_members = (array) get_posts( [
				'post_type'      => 'staff',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'fields'         => 'ids'
			] );
			foreach ( $staff_members as $staff_member ) {
				$choices[ absint( $staff_member ) ] = sanitize_text_field( get_the_title( $staff_member ) );
			}
			return $choices;
		}

	}

}

new Assign_Staff_Author_Total;
