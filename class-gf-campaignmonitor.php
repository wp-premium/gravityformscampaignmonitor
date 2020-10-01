<?php

// don't load directly
if ( ! defined( 'ABSPATH' ) ) {
	die();
}

GFForms::include_feed_addon_framework();

/**
 * Gravity Forms Campaign Monitor Add-On.
 *
 * @since     Unknown
 * @package   GravityForms
 * @author    Rocketgenius
 * @copyright Copyright (c) 2017, Rocketgenius
 */
class GFCampaignMonitor extends GFFeedAddOn {

	/**
	 * Contains an instance of this class, if available.
	 *
	 * @since  Unknown
	 * @access private
	 * @var    object $_instance If available, contains an instance of this class.
	 */
	private static $_instance = null;

	/**
	 * Defines the version of the Campaign Monitor Add-On.
	 *
	 * @since  Unknown
	 * @access protected
	 * @var    string $_version Contains the version, defined from campaignmonitor.php
	 */
	protected $_version = GF_CAMPAIGN_MONITOR_VERSION;

	/**
	 * Defines the minimum Gravity Forms version required.
	 *
	 * @since  Unknown
	 * @access protected
	 * @var    string $_min_gravityforms_version The minimum version required.
	 */
	protected $_min_gravityforms_version = '2.3';

	/**
	 * Defines the plugin slug.
	 *
	 * @since  Unknown
	 * @access protected
	 * @var    string $_slug The slug used for this plugin.
	 */
	protected $_slug = 'gravityformscampaignmonitor';

	/**
	 * Defines the main plugin file.
	 *
	 * @since  Unknown
	 * @access protected
	 * @var    string $_path The path to the main plugin file, relative to the plugins folder.
	 */
	protected $_path = 'gravityformscampaignmonitor/campaignmonitor.php';

	/**
	 * Defines the full path to this class file.
	 *
	 * @since  Unknown
	 * @access protected
	 * @var    string $_full_path The full path.
	 */
	protected $_full_path = __FILE__;

	/**
	 * Defines the URL where this Add-On can be found.
	 *
	 * @since  Unknown
	 * @access protected
	 * @var    string The URL of the Add-On.
	 */
	protected $_url = 'http://www.gravityforms.com';

	/**
	 * Defines the title of this Add-On.
	 *
	 * @since  Unknown
	 * @access protected
	 * @var    string $_title The title of the Add-On.
	 */
	protected $_title = 'Campaign Monitor Add-On';

	/**
	 * Defines the short title of the Add-On.
	 *
	 * @since  Unknown
	 * @access protected
	 * @var    string $_short_title The short title.
	 */
	protected $_short_title = 'Campaign Monitor';

	/**
	 * Defines if Add-On should use Gravity Forms servers for update data.
	 *
	 * @since  Unknown
	 * @access protected
	 * @var    bool
	 */
	protected $_enable_rg_autoupgrade = true;

	/**
	 * Defines the capability needed to access the Add-On settings page.
	 *
	 * @since  Unknown
	 * @access protected
	 * @var    string $_capabilities_settings_page The capability needed to access the Add-On settings page.
	 */
	protected $_capabilities_settings_page = 'gravityforms_campaignmonitor';

	/**
	 * Defines the capability needed to access the Add-On form settings page.
	 *
	 * @since  Unknown
	 * @access protected
	 * @var    string $_capabilities_form_settings The capability needed to access the Add-On form settings page.
	 */
	protected $_capabilities_form_settings = 'gravityforms_campaignmonitor';

	/**
	 * Defines the capability needed to uninstall the Add-On.
	 *
	 * @since  Unknown
	 * @access protected
	 * @var    string $_capabilities_uninstall The capability needed to uninstall the Add-On.
	 */
	protected $_capabilities_uninstall = 'gravityforms_campaignmonitor_uninstall';

	/**
	 * Defines the capabilities needed for the Campaign Monitor Add-On
	 *
	 * @since  Unknown
	 * @access protected
	 * @var    array $_capabilities The capabilities needed for the Add-On
	 */
	protected $_capabilities = array( 'gravityforms_campaignmonitor', 'gravityforms_campaignmonitor_uninstall' );

	/**
	 * Contains an instance of the Campaign Monitor API library, if available.
	 *
	 * @since  3.5
	 * @access protected
	 * @var    object $api If available, contains an instance of the Campaign Monitor API library.
	 */
	public $api = null;

	/**
	 * Get instance of this class.
	 *
	 * @since  Unknown
	 * @access public
	 * @static
	 *
	 * @return $_instance
	 */
	public static function get_instance() {

		if ( null === self::$_instance ) {
			self::$_instance = new self;
		}

		return self::$_instance;

	}

	/**
	 * Register needed hooks for Add-On.
	 *
	 * @since  Unknown
	 * @access public
	 */
	public function init() {

		parent::init();

		$this->add_delayed_payment_support(
			array(
				'option_label' => esc_html__( 'Subscribe user to Campaign Monitor only when payment is received.', 'gravityformscampaignmonitor' )
			)
		);

	}

	/**
	 * Return the plugin's icon for the plugin/form settings menu.
	 *
	 * @since 3.9
	 *
	 * @return string
	 */
	public function get_menu_icon() {

		return file_get_contents( $this->get_base_path() . '/images/menu-icon.svg' );

	}





	// # PLUGIN SETTINGS -----------------------------------------------------------------------------------------------

