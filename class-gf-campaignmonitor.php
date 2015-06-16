<?php

GFForms::include_feed_addon_framework();

class GFCampaignMonitor extends GFFeedAddOn {

	protected $_version = GF_CAMPAIGN_MONITOR_VERSION;
	protected $_min_gravityforms_version = '1.8.17';
	protected $_slug = 'gravityformscampaignmonitor';
	protected $_path = 'gravityformscampaignmonitor/campaignmonitor.php';
	protected $_full_path = __FILE__;
	protected $_url = 'http://www.gravityforms.com';
	protected $_title = 'Campaign Monitor Add-On';
	protected $_short_title = 'Campaign Monitor';

	// Members plugin integration
	protected $_capabilities = array( 'gravityforms_campaignmonitor', 'gravityforms_campaignmonitor_uninstall' );

	// Permissions
	protected $_capabilities_settings_page = 'gravityforms_campaignmonitor';
	protected $_capabilities_form_settings = 'gravityforms_campaignmonitor';
	protected $_capabilities_uninstall = 'gravityforms_campaignmonitor_uninstall';
	protected $_enable_rg_autoupgrade = true;

	private static $_instance = null;

	public static function get_instance() {
		if ( self::$_instance == null ) {
			self::$_instance = new GFCampaignMonitor();
		}

		return self::$_instance;
	}

	public function init() {

		parent::init();

		$this->add_delayed_payment_support( array(
			'option_label' => __( 'Subscribe user to Campaign Monitor only when payment is received.', 'gravityformscampaignmonitor' )
		) );

	}

	public function init_admin() {
		parent::init_admin();

		add_filter( 'gform_addon_navigation', array( $this, 'maybe_create_menu' ) );
	}

	//------- AJAX FUNCTIONS ------------------//

	public function init_ajax() {
		parent::init_ajax();

		add_action( 'wp_ajax_gf_dismiss_campaignmonitor_menu', array( $this, 'ajax_dismiss_menu' ) );

	}

	public function maybe_create_menu( $menus ) {
		$current_user = wp_get_current_user();
		$dismiss_campaignmonitor_menu = get_metadata( 'user', $current_user->ID, 'dismiss_campaignmonitor_menu', true );
		if ( $dismiss_campaignmonitor_menu != '1' ){
			$menus[] = array( 'name' => $this->_slug, 'label' => $this->get_short_title(), 'callback' => array( $this, 'temporary_plugin_page' ), 'permission' => $this->_capabilities_form_settings );
		}

		return $menus;
	}

	public function ajax_dismiss_menu() {

		$current_user = wp_get_current_user();
		update_metadata( 'user', $current_user->ID, 'dismiss_campaignmonitor_menu', '1' );
	}

	public function temporary_plugin_page() {
		$current_user = wp_get_current_user();
		?>
		<script type="text/javascript">
			function dismissMenu(){
				jQuery('#gf_spinner').show();
				jQuery.post(ajaxurl, {
						action : "gf_dismiss_campaignmonitor_menu"
					},
					function (response) {
						document.location.href='?page=gf_edit_forms';
						jQuery('#gf_spinner').hide();
					}
				);

			}
		</script>

		<div class="wrap about-wrap">
			<h1><?php _e( 'Campaign Monitor Add-On v3.0', 'gravityformscampaignmonitor' ) ?></h1>
			<div class="about-text"><?php _e( 'Thank you for updating! The new version of the Gravity Forms Campaign Monitor Add-On makes changes to how you manage your Campaign Monitor integration.', 'gravityformscampaignmonitor' ) ?></div>
			<div class="changelog">
				<hr/>
				<div class="feature-section col two-col">
					<div class="col-1">
						<h3><?php _e( 'Manage Campaign Monitor Contextually', 'gravityformscampaignmonitor' ) ?></h3>
						<p><?php _e( 'Campaign Monitor Feeds are now accessed via the Campaign Monitor sub-menu within the Form Settings for the Form with which you would like to integrate Campaign Monitor.', 'gravityformscampaignmonitor' ) ?></p>
					</div>
					<div class="col-2 last-feature">
						<img src="http://gravityforms.s3.amazonaws.com/webimages/AddonNotice/NewCampaignMonitor3.png">
					</div>
				</div>

				<hr/>

				<form method="post" id="dismiss_menu_form" style="margin-top: 20px;">
					<input type="checkbox" name="dismiss_campaignmonitor_menu" value="1" onclick="dismissMenu();"> <label><?php _e( 'I understand this change, dismiss this message!', 'gravityformscampaignmonitor' ) ?></label>
					<img id="gf_spinner" src="<?php echo GFCommon::get_base_url() . '/images/spinner.gif'?>" alt="<?php _e( 'Please wait...', 'gravityformscampaignmonitor' ) ?>" style="display:none;"/>
				</form>

			</div>
		</div>
	<?php
	}

