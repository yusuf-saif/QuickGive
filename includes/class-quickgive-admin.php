<?php
/**
 * Admin settings page for QuickGive.
 *
 * @package QuickGive
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles the WordPress admin settings UI, donation log, and overview dashboard.
 */
class QuickGive_Admin {

	/**
	 * Option group / option name used to store all settings.
	 */
	const OPTION_NAME = 'quickgive_settings';

	/**
	 * Constructor — register hooks.
	 */
	public function __construct() {
		add_action( 'admin_menu',            array( $this, 'add_menu_page' ) );
		add_action( 'admin_init',            array( $this, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
	}

	// -------------------------------------------------------------------------
	// Menus
	// -------------------------------------------------------------------------

	/**
	 * Register the top-level admin menu and all sub-pages.
	 */
	public function add_menu_page() {
		add_menu_page(
			__( 'QuickGive Settings', 'quickgive' ),
			__( 'QuickGive', 'quickgive' ),
			'manage_options',
			QUICKGIVE_SLUG,
			array( $this, 'render_settings_page' ),
			'dashicons-heart',
			56
		);

		add_submenu_page(
			QUICKGIVE_SLUG,
			__( 'Overview', 'quickgive' ),
			__( 'Overview', 'quickgive' ),
			'manage_options',
			QUICKGIVE_SLUG . '-overview',
			array( $this, 'render_overview_page' )
		);

		add_submenu_page(
			QUICKGIVE_SLUG,
			__( 'Donation Log', 'quickgive' ),
			__( 'Donation Log', 'quickgive' ),
			'manage_options',
			QUICKGIVE_SLUG . '-log',
			array( $this, 'render_log_page' )
		);
	}

	// -------------------------------------------------------------------------
	// Settings registration
	// -------------------------------------------------------------------------

	/**
	 * Register settings, sections, and fields via the Settings API.
	 */
	public function register_settings() {
		register_setting(
			QUICKGIVE_SLUG,
			self::OPTION_NAME,
			array(
				'sanitize_callback' => array( $this, 'sanitize_settings' ),
			)
		);

		// --- Paystack API section ---
		add_settings_section( 'quickgive_api', __( 'Paystack API', 'quickgive' ), '__return_false', QUICKGIVE_SLUG );

		$this->add_field( 'quickgive_api', 'mode',            __( 'Mode',             'quickgive' ), 'render_mode_field' );
		$this->add_field( 'quickgive_api', 'public_key_test', __( 'Test Public Key',  'quickgive' ), 'render_text_field' );
		$this->add_field( 'quickgive_api', 'secret_key_test', __( 'Test Secret Key',  'quickgive' ), 'render_password_field' );
		$this->add_field( 'quickgive_api', 'public_key_live', __( 'Live Public Key',  'quickgive' ), 'render_text_field' );
		$this->add_field( 'quickgive_api', 'secret_key_live', __( 'Live Secret Key',  'quickgive' ), 'render_password_field' );
		$this->add_field( 'quickgive_api', 'currency',        __( 'Currency',         'quickgive' ), 'render_currency_field' );

		// --- Donation options section ---
		add_settings_section( 'quickgive_donation', __( 'Donation Options', 'quickgive' ), '__return_false', QUICKGIVE_SLUG );

		$this->add_field( 'quickgive_donation', 'preset_amounts', __( 'Preset Amounts (comma-separated)',   'quickgive' ), 'render_text_field' );
		$this->add_field( 'quickgive_donation', 'allow_custom',   __( 'Allow Custom Amount',                'quickgive' ), 'render_checkbox_field' );
		$this->add_field( 'quickgive_donation', 'min_amount',     __( 'Minimum Amount',                     'quickgive' ), 'render_number_field' );
		$this->add_field( 'quickgive_donation', 'max_amount',     __( 'Maximum Amount (0 = no limit)',       'quickgive' ), 'render_number_field' );

		// --- UI / Button section ---
		add_settings_section( 'quickgive_ui', __( 'Button & Messages', 'quickgive' ), '__return_false', QUICKGIVE_SLUG );

		$this->add_field( 'quickgive_ui', 'button_label',     __( 'Button Label',                                        'quickgive' ), 'render_text_field' );
		$this->add_field( 'quickgive_ui', 'thankyou_message', __( 'Thank-You Message (shown in popup after payment)',     'quickgive' ), 'render_textarea_field' );

		// --- Donor Email section ---
		add_settings_section(
			'quickgive_email',
			__( 'Donor Thank-You Email', 'quickgive' ),
			array( $this, 'render_email_section_description' ),
			QUICKGIVE_SLUG
		);

		$this->add_field( 'quickgive_email', 'email_enabled',    __( 'Enable Thank-You Email',  'quickgive' ), 'render_checkbox_field' );
		$this->add_field( 'quickgive_email', 'email_from_name',  __( 'From Name',               'quickgive' ), 'render_text_field' );
		$this->add_field( 'quickgive_email', 'email_from_email', __( 'From Email',              'quickgive' ), 'render_text_field' );
		$this->add_field( 'quickgive_email', 'email_subject',    __( 'Subject',                 'quickgive' ), 'render_text_field' );
		$this->add_field( 'quickgive_email', 'email_body',       __( 'Body',                    'quickgive' ), 'render_email_body_field' );
	}

	/**
	 * Helper — register a settings field with a shared args array.
	 *
	 * @param string $section  Settings section ID.
	 * @param string $key      Option key.
	 * @param string $label    Field label.
	 * @param string $callback Renderer method name on this class.
	 */
	private function add_field( $section, $key, $label, $callback ) {
		add_settings_field(
			'quickgive_' . $key,
			$label,
			array( $this, $callback ),
			QUICKGIVE_SLUG,
			$section,
			array( 'key' => $key )
		);
	}

	// -------------------------------------------------------------------------
	// Settings sanitization
	// -------------------------------------------------------------------------

	/**
	 * Sanitize all settings before saving.
	 *
	 * @param array $input Raw form input.
	 * @return array Sanitised settings array.
	 */
	public function sanitize_settings( $input ) {
		$clean = array();

		// --- API ---
		$clean['mode'] = isset( $input['mode'] ) && 'live' === $input['mode'] ? 'live' : 'test';

		$clean['public_key_test'] = sanitize_text_field( $input['public_key_test'] ?? '' );
		$clean['secret_key_test'] = sanitize_text_field( $input['secret_key_test'] ?? '' );
		$clean['public_key_live'] = sanitize_text_field( $input['public_key_live'] ?? '' );
		$clean['secret_key_live'] = sanitize_text_field( $input['secret_key_live'] ?? '' );

		$allowed_currencies = array( 'NGN', 'GHS', 'ZAR', 'KES', 'USD', 'GBP', 'EUR' );
		$clean['currency']  = in_array( $input['currency'] ?? 'NGN', $allowed_currencies, true )
			? $input['currency']
			: 'NGN';

		// --- Donation options ---
		$raw_amounts             = sanitize_text_field( $input['preset_amounts'] ?? '' );
		$clean['preset_amounts'] = preg_replace( '/[^0-9,.]/', '', $raw_amounts );

		$clean['allow_custom'] = ! empty( $input['allow_custom'] ) ? '1' : '0';
		$clean['min_amount']   = max( 0, (int) ( $input['min_amount'] ?? 0 ) );
		$clean['max_amount']   = max( 0, (int) ( $input['max_amount'] ?? 0 ) );

		// --- UI ---
		$clean['button_label']     = sanitize_text_field( $input['button_label'] ?? __( 'Donate Now', 'quickgive' ) );
		$clean['thankyou_message'] = wp_kses_post( $input['thankyou_message'] ?? '' );

		// --- Donor email ---
		$clean['email_enabled']    = ! empty( $input['email_enabled'] ) ? '1' : '0';
		$clean['email_from_name']  = sanitize_text_field( $input['email_from_name'] ?? '' );
		$clean['email_from_email'] = sanitize_email( $input['email_from_email'] ?? '' );
		$clean['email_subject']    = sanitize_text_field( $input['email_subject'] ?? '' );
		$clean['email_body']       = sanitize_textarea_field( $input['email_body'] ?? '' );

		return $clean;
	}

	// -------------------------------------------------------------------------
	// Asset enqueue
	// -------------------------------------------------------------------------

	/**
	 * Enqueue admin CSS on our plugin pages only.
	 *
	 * @param string $hook Current admin page hook suffix.
	 */
	public function enqueue_admin_assets( $hook ) {
		$our_hooks = array(
			'toplevel_page_' . QUICKGIVE_SLUG,
			'quickgive_page_' . QUICKGIVE_SLUG . '-overview',
			'quickgive_page_' . QUICKGIVE_SLUG . '-log',
		);

		if ( ! in_array( $hook, $our_hooks, true ) ) {
			return;
		}

		wp_enqueue_style(
			'quickgive-admin',
			QUICKGIVE_URL . 'assets/css/quickgive-admin.css',
			array(),
			QUICKGIVE_VERSION
		);
	}

	// -------------------------------------------------------------------------
	// Field renderers
	// -------------------------------------------------------------------------

	/** @param array $args Field args including 'key'. */
	public function render_text_field( $args ) {
		$opts = get_option( self::OPTION_NAME, array() );
		$key  = $args['key'];
		$val  = $opts[ $key ] ?? '';
		printf(
			'<input type="text" class="regular-text" name="%1$s[%2$s]" id="%2$s" value="%3$s" />',
			esc_attr( self::OPTION_NAME ),
			esc_attr( $key ),
			esc_attr( $val )
		);
	}

	/** @param array $args Field args including 'key'. */
	public function render_password_field( $args ) {
		$opts = get_option( self::OPTION_NAME, array() );
		$key  = $args['key'];
		$val  = $opts[ $key ] ?? '';
		printf(
			'<input type="password" class="regular-text" name="%1$s[%2$s]" id="%2$s" value="%3$s" autocomplete="new-password" />',
			esc_attr( self::OPTION_NAME ),
			esc_attr( $key ),
			esc_attr( $val )
		);
	}

	/** @param array $args Field args including 'key'. */
	public function render_number_field( $args ) {
		$opts = get_option( self::OPTION_NAME, array() );
		$key  = $args['key'];
		$val  = absint( $opts[ $key ] ?? 0 );
		printf(
			'<input type="number" min="0" class="small-text" name="%1$s[%2$s]" id="%2$s" value="%3$s" />',
			esc_attr( self::OPTION_NAME ),
			esc_attr( $key ),
			esc_attr( (string) $val )
		);
	}

	/** @param array $args Field args including 'key'. */
	public function render_checkbox_field( $args ) {
		$opts    = get_option( self::OPTION_NAME, array() );
		$key     = $args['key'];
		$is_on   = '1' === ( $opts[ $key ] ?? '0' );
		printf(
			'<label><input type="checkbox" name="%1$s[%2$s]" id="%2$s" value="1"%3$s /> %4$s</label>',
			esc_attr( self::OPTION_NAME ),
			esc_attr( $key ),
			checked( $is_on, true, false ),
			esc_html__( 'Enabled', 'quickgive' )
		);
	}

	/** @param array $args Field args including 'key'. */
	public function render_textarea_field( $args ) {
		$opts = get_option( self::OPTION_NAME, array() );
		$key  = $args['key'];
		$val  = $opts[ $key ] ?? '';
		printf(
			'<textarea class="large-text" rows="4" name="%1$s[%2$s]" id="%2$s">%3$s</textarea>',
			esc_attr( self::OPTION_NAME ),
			esc_attr( $key ),
			esc_textarea( $val )
		);
	}

	/**
	 * Render the email body textarea with placeholder hint.
	 *
	 * @param array $args Field args.
	 */
	public function render_email_body_field( $args ) {
		$opts = get_option( self::OPTION_NAME, array() );
		$key  = $args['key'];
		$val  = $opts[ $key ] ?? '';
		printf(
			'<textarea class="large-text" rows="8" name="%1$s[%2$s]" id="%2$s">%3$s</textarea>',
			esc_attr( self::OPTION_NAME ),
			esc_attr( $key ),
			esc_textarea( $val )
		);
		echo '<p class="description">';
		esc_html_e( 'Available placeholders:', 'quickgive' );
		echo ' <code>{amount}</code> <code>{currency}</code> <code>{email}</code> <code>{reference}</code> <code>{site_name}</code>';
		echo '</p>';
	}

	/** Render mode radio buttons. */
	public function render_mode_field( $args ) {
		$opts = get_option( self::OPTION_NAME, array() );
		$mode = $opts['mode'] ?? 'test';
		foreach ( array( 'test' => __( 'Test', 'quickgive' ), 'live' => __( 'Live', 'quickgive' ) ) as $value => $label ) {
			printf(
				'<label style="margin-right:16px"><input type="radio" name="%1$s[mode]" value="%2$s"%3$s /> %4$s</label>',
				esc_attr( self::OPTION_NAME ),
				esc_attr( $value ),
				checked( $mode, $value, false ),
				esc_html( $label )
			);
		}
	}

	/** Render currency select. */
	public function render_currency_field( $args ) {
		$opts       = get_option( self::OPTION_NAME, array() );
		$selected   = $opts['currency'] ?? 'NGN';
		$currencies = array(
			'NGN' => 'NGN — Nigerian Naira',
			'GHS' => 'GHS — Ghanaian Cedi',
			'ZAR' => 'ZAR — South African Rand',
			'KES' => 'KES — Kenyan Shilling',
			'USD' => 'USD — US Dollar',
			'GBP' => 'GBP — British Pound',
			'EUR' => 'EUR — Euro',
		);
		echo '<select name="' . esc_attr( self::OPTION_NAME ) . '[currency]">';
		foreach ( $currencies as $code => $label ) {
			printf(
				'<option value="%1$s"%2$s>%3$s</option>',
				esc_attr( $code ),
				selected( $selected, $code, false ),
				esc_html( $label )
			);
		}
		echo '</select>';
	}

	/** Description callback for the email section. */
	public function render_email_section_description() {
		echo '<p class="description">' . esc_html__( 'Send an automatic thank-you email to donors after their payment is verified. The email is sent only after successful server-side confirmation — never on unverified frontend callbacks.', 'quickgive' ) . '</p>';
	}

	// -------------------------------------------------------------------------
	// Page renderers
	// -------------------------------------------------------------------------

	/**
	 * Render the main settings page.
	 */
	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'quickgive' ) );
		}
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

			<div class="quickgive-shortcode-tip">
				<span><?php esc_html_e( 'Shortcode:', 'quickgive' ); ?></span>
				<code>[quickgive_donation_popup]</code>
				<span><?php esc_html_e( '— place anywhere on your site to show the donation button.', 'quickgive' ); ?></span>
			</div>

			<form method="post" action="options.php">
				<?php
				settings_fields( QUICKGIVE_SLUG );
				do_settings_sections( QUICKGIVE_SLUG );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Render the Overview page.
	 */
	public function render_overview_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'quickgive' ) );
		}

		$summary  = QuickGive_Logger::get_summary();
		$opts     = get_option( self::OPTION_NAME, array() );
		$currency = sanitize_text_field( $opts['currency'] ?? 'NGN' );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Donation Overview', 'quickgive' ); ?></h1>

			<!-- Summary cards -->
			<div class="quickgive-overview-cards">

				<div class="quickgive-card">
					<div class="quickgive-card__value"><?php echo esc_html( number_format_i18n( $summary['total_count'] ) ); ?></div>
					<div class="quickgive-card__label"><?php esc_html_e( 'Total Attempts', 'quickgive' ); ?></div>
				</div>

				<div class="quickgive-card quickgive-card--success">
					<div class="quickgive-card__value"><?php echo esc_html( number_format_i18n( $summary['success_count'] ) ); ?></div>
					<div class="quickgive-card__label"><?php esc_html_e( 'Successful Donations', 'quickgive' ); ?></div>
				</div>

				<div class="quickgive-card quickgive-card--raised">
					<div class="quickgive-card__value">
						<?php echo esc_html( $currency . ' ' . number_format( $summary['total_raised'], 2 ) ); ?>
					</div>
					<div class="quickgive-card__label"><?php esc_html_e( 'Total Raised', 'quickgive' ); ?></div>
				</div>

			</div><!-- /.quickgive-overview-cards -->

			<!-- Recent successful donations -->
			<h2 style="margin-top:32px"><?php esc_html_e( 'Recent Donations', 'quickgive' ); ?></h2>

			<?php if ( empty( $summary['recent'] ) ) : ?>
				<p><?php esc_html_e( 'No successful donations recorded yet.', 'quickgive' ); ?></p>
			<?php else : ?>
				<table class="wp-list-table widefat fixed striped" style="max-width:700px">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Date', 'quickgive' ); ?></th>
							<th><?php esc_html_e( 'Email', 'quickgive' ); ?></th>
							<th><?php esc_html_e( 'Amount', 'quickgive' ); ?></th>
							<th><?php esc_html_e( 'Type', 'quickgive' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $summary['recent'] as $row ) : ?>
							<tr>
								<td><?php echo esc_html( $row->created_at ); ?></td>
								<td><?php echo esc_html( $row->donor_email ); ?></td>
								<td><?php echo esc_html( $row->currency . ' ' . number_format( $row->amount, 2 ) ); ?></td>
								<td>
									<span class="quickgive-type-badge quickgive-type-badge--<?php echo esc_attr( $row->amount_type ?? 'preset' ); ?>">
										<?php echo esc_html( ucfirst( $row->amount_type ?? 'preset' ) ); ?>
									</span>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
				<p>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . QUICKGIVE_SLUG . '-log' ) ); ?>">
						<?php esc_html_e( '→ View full donation log', 'quickgive' ); ?>
					</a>
				</p>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Render the full paginated donation log page.
	 */
	public function render_log_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'quickgive' ) );
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$status_filter    = isset( $_GET['status'] ) ? sanitize_text_field( wp_unslash( $_GET['status'] ) ) : '';
		$allowed_statuses = array( '', 'success', 'failed', 'pending' );
		if ( ! in_array( $status_filter, $allowed_statuses, true ) ) {
			$status_filter = '';
		}

		$per_page = 50;
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$page   = max( 1, absint( $_GET['paged'] ?? 1 ) );
		$offset = ( $page - 1 ) * $per_page;
		$total  = QuickGive_Logger::get_count( $status_filter );
		$pages  = $total > 0 ? (int) ceil( $total / $per_page ) : 1;

		$donations = QuickGive_Logger::get_donations(
			array(
				'limit'  => $per_page,
				'offset' => $offset,
				'status' => $status_filter,
			)
		);

		$base_url      = add_query_arg( 'page', QUICKGIVE_SLUG . '-log', admin_url( 'admin.php' ) );
		$status_counts = QuickGive_Logger::get_status_counts();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Donation Log', 'quickgive' ); ?></h1>

			<!-- Status filter tabs -->
			<ul class="subsubsub">
				<?php
				$filters = array(
					''        => array( __( 'All', 'quickgive' ),        $status_counts['total'] ),
					'success' => array( __( 'Successful', 'quickgive' ), $status_counts['success'] ),
					'failed'  => array( __( 'Failed', 'quickgive' ),     $status_counts['failed'] ),
					'pending' => array( __( 'Pending', 'quickgive' ),    $status_counts['pending'] ),
				);
				$links = array();
				foreach ( $filters as $filter_val => $data ) {
					list( $label, $count ) = $data;
					$url     = $filter_val ? add_query_arg( 'status', $filter_val, $base_url ) : $base_url;
					$class   = ( $status_filter === $filter_val ) ? 'current' : '';
					$links[] = sprintf(
						'<li><a href="%1$s" class="%2$s">%3$s <span class="count">(%4$s)</span></a>',
						esc_url( $url ),
						esc_attr( $class ),
						esc_html( $label ),
						esc_html( number_format_i18n( $count ) )
					);
				}
				// All values inside $links are built with esc_url/esc_attr/esc_html — safe to implode and echo.
				echo implode( ' | ', $links ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				?>
			</ul>

			<?php if ( empty( $donations ) && 1 === $page ) : ?>
				<p style="margin-top:2em"><?php esc_html_e( 'No donations recorded yet.', 'quickgive' ); ?></p>
			<?php else : ?>
				<p class="description">
					<?php
					printf(
						/* translators: 1: first item number, 2: last item number, 3: total count */
						esc_html__( 'Showing %1$s–%2$s of %3$s donations.', 'quickgive' ),
						esc_html( number_format_i18n( $offset + 1 ) ),
						esc_html( number_format_i18n( min( $offset + $per_page, $total ) ) ),
						esc_html( number_format_i18n( $total ) )
					);
					?>
				</p>

				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Date',      'quickgive' ); ?></th>
							<th><?php esc_html_e( 'Email',     'quickgive' ); ?></th>
							<th><?php esc_html_e( 'Amount',    'quickgive' ); ?></th>
							<th><?php esc_html_e( 'Currency',  'quickgive' ); ?></th>
							<th><?php esc_html_e( 'Type',      'quickgive' ); ?></th>
							<th><?php esc_html_e( 'Status',    'quickgive' ); ?></th>
							<th><?php esc_html_e( 'Reference', 'quickgive' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $donations as $row ) : ?>
							<tr>
								<td><?php echo esc_html( $row->created_at ); ?></td>
								<td><?php echo esc_html( $row->donor_email ); ?></td>
								<td><?php echo esc_html( number_format( $row->amount, 2 ) ); ?></td>
								<td><?php echo esc_html( $row->currency ); ?></td>
								<td>
									<span class="quickgive-type-badge quickgive-type-badge--<?php echo esc_attr( $row->amount_type ?? 'preset' ); ?>">
										<?php echo esc_html( ucfirst( $row->amount_type ?? 'preset' ) ); ?>
									</span>
								</td>
								<td>
									<span class="quickgive-status quickgive-status--<?php echo esc_attr( $row->status ); ?>">
										<?php echo esc_html( ucfirst( $row->status ) ); ?>
									</span>
								</td>
								<td><code><?php echo esc_html( $row->reference ); ?></code></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>

				<?php if ( $pages > 1 ) : ?>
				<div class="tablenav bottom">
					<div class="tablenav-pages">
						<span class="displaying-num">
							<?php
							printf(
								/* translators: 1: current page number, 2: total pages */
								esc_html__( 'Page %1$s of %2$s', 'quickgive' ),
								esc_html( number_format_i18n( $page ) ),
								esc_html( number_format_i18n( $pages ) )
							);
							?>
						</span>
						<span class="pagination-links">
							<?php if ( $page > 1 ) : ?>
								<a class="prev-page button" href="<?php echo esc_url( add_query_arg( 'paged', $page - 1, $base_url ) ); ?>">
									&laquo; <?php esc_html_e( 'Previous', 'quickgive' ); ?>
								</a>
							<?php endif; ?>
							<?php if ( $page < $pages ) : ?>
								<a class="next-page button" href="<?php echo esc_url( add_query_arg( 'paged', $page + 1, $base_url ) ); ?>">
									<?php esc_html_e( 'Next', 'quickgive' ); ?> &raquo;
								</a>
							<?php endif; ?>
						</span>
					</div>
				</div>
				<?php endif; ?>

			<?php endif; ?>
		</div>
		<?php
	}
}
