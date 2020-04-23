<?php

// don't load directly
if ( ! defined( 'ABSPATH' ) ) {
	die();
}

GFForms::include_feed_addon_framework();

/**
 * Gravity Forms Zammad Add-On.
 *
 * @since     1.0
 * @package   GravityForms
 * @author    Johan VIVIEN (Hotfirenet)
 * @copyright Copyright (c) 2020, Hotfirenet
 */
class GFZammad extends GFFeedAddOn {

	/**
	 * Contains an instance of this class, if available.
	 *
	 * @since  1.0
	 * @access private
	 * @var    object $_instance If available, contains an instance of this class.
	 */
	private static $_instance = null;

	/**
	 * Defines the version of the Help Scout Add-On.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    string $_version Contains the version, defined from Zammad.php
	 */
	protected $_version = GF_ZAMMAD_VERSION;

	/**
	 * Defines the minimum Gravity Forms version required.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    string $_min_gravityforms_version The minimum version required.
	 */
	protected $_min_gravityforms_version = '2.1';

	/**
	 * Defines the plugin slug.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    string $_slug The slug used for this plugin.
	 */
	protected $_slug = 'gravityformszammad';

	/**
	 * Defines the main plugin file.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    string $_path The path to the main plugin file, relative to the plugins folder.
	 */
	protected $_path = 'gravityformszammad/zammad.php';

	/**
	 * Defines the full path to this class file.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    string $_full_path The full path.
	 */
	protected $_full_path = __FILE__;

	/**
	 * Defines the URL where this Add-On can be found.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    string The URL of the Add-On.
	 */
	protected $_url = 'http://hotfirenet.com/gf/zammad';

	/**
	 * Defines the title of this Add-On.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    string $_title The title of the Add-On.
	 */
	protected $_title = 'Gravity Forms Zammad Add-On';

	/**
	 * Defines the short title of the Add-On.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    string $_short_title The short title.
	 */
	protected $_short_title = 'Zammad';

	/**
	 * Defines if Add-On should use Gravity Forms servers for update data.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    bool
	 */
	protected $_enable_rg_autoupgrade = true;

	/**
	 * Defines if only the first matching feed will be processed.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    bool
	 */
	protected $_single_feed_submission = true;

	/**
	 * Defines the capability needed to access the Add-On settings page.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    string $_capabilities_settings_page The capability needed to access the Add-On settings page.
	 */
	protected $_capabilities_settings_page = 'gravityforms_zammad';

	/**
	 * Defines the capability needed to access the Add-On form settings page.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    string $_capabilities_form_settings The capability needed to access the Add-On form settings page.
	 */
	protected $_capabilities_form_settings = 'gravityforms_zammad';

	/**
	 * Defines the capability needed to uninstall the Add-On.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    string $_capabilities_uninstall The capability needed to uninstall the Add-On.
	 */
	protected $_capabilities_uninstall = 'gravityforms_zammad_uninstall';

	/**
	 * Defines the capabilities needed for the Help Scout Add-On
	 *
	 * @since  1.0
	 * @access protected
	 * @var    array $_capabilities The capabilities needed for the Add-On
	 */
	protected $_capabilities = array( 'gravityforms_zammad', 'gravityforms_zammad_uninstall' );

	/**
	 * Defines the capabilities needed for the Help Scout Add-On
	 *
	 * @since  1.0
	 * @access protected
	 * @var    array $_capabilities The capabilities needed for the Add-On
	 */
	protected $zammad_url = '';

	/**
	 * Defines the capabilities needed for the Help Scout Add-On
	 *
	 * @since  1.0
	 * @access protected
	 * @var    array $_capabilities The capabilities needed for the Add-On
	 */
	protected $zammad_token = '';

	/**
	 * Contains an instance of the Zammad API library, if available.
	 *
	 * @since  1.0
	 * @access protected
	 * @var    GF_Zammad_API $api If available, contains an instance of the Zammad API library.
	 */
	protected $api = null;

