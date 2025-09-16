<?php

/**
 * Custom Fields controller
 *
 * Handle custom fields related AJAX actions
 *
 * @since       5.7.0
 * @package     Email_Subscribers
 * @subpackage  Email_Subscribers/lite/includes/controllers
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ES_Custom_Fields_Controller
 *
 * @since 5.7.0
 */
class ES_Custom_Fields_Controller {

	/**
	 * Create custom field
	 *
	 * @param array $data Custom field data.
	 *
	 * @return array
	 *
	 * @since 5.7.0
	 */
	public static function create_custom_field( $data ) {
		// If data is a JSON string, decode it.
		if ( is_string( $data ) && ! empty( $data ) ) {
			$decoded_data = json_decode( $data, true );
			if ( JSON_ERROR_NONE === json_last_error() ) {
				$data = $decoded_data;
			}
		}

		// Validate required fields.
		if ( empty( $data['slug'] ) || empty( $data['label'] ) || empty( $data['type'] ) ) {
			return array(
				'success' => false,
				'message' => __( 'Missing required fields: slug, label, and type are required.', 'email-subscribers' ),
			);
		}

		// Sanitize data.
		$slug = sanitize_text_field( $data['slug'] );
		$label = sanitize_text_field( $data['label'] );
		$type = sanitize_text_field( $data['type'] );
		$meta = isset( $data['meta'] ) ? wp_json_encode( $data['meta'] ) : '';

		// Check if slug already exists.
		$existing_field = ES()->custom_fields_db->get_by( 'slug', $slug );
		if ( $existing_field ) {
			return array(
				'success' => false,
				'message' => __( 'A custom field with this slug already exists.', 'email-subscribers' ),
			);
		}

		// Insert custom field.
		$field_data = array(
			'slug'  => $slug,
			'label' => $label,
			'type'  => $type,
			'meta'  => $meta,
		);

		$field_id = ES()->custom_fields_db->insert( $field_data );

		if ( $field_id ) {
			// Create corresponding column in contacts table
			$column_added = ES()->contacts_db->add_custom_field_col_in_contacts_table( $slug, $type );
			
			if ( ! $column_added ) {
				// Log warning but don't fail the creation since the field was created successfully
				error_log( "Warning: Failed to create column '{$slug}' in contacts table for custom field ID {$field_id}" );
			}
			
			$created_field = ES()->custom_fields_db->get( $field_id );

			// Decode meta for response.
			if ( $created_field && ! empty( $created_field['meta'] ) ) {
				$created_field['meta'] = json_decode( $created_field['meta'], true );
			}

			return array(
				'success' => true,
				'data'    => $created_field,
				'message' => __( 'Custom field created successfully.', 'email-subscribers' ),
			);
		} else {
			return array(
				'success' => false,
				'message' => __( 'Failed to create custom field.', 'email-subscribers' ),
			);
		}
	}

	/**
	 * Get custom fields
	 *
	 * @return array
	 *
	 * @since 5.7.0
	 */
	public static function get_custom_fields() {
		$custom_fields = ES()->custom_fields_db->get_custom_fields();

		// Decode meta for each field.
		if ( ! empty( $custom_fields ) ) {
			foreach ( $custom_fields as &$field ) {
				if ( ! empty( $field['meta'] ) ) {
					$field['meta'] = json_decode( $field['meta'], true );
				}
			}
		}

		return array(
			'success' => true,
			'data'    => $custom_fields ? $custom_fields : array(),
		);
	}

	/**
	 * Delete custom field
	 *
	 * @param array $data Request data containing field ID.
	 *
	 * @return array
	 *
	 * @since 5.7.0
	 */
	public static function delete_custom_field( $data ) {
		if ( empty( $data['id'] ) ) {
			return array(
				'success' => false,
				'message' => __( 'Field ID is required.', 'email-subscribers' ),
			);
		}

		$field_id = absint( $data['id'] );
		$result   = ES()->custom_fields_db->delete( $field_id );

		if ( $result ) {
			return array(
				'success' => true,
				'message' => __( 'Custom field deleted successfully.', 'email-subscribers' ),
			);
		} else {
			return array(
				'success' => false,
				'message' => __( 'Failed to delete custom field.', 'email-subscribers' ),
			);
		}
	}

	/**
	 * Update custom field
	 *
	 * @param array $data Custom field data.
	 *
	 * @return array
	 *
	 * @since 5.7.0
	 */
	public static function update_custom_field( $data ) {
		if ( empty( $data['id'] ) ) {
			return array(
				'success' => false,
				'message' => __( 'Field ID is required.', 'email-subscribers' ),
			);
		}

		$field_id = absint( $data['id'] );

		// Get existing field.
		$existing_field = ES()->custom_fields_db->get( $field_id );
		if ( ! $existing_field ) {
			return array(
				'success' => false,
				'message' => __( 'Custom field not found.', 'email-subscribers' ),
			);
		}

		// Prepare update data.
		$update_data = array();

		if ( ! empty( $data['label'] ) ) {
			$update_data['label'] = sanitize_text_field( $data['label'] );
		}

		if ( ! empty( $data['type'] ) ) {
			$update_data['type'] = sanitize_text_field( $data['type'] );
		}

		if ( isset( $data['meta'] ) ) {
			$update_data['meta'] = wp_json_encode( $data['meta'] );
		}

		$result = ES()->custom_fields_db->update( $field_id, $update_data );

		if ( $result ) {
			$updated_field = ES()->custom_fields_db->get( $field_id );

			// Decode meta for response.
			if ( $updated_field && ! empty( $updated_field['meta'] ) ) {
				$updated_field['meta'] = json_decode( $updated_field['meta'], true );
			}

			return array(
				'success' => true,
				'data'    => $updated_field,
				'message' => __( 'Custom field updated successfully.', 'email-subscribers' ),
			);
		} else {
			return array(
				'success' => false,
				'message' => __( 'Failed to update custom field.', 'email-subscribers' ),
			);
		}
	}
}