	// ------- Plugin settings -------
	public function plugin_settings_fields() {
		return array(
			array(
				'title'       => __( 'Campaign Monitor Account Information', 'gravityformscampaignmonitor' ),
				'description' => sprintf( __( 'Campaign Monitor is an email marketing software for designers and their clients. Use Gravity Forms to collect customer information and automatically add them to your client\'s Campaign Monitor subscription list. If you don\'t have a Campaign Monitor account, you can  %1$ssign up for one here%2$s', 'gravityformscampaignmonitor' ),
					'<a href="http://www.campaignmonitor.com" target="_blank">', '</a>.' ),
				'fields'      => array(
					array(
						'name'              => 'apiKey',
						'label'             => __( 'API Key', 'gravityformscampaignmonitor' ),
						'type'              => 'api_key',
						'class'             => 'medium',
						'feedback_callback' => array( $this, 'is_valid_api_key' )

					),
					array(
						'name'  => 'apiClientId',
						'label' => __( 'Client API Key', 'gravityformscampaignmonitor' ),
						'type'  => 'api_client_id',
						'class' => 'medium',
						'feedback_callback' => array( $this, 'is_valid_client_id' )
					),
				)
			),
		);

	}

	public function settings_api_key( $field, $echo = true ) {

		$field['type'] = 'text';

		$api_key_field = $this->settings_text( $field, false );

		//switch type="text" to type="password" so the key is not visible
		$api_key_field = str_replace( 'type="text"','type="password"', $api_key_field );

		$caption = '<small>' . sprintf( __( "You can find your unique API key by clicking on the 'Account Settings' link at the top of your Campaign Monitor screen.", 'gravityformscampaignmonitor' ) ) . '</small>';

		if ( $echo ) {
			echo $api_key_field . '</br>' . $caption;
		}

		return $api_key_field . '</br>' . $caption;

	}

	public function settings_api_client_id( $field, $echo = true ) {

		$field['type'] = 'text';

		$api_client_id = $this->settings_text( $field, false );

		$caption = '<small>' . sprintf( __( '(Optional) Enter an API Client ID to limit this Add-On to the specified client.', 'gravityformscampaignmonitor' ) ) . '</small>';

		if ( $echo ) {
			echo $api_client_id . '</br>' . $caption;
		}

		return $api_client_id . '</br>' . $caption;

	}

	//-------- Form Settings ---------
	public function feed_edit_page( $form, $feed_id ) {

		// ensures valid credentials were entered in the settings page
		if ( $this->is_valid_api_key() === false && $this->is_valid_client_id() === false ) {
			?>
			<div><?php echo sprintf( __( 'We are unable to login to Campaign Monitor with the provided API key. Please make sure you have entered a valid API key in the %sSettings Page%s', 'gravityformscampaignmonitor' ),
					'<a href="' . esc_url( $this->get_plugin_settings_url() ) . '">', '</a>' ); ?>
			</div>
			<?php
			return;
		}

		echo '<script type="text/javascript">var form = ' . GFCommon::json_encode( $form ) . ';</script>';

		parent::feed_edit_page( $form, $feed_id );
	}