	/**
	 * Get instance of this class.
	 *
	 * @since  1.0
	 * @access public
	 * @static
	 *
	 * @return $_instance
	 */
	public static function get_instance() {

		if ( null === self::$_instance ) {
			self::$_instance = new GFZammad();
		}

		return self::$_instance;

	}

	/**
	 * Register needed plugin hooks and PayPal delayed payment support.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @uses GFAddOn::is_gravityforms_supported()
	 * @uses GFFeedAddOn::add_delayed_payment_support()
	 */
	public function init() {

		parent::init();

		if ( $this->is_gravityforms_supported( '2.0-beta-3' ) ) {
			add_filter( 'gform_entry_detail_meta_boxes', array( $this, 'register_meta_box' ), 10, 3 );
		} else {
			add_action( 'gform_entry_detail_sidebar_middle', array( $this, 'add_entry_detail_panel' ), 10, 2 );
		}

		$settings = $this->get_api_settings();

		$this->zammad_url   = $settings['zammadURL'];
		$this->zammad_token = $settings['zammadToken'];
	}

	// # PLUGIN SETTINGS -----------------------------------------------------------------------------------------------

	/**
	 * Setup plugin settings fields.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @uses GFZammad::plugin_settings_description()
	 *
	 * @return array
	 */
	public function plugin_settings_fields() {
		return array(
			array(
				'title'       => esc_html__( 'Zammad Informations', 'gravityformszammad' ),
				'description' => $this->plugin_settings_description(),
				'fields'      => array(
					array(
						'name'              => 'zammadURL',
						'label'             => esc_html__( 'Zammad URL', 'gravityformszammad' ),
						'type'              => 'zammad_url',
						'class'             => 'medium',
						'feedback_callback' => array( $this, 'is_valid_plugin_key' ),
					),
					array(
						'name'              => 'zammadToken',
						'label'             => esc_html__( 'Zammad Token', 'gravityformszammad' ),
						'type'              => 'zammad_token',
						'class'             => 'medium',
						'feedback_callback' => array( $this, 'is_valid_plugin_key' ),
					),
				),

				array(
					'type'     => 'save',
					'messages' => array( 'success' => esc_html__( 'Settings updated successfully', 'gravityformsauthorizenet' ) )

				),
			),
		);
	}

	/**
	 * Prepare plugin settings description.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @return string
	 */
	public function plugin_settings_description() {

		// Prepare description.
		$description = sprintf(
			'<p>%s</p>',
			sprintf(
				esc_html__( 'Zammad is an open source helpdesk system.%2$s', 'gravityformszammad' ),
				'<a href="http://www.zammad.org/" target="_blank">', '</a>'
			)
		);

		return $description;

	}

	public function settings_zammad_url( $field, $echo = true ) {

		$zammad_url_field = $this->settings_text( $field, false );

		if ( $echo ) {
			echo $zammad_url_field;
		}

		return $zammad_url_field;
	}

	public function settings_zammad_token( $field, $echo = true ) {

		$zammad_token_field = $this->settings_text( $field, false );

		if ( $echo ) {
			echo $zammad_token_field;
		}

		return $zammad_token_field;
	}

	public function uninstall() {
		parent::uninstall();
		delete_option( 'gravityformsaddon_zammad_version' );
		delete_option( 'gf_zammad_api_url' );
		delete_option( 'gf_zammad_api_token' );
	}

	/**
	 * Defines the supported notification events.
	 *
	 * @since 1.0
	 *
	 * @param array $form The current form.
	 *
	 * @return array
	 */
	public function supported_notification_events( $form ) {

		$slug = $this->get_slug();

		return array(
			"{$slug}_conversation_created" => __( 'Help Scout Conversation Created', 'gravityformszammad' ),
		);

	}

	// # FEED SETTINGS -------------------------------------------------------------------------------------------------