	/**
	 * Prepare plugin settings fields.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @uses GFCampaignMonitor::get_clients_as_choices()
	 *
	 * @return array
	 */
	public function plugin_settings_fields() {

		return array(
			array(
				'title'       => esc_html__( 'Campaign Monitor Account Information', 'gravityformscampaignmonitor' ),
				'description' => sprintf(
					'<p>%s</p>',
					sprintf(
						esc_html__( 'Campaign Monitor is an email marketing software for designers and their clients. Use Gravity Forms to collect customer information and automatically add it to your client\'s Campaign Monitor subscription list. If you don\'t have a Campaign Monitor account, you can %1$ssign up for one here.%2$s', 'gravityformscampaignmonitor' ),
						'<a href="http://www.campaignmonitor.com" target="_blank">',
						'</a>'
					)
				),
				'fields'      => array(
					array(
						'name'              => 'apiKey',
						'label'             => esc_html__( 'API Key', 'gravityformscampaignmonitor' ),
						'type'              => 'text',
						'class'             => 'medium',
						'feedback_callback' => array( $this, 'initialize_api' ),
						'description'       => esc_html__( "To locate your API key, in your Campaign Monitor account, click on your profile image and then select 'Account settings'. On the Account settings page click 'API keys' and then click 'Show API key'. If you haven't generated one yet, click 'Generate API key' instead.", 'gravityformscampaignmonitor' ),
					),
				)
			),
		);

	}





	// # FEED SETTINGS -------------------------------------------------------------------------------------------------

	/**
	 * Configures the settings which should be rendered on the feed edit page.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @uses GFCampaignMonitor::get_clients_as_choices()
	 * @uses GFCampaignMonitor::get_custom_fields_as_field_map()
	 * @uses GFCampaignMonitor::get_lists_as_choices()
	 * @uses GFCampaignMonitor::is_clients_hidden()
	 *
	 * @return array The feed settings.
	 */
	public function feed_settings_fields() {

		return array(
			array(
				'title'  => esc_html__( 'Campaign Monitor Feed', 'gravityformscampaignmonitor' ),
				'fields' => array(
					array(
						'name'     => 'feedName',
						'label'    => esc_html__( 'Name', 'gravityformscampaignmonitor' ),
						'type'     => 'text',
						'required' => true,
						'class'    => 'medium',
						'tooltip'  => sprintf(
							'<h6>%s</h6>%s',
							esc_html__( 'Name', 'gravityformscampaignmonitor' ),
							esc_html__( 'Enter a feed name to uniquely identify this setup.', 'gravityformscampaignmonitor' )
						),
					),
					array(
						'name'     => 'client',
						'label'    => esc_html__( 'Client', 'gravityformscampaignmonitor' ),
						'type'     => 'select',
						'onchange' => 'jQuery(this).parents("form").submit();',
						'hidden'   => $this->is_clients_hidden(),
						'choices'  => $this->get_clients_as_choices(),
						'tooltip'  => sprintf(
							'<h6>%s</h6>%s',
							esc_html__( 'Client', 'gravityformscampaignmonitor' ),
							esc_html__( 'Select the Campaign Monitor client you would like to add your contacts to.', 'gravityformscampaignmonitor' )
						),
					),
					array(
						'name'       => 'contactList',
						'label'      => esc_html__( 'Contact List', 'gravityformscampaignmonitor' ),
						'type'       => 'select',
						'required'   => true,
						'choices'    => $this->get_lists_as_choices(),
						'onchange'   => 'jQuery(this).parents("form").submit();',
						'dependency' => array( $this, 'has_selected_client' ),
						'no_choices' => sprintf(
							esc_html__( 'No clients found. Please configure one or more clients in your %sCampaign Monitor%s account.', 'gravityformscampaignmonitor' ),
							'<a href="http://www.campaignmonitor.com" target="_blank">',
							'</a>'
						),
						'tooltip'    => sprintf(
							'<h6>%s</h6>%s',
							esc_html__( 'Contact List', 'gravityformscampaignmonitor' ),
							esc_html__( 'Select the Campaign Monitor list you would like to add your contacts to.', 'gravityformscampaignmonitor' )
						),
					),
					array(
						'name'       => 'listFields',
						'label'      => esc_html__( 'Map Fields', 'gravityformscampaignmonitor' ),
						'type'       => 'field_map',
						'dependency' => 'contactList',
						'field_map'  => $this->get_custom_fields_as_field_map(),
						'tooltip'    => sprintf(
							'<h6>%s</h6>%s',
							esc_html__( 'Map Fields', 'gravityformscampaignmonitor' ),
							esc_html__( 'Associate your Campaign Monitor custom fields to the appropriate Gravity Form fields by selecting the appropriate form field from the list.', 'gravityformscampaignmonitor' )
						),
					),
					array(
						'name'       => 'optin',
						'label'      => esc_html__( 'Conditional Logic', 'gravityformscampaignmonitor' ),
						'type'       => 'feed_condition',
						'dependency' => 'contactList',
						'tooltip'    => sprintf(
							'<h6>%s</h6>%s',
							esc_html__( 'Conditional Logic', 'gravityformscampaignmonitor' ),
							esc_html__( 'When conditional logic is enabled, form submissions will only be exported to Campaign Monitor when the condition is met. When disabled all form submissions will be exported.', 'gravityformscampaignmonitor' )
						),
					),
					array(
						'name'       => 'resubscribe',
						'label'      => esc_html__( 'Options', 'gravityformscampaignmonitor' ),
						'type'       => 'option_resubscribe',
						'dependency' => 'contactList',
						'onclick'    => "if(this.checked){jQuery('#campaignmonitor_resubscribe_warning').slideDown();} else{jQuery('#campaignmonitor_resubscribe_warning').slideUp();}",
					),
					array(
						'type'       => 'save',
						'dependency' => 'contactList',
					),
				)
			),
		);

	}