	public function feed_settings_fields() {
		return array(
			array(
				'title'       => __( 'Campaign Monitor Feed', 'gravityformscampaignmonitor' ),
				'description' => '',
				'fields'      => array(
					array(
						'name'     => 'feedName',
						'label'    => __( 'Name', 'gravityformscampaignmonitor' ),
						'type'     => 'text',
						'required' => true,
						'class'    => 'medium',
						'tooltip'  => '<h6>' . __( 'Name', 'gravityformscampaignmonitor' ) . '</h6>' . __( 'Enter a feed name to uniquely identify this setup.', 'gravityformscampaignmonitor' ),
					),
					array(
						'name'     => 'client',
						'label'    => __( 'Client', 'gravityformscampaignmonitor' ),
						'type'     => 'select',
						'onchange' => 'jQuery(this).parents("form").submit();',
						'hidden'   => $this->is_clients_hidden(),
						'choices'  => $this->get_campaignmonitor_clients(),
						'tooltip'  => '<h6>' . __( 'Client', 'gravityformscampaignmonitor' ) . '</h6>' . __( 'Select the Campaign Monitor client you would like to add your contacts to.', 'gravityformscampaignmonitor' ),
					),
					array(
						'name'       => 'contactList',
						'label'      => __( 'Contact List', 'gravityformscampaignmonitor' ),
						'type'       => 'contact_list',
						'onchange'   => 'jQuery(this).parents("form").submit();',
						'dependency' => array( $this, 'has_selected_client' ),
						'tooltip'    => '<h6>' . __( 'Contact List', 'gravityformscampaignmonitor' ) . '</h6>' . __( 'Select the Campaign Monitor list you would like to add your contacts to.', 'gravityformscampaignmonitor' ),
					),
					array(
						'name'       => 'listFields',
						'label'      => __( 'Map Fields', 'gravityformscampaignmonitor' ),
						'type'       => 'field_map',
						'dependency' => 'contactList',
						'field_map'	 => $this->create_list_field_map(),
						'tooltip'    => '<h6>' . __( 'Map Fields', 'gravityformscampaignmonitor' ) . '</h6>' . __( 'Associate your Campaign Monitor custom fields to the appropriate Gravity Form fields by selecting the appropriate form field from the list.', 'gravityformscampaignmonitor' ),
					),
					array(
						'name'       => 'optin',
						'label'      => __( 'Opt In', 'gravityformscampaignmonitor' ),
						'type'       => 'feed_condition',
						'dependency' => 'contactList',
						'tooltip'    => '<h6>' . __( 'Opt-In Condition', 'gravityformscampaignmonitor' ) . '</h6>' . __( 'When the opt-in condition is enabled, form submissions will only be exported to Campaign Monitor when the condition is met. When disabled all form submissions will be exported.', 'gravityformscampaignmonitor' ),
					),
					array(
						'name'       => 'resubscribe',
						'label'      => __( 'Options', 'gravityformscampaignmonitor' ),
						'type'       => 'option_resubscribe',
						'dependency' => 'contactList',
						'onclick'    => "if(this.checked){jQuery('#campaignmonitor_resubscribe_warning').slideDown();} else{jQuery('#campaignmonitor_resubscribe_warning').slideUp();}",
					),
				)
			),
		);
	}

	public function settings_contact_list( $field, $echo = true ) {

		$client_id = $this->get_setting( 'client' );
		if ( empty( $client_id ) ) {
			$clients   = $this->get_clients();
			$client_id = $clients[0]->ClientID;
		}

		$this->include_api();
		$api = new CS_REST_Clients( $client_id, $this->get_key() );

		$response = $api->get_lists();

		if ( ! $response->was_successful() ) {
			return;
		}

		$lists[] = array(
			'label' => 'Select List',
			'value' => '',
		);

		$retrieved_lists = $response->response;

		foreach ( $retrieved_lists as $list ) {

			$lists[] = array(
				'label' => $list->Name,
				'value' => $list->ListID,
			);

		}

		$field['type']    = 'select';
		$field['choices'] = $lists;

		$html = $this->settings_select( $field, false );

		if ( $echo ) {
			echo $html;
		}

		return $html;

	}

	public function create_list_field_map() {

		$list_id = $this->get_setting( 'contactList' );

		$custom_fields = $this->get_custom_fields( $list_id );

		return $custom_fields;

	}

	public function settings_option_resubscribe( $field, $echo = true ) {

		$field['type'] = 'checkbox';

		$options          = array(
			array(
				'label' => __( 'Resubscribe', 'gravityformscampaignmonitor' ),
				'name'  => 'resubscribe',
			),
		);
		$field['choices'] = $options;
		$html             = $this->settings_checkbox( $field, false );

		$tooltip_content = '<h6>' . __( 'Resubscribe', 'gravityformscampaignmonitor' ) . '</h6>' . __( 'When this option is enabled, if the subscriber is in an inactive state or has previously been unsubscribed, they will be re-added to the active list. Therefore, this option should be used with caution and only when appropriate.', 'gravityformscampaignmonitor' );
		$tooltip         = gform_tooltip( $tooltip_content, '', true );

		$html = str_replace( '</div>', $tooltip . '</div>', $html );

		$resubscribe_warning_style = $this->get_setting( 'resubscribe' ) ? '' : 'display:none';
		$html .= '<small><span id="campaignmonitor_resubscribe_warning" style="' . $resubscribe_warning_style . '">' . __( 'This option will re-subscribe users that have been unsubscribed. Use with caution and only when appropriate.', 'gravityformscampaignmonitor' ) . '</span></small>';

		if ( $echo ) {
			echo $html;
		}

		return $html;

	}