	/**
	 * Setup fields for feed settings.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @uses GFAddOn::get_default_feed_name()
	 *
	 * @return array
	 */
	public function feed_settings_fields() {
		// Build base fields array.
		$base_fields = array(
			array(
				'title'  => '',
				'fields' => array(
					array(
						'name'          => 'feedName',
						'label'         => esc_html__( 'Feed Name', 'gravityformszammad' ),
						'type'          => 'text',
						'required'      => true,
						'class'         => 'medium',
						'tooltip'       => sprintf(
							'<h6>%s</h6>%s',
							esc_html__( 'Name', 'gravityformszammad' ),
							esc_html__( 'Enter a feed name to uniquely identify this setup.', 'gravityformszammad' )
						),
					)
				),
			),
		);

		// Build conditional logic fields.
		$conditional_fields = array(
			array(
				'fields' => array(
					array(
						'name'           => 'feedCondition',
						'type'           => 'feed_condition',
						'label'          => esc_html__( 'Conditional Logic', 'gravityformszammad' ),
						'checkbox_label' => esc_html__( 'Enable', 'gravityformszammad' ),
						'instructions'   => esc_html__( 'Create if', 'gravityformszammad' ),
						'tooltip'        => sprintf(
							'<h6>%s</h6>%s',
							esc_html__( 'Conditional Logic', 'gravityformszammad' ),
							esc_html__( 'When conditional logic is enabled, form submissions will only be created when the condition is met. When disabled, all form submissions will be created.', 'gravityformszammad' )
						),
					),
				),
			),
		);

		$create_post_fields = $this->feed_settings_fields_zammad();

		return array_merge( $base_fields, $create_post_fields, $conditional_fields );

	}

	/**
	 * Setup fields for post creation feed settings.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @uses GFZammad::get_default_field_merge_tag()
	 *
	 * @return array
	 */
	public function feed_settings_fields_zammad() {
		// Setup fields array.
		$fields = array(
			'content'  => array(
				'title'  => esc_html__( 'Ticket Content', 'gravityformszammad' ),
				'fields' => array(
					array(
						'name'          => 'ticket_email_customer',
						'label'         => esc_html__( 'Customer email', 'gravityformszammad' ),
						'type'          => 'text',
						'required'      => true,
						'class'         => 'medium merge-tag-support mt-position-right mt-hide_all_fields',
						'default_value' => $this->get_default_field_merge_tag( 'email' ),
					),
					array(
						'name'          => 'ticket_title',
						'label'         => esc_html__( 'Title ticket', 'gravityformszammad' ),
						'type'          => 'text',
						'required'      => true,
						'class'         => 'medium merge-tag-support mt-position-right mt-hide_all_fields',
						'default_value' => $this->get_default_field_merge_tag( 'title' ),
					),
					array(
						'label'          => esc_html__( 'Group ID', 'gravityformszammad' ),
						'name'           => 'group_id',
						'type'           => 'select',
						'default_value'  => 1,
						'required'       => true,
						'tooltip'        => sprintf(
							'<h6>%s</h6>%s',
							esc_html__( 'Group ID', 'gravityformszammad' ),
							esc_html__( 'Select the group.', 'gravityformszammad' )
						),
						'choices'        => $this->get_zamamd_ticket_groups(),
					),
					array(
						'label'          => esc_html__( 'State ID', 'gravityformszammad' ),
						'name'           => 'state_id',
						'type'           => 'select',
						'default_value'  => 1,
						'required'       => true,
						'tooltip'        => sprintf(
							'<h6>%s</h6>%s',
							esc_html__( 'State ID', 'gravityformszammad' ),
							esc_html__( 'Select the ticket state.', 'gravityformszammad' )
						),
						'choices'        => $this->get_zamamd_ticket_state(),
					),
					array(
						'label'          => esc_html__( 'Priority ID', 'gravityformszammad' ),
						'name'           => 'priority_id',
						'type'           => 'select',
						'default_value'  => 1,
						'required'       => true,
						'tooltip'        => sprintf(
							'<h6>%s</h6>%s',
							esc_html__( 'Priority ID', 'gravityformszammad' ),
							esc_html__( 'Select the ticket priority.', 'gravityformszammad' )
						),
						'choices'        => $this->get_zamamd_ticket_priorities(),
					),
					array(
						'name'          => 'article_to',
						'label'         => esc_html__( 'To', 'gravityformszammad' ),
						'type'          => 'text',
						'required'      => true,
						'class'         => 'medium merge-tag-support mt-position-right mt-hide_all_fields',
						'tooltip'        => sprintf(
							'<h6>%s</h6>%s',
							esc_html__( 'To', 'gravityformszammad' ),
							esc_html__( 'Email address.', 'gravityformszammad' )
						),
					),
					array(
						'name'          => 'article_subject',
						'label'         => esc_html__( 'Subject ticket', 'gravityformszammad' ),
						'type'          => 'text',
						'required'      => false,
						'class'         => 'medium merge-tag-support mt-position-right mt-hide_all_fields',
						'default_value' => $this->get_default_field_merge_tag( 'subject' ),
					),
					array(
						'name'          => 'article_content',
						'label'         => esc_html__( 'Content', 'gravityformszammad' ),
						'type'          => 'textarea',
						'required'      => true,
						'class'         => 'medium merge-tag-support mt-position-right mt-hide_all_fields',
						'default_value' => $this->get_default_field_merge_tag( 'message' ),
						'tooltip'       => sprintf(
							'<h6>%s</h6>%s',
							esc_html__( 'Post Content', 'gravityformszammad' ),
							esc_html__( "Define the post's content. File upload field merge tags used within the post content will automatically have their files uploaded to the media library and associated with the post.", 'gravityformsadvancedpostcreation' )
						),
					),
					array(
						'label'          => esc_html__( 'Type', 'gravityformszammad' ),
						'name'           => 'type',
						'type'           => 'select',
						'default_value'  => 1,
						'required'       => true,
						'tooltip'        => sprintf(
							'<h6>%s</h6>%s',
							esc_html__( 'Type', 'gravityformszammad' ),
							esc_html__( 'Select the ticket type.', 'gravityformszammad' )
						),
						'choices'        => $this->get_zamamd_ticket_types(),
						'default_value' => 'web',
					),
					array(
						'name'          => 'ticket_tags',
						'label'         => esc_html__( 'Tags ticket', 'gravityformszammad' ),
						'type'          => 'text',
						'required'      => false,
						'class'         => 'medium merge-tag-support mt-position-right mt-hide_all_fields',
					),
				),
			),
		);

		return $fields;
	}