	/**
	 * Check if the clients setting should be displayed.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @uses GFCampaignMonitor::has_multiple_clients()
	 *
	 * @return bool
	 */
	public function is_clients_hidden() {

		return ! $this->has_multiple_clients();

	}

	/**
	 * Prepare Campaign Monitor custom fields as a field map.
	 *
	 * @since  3.5
	 * @access public
	 *
	 * @uses GFAddOn::get_setting()
	 * @uses GFAddOn::log_error()
	 * @uses GFCampaignMonitor::initialize_api()
	 * @uses GF_CampaignMonitor_API::get_custom_fields()
	 *
	 * @return array
	 */
	public function get_custom_fields_as_field_map() {

		// Initialize field map.
		$field_map = array(
			array(
				'name'     => 'email',
				'label'    => esc_html__( 'Email Address', 'gravityformscampaignmonitor' ),
				'required' => true,
			),
			array(
				'name'     => 'fullname',
				'label'    => esc_html__( 'Full Name', 'gravityformscampaignmonitor' ),
			),
		);

		// If API is not initialized, return.
		if ( ! $this->initialize_api() ) {
			return $field_map;
		}

		// Get the list ID.
		$list_id = $this->get_setting( 'contactList' );

		try {

			// Get custom fields.
			$custom_fields = $this->api->get_custom_fields( $list_id );

		} catch ( \Exception $e ) {

			// Log that we could not retrieve custom fields.
			$this->log_error( __METHOD__ . '(): Unable to retrieve custom fields; ' . $e->getMessage() );

			return $field_map;

		}

		// Loop through custom fields.
		foreach ( $custom_fields as $custom_field ) {

			// Prepare custom field key.
			$field_key = str_replace( array( '[', ']' ), '', $custom_field['Key'] );

			// Add custom field to field map.
			$field_map[] = array(
				'name'  => $field_key,
				'label' => esc_html( $custom_field['FieldName'] ),
			);

		}

		return $field_map;

	}

	/**
	 * Define the markup for the option_resubscribe type field.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @param array     $field The field properties.
	 * @param bool|true $echo Should the setting markup be echoed.
	 *
	 * @uses GFAddOn::get_setting()
	 * @uses GFAddOn::settings_checkbox()
	 *
	 * @return string|void
	 */
	public function settings_option_resubscribe( $field, $echo = true ) {

		// Define field type.
		$field['type'] = 'checkbox';

		// Prepare field choices.
		$field['choices'] = array(
			array(
				'label' => esc_html__( 'Resubscribe', 'gravityformscampaignmonitor' ),
				'name'  => 'resubscribe',
			),
		);

		// Display checkbox field.
		unset( $field['callback'] );
		$html = $this->settings_checkbox( $field, false );

		// Prepare field tooltip.
		$tooltip_content = sprintf(
			'<h6>%s</h6>%s',
			esc_html__( 'Resubscribe', 'gravityformscampaignmonitor' ),
			esc_html__( 'When this option is enabled, if the subscriber is in an inactive state or has previously been unsubscribed, they will be re-added to the active list. Therefore, this option should be used with caution and only when appropriate.', 'gravityformscampaignmonitor' )
		);
		
		// Display tooltip.
		$html = str_replace( '</div>', '&nbsp' . gform_tooltip( $tooltip_content, '', true ) . '</div>', $html );

		// Display warning.
		$html .= sprintf(
			'<small><span id="campaignmonitor_resubscribe_warning" style="%s">%s</span></small>',
			$this->get_setting( 'resubscribe' ) ? '' : 'display:none',
			esc_html__( 'This option will re-subscribe users that have been unsubscribed. Use with caution and only when appropriate.', 'gravityformscampaignmonitor' )
		);

		if ( $echo ) {
			echo $html;
		}

		return $html;

	}





	// # FEED LIST -------------------------------------------------------------------------------------------------

	/**
	 * Prevent feeds being listed or created if the api key or client id aren't valid.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @uses GFCampaignMonitor::initialize_api()
	 *
	 * @return bool
	 */
	public function can_create_feed() {

		return $this->initialize_api();

	}

	/**
	 * Allow the feed to be duplicated.
	 *
	 * @since 3.8
	 *
	 * @param array|int $id The ID of the feed to be duplicated or the feed object when duplicating a form.
	 *
	 * @return bool
	 */
	public function can_duplicate_feed( $id ) {

		return true;

	}


	/**
	 * Configures which columns should be displayed on the feed list page.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @return array
	 */
	public function feed_list_columns() {

		return array(
			'feedName'    => esc_html__( 'Name', 'gravityformscampaignmonitor' ),
			'client'      => esc_html__( 'Campaign Monitor Client', 'gravityformscampaignmonitor' ),
			'contactList' => esc_html__( 'Campaign Monitor List', 'gravityformscampaignmonitor' )
		);

	}