	public function is_clients_hidden() {
		if ( $this->has_multiple_clients() ) {
			return false;
		}

		return true;
	}

	public function has_multiple_clients() {

		$clients = $this->get_clients();
		if ( ! $clients || count( $clients ) == 1 ) {
			return false;
		}

		return true;
	}

	public function has_selected_client() {

		if ( $this->has_multiple_clients() ) {
			$selected_client = $this->get_setting( 'client' );

			return ! empty( $selected_client );
		}

		return true;
	}

	private function get_clients() {

		$clients = GFCache::get( 'campaignmonitor_clients' );
		if ( ! $clients ) {

			$this->include_api();
			$api = new CS_REST_General( $this->get_key() );

			//getting all clients
			$response = $api->get_clients();
			if ( $response->http_status_code == 200 ) {
				$clients = $response->response;
				GFCache::set( 'campaignmonitor_clients', $clients );
			}
		}

		return $clients;

	}

	public function get_campaignmonitor_clients() {

		$campaignmonitor_clients = $this->get_clients();

		if ( ! $campaignmonitor_clients ) {
			return;
		}

		if ( $this->has_multiple_clients() ) {
			$clients_dropdown[] = array(
				'label' => 'Select Client',
				'value' => '',
			);

		}

		foreach ( $campaignmonitor_clients as $client ) {

			$clients_dropdown[] = array(
				'label' => $client->Name,
				'value' => $client->ClientID,
			);

		}

		return $clients_dropdown;

	}

	public function get_custom_fields( $list_id ) {

		$this->include_api();
		$api = new CS_REST_Lists( $list_id, $this->get_key() );

		$custom_fields = array(
			array( 'label' => 'Email Address', 'name' => 'email', 'required' => true ),
			array( 'label' => 'Full Name', 'name' => 'fullname' ),
		);

		$response = $api->get_custom_fields();
		if ( ! $response->was_successful() ) {
			return $custom_fields;
		}

		$custom_field_objects = $response->response;

		foreach ( $custom_field_objects as $custom_field ) {
			$name            = str_replace( '[', '', $custom_field->Key );
			$name            = str_replace( ']', '', $name );
			$custom_fields[] = array( 'label' => $custom_field->FieldName, 'name' => $name );
		}

		return $custom_fields;

	}

	public function feed_list_columns() {
		return array(
			'feedName'		=> __( 'Name', 'gravityformscampaignmonitor' ),
			'client'		=> __( 'Campaign Monitor Client', 'gravityformscampaignmonitor' ),
			'contactList'	=> __( 'Campaign Monitor List', 'gravityformscampaignmonitor' )
		);
	}

	public function get_column_value_client( $feed ) {
		return $this->get_client_name( $feed['meta']['client'] );
	}

	public function get_column_value_contactList( $feed ) {
		return $this->get_list_name( $feed['meta']['client'], $feed['meta']['contactList'] );
	}

	private function get_client_name( $client_id ) {
		global $_clients;

		$campaignmonitor_clients = $this->get_clients();

		if ( ! isset( $_clients ) ) {

			$_clients = $campaignmonitor_clients;

		}

		$client_name_array = wp_filter_object_list( $_clients, array( 'ClientID' => $client_id ), 'and', 'Name' );
		if ( $client_name_array ) {
			$client_names = array_values( $client_name_array );
			$client_name  = $client_names[0];
		} else {
			$client_name = $client_id . ' ' . __( '(List not found in Campaign Monitor', 'gravityformscampaignmonitor' ) . ')';
		}

		return $client_name;

	}

	private function get_list_name( $client_id, $list_id ) {
		global $_lists;

		if ( ! isset( $_lists ) ) {

			$this->include_api();
			$api      = new CS_REST_Clients( $client_id, $this->get_key() );
			$response = $api->get_lists();
			$_lists   = $response->response;
		}

		$list_name_array = wp_filter_object_list( $_lists, array( 'ListID' => $list_id ), 'and', 'Name' );
		if ( $list_name_array ) {
			$list_names = array_values( $list_name_array );
			$list_name  = $list_names[0];
		} else {
			$list_name = $list_id . ' ' . __( '(List not found in Campaign Monitor', 'gravityformscampaignmonitor' ) . ')';
		}

		return $list_name;
	}

	// used to upgrade old feeds into new version
	public function upgrade( $previous_version ) {
		if ( empty( $previous_version ) ) {
			$previous_version = get_option( 'gf_campaignmonitor_version' );
		}
		$previous_is_pre_addon_framework = ! empty( $previous_version ) && version_compare( $previous_version, '3.0.dev1', '<' );

		if ( $previous_is_pre_addon_framework ) {

			$this->copy_feeds();

			$old_settings = get_option( 'gf_campaignmonitor_settings' );

			$new_settings = array(
				'apiKey'      => $old_settings['api_key'],
				'apiClientId' => $old_settings['client_id'],
			);

			parent::update_plugin_settings( $new_settings );

			//set paypal delay setting
			$this->update_paypal_delay_settings( 'delay_campaignmonitor_subscription' );

		}

	}

	public function copy_feeds() {

		$old_feeds = $this->get_old_feeds();

		if ( ! $old_feeds ) {
			return;
		}

		$counter = 1;
		foreach ( $old_feeds as $old_feed ) {
			$feed_name  = 'Feed ' . $counter;
			$form_id   = $old_feed['form_id'];
			$is_active = $old_feed['is_active'];

			$new_meta = array(
				'feedName'    => $feed_name,
				'client'      => rgar( $old_feed['meta'], 'client_id' ),
				'contactList' => rgar( $old_feed['meta'], 'contact_list_id' ),
				'resubscribe' => rgar( $old_feed['meta'], 'resubscribe' ),
			);

			foreach ( $old_feed['meta']['field_map'] as $var_tag => $field_id ) {
				$new_meta['listFields_' . $var_tag] = $field_id;
			}

			$optin_enabled = rgar( $old_feed['meta'], 'optin_enabled' );
			if ( $optin_enabled ) {
				$new_meta['feed_condition_conditional_logic']        = 1;
				$new_meta['feed_condition_conditional_logic_object'] = array(
					'conditionalLogic' => array(
						'actionType' => 'show',
						'logicType'  => 'all',
						'rules'      => array(
							array(
								'fieldId'  => $old_feed['meta']['optin_field_id'],
								'operator' => $old_feed['meta']['optin_operator'],
								'value'    => $old_feed['meta']['optin_value'],
							),
						)
					)
				);
			} else {
				$new_meta['feed_condition_conditional_logic'] = 0;
			}

			$this->insert_feed( $form_id, $is_active, $new_meta );
			$counter ++;
		}
	}

	public function update_paypal_delay_settings( $old_delay_setting_name ) {
		global $wpdb;
		$this->log_debug( __METHOD__ . '(): Checking to see if there are any delay settings that need to be migrated for PayPal Standard.' );

		$new_delay_setting_name = 'delay_' . $this->_slug;

		//get paypal feeds from old table
		$paypal_feeds_old = $this->get_old_paypal_feeds();

		//loop through feeds and look for delay setting and create duplicate with new delay setting for the framework version of PayPal Standard
		if ( ! empty( $paypal_feeds_old ) ){
			$this->log_debug( __METHOD__ . '(): Old feeds found for ' . $this->_slug . ' - copying over delay settings.' );
			foreach ( $paypal_feeds_old as $old_feed ) {
				$meta = $old_feed['meta'];
				if ( ! rgempty( $old_delay_setting_name, $meta ) ){
					$meta[ $new_delay_setting_name ] = $meta[ $old_delay_setting_name ];
					//update paypal meta to have new setting
					$meta = maybe_serialize( $meta );
					$wpdb->update( "{$wpdb->prefix}rg_paypal", array( 'meta' => $meta ), array( 'id' => $old_feed['id'] ), array( '%s' ), array( '%d' ) );
				}
			}
		}

		//get paypal feeds from new framework table
		$paypal_feeds = $this->get_feeds_by_slug( 'gravityformspaypal' );
		if ( ! empty( $paypal_feeds ) ){
			$this->log_debug( __METHOD__ . '(): New feeds found for ' . $this->_slug . ' - copying over delay settings.' );
			foreach ( $paypal_feeds as $feed ) {
				$meta = $feed['meta'];
				if ( ! rgempty( $old_delay_setting_name, $meta ) ){
					$meta[ $new_delay_setting_name ] = $meta[ $old_delay_setting_name ];
					$this->update_feed_meta( $feed['id'], $meta );
				}
			}
		}
	}