	/**
	 * Get the merge tag for first form field found matching field type.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @param string $field_type Field type to search for.
	 *
	 * @uses GFAddOn::get_current_form()
	 * @uses GFAPI::get_fields_by_type()
	 *
	 * @return string
	 */
	public function get_default_field_merge_tag( $field_type = '' ) {

		// If no field type was provided, return.
		if ( rgblank( $field_type ) ) {
			return '';
		}

		// Get current form.
		$form = $this->get_current_form();

		// Get form fields for field type.
		$fields = GFAPI::get_fields_by_type( $form, $field_type );

		// If no fields were found, return.
		if ( empty( $fields ) ) {
			return '';
		}

		return '{' . $fields[0]->label . ':' . $fields[0]->id . '}';

	}

	/**
	 * Setup columns for feed list table.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @return array
	 */
	public function feed_list_columns() {

		return array(
			'feedName'   => esc_html__( 'Name', 'gravityformszammad' ),
			'ticketTitle' => esc_html__( 'Title ticket', 'gravityformszammad' ),
		);

	}

	/**
	 * Enable feed duplication.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @param int|array $id The ID of the feed to be duplicated or the feed object when duplicating a form.
	 *
	 * @return bool
	 */
	public function can_duplicate_feed( $id ) {

		return true;

	}

	// # FEED PROCESSING -----------------------------------------------------------------------------------------------

	/**
	 * Determines if feed processing should happen asynchronously.
	 *
	 * @since 1.0
	 *
	 * @param array $feed  The feed currently being processed.
	 * @param array $entry The entry currently being processed.
	 * @param array $form  The form currently being processed.
	 *
	 * @return bool
	 */
	public function is_asynchronous( $feed, $entry, $form ) {
		if ( $this->_bypass_feed_delay ) {
			return false;
		}

		return parent::is_asynchronous( $feed, $entry, $form );
	}