	/**
	 * Returns the value to be displayed in the client column.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @param array $feed The feed being included in the feed list.
	 *
	 * @uses GFAddOn::log_error()
	 * @uses GFAddOn::get_plugin_settings()
	 * @uses GFAddOn::get_setting()
	 * @uses GFCampaignMonitor::initialize_api()
	 * @uses GF_CampaignMonitor_API::get_client()
	 *
	 * @return string
	 */
	public function get_column_value_client( $feed ) {

		// If we cannot initialize the API, return client ID.
		if ( ! $this->initialize_api() ) {
			return $feed['meta']['client'];
		}

		// Set client ID to feed client ID.
		if ( rgars( $feed, 'meta/client' ) ) {

			$client_id = $feed['meta']['client'];

		} else {

			// Use default client.
			$client_id = $this->get_default_client();

		}

		try {

			// Get client.
			$client = $this->api->get_client( $client_id );

			return esc_html( $client['BasicDetails']['CompanyName'] );

		} catch ( \Exception $e ) {

			// Log that we could not get the client.
			$this->log_error( __METHOD__ . '(): Unable to get client; ' . $e->getMessage() );

			return sprintf(
				'<strong>%s</strong>',
				esc_html__( 'Client could not be found.', 'gravityformscampaignmonitor' )
			);

		}

	}

	/**
	 * Returns the value to be displayed in the contactList column.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @param array $feed The feed being included in the feed list.
	 *
	 * @uses GFAddOn::log_error()
	 * @uses GFCampaignMonitor::initialize_api()
	 * @uses GF_CampaignMonitor_API::get_list()
	 *
	 * @return string
	 */
	public function get_column_value_contactList( $feed ) {

		// If we cannot initialize the API, return list ID.
		if ( ! $this->initialize_api() || ! rgars( $feed, 'meta/contactList' ) ) {
			return rgars( $feed, 'meta/contactList' );
		}

		try {

			// Get list.
			$list = $this->api->get_list( $feed['meta']['contactList'] );

			return esc_html( $list['Title'] );

		} catch ( \Exception $e ) {

			// Log that we could not get the list.
			$this->log_error( __METHOD__ . '(): Unable to get list; ' . $e->getMessage() );

			return sprintf(
				'<strong>%s</strong>',
				esc_html__( 'List could not be found.', 'gravityformscampaignmonitor' )
			);

		}

	}





	// # FEED PROCESSING -----------------------------------------------------------------------------------------------

	/**
	 * Initiate processing the feed.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @param  array $feed  The feed object to be processed.
	 * @param  array $entry The entry object currently being processed.
	 * @param  array $form  The form object currently being processed.
	 *
	 * @uses GFAddOn::get_field_map_fields()
	 * @uses GFAddOn::get_field_value()
	 * @uses GFAddOn::log_debug()
	 * @uses GFCampaignMonitor::initialize_api()
	 * @uses GF_CampaignMonitor_API::add_subscriber()
	 * @uses GFCommon::is_invalid_or_empty_email()
	 * @uses GFFeedAddOn::add_feed_error()
	 */
	public function process_feed( $feed, $entry, $form ) {

		// If API cannot be initialized, exit.
		if ( ! $this->initialize_api() ) {

			// Log that API could not be initialized.
			$this->add_feed_error( esc_html__( 'User could not be subscribed because API could not be initialized.', 'gravityformscampaignmonitor' ), $feed, $entry, $form );

			return;

		}

		// Initialize subscriber object.
		$subscriber = array(
			'EmailAddress' => $this->get_field_value( $form, $entry, $feed['meta']['listFields_email'] ),
			'Name'         => $this->get_field_value( $form, $entry, rgars( $feed, 'meta/listFields_fullname' ) ),
			'CustomFields' => array(),
			'Resubscribe'  => rgars( $feed, 'meta/resubscribe' ) ? true : false,
		);

		// If provided email address is empty or invalid, exit.
		if ( GFCommon::is_invalid_or_empty_email( $subscriber['EmailAddress'] ) ) {

			// Log that subscriber could not be added.
			$this->add_feed_error( esc_html__( 'User could not be subscribed because provided email address was empty or invalid.', 'gravityformscampaignmonitor' ), $feed, $entry, $form );

			return;

		}

		// Get field map.
		$field_map = $this->get_field_map_fields( $feed, 'listFields' );

		/**
		 * Modify how Campaign Monitor Add-On handles blank custom fields.
		 * The default behaviour is to remove custom fields which don't have a value from the CustomFields array so they aren't sent to Campaign Monitor.
		 *
		 * @since  Unknown
		 *
		 * @param bool  $override The default is false.
		 * @param array $entry    The Entry which is currently being processed.
		 * @param array $form     The Form which is currently being processed.
		 * @param array $feed     The Feed which is currently being processed.
		 */
		$override_custom_fields = gf_apply_filters( array( 'gform_campaignmonitor_override_blank_custom_fields', $form['id'], $feed['id'] ), false, $entry, $form, $feed );

		// Loop through field map.
		foreach ( $field_map as $key => $field_id ) {

			// If this is an email or name field, skip it.
			if ( in_array( $key, array( 'email', 'fullname' ) ) ) {
				continue;
			}

			// Get field value.
			$field_values = $this->get_field_value( $form, $entry, $field_id );

			// Convert field value to array.
			if ( ! is_array( $field_values ) ) {
				$field_values = array( $field_values );
			}

			// Loop through values and add to subscriber custom fields.
			foreach ( $field_values as $field_value ) {

				// If we are not overriding custom fields and the field value is blank, skip it.
				if ( ! $override_custom_fields && rgblank( $field_value ) ) {
					continue;
				}

				// Add custom field.
				$subscriber['CustomFields'][] = array(
					'Key'   => $key,
					'Value' => $field_value,
				);

			}

		}

		/**
		 * Modify the subscriber parameters before they are sent to Campaign Monitor.
		 *
		 * @since  Unknown
		 *
		 * @param array $subscriber An associative array containing all the parameters to be passed to Campaign Monitor.
		 * @param array $entry      The Entry which is currently being processed.
		 * @param array $form       The Form which is currently being processed.
		 * @param array $feed       The Feed which is currently being processed.
		 */
		$subscriber = gf_apply_filters( 'gform_campaignmonitor_override_subscriber', $form['id'], $subscriber, $entry, $form, $feed );

		$this->log_debug( __METHOD__ . '(): Subscriber to be added => ' . print_r( $subscriber, true ) );

		try {

			// Subscribe user.
			$this->api->add_subscriber( $subscriber, rgars( $feed, 'meta/contactList' ) );

			// Log that user was subscribed.
			$this->log_debug( __METHOD__ . '(): User was subscribed to list.' );

			return;

		} catch ( \Exception $e ) {

			// Log that user could not be subscribed.
			$this->add_feed_error( sprintf( esc_html__( 'User could not be subscribed: %s', 'gravityformscampaignmonitor' ), $e->getMessage() ), $feed, $entry, $form );

			return;

		}

	}

