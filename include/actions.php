<?php
/**
 * Actions added to action hooks to implement fields and saving of country info.
 */

if ( ! defined( 'ABSPATH' ) ) {
 	exit; // Don't access directly
}

/**
 * Adds the `country_name` field to the term form.
 *
 * @param string|object $edit_lang The edited object, if term is being edited.
 */
function plc_add_country_name_field( $edit_lang = '' ) {
  if ( $edit_lang ) {
    $country_name = ! empty( $edit_lang ) ? get_term_meta( $edit_lang->term_id, 'country_name', true ) : '';
  }
  ?>
  <div class="form-field">
    <label for="country_name"><?php esc_html_e( 'Country Name', 'polylang-countries' ); ?></label>
    <?php
    printf(
      '<input name="country_name" id="country_name" type="text" value="%s" size="40"/>',
      ! empty( $country_name ) ? esc_attr( $country_name ) : ''
    );
    ?>
    <p><?php esc_html_e( 'The name of the country for this locale.', 'polylang-countries' ); ?></p>
  </div>
  <?php
}
add_action( 'pll_language_edit_form_fields', 'plc_add_country_name_field' );
add_action( 'pll_language_add_form_fields', 'plc_add_country_name_field' );

/**
 * Saves the `country_name` data to term meta when saving.
 *
 * @param  array $args  Submitted args for the create/update of term.
 */
function plc_save_country_name( $args ) {
  if ( isset( $args['country_name'] ) ) {
    $locale_slug = sanitize_text_field( $args['slug'] );
    $country_name = sanitize_text_field( $args['country_name'] );
    $language = get_term_by( 'slug', $locale_slug, 'language' );
    $success = update_term_meta( $language->term_id, 'country_name', $country_name );
  }
}
add_action( 'pll_update_language', 'plc_save_country_name' );
add_action( 'pll_add_language', 'plc_save_country_name' );