	/**
	 * Send webhook request.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @param array $feed  The current Feed object.
	 * @param array $entry The current Entry object.
	 * @param array $form  The current Form object.
	 *
	 * @uses GFAddOn::log_debug()
	 * @uses GFAddOn::log_error()
	 * @uses GFCommon::replace_variables()
	 * @uses GFFeedAddOn::add_feed_error()
	 * @uses GF_Webhooks::get_request_data()
	 */
	public function process_feed( $feed, $entry, $form ) {

		// If this entry already has a Zammad Ticket, exit.
		if ( gform_get_meta( $entry['id'], 'zammad_ticket_id' ) ) {
			$this->log_debug( __METHOD__ . '(): Entry already has a Zammad ticket associated to it. Skipping processing.' );
			return;
		}

		$email = GFCommon::replace_variables( rgars( $feed, 'meta/ticket_email_customer' ), $form, $entry );

		// If the email address is invalid, exit.
		if ( GFCommon::is_invalid_or_empty_email( $email ) ) {
			$this->add_feed_error( esc_html__( 'Unable to create ticket because a valid email address was not provided.', 'gravityformszammad' ), $feed, $entry, $form );
			return;
		}

		//Todo
		$customer_id = '';

		$data = array(
			'title'       => GFCommon::replace_variables( rgars( $feed, 'meta/ticket_title' ), $form, $entry ),
			'group_id'    => $feed['meta']['group_id'],
			'state_id'    => $feed['meta']['state_id'],
			'priority_id' => $feed['meta']['priority_id'],
			'customer_id' => $customer_id,
			'article'  => array(
				'from'         => $email,
				'to'           => $feed['meta']['article_to'],
				'subject'      => GFCommon::replace_variables( rgars( $feed, 'meta/article_subject' ), $form, $entry ),
				'body'         => GFCommon::replace_variables( rgars( $feed, 'meta/article_content' ), $form, $entry ),
				'content_type' => 'text/html',
				'type'         => 'web',
				'internal'     => 'false',
			),
			'tags'     => $this->merge_tags( $feed, $entry, $form ),
			'note'     => GFCommon::replace_variables( rgars( $feed, 'meta/ticket_note' ), $form, $entry ),
		);

		if ( empty( $data['customer_id'] ) ) {
			$data['customer_id']     = 'guess:'.$email;
			$data['article']['from'] = $email;
			$data['article']['to']   = $email;
		}

		$this->log_debug( __METHOD__ . '(): data: ' . print_r( $data, true )  );

		$ticket = $this->api()->create_ticket( $data );

		if( is_wp_error( $ticket ) ) {

			// Log that conversation was not created.
			$this->add_feed_error( 'Ticket was not created; ' . $ticket->get_error_message(), $feed, $entry, $form );
			$this->maybe_log_error_data( $ticket );

			return;

		} else {

			// Add ticket infos to entry meta.
			gform_update_meta( $entry['id'], 'zammad_ticket_id', $ticket['id'] );
			gform_update_meta( $entry['id'], 'zammad_ticket_number', $ticket['number'] );
			$this->log_debug( __METHOD__ . 'Entry ID: ' . $entry['id'] . ' Ticket ID: ' . $ticket['id'] );


			// Log that conversation was created.
			$this->log_debug( __METHOD__ . '(): ticket has been created.' );

			GFAPI::send_notifications( $form, $entry, $this->get_slug() . '_ticket_created' );
		}

		/**
		 * Fired after create ticket has been executed.
		 *
		 * @since 1.0
		 *
		 * @param WP_Error|array $response The response or WP_Error on failure.
		 * @param array          $feed     The current Feed object.
		 * @param array          $entry    The current Entry object.
		 * @param array          $form     The current Form object.
		 */
		gf_do_action( array( 'gform_zammad_post_request', $form['id'], $feed['id'] ), $ticket, $feed, $entry, $form );
	}

	// # ENTRY DETAILS -------------------------------------------------------------------------------------------------