	/**
	 * Returns the value of the selected field.
	 *
	 * @param array $form The form object currently being processed.
	 * @param array $entry The entry object currently being processed.
	 * @param string $field_id The ID of the field being processed.
	 *
	 * @return array
	 */
	public function get_field_value( $form, $entry, $field_id ) {

		$field_value = '';

		switch ( strtolower( $field_id ) ) {

			case 'form_title':
				$field_value = rgar( $form, 'title' );
				break;

			case 'date_created':
				$date_created = rgar( $entry, strtolower( $field_id ) );
				if ( empty( $date_created ) ) {
					//the date created may not yet be populated if this function is called during the validation phase and the entry is not yet created
					$field_value = gmdate( 'Y-m-d H:i:s' );
				} else {
					$field_value = $date_created;
				}
				break;

			case 'ip':
			case 'source_url':
				$field_value = rgar( $entry, strtolower( $field_id ) );
				break;

			default:

				$field = GFFormsModel::get_field( $form, $field_id );

				if ( is_object( $field ) ) {

					$is_integer = $field_id == intval( $field_id );
					$input_type = RGFormsModel::get_input_type( $field );

					if ( $is_integer && $input_type == 'address' ) {

						$field_value = $this->get_full_address( $entry, $field_id );

					} elseif ( $is_integer && $input_type == 'name' ) {

						$field_value = $this->get_full_name( $entry, $field_id );

					} elseif ( $is_integer && $input_type == 'checkbox' ) {

						$field_value = array();
						foreach ( $field->inputs as $input ) {
							$index         = (string) $input['id'];
							$field_value[] = $this->maybe_override_field_value( rgar( $entry, $index ), $form, $entry, $index );
						}

					} elseif ( $input_type == 'multiselect' ) {

						$value = $this->maybe_override_field_value( rgar( $entry, $field_id ), $form, $entry, $field_id );
						if ( ! empty( $value ) ) {
							/** @var GF_Field_MultiSelect $field */
							$field_value = $field->to_array( $value );
						}

					} elseif ( GFCommon::is_product_field( $field->type ) && $field->enablePrice ) {

						$ary         = explode( '|', rgar( $entry, $field_id ) );
						$field_value = count( $ary ) > 0 ? $ary[0] : '';

					} else {

						if ( is_callable( array( 'GF_Field', 'get_value_export' ) ) ) {
							$field_value = $field->get_value_export( $entry, $field_id );
						} else {
							$field_value = rgar( $entry, $field_id );
						}

					}

					if ( ! in_array( $input_type, array( 'checkbox', 'multiselect' ) ) ) {
						$field_value = $this->maybe_override_field_value( $field_value, $form, $entry, $field_id );
					}

				} else {

					$field_value = $this->maybe_override_field_value( rgar( $entry, $field_id ), $form, $entry, $field_id );
				}

		}

		return $field_value;

	}

	/**
	 * Use the legacy gform_campaignmonitor_field_value filter instead of the framework gform_SLUG_field_value filter.
	 *
	 * @param string $field_value The field value.
	 * @param array $form The form object currently being processed.
	 * @param array $entry The entry object currently being processed.
	 * @param string $field_id The ID of the field being processed.
	 *
	 * @return string
	 */
	public function maybe_override_field_value( $field_value, $form, $entry, $field_id ) {

		return gf_apply_filters( 'gform_campaignmonitor_field_value', array(
			$form['id'],
			$field_id
		), $field_value, $form['id'], $field_id, $entry );

	}





	// # HELPER METHODS ------------------------------------------------------------------------------------------------

