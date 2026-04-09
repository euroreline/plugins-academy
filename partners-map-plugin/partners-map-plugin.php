<?php
/*
Plugin Name: Partners Map Plugin
Description: CPT Συνεργάτες με shortcodes [partners_map], [partners_list], [partner_submission_form]
Version: 1.0.0
Author: Custom Build
Text Domain: partners-map-plugin
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Partners_Map_Plugin' ) ) {

	class Partners_Map_Plugin {

		const OPTION_API_KEY = 'pmp_google_maps_api_key';
		const NONCE_ACTION_FORM = 'pmp_partner_submission_action';
		const NONCE_NAME_FORM   = 'pmp_partner_submission_nonce';

		public function __construct() {
			add_action( 'init', array( $this, 'register_post_type' ) );
			add_action( 'add_meta_boxes', array( $this, 'register_meta_boxes' ) );
			add_action( 'save_post_partners', array( $this, 'save_partner_meta' ) );

			add_action( 'admin_init', array( $this, 'register_settings' ) );
			add_action( 'admin_menu', array( $this, 'add_settings_page' ) );

			add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ) );

			add_shortcode( 'partners_map', array( $this, 'shortcode_partners_map' ) );
			add_shortcode( 'partners_list', array( $this, 'shortcode_partners_list' ) );
			add_shortcode( 'partner_submission_form', array( $this, 'shortcode_partner_submission_form' ) );

			add_action( 'init', array( $this, 'handle_frontend_submission' ) );
		}

		public function register_post_type() {
			$labels = array(
				'name'               => 'Συνεργάτες',
				'singular_name'      => 'Συνεργάτης',
				'add_new'            => 'Προσθήκη Νέου',
				'add_new_item'       => 'Προσθήκη Νέου Συνεργάτη',
				'edit_item'          => 'Επεξεργασία Συνεργάτη',
				'new_item'           => 'Νέος Συνεργάτης',
				'view_item'          => 'Προβολή Συνεργάτη',
				'search_items'       => 'Αναζήτηση Συνεργατών',
				'not_found'          => 'Δεν βρέθηκαν συνεργάτες',
				'not_found_in_trash' => 'Δεν βρέθηκαν συνεργάτες στον κάδο',
				'menu_name'          => 'Συνεργάτες',
			);

			$args = array(
				'labels'             => $labels,
				'public'             => true,
				'show_in_menu'       => true,
				'menu_position'      => 5,
				'menu_icon'          => 'dashicons-location',
				'supports'           => array( 'title' ),
				'has_archive'        => false,
				'show_in_rest'       => false,
				'publicly_queryable' => true,
			);

			register_post_type( 'partners', $args );
		}

		public function register_meta_boxes() {
			add_meta_box(
				'pmp_partner_details',
				'Στοιχεία Συνεργάτη',
				array( $this, 'render_partner_meta_box' ),
				'partners',
				'normal',
				'high'
			);
		}

		public function render_partner_meta_box( $post ) {
			wp_nonce_field( 'pmp_save_partner_meta', 'pmp_partner_meta_nonce' );

			$address      = get_post_meta( $post->ID, 'partner_address', true );
			$city         = get_post_meta( $post->ID, 'partner_city', true );
			$postal_code  = get_post_meta( $post->ID, 'partner_postal_code', true );
			$phone        = get_post_meta( $post->ID, 'partner_phone', true );
			$marker_color = get_post_meta( $post->ID, 'partner_marker_color', true );
			$partner_type = get_post_meta( $post->ID, 'partner_type', true );

			if ( empty( $marker_color ) ) {
				$marker_color = 'blue';
			}

			if ( empty( $partner_type ) ) {
				$partner_type = 'efarmostis';
			}
			?>
			<style>
				.pmp-admin-grid {
					display: grid;
					grid-template-columns: 180px 1fr;
					gap: 12px 16px;
					align-items: center;
					max-width: 900px;
				}
				.pmp-admin-grid label {
					font-weight: 600;
				}
				.pmp-admin-grid input,
				.pmp-admin-grid select {
					width: 100%;
					max-width: 500px;
				}
			</style>

			<div class="pmp-admin-grid">
				<label for="pmp_partner_address">Διεύθυνση</label>
				<input type="text" id="pmp_partner_address" name="partner_address" value="<?php echo esc_attr( $address ); ?>">

				<label for="pmp_partner_city">Πόλη</label>
				<input type="text" id="pmp_partner_city" name="partner_city" value="<?php echo esc_attr( $city ); ?>">

				<label for="pmp_partner_postal_code">Τ.Κ.</label>
				<input type="text" id="pmp_partner_postal_code" name="partner_postal_code" value="<?php echo esc_attr( $postal_code ); ?>">

				<label for="pmp_partner_phone">Τηλέφωνο</label>
				<input type="text" id="pmp_partner_phone" name="partner_phone" value="<?php echo esc_attr( $phone ); ?>">

				<label for="pmp_partner_marker_color">Χρώμα Marker</label>
				<select id="pmp_partner_marker_color" name="partner_marker_color">
					<option value="blue" <?php selected( $marker_color, 'blue' ); ?>>Μπλε</option>
					<option value="green" <?php selected( $marker_color, 'green' ); ?>>Πράσινο</option>
					<option value="red" <?php selected( $marker_color, 'red' ); ?>>Κόκκινο</option>
					<option value="yellow" <?php selected( $marker_color, 'yellow' ); ?>>Κίτρινο</option>
				</select>

				<label for="pmp_partner_type">Είδος Συνεργάτη</label>
				<select id="pmp_partner_type" name="partner_type">
					<option value="efarmostis" <?php selected( $partner_type, 'efarmostis' ); ?>>Εφαρμοστής</option>
					<option value="katastima" <?php selected( $partner_type, 'katastima' ); ?>>Κατάστημα</option>
					<option value="distributor" <?php selected( $partner_type, 'distributor' ); ?>>Διανομέας</option>
				</select>
			</div>
			<?php
		}

		public function save_partner_meta( $post_id ) {
			if ( ! isset( $_POST['pmp_partner_meta_nonce'] ) ) {
				return;
			}

			if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['pmp_partner_meta_nonce'] ) ), 'pmp_save_partner_meta' ) ) {
				return;
			}

			if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
				return;
			}

			if ( ! current_user_can( 'edit_post', $post_id ) ) {
				return;
			}

			$fields = array(
				'partner_address',
				'partner_city',
				'partner_postal_code',
				'partner_phone',
				'partner_marker_color',
				'partner_type',
			);

			foreach ( $fields as $field ) {
				$value = isset( $_POST[ $field ] ) ? sanitize_text_field( wp_unslash( $_POST[ $field ] ) ) : '';
				update_post_meta( $post_id, $field, $value );
			}
		}

		public function register_settings() {
			register_setting(
				'pmp_settings_group',
				self::OPTION_API_KEY,
				array(
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
					'default'           => '',
				)
			);
		}

		public function add_settings_page() {
			add_options_page(
				'Partners Map Settings',
				'Partners Map',
				'manage_options',
				'pmp-settings',
				array( $this, 'render_settings_page' )
			);
		}

		public function render_settings_page() {
			?>
			<div class="wrap">
				<h1>Partners Map Settings</h1>
				<form method="post" action="options.php">
					<?php settings_fields( 'pmp_settings_group' ); ?>
					<table class="form-table">
						<tr>
							<th scope="row">
								<label for="pmp_google_maps_api_key">Google Maps API Key</label>
							</th>
							<td>
								<input
									type="text"
									id="pmp_google_maps_api_key"
									name="<?php echo esc_attr( self::OPTION_API_KEY ); ?>"
									value="<?php echo esc_attr( get_option( self::OPTION_API_KEY, '' ) ); ?>"
									class="regular-text"
								>
								<p class="description">
									Βάλε εδώ το Google Maps JavaScript API key.
								</p>
							</td>
						</tr>
					</table>
					<?php submit_button(); ?>
				</form>
			</div>
			<?php
		}

		public function register_assets() {
			wp_register_style(
				'pmp-styles',
				plugin_dir_url( __FILE__ ) . 'assets/css/partners-map-plugin.css',
				array(),
				'1.0.0'
			);

			wp_register_script(
				'pmp-scripts',
				plugin_dir_url( __FILE__ ) . 'assets/js/partners-map-plugin.js',
				array(),
				'1.0.0',
				true
			);
		}

		private function get_partner_type_label( $type_key ) {
			$labels = array(
				'efarmostis' => 'Εφαρμοστής',
				'katastima'  => 'Κατάστημα',
				'distributor'=> 'Διανομέας',
			);

			return isset( $labels[ $type_key ] ) ? $labels[ $type_key ] : 'Άγνωστο';
		}

		private function get_marker_icon( $color ) {
			$allowed = array( 'blue', 'green', 'red', 'yellow' );
			if ( ! in_array( $color, $allowed, true ) ) {
				$color = 'blue';
			}

			return 'https://maps.google.com/mapfiles/ms/icons/' . $color . '-dot.png';
		}

		private function get_partners_data() {
			$partners = get_posts(
				array(
					'post_type'      => 'partners',
					'posts_per_page' => -1,
					'orderby'        => 'title',
					'order'          => 'ASC',
					'post_status'    => 'publish',
				)
			);

			$data = array();

			foreach ( $partners as $partner ) {
				$title        = get_the_title( $partner->ID );
				$address      = get_post_meta( $partner->ID, 'partner_address', true );
				$city         = get_post_meta( $partner->ID, 'partner_city', true );
				$postal_code  = get_post_meta( $partner->ID, 'partner_postal_code', true );
				$phone        = get_post_meta( $partner->ID, 'partner_phone', true );
				$marker_color = get_post_meta( $partner->ID, 'partner_marker_color', true );
				$partner_type = get_post_meta( $partner->ID, 'partner_type', true );

				if ( empty( $marker_color ) ) {
					$marker_color = 'blue';
				}

				$full_address_parts = array_filter(
					array(
						$address,
						$city,
						$postal_code,
						'Greece',
					)
				);

				$full_address = implode( ', ', $full_address_parts );

				$data[] = array(
					'id'              => $partner->ID,
					'title'           => $title,
					'address'         => $address,
					'city'            => $city,
					'postal_code'     => $postal_code,
					'full_address'    => $full_address,
					'phone'           => $phone,
					'marker_color'    => $marker_color,
					'marker_icon'     => $this->get_marker_icon( $marker_color ),
					'type_key'        => $partner_type,
					'type_label'      => $this->get_partner_type_label( $partner_type ),
				);
			}

			return $data;
		}

		private function enqueue_frontend_assets_if_needed() {
			wp_enqueue_style( 'pmp-styles' );
			wp_enqueue_script( 'pmp-scripts' );

			$api_key = trim( (string) get_option( self::OPTION_API_KEY, '' ) );

			wp_localize_script(
				'pmp-scripts',
				'pmpData',
				array(
					'apiKey' => $api_key,
					'i18n'   => array(
						'noResults' => 'Δεν βρέθηκαν συνεργάτες.',
					),
				)
			);
		}

		public function shortcode_partners_map( $atts ) {
			$atts = shortcode_atts(
				array(
					'height' => '500px',
					'zoom'   => '6',
				),
				$atts,
				'partners_map'
			);

			$this->enqueue_frontend_assets_if_needed();

			$partners_json = wp_json_encode( $this->get_partners_data() );
			$map_id        = 'pmp-map-' . wp_rand( 1000, 999999 );
			$height        = preg_replace( '/[^0-9a-z%.-]/i', '', $atts['height'] );
			$zoom          = absint( $atts['zoom'] );
			if ( $zoom <= 0 ) {
				$zoom = 6;
			}

			ob_start();
			?>
			<div
				id="<?php echo esc_attr( $map_id ); ?>"
				class="pmp-map"
				style="height: <?php echo esc_attr( $height ); ?>;"
				data-partners="<?php echo esc_attr( $partners_json ); ?>"
				data-zoom="<?php echo esc_attr( $zoom ); ?>"
			></div>
			<?php
			return ob_get_clean();
		}

		public function shortcode_partners_list() {
	$this->enqueue_frontend_assets_if_needed();

	$partners = $this->get_partners_data();

	ob_start();
	?>

	<div class="pmp-list-wrap-container">

		<!-- SEARCH TOP -->
		<div class="pmp-search-row">
			<input
				type="text"
				class="pmp-search-input"
				data-pmp-search-input="1"
				placeholder="Αναζήτηση βάση ονόματος, πόλης, διεύθυνσης, ΤΚ ή τηλεφώνου..."
			>
		</div>

		<!-- SCROLLABLE CARDS WINDOW -->
		<div class="pmp-list-wrap" data-pmp-list-wrap="1">

			<div class="pmp-results" data-pmp-results="1">

				<?php if ( empty( $partners ) ) : ?>

					<div class="pmp-no-results">
						Δεν υπάρχουν καταχωρημένοι συνεργάτες.
					</div>

				<?php else : ?>

					<?php foreach ( $partners as $partner ) :

						$search_blob = strtolower(
							implode(
								' ',
								array_filter(
									array(
										$partner['title'],
										$partner['address'],
										$partner['city'],
										$partner['postal_code'],
										$partner['phone'],
										$partner['type_label'],
									)
								)
							)
						);
					?>

					<div
						class="pmp-partner-card"
						data-pmp-partner-card="1"
						data-partner-id="<?php echo esc_attr( $partner['id'] ); ?>"
						data-search="<?php echo esc_attr( $search_blob ); ?>"
					>

						<div class="pmp-partner-title">
							<?php echo esc_html( $partner['title'] ); ?>
						</div>

						<?php if ( ! empty( $partner['address'] ) ) : ?>
							<div class="pmp-partner-line">
								<strong>Διεύθυνση:</strong>
								<?php echo esc_html( $partner['address'] ); ?>
							</div>
						<?php endif; ?>

						<?php if ( ! empty( $partner['city'] ) ) : ?>
							<div class="pmp-partner-line">
								<strong>Πόλη:</strong>
								<?php echo esc_html( $partner['city'] ); ?>
							</div>
						<?php endif; ?>

						<?php if ( ! empty( $partner['postal_code'] ) ) : ?>
							<div class="pmp-partner-line">
								<strong>Τ.Κ.:</strong>
								<?php echo esc_html( $partner['postal_code'] ); ?>
							</div>
						<?php endif; ?>

						<?php if ( ! empty( $partner['phone'] ) ) : ?>
							<div class="pmp-partner-line">
								<strong>Τηλέφωνο:</strong>
								<?php echo esc_html( $partner['phone'] ); ?>
							</div>
						<?php endif; ?>

						<div class="pmp-partner-line">
							<strong>Είδος:</strong>
							<?php echo esc_html( $partner['type_label'] ); ?>
						</div>

						<button
							type="button"
							class="pmp-focus-btn"
							data-pmp-focus-partner="<?php echo esc_attr( $partner['id'] ); ?>"
						>
							Προβολή στον χάρτη
						</button>

					</div>

					<?php endforeach; ?>

					<div
						class="pmp-no-results pmp-hidden"
						data-pmp-empty-state="1"
					>
						Δεν βρέθηκαν συνεργάτες.
					</div>

				<?php endif; ?>

			</div>

		</div>

		<!-- SEARCH BOTTOM -->
		<div class="pmp-search-row">
			<input
				type="text"
				class="pmp-search-input"
				data-pmp-search-input="1"
				placeholder="Αναζήτηση βάση ονόματος, πόλης, διεύθυνσης, ΤΚ ή τηλεφώνου..."
			>
		</div>

	</div>

	<?php

	return ob_get_clean();
}

		public function shortcode_partner_submission_form() {
			$this->enqueue_frontend_assets_if_needed();

			$message = '';
			$type    = '';

			if ( isset( $_GET['pmp_submitted'] ) ) {
				$status = sanitize_text_field( wp_unslash( $_GET['pmp_submitted'] ) );
				if ( 'success' === $status ) {
					$message = 'Ο συνεργάτης καταχωρήθηκε επιτυχώς.';
					$type    = 'success';
				} elseif ( 'error' === $status ) {
					$message = 'Υπήρξε πρόβλημα στην υποβολή. Προσπάθησε ξανά.';
					$type    = 'error';
				}
			}

			ob_start();
			?>
			<div class="pmp-form-wrap">
				<?php if ( ! empty( $message ) ) : ?>
					<div class="pmp-message pmp-message-<?php echo esc_attr( $type ); ?>">
						<?php echo esc_html( $message ); ?>
					</div>
				<?php endif; ?>

				<form method="post" class="pmp-form">
					<?php wp_nonce_field( self::NONCE_ACTION_FORM, self::NONCE_NAME_FORM ); ?>

					<input type="hidden" name="pmp_action" value="submit_partner">
					<input type="text" name="pmp_website" value="" class="pmp-honeypot" autocomplete="off" tabindex="-1">

					<div class="pmp-form-grid">
						<div class="pmp-form-field">
							<label for="pmp_partner_title">Όνομα Συνεργάτη</label>
							<input type="text" id="pmp_partner_title" name="partner_title" required>
						</div>

						<div class="pmp-form-field">
							<label for="pmp_partner_phone">Τηλέφωνο</label>
							<input type="text" id="pmp_partner_phone" name="partner_phone" required>
						</div>

						<div class="pmp-form-field pmp-form-field-full">
							<label for="pmp_partner_address_front">Διεύθυνση</label>
							<input type="text" id="pmp_partner_address_front" name="partner_address" required>
						</div>

						<div class="pmp-form-field">
							<label for="pmp_partner_city_front">Πόλη</label>
							<input type="text" id="pmp_partner_city_front" name="partner_city" required>
						</div>

						<div class="pmp-form-field">
							<label for="pmp_partner_postal_code_front">Τ.Κ.</label>
							<input type="text" id="pmp_partner_postal_code_front" name="partner_postal_code">
						</div>

						<div class="pmp-form-field">
							<label for="pmp_partner_marker_color_front">Χρώμα Marker</label>
							<select id="pmp_partner_marker_color_front" name="partner_marker_color">
								<option value="blue">Μπλε</option>
								<option value="green">Πράσινο</option>
								<option value="red">Κόκκινο</option>
								<option value="yellow">Κίτρινο</option>
							</select>
						</div>

						<div class="pmp-form-field">
							<label for="pmp_partner_type_front">Είδος Συνεργάτη</label>
							<select id="pmp_partner_type_front" name="partner_type">
								<option value="efarmostis">Εφαρμοστής</option>
								<option value="katastima">Κατάστημα</option>
								<option value="distributor">Διανομέας</option>
							</select>
						</div>
					</div>

					<div class="pmp-form-actions">
						<button type="submit" class="pmp-submit-btn">Υποβολή</button>
					</div>
				</form>
			</div>
			<?php
			return ob_get_clean();
		}

		public function handle_frontend_submission() {
			if ( ! isset( $_POST['pmp_action'] ) ) {
				return;
			}

			$action = sanitize_text_field( wp_unslash( $_POST['pmp_action'] ) );
			if ( 'submit_partner' !== $action ) {
				return;
			}

			if ( ! isset( $_POST[ self::NONCE_NAME_FORM ] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ self::NONCE_NAME_FORM ] ) ), self::NONCE_ACTION_FORM ) ) {
				return;
			}

			if ( ! empty( $_POST['pmp_website'] ) ) {
				return;
			}

			$title        = isset( $_POST['partner_title'] ) ? sanitize_text_field( wp_unslash( $_POST['partner_title'] ) ) : '';
			$address      = isset( $_POST['partner_address'] ) ? sanitize_text_field( wp_unslash( $_POST['partner_address'] ) ) : '';
			$city         = isset( $_POST['partner_city'] ) ? sanitize_text_field( wp_unslash( $_POST['partner_city'] ) ) : '';
			$postal_code  = isset( $_POST['partner_postal_code'] ) ? sanitize_text_field( wp_unslash( $_POST['partner_postal_code'] ) ) : '';
			$phone        = isset( $_POST['partner_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['partner_phone'] ) ) : '';
			$marker_color = isset( $_POST['partner_marker_color'] ) ? sanitize_text_field( wp_unslash( $_POST['partner_marker_color'] ) ) : 'blue';
			$partner_type = isset( $_POST['partner_type'] ) ? sanitize_text_field( wp_unslash( $_POST['partner_type'] ) ) : 'efarmostis';

			if ( empty( $title ) || empty( $address ) || empty( $city ) || empty( $phone ) ) {
				$this->redirect_after_submission( 'error' );
			}

			$post_id = wp_insert_post(
				array(
					'post_type'   => 'partners',
					'post_title'  => $title,
					'post_status' => 'publish',
				),
				true
			);

			if ( is_wp_error( $post_id ) || ! $post_id ) {
				$this->redirect_after_submission( 'error' );
			}

			update_post_meta( $post_id, 'partner_address', $address );
			update_post_meta( $post_id, 'partner_city', $city );
			update_post_meta( $post_id, 'partner_postal_code', $postal_code );
			update_post_meta( $post_id, 'partner_phone', $phone );
			update_post_meta( $post_id, 'partner_marker_color', $marker_color );
			update_post_meta( $post_id, 'partner_type', $partner_type );

			$this->redirect_after_submission( 'success' );
		}

		private function redirect_after_submission( $status ) {
			$referer = wp_get_referer();

			if ( ! $referer ) {
				$referer = home_url( '/' );
			}

			$url = add_query_arg(
				array(
					'pmp_submitted' => $status,
				),
				$referer
			);

			wp_safe_redirect( $url );
			exit;
		}
	}

	new Partners_Map_Plugin();
}

register_activation_hook(
	__FILE__,
	function() {
		$plugin = new Partners_Map_Plugin();
		$plugin->register_post_type();
		flush_rewrite_rules();
	}
);

register_deactivation_hook(
	__FILE__,
	function() {
		flush_rewrite_rules();
	}
);