	/**
	 * Add the Help Scout details meta box to the entry detail page.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @param array $meta_boxes The properties for the meta boxes.
	 * @param array $entry      The entry currently being viewed/edited.
	 * @param array $form       The form object used to process the current entry.
	 *
	 * @return array
	 */
	public function register_meta_box( $meta_boxes, $entry, $form ) {

		if ( $this->get_active_feeds( $form['id'] ) ) {
			$meta_boxes[ $this->_slug ] = array(
				'title'    => esc_html__( 'Helpdesk Details', 'gravityformszammad' ),
				'callback' => array( $this, 'add_details_meta_box' ),
				'context'  => 'side',
			);
		}

		return $meta_boxes;

	}	

	/**
	 * The callback used to echo the content to the meta box.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @param array $args An array containing the form and entry objects.
	 *
	 * @uses GFHelpScout::get_panel_markup()
	 */
	public function add_details_meta_box( $args ) {

		echo $this->get_panel_markup( $args['form'], $args['entry'] );

	}

	/**
	 * Generate the markup for use in the meta box.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @param array $form  The current Form object.
	 * @param array $entry The current Entry object.
	 *
	 * @uses GFAddOn::log_error()
	 * @uses GFCommon::format_date()
	 * @uses GFZammad::get_entry_zammad_id()
	 *
	 * @return string
	 */
	public function get_panel_markup( $form, $entry ) {

		// Initialize HTML string.
		$html = '';

		//$html .= '<pre>' . print_r( $entry ) . '</pre>';
		$html .= 'Ticket: ' . $this->get_entry_zammad_id( $entry ) .'<br>';
		$html .= 'Number: ' . $this->get_entry_zammad_number( $entry ) .'<br>';
		$html .= esc_html__( 'Ticket ID', 'gravityformszammad' ) . ': <a href="' . $this->zammad_url . '/#ticket/zoom/' . $this->get_entry_zammad_id( $entry ) . '" target="_blank">Show ticket</a><br /><br />';
	
		return $html;
	}

	// # HELPER METHODS ------------------------------------------------------------------------------------------------

	private function get_api_settings() {

		$settings = $this->get_plugin_settings();

		return array(
			'zammadURL'    => rgar( $settings, 'zammadURL' ),
			'zammadToken'  => rgar( $settings, 'zammadToken' )
		);
	}

	/**
	 * @return false|GF_Zammad_API
	 */
	public function api() {

		if ( ! is_null( $this->api ) ) {
			return $this->api;
		}

		require_once( 'includes/class-gf-zammad-api.php' );

		$this->api = new GF_Zammad_API( $this->zammad_url, $this->zammad_token );

		return $this->api;
	}

	/**
	 * Writes the supplied error data to the error log.
	 *
	 * @since 1.0
	 *
	 * @param WP_Error|mixed $error_data A WP_Error object or the error data to be written to the log.
	 */
	public function maybe_log_error_data( $error_data ) {
		if ( is_wp_error( $error_data ) ) {
			$error_data = $error_data->get_error_data();
		}

		if ( empty( $error_data ) ) {
			return;
		}

		$backtrace = debug_backtrace();
		$method    = $backtrace[1]['class'] . '::' . $backtrace[1]['function'];
		$this->log_error( $method . '(): ' . print_r( $error_data, true ) );
	}

	/**
	 * Add the helpdesk ID and number entry meta property.
	 *
	 * @since  1.0
	 * @access public
	 * @param  array $entry_meta An array of entry meta already registered with the gform_entry_meta filter.
	 * @param  int   $form_id The form id.
	 *
	 * @return array The filtered entry meta array.
	 */
	public function get_entry_meta( $entry_meta, $form_id ) {

		$entry_meta = array(
			'zammad_ticket_id' => array(
				'label'             => __( 'Zammad ticket ID', 'gravityformszammad' ),
				'is_numeric'        => true,
				'is_default_column' => false,
			),
			'zammad_ticket_number' => array(
				'label'             => __( 'Zammad ticket number', 'gravityformszammad' ),
				'is_numeric'        => true,
				'is_default_column' => false,
			),
		);

		return $entry_meta;

	}