	/**
	 * Initializes Campaign Monitor API if credentials are valid.
	 *
	 * @since  3.5
	 * @access public
	 *
	 * @uses GFAddOn::get_plugin_settings()
	 * @uses GFAddOn::log_debug()
	 * @uses GFAddOn::log_error()
	 * @uses GF_CampaignMonitor_API::auth_test()
	 *
	 * @return bool|null
	 */
	public function initialize_api() {

		// If API is alredy initialized and license key is not provided, return true.
		if ( ! is_null( $this->api ) ) {
			return true;
		}

		// Load the API library.
		if ( ! class_exists( 'GF_CampaignMonitor_API' ) ) {
			require_once( 'includes/class-gf-campaignmonitor-api.php' );
		}

		// Get the plugin settings.
		$settings = $this->get_plugin_settings();

		// If the API key is empty, do not run a validation check.
		if ( ! rgar( $settings, 'apiKey' ) ) {
			return null;
		}

		// Log validation step.
		$this->log_debug( __METHOD__ . '(): Validating API Info.' );

		// Setup a new Fillable PDFs API object with the API credentials.
		$api = new GF_CampaignMonitor_API( $settings['apiKey'] );

		try {

			// Get system date.
			$api->auth_test();

			// Assign API library to instance.
			$this->api = $api;

			// Log that authentication test passed.
			$this->log_debug( __METHOD__ . '(): API credentials are valid.' );

			return true;

		} catch ( \Exception $e ) {

			// Log that authentication test failed.
			$this->log_error( __METHOD__ . '(): API credentials are invalid; '. $e->getMessage() );

			return false;

		}

	}

	/**
	 * Get default Campaign Monitor client ID.
	 *
	 * @since  3.5
	 * @access public
	 *
	 * @uses GFAddOn::log_error()
	 * @uses GFCampaignMonitor::initialize_api()
	 * @uses GF_CampaignMonitor_API::get_clients()
	 *
	 * @return bool|array|string
	 */
	public function get_default_client( $return_id = true ) {

		// If API cannot be initialized, return false.
		if ( ! $this->initialize_api() ) {
			return false;
		}

		try {

			// Get clients.
			$clients = $this->api->get_clients();

			// Get default client.
			$client = array_shift( $clients );

			return $return_id ? $client['ClientID'] : $client;

		} catch ( \Exception $e ) {

			// Log that we could not retrieve the clients.
			$this->log_error( __METHOD__ . '(): Unable to retrieve clients; ' . $e->getMessage() );

			return false;

		}

	}

	/**
	 * Get available Campaign Monitor clients as choices.
	 *
	 * @since  3.5
	 * @access public
	 *
	 * @uses GFAddOn::log_error()
	 * @uses GFCampaignMonitor::initialize_api()
	 * @uses GF_CampaignMonitor_API::get_clients()
	 *
	 * @return array
	 */
	public function get_clients_as_choices() {

		// If API cannot be initialized, return array.
		if ( ! $this->initialize_api() ) {
			return array();
		}

		// Initialize choices array.
		$choices = array(
			array(
				'label' => esc_html__( 'Select a Client', 'gravityformscampaignmonitor' ),
				'value' => '',
			)
		);

		try {

			// Get clients.
			$clients = $this->api->get_clients();

		} catch ( \Exception $e ) {

			// Log that we could not retrieve the clients.
			$this->log_error( __METHOD__ . '(): Unable to retrieve clients; ' . $e->getMessage() );

			return array();

		}

		// If no clients were found, return.
		if ( empty( $clients ) ) {
			return array();
		}

		// Loop through array.
		foreach ( $clients as $client ) {

			// Add client as choice.
			$choices[] = array(
				'label' => esc_html( $client['Name'] ),
				'value' => esc_attr( $client['ClientID'] ),
			);

		}

		return $choices;

	}

	/**
	 * Get available Campaign Monitor lists as choices.
	 *
	 * @since  3.5
	 * @access public
	 *
	 * @param string $client_ID Client to get lists from.
	 *
	 * @uses GFAddOn::log_error()
	 * @uses GFAddOn::get_plugin_settings()
	 * @uses GFAddOn::get_setting()
	 * @uses GFCampaignMonitor::initialize_api()
	 * @uses GF_CampaignMonitor_API::get_lists()
	 * @uses GF_CampaignMonitor_API::set_client_id()
	 *
	 * @return array
	 */
	public function get_lists_as_choices( $client_id = null ) {

		// If API cannot be initialized, return array.
		if ( ! $this->initialize_api() ) {
			return array();
		}

		// Initialize choices array.
		$choices = array(
			array(
				'label' => esc_html__( 'Select a List', 'gravityformscampaignmonitor' ),
				'value' => '',
			)
		);

		// Get client ID.
		if ( empty( $client_id ) ) {

			// If a client has been selected, use it.
			if ( $this->get_setting( 'client' ) ) {

				$client_id = $this->get_setting( 'client' );

			} else {

				// Use default client ID.
				$client_id = $this->get_default_client();

			}

		}

		try {

			// Set client ID.
			$this->api->set_client_id( $client_id );

			// Get lists.
			$lists = $this->api->get_lists();

		} catch ( \Exception $e ) {

			// Log that we could not retrieve the lists.
			$this->log_error( __METHOD__ . '(): Unable to retrieve lists; ' . $e->getMessage() );

			return array();

		}

		// If no lists were found, return.
		if ( empty( $lists ) ) {
			return array();
		}

		// Loop through array.
		foreach ( $lists as $list ) {

			// Add list as choice.
			$choices[] = array(
				'label' => esc_html( $list['Name'] ),
				'value' => esc_attr( $list['ListID'] ),
			);

		}

		return $choices;

	}

