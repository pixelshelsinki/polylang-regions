<?php

/**
 * Gets the languages with the added country parameter.
 *
 * Polylang doesn't allow us to add extra props to the language object, so we
 * need to add them this way.
 *
 * @param  string $args The arguments for getting the languages.
 * @return array        An array of found languages.
 */
function plc_the_languages( $args = '' ) {
  // Get the languages using the same arguments.
  $languages = pll_the_languages( $args );

  // If it is raw, then add to the array.
  if ( $languages && isset( $args['raw'] ) ) {
    foreach ( $languages as $key => $language ) {
      $language['country_name'] = plc_get_country_name( $language );
    }
  }

  return $languages;
}

/**
 * Gets the languages sorted by country, based on locale code. Note, only
 * returns raw data.
 *
 * @param  string $args The arguments for getting the languages.
 * @return array        The countries and then languages for those countries.
 */
function plc_get_countries_and_languages( $args = '' ) {
  // Force to true.
  $args['raw'] = true;

  // Get languages
  $languages = pll_the_languages( $args );

  // Countries array.
  $countries = [];

  foreach ( $languages as $locale => $language ) {
    // Get the locale and country code.
    $exploded_locale = explode( '-', $locale );
    $country = isset( $exploded_locale[1] ) ? $exploded_locale[1] : $exploded_locale ;

    // If the country doesn't exist in the array we create it.
    if ( ! array_key_exists( $country, $countries ) ) {
      $countries[$country] = [];
    }

    // We check always in case a locale has no country name set.
    if ( empty( $countries[$country]['country'] ) ) {
      $countries[$country]['country'] = plc_get_country_name( $language );
    }

    // Add the language to the list of country languages.
    $countries[$country]['languages'][] = $language;
  }

  return $countries;
}

/**
 * Gets the current country, based on locale code.
 * @return string The country name.
 */
function plc_current_country() {
  // Get the slug.
  $current_slug = pll_current_language( 'slug' );
  // Get the term by slug.
  $language = get_term_by( 'slug', $current_slug, 'language' );

  if ( ! $language ) {
    return false;
  }

  // Get the country name meta.
  $country_name = plc_get_country_name( $language );
  return $country_name;
}

/**
 * Gets the default country, based on locale code.
 * @return string The country name.
 */
function plc_default_country() {
  // Get the default slug.
  $default_slug = pll_default_language( 'slug' );
  // Get the term by slug.
  $language = get_term_by( 'slug', $default_slug, 'language' );

  if ( ! $language ) {
    return false;
  }

  // Get the country name meta.
  $country_name = plc_get_country_name( $language );

  return $country_name;
}

/**
 * Fetches the country name from term meta.
 *
 * @param  object|array $language The language term object or array.
 * @return string                 The country.
 */
function plc_get_country_name( $language ) {
  $language_id = is_array( $language ) ? $language['id'] : $language->term_id ;
  $country_name = get_term_meta( $language_id, 'country_name', true ) ?: '' ;

  return $country_name;
}