	/**
	 * Retrieve the helpdesk zammad id for the current entry.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @param array $entry The entry currently being viewed/edited.
	 *
	 * @return string
	 */
	public function get_entry_zammad_id( $entry ) {

		// Define entry meta key.
		$key = 'zammad_ticket_id';

		// Get helpdesk ID.
		$id = rgar( $entry, $key );

		if ( empty( $id ) && rgget( 'gf_zammad' ) === 'process' ) {
			$id = gform_get_meta( $entry['id'], $key );
		}

		return $id;

	}

	/**
	 * Retrieve the helpdesk zammad number for the current entry.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @param array $entry The entry currently being viewed/edited.
	 *
	 * @return string
	 */
	public function get_entry_zammad_number( $entry ) {

		// Define entry meta key.
		$key = 'zammad_ticket_number';

		// Get helpdesk ID.
		$number = rgar( $entry, $key );

		if ( empty( $number ) && rgget( 'gf_zammad' ) === 'process' ) {
			$number = gform_get_meta( $entry['id'], $key );
		}

		return $number;

	}

	/**
	 * Merge tags.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @param array $feed  The current Feed object.
	 * @param array $entry The current Entry object.
	 * @param array $form The current Form object.
	 *
	 * @return string
	 */	
	public function merge_tags( $feed, $entry, $form ) {
		
		// Get tags.
		$tags = explode(',', rgars( $feed, 'meta/ticket_tags' ) );
		$tags = array_map( 'trim', $tags );

		// Prepare tags.
		if ( ! empty( $tags ) ) {

			// Loop through tags, replace merge tags.
			foreach ( $tags as &$tag ) {
				$tag = GFCommon::replace_variables( $tag, $form, $entry, false, false, false, 'text' );
				$tag = trim( $tag );
			}

			// Remove empty tags.
			$tags = array_filter( $tags );

		}

		return implode( ',', $tags );
	}

	/**
	 * Retrieve the helpdesk zammad ticket state.
	 *
	 * @since  1.0
	 * @access public
	 *
	 *
	 * @return array
	 */
	public function get_zamamd_ticket_state() {
		$ticket_states = $this->api()->get_ticket_states();

		$states = array();
		foreach ( $ticket_states as $ticket_state ) {
			$states[] = array(
				'label' => $ticket_state['name'],
				'value' => $ticket_state['id']
			);
		}

		return $states;
	}

	/**
	 * Retrieve the helpdesk zammad ticket type.
	 *
	 * @since  1.0
	 * @access public
	 *
	 *
	 * @return array
	 */
	public function get_zamamd_ticket_types() {
		$types = array(
			array(
				'label' =>'Email',
				'value' => 'email',
			),
			array(
				'label' =>'Web',
				'value' => 'web',
			),
			array(
				'label' =>'Phone',
				'value' => 'phone',
			),
			array(
				'label' =>'Sms',
				'value' => 'sms',
			),
		);

		return $types;
	}

	/**
	 * Retrieve the helpdesk zammad ticket priority.
	 *
	 * @since  1.0
	 * @access public
	 *
	 *
	 * @return array
	 */
	public function get_zamamd_ticket_priorities() {
		$ticket_priorities = $this->api()->get_ticket_priorities();

		$priorities = array();
		foreach ( $ticket_priorities as $ticket_prioritie ) {
			$priorities[] = array(
				'label' => $ticket_prioritie['name'],
				'value' => $ticket_prioritie['id']
			);
		}

		return $priorities;
	}

	/**
	 * Retrieve the helpdesk zammad ticket state.
	 *
	 * @since  1.0
	 * @access public
	 *
	 *
	 * @return array
	 */
	public function get_zamamd_ticket_groups() {
		$zammad_groups = $this->api()->get_groups();

		$groups = array();
		foreach ( $zammad_groups as $group ) {
			$groups[] = array(
				'label' => $group['name'],
				'value' => $group['id']
			);
		}

		return $groups;
	}

}