	/**
	 * Check if multiple Campaign Monitor clients exist for API key.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @uses GFAddOn::log_error()
	 * @uses GFCampaignMonitor::initialize_api()
	 * @uses GF_CampaignMonitor_API::get_clients()
	 *
	 * @return bool
	 */
	public function has_multiple_clients() {

		// If API cannot be initialized, return false.
		if ( ! $this->initialize_api() ) {
			return false;
		}

		try {

			// Get clients.
			$clients = $this->api->get_clients();

			return count( $clients ) > 1;

		} catch ( \Exception $e ) {

			// Log that we could not retrieve clients.
			$this->log_error( __METHOD__ . '(): Unable to retrieve clients; ' . $e->getMessage() );

			return false;

		}

	}

	/**
	 * Has a choice been selected for the client setting?
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @uses GFAddOn::get_setting()
	 * @uses GFCampaignMonitor::has_multiple_clients()
	 *
	 * @return bool
	 */
	public function has_selected_client() {

		// If multiple clients exist, check if client has been selected.
		if ( $this->has_multiple_clients() ) {

			// Get selected client.
			$selected_client = $this->get_setting( 'client' );

			return ! empty( $selected_client );

		}

		return true;

	}





	// # UPGRADE ROUTINES ----------------------------------------------------------------------------------------------

	/**
	 * Checks if a previous version was installed and if the feeds need migrating to the framework structure.
	 *
	 * @since  3.0
	 * @access public
	 *
	 * @param string $previous_version The version number of the previously installed version.
	 *
	 * @uses GFAddOn::get_plugin_settings()
	 * @uses GFAddOn::update_plugin_settings()
	 * @uses GFCampaignMonitor::copy_feeds()
	 * @uses GFCampaignMonitor::update_paypal_delay_settings()
	 */
	public function upgrade( $previous_version ) {

		// Get previous version from pre Add-On Framework.
		if ( empty( $previous_version ) ) {
			$previous_version = get_option( 'gf_campaignmonitor_version' );
		}

		// Check if previous version is from before the Add-On Framework.
		$previous_is_pre_addon_framework = ! empty( $previous_version ) && version_compare( $previous_version, '3.0.dev1', '<' );

		// Migrate feeds to Add-On Framework.
		if ( $previous_is_pre_addon_framework ) {

			$this->copy_feeds();

			$old_settings = get_option( 'gf_campaignmonitor_settings' );

			$new_settings = array(
				'apiKey'      => $old_settings['api_key'],
				'apiClientId' => $old_settings['client_id'],
			);

			$this->update_plugin_settings( $new_settings );

			//set paypal delay setting
			$this->update_paypal_delay_settings( 'delay_campaignmonitor_subscription' );

		}

		// Check if previous version is from before the API rewrite.
		$previous_is_pre_api_rewrite = ! empty( $previous_version ) && version_compare( $previous_version, '3.5', '<' );

		// Migrate client API key plugin setting.
		if ( $previous_is_pre_api_rewrite ) {

			// Get plugin settings.
			$settings = $this->get_plugin_settings();

			// Migrate client API key to API key field.
			$settings['apiKey'] = $settings['apiClientId'];

			// Save settings.
			$this->update_plugin_settings( $settings );

		}

	}

	/**
	 * Migrate feeds to the Feed Add-On Framework.
	 *
	 * @since  3.0
	 * @access public
	 *
	 * @use GFCampaignMonitor::get_old_feeds()
	 * @use GFFeedAddOn::insert_feed()
	 */
	public function copy_feeds() {

		// Get old Campaign Monitor feeds.
		$old_feeds = $this->get_old_feeds();

		// If no feeds were found, exit.
		if ( ! $old_feeds ) {
			return;
		}

		// Initialize feed counter.
		$counter = 1;

		// Loop through old feeds.
		foreach ( $old_feeds as $old_feed ) {

			// Set feed name, form ID, active state.
			$feed_name = 'Feed ' . $counter;
			$form_id   = $old_feed['form_id'];
			$is_active = $old_feed['is_active'];

			// Prepare new feed meta.
			$new_meta = array(
				'feedName'    => $feed_name,
				'client'      => rgar( $old_feed['meta'], 'client_id' ),
				'contactList' => rgar( $old_feed['meta'], 'contact_list_id' ),
				'resubscribe' => rgar( $old_feed['meta'], 'resubscribe' ),
			);

			// Migrate field mapping.
			foreach ( $old_feed['meta']['field_map'] as $var_tag => $field_id ) {
				$new_meta[ 'listFields_' . $var_tag ] = $field_id;
			}

			// Get opt-in state.
			$optin_enabled = rgar( $old_feed['meta'], 'optin_enabled' );

			// Migrate opt-in settings.
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

			// Save new feed.
			$this->insert_feed( $form_id, $is_active, $new_meta );

			$counter++;

		}

	}