	public function get_old_paypal_feeds() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'rg_paypal';

		if ( ! $this->table_exists( $table_name ) ) {
			return false;
		}

		$form_table_name = GFFormsModel::get_form_table_name();
		$sql     = "SELECT s.id, s.is_active, s.form_id, s.meta, f.title as form_title
				FROM {$table_name} s
				INNER JOIN {$form_table_name} f ON s.form_id = f.id";

		$this->log_debug( __METHOD__ . "(): getting old paypal feeds: {$sql}" );

		$results = $wpdb->get_results( $sql, ARRAY_A );

		$this->log_debug( __METHOD__ . "(): error?: {$wpdb->last_error}" );

		$count = sizeof( $results );

		$this->log_debug( __METHOD__ . "(): count: {$count}" );

		for ( $i = 0; $i < $count; $i ++ ) {
			$results[ $i ]['meta'] = maybe_unserialize( $results[ $i ]['meta'] );
		}

		return $results;
	}

	public function get_old_feeds() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'rg_campaignmonitor';

		if ( ! $this->table_exists( $table_name ) ) {
			return false;
		}

		$form_table_name = RGFormsModel::get_form_table_name();
		$sql             = "SELECT s.id, s.is_active, s.form_id, s.meta, f.title as form_title
				FROM $table_name s
				INNER JOIN $form_table_name f ON s.form_id = f.id";

		$results = $wpdb->get_results( $sql, ARRAY_A );

		$count = sizeof( $results );
		for ( $i = 0; $i < $count; $i ++ ) {
			$results[ $i ]['meta'] = maybe_unserialize( $results[ $i ]['meta'] );
		}

		return $results;
	}

	public function process_feed( $feed, $entry, $form ) {

		$this->export_feed( $entry, $form, $feed );

	}

	public function export_feed( $entry, $form, $feed ) {

		$resubscribe = $feed['meta']['resubscribe'] ? true : false;
		$email       = $entry[ $feed['meta']['listFields_email'] ];
		$name        = '';
		if ( ! empty( $feed['meta']['listFields_fullname'] ) ) {
			$name = $this->get_name( $entry, $feed['meta']['listFields_fullname'] );
		}

		$merge_vars = array();
		$field_maps = $this->get_field_map_fields( $feed, 'listFields' );
		foreach ( $field_maps as $var_key => $field_id ) {
			$field = RGFormsModel::get_field( $form, $field_id );
			if ( GFCommon::is_product_field( $field['type'] ) && rgar( $field, 'enablePrice' ) ) {
				$ary          = explode( '|', $entry[ $field_id ] );
				$product_name = count( $ary ) > 0 ? $ary[0] : '';
				$merge_vars[] = array( 'Key' => $var_key, 'Value' => $product_name );
			} elseif ( RGFormsModel::get_input_type( $field ) == 'checkbox' ) {
				foreach ( $field['inputs'] as $input ) {
					$index = (string) $input['id'];
					$merge_vars[] = array(
						'Key'   => $var_key,
						'Value' => apply_filters( 'gform_campaignmonitor_field_value', rgar( $entry, $index ), $form['id'], $field_id, $entry )
					);
				}
			} elseif ( RGFormsModel::get_input_type( $field ) == 'multiselect' ) {
				$value = apply_filters( 'gform_campaignmonitor_field_value', rgar( $entry, $field_id ), $form['id'], $field_id, $entry );
				$value_ary = explode( ',', $value );
				if ( ! empty( $value_ary ) && is_array( $value_ary ) ) {
					$value_ary = array_filter( $value_ary ); //removing blank values
					foreach ( $value_ary as $val ) {
						$merge_vars[] = array(
							'Key'   => $var_key,
							'Value' => $val,
						);
					}
				}
			} elseif ( ! in_array( $var_key, array( 'email', 'fullname' ) ) ) {
				$merge_vars[] = array(
					'Key'   => $var_key,
					'Value' => apply_filters( 'gform_campaignmonitor_field_value', rgar( $entry, $field_id ), $form['id'], $field_id, $entry )
				);
			}
		}

		$override_custom_fields = apply_filters( 'gform_campaignmonitor_override_blank_custom_fields', false, $entry, $form, $feed );
		if ( ! $override_custom_fields ) {
			$merge_vars = $this->remove_blank_custom_fields( $merge_vars );
		}

		$subscriber = array(
			'EmailAddress' => $email,
			'Name'         => $name,
			'CustomFields' => $merge_vars,
			'Resubscribe'  => $resubscribe,
		);
		$subscriber = apply_filters( 'gform_campaignmonitor_override_subscriber', $subscriber, $entry, $form, $feed );

		$this->include_api();
		$api = new CS_REST_Subscribers( $feed['meta']['contactList'], $this->get_key() );
		$this->log_debug( __METHOD__ . '(): Adding subscriber => ' . print_r( $subscriber,1 ) );

		$result = $api->add( $subscriber );

		$this->log_debug( __METHOD__ . '(): Result => ' . print_r( $result, true ) );
	}

	private static function remove_blank_custom_fields( $merge_vars ) {
		$i = 0;
		$count = count( $merge_vars );

		for ( $i = 0; $i < $count; $i++ ) {
			if ( rgblank( $merge_vars[ $i ]['Value'] ) ) {
				unset( $merge_vars[ $i ] );
			}
		}
		//resort the array because items could have been removed, this will give an error from Campaign Monitor if the keys are not in numeric sequence
		sort( $merge_vars );
		return $merge_vars;
	}

	private function get_name( $entry, $field_id ) {

		//If field is simple (one input), simply return full content
		$name = rgar( $entry, $field_id );
		if ( ! empty( $name ) ) {
			return $name;
		}

		//Complex field (multiple inputs). Join all pieces and create name
		$prefix = trim( rgar( $entry, $field_id . '.2' ) );
		$first  = trim( rgar( $entry, $field_id . '.3' ) );
		$middle = trim( rgar( $entry, $field_id . '.4' ) );
		$last   = trim( rgar( $entry, $field_id . '.6' ) );
		$suffix = trim( rgar( $entry, $field_id . '.8' ) );

		$name = $prefix;
		$name .= ! empty( $name ) && ! empty( $first ) ? " $first" : $first;
		$name .= ! empty( $name ) && ! empty( $middle ) ? " $middle" : $middle;
		$name .= ! empty( $name ) && ! empty( $last ) ? " $last" : $last;
		$name .= ! empty( $name ) && ! empty( $suffix ) ? " $suffix" : $suffix;

		return $name;
	}

	public function is_valid_key( $key ) {
		if ( empty( $key ) ) {
			return null;
		}
		$this->include_api();
		$api    = new CS_REST_General( $key );
		$result = $api->get_systemdate();

		return $result->was_successful();
	}

	public function is_valid_api_key() {
		return $this->is_valid_key( $this->get_api_key() );
	}

	public function is_valid_client_id() {
		return $this->is_valid_key( $this->get_client_id() );
	}

	public function get_key() {
		$settings = $this->get_plugin_settings();
		$api_key  = $settings['apiKey'];
		$client_id = $settings['apiClientId'];

		return empty( $client_id ) ? $api_key : $client_id;
	}

	public function get_api_key() {

		$settings = $this->get_plugin_settings();
		$api_key  = $settings['apiKey'];

		return $api_key;
	}

	public function get_client_id() {
		$settings  = $this->get_plugin_settings();
		$client_id = $settings['apiClientId'];

		return $client_id;
	}

	public function include_api() {

		if ( ! class_exists( 'CS_REST_Clients' ) ) {
			require_once $this->get_base_path() . '/api/csrest_clients.php';
		}

		if ( ! class_exists( 'CS_REST_General' ) ) {
			require_once $this->get_base_path() . '/api/csrest_general.php';
		}

		if ( ! class_exists( 'CS_REST_Lists' ) ) {
			require_once $this->get_base_path() . '/api/csrest_lists.php';
		}

		if ( ! class_exists( 'CS_REST_Subscribers' ) ) {
			require_once $this->get_base_path() . '/api/csrest_subscribers.php';
		}

	}

}