	/**
	 * Migrate the delayed payment setting for the PayPal Add-On integration.
	 *
	 * @since  3.0
	 * @access public
	 *
	 * @param string $old_delay_setting_name
	 *
	 * @uses GFAddOn::log_debug()
	 * @uses GFFeedAddOn::get_feeds_by_slug()
	 * @uses GFFeedAddOn::update_feed_meta()
	 * @uses wpdb::update()
	 */
	public function update_paypal_delay_settings( $old_delay_setting_name ) {

		global $wpdb;

		// Log that we are beginning migration.
		$this->log_debug( __METHOD__ . '(): Checking to see if there are any delay settings that need to be migrated for PayPal Standard.' );

		// Get new delay setting name.
		$new_delay_setting_name = 'delay_' . $this->_slug;

		// Get paypal feeds from old table.
		$paypal_feeds_old = $this->get_old_paypal_feeds();

		// Loop through feeds, look for delay setting and create duplicate with new delay setting for the framework version of PayPal Standard.
		if ( ! empty( $paypal_feeds_old ) ) {

			// Log that we are migrating delay settings.
			$this->log_debug( __METHOD__ . '(): Old feeds found for ' . $this->_slug . ' - copying over delay settings.' );

			// Loop through PayPal feeds.
			foreach ( $paypal_feeds_old as $old_feed ) {

				// Get feed meta.
				$meta = $old_feed['meta'];

				// If feed was not delayed, skip it.
				if ( rgempty( $old_delay_setting_name, $meta ) ) {
					continue;
				}

				$meta[ $new_delay_setting_name ] = $meta[ $old_delay_setting_name ];
				$meta                            = maybe_serialize( $meta );

				$wpdb->update( "{$wpdb->prefix}rg_paypal", array( 'meta' => $meta ), array( 'id' => $old_feed['id'] ), array( '%s' ), array( '%d' ) );

			}

		}

		// Get paypal feeds from new framework table.
		$paypal_feeds = $this->get_feeds_by_slug( 'gravityformspaypal' );

		// Loop through feeds, look for delay setting and create duplicate with new delay setting for the framework version of PayPal Standard.
		if ( ! empty( $paypal_feeds ) ) {

			// Log that we are migrating delay settings.
			$this->log_debug( __METHOD__ . '(): New feeds found for ' . $this->_slug . ' - copying over delay settings.' );

			// Loop through PayPal feeds.
			foreach ( $paypal_feeds as $feed ) {

				// Get feed meta.
				$meta = $feed['meta'];

				// If feed was not delayed, skip it.
				if ( rgempty( $old_delay_setting_name, $meta ) ) {
					continue;
				}

				$meta[ $new_delay_setting_name ] = $meta[ $old_delay_setting_name ];
				$this->update_feed_meta( $feed['id'], $meta );

			}

		}

	}

	/**
	 * Retrieve any old PayPal feeds.
	 *
	 * @since  3.0
	 * @access public
	 *
	 * @uses GFAddOn::log_debug()
	 * @uses GFAddOn::table_exists()
	 * @uses GFFormsModel::get_form_table_name()
	 * @uses wpdb::table_exists()
	 *
	 * @return bool|array
	 */
	public function get_old_paypal_feeds() {

		global $wpdb;

		// Define PayPal feeds table name.
		$table_name = $wpdb->prefix . 'rg_paypal';

		// If PayPal feeds table does not exist, exit.
		if ( ! $this->table_exists( $table_name ) ) {
			return false;
		}

		// Get forms table name.
		$form_table_name = GFFormsModel::get_form_table_name();

		// Prepare query.
		$sql             = "SELECT s.id, s.is_active, s.form_id, s.meta, f.title as form_title
				FROM {$table_name} s
				INNER JOIN {$form_table_name} f ON s.form_id = f.id";

		// Log query.
		$this->log_debug( __METHOD__ . "(): getting old paypal feeds: {$sql}" );

		// Get results.
		$results = $wpdb->get_results( $sql, ARRAY_A );

		// Log error.
		$this->log_debug( __METHOD__ . "(): error?: {$wpdb->last_error}" );

		// Get feed count.
		$count = count( $results );

		// Log feed count.
		$this->log_debug( __METHOD__ . "(): count: {$count}" );

		// Unserialize feed data.
		for ( $i = 0; $i < $count; $i ++ ) {
			$results[ $i ]['meta'] = maybe_unserialize( $results[ $i ]['meta'] );
		}

		return $results;

	}

	/**
	 * Retrieve any old feeds which need migrating to the framework.
	 *
	 * @since  3.0
	 * @access public
	 *
	 * @uses GFAddOn::table_exists()
	 * @uses GFFormsModel::get_form_table_name()
	 * @uses wpdb::table_exists()
	 *
	 * @return bool|array
	 */
	public function get_old_feeds() {

		global $wpdb;

		// Define Campaign Monitor feeds table name.
		$table_name = $wpdb->prefix . 'rg_campaignmonitor';

		// If Campaign Monitor feeds table does not exist, exit.
		if ( ! $this->table_exists( $table_name ) ) {
			return false;
		}

		// Get forms table name.
		$form_table_name = GFFormsModel::get_form_table_name();

		// Prepare query.
		$sql             = "SELECT s.id, s.is_active, s.form_id, s.meta, f.title as form_title
				FROM $table_name s
				INNER JOIN $form_table_name f ON s.form_id = f.id";

		// Get feeds.
		$results = $wpdb->get_results( $sql, ARRAY_A );

		// Unserialize feed data.
		for ( $i = 0; $i < count( $results ); $i ++ ) {
			$results[ $i ]['meta'] = maybe_unserialize( $results[ $i ]['meta'] );
		}

		return $results;

	}

}
