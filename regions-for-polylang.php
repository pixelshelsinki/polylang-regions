<?php
/**
 * Plugin Name:     Regions For Polylang
 * Plugin URI:      PLUGIN SITE HERE
 * Description:     Adds support for regions in directory url patterns.
 * Author:          Pixels
 * Author URI:      https://pixels.fi
 * Text Domain:     regions-for-polylang
 * Domain Path:     /languages
 * Version:         0.1.0
 *
 * @package         Regions_For_Polylang
 */

/**
* Links model for use when the country and language code is added in url as directories
* for example mysite.com/gb/en/something
* implements the "links_model interface"
*
* @since 0.1.0
*/
class RFP_Links_Directory_Regions extends PLL_Links_Directory {

  /**
  * Constructor
  *
  * @since 0.1.0
  *
  * @param object $model PLL_Model instance
  */
  public function __construct( &$model ) {
    parent::__construct( $model );

    if ( did_action( 'pll_init' ) ) {
      $this->init();
    } else {
      add_action( 'pll_init', array( $this, 'init' ) );
    }
  }

  public function init() {
		if ( did_action( 'setup_theme' ) ) {
			$this->add_permastruct();
		} else {
			add_action( 'setup_theme', array( $this, 'add_permastruct' ), 2 );
		}

    // Make sure to prepare rewrite rules when flushing
		add_action( 'pre_option_rewrite_rules', array( $this, 'prepare_rewrite_rules' ) );
  }

  public function split_locale_info_from_slug( $slug ) {
    $exploded_locale = explode( '-', $slug );
    $locale = array(
      'country' => isset($exploded_locale[1]) ? $exploded_locale[1] : $exploded_locale[0],
      'language'=> $exploded_locale[0]
    );
    return $locale;
  }

  public function split_country_lang_info_from_slug( $slugs ) {
    $locales = array(
      'countries' => array(),
      'languages' => array()
    );

    foreach ($slugs as $key => $slug) {
      $exploded_locale = explode( '-', $slug );
      $locales['countries'][] = isset($exploded_locale[1]) ? $exploded_locale[1] : $exploded_locale[0];
      $locales['languages'][] = $exploded_locale[0];
    }
    return $locales;
  }

  /**
	 * Adds the country and language codes to the url
	 * links_model interface
	 *
	 * @since 0.1.0
	 *
	 * @param string $url  url to modify
	 * @param object $lang language slug
	 * @return string modified url
	 */
	public function add_language_to_link( $url, $lang ) {
		if ( ! empty( $lang ) ) {
      $locale = $this->split_locale_info_from_slug( $lang->slug );
			$base = $this->options['rewrite'] ? '' : 'language/';
			$slug = $this->options['default_lang'] == $lang->slug && $this->options['hide_default'] ? '' : $base . $locale['country'] . '/' . $locale['language'] . '/';
			if ( false === strpos( $url, $this->home . '/' . $this->root . $slug ) ) {
				return str_replace( $this->home . '/' . $this->root, $this->home . '/' . $this->root . $slug, $url );
			}
		}
		return $url;
	}

  /**
	 * Returns the url without country and language codes
	 * links_model interface
	 *
	 * @since 0.1.0
	 *
	 * @param string $url url to modify
	 * @return string modified url
	 */
	function remove_language_from_link( $url ) {
		foreach ( $this->model->get_languages_list() as $language ) {
			if ( ! $this->options['hide_default'] || $this->options['default_lang'] != $language->slug ) {
				$languages[] = $language->slug;
			}
		}

		if ( ! empty( $languages ) ) {
      $split_locale_info = $this->split_country_lang_info_from_slug( $languages );
			$pattern = str_replace( '/', '\/', $this->home . '/' . $this->root );
			$pattern = '#' . $pattern . ( $this->options['rewrite'] ? '' : 'language\/' ) . '('.implode( '|', $split_locale_info['countries'] ).')\/('.implode( '|', $split_locale_info['languages'] ).')(\/|$)#';
      $url = preg_replace( $pattern,  $this->home . '/' . $this->root, $url );
		}
		return $url;
	}

  /**
	 * Returns the language based on country and language codes in url
	 * links_model interface
	 *
	 * @since 0.1.0
	 *
	 * @param string $url optional, defaults to current url
	 * @return string language slug
	 */
	public function get_language_from_url( $url = '' ) {
		if ( empty( $url ) ) {
			$url  = ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
		}

    $language_list = $this->model->get_languages_list( array( 'fields' => 'slug' ) );
    $split_locale_info = $this->split_country_lang_info_from_slug( $language_list );
		$pattern = str_replace( '/', '\/', $this->home . '/' . $this->root . ( $this->options['rewrite'] ? '' : 'language/' ) );
		$pattern = '#' . $pattern . '('. implode( '|', $split_locale_info['countries'] ) . ')\/('. implode( '|', $split_locale_info['languages'] ) . ')(\/|$)#';
    $match_in_url = preg_match( $pattern, trailingslashit( $url ), $matches );

    if ( $match_in_url ) {
      // If the language code and country code is the same, the language might
      // be set up using just the language code, so here we check for this case.
      if ( $matches[1] === $matches[2] ) {
        if ( in_array( $matches[2] . '-' . $matches[1], $language_list ) ) {
          $slug = $matches[2] . '-' . $matches[1];
        } elseif ( in_array( $matches[1], $language_list ) ) {
          $slug = $matches[1];
        } else {
          $slug = '';
        }
      } else {
        $slug = $matches[2] . '-' . $matches[1];
      }
    } else {
      $slug = '';
    }

    return $slug;
	}

  /**
	 * Returns the home url
	 * links_model interface
	 *
	 * @since 0.1.0
	 *
	 * @param object $lang PLL_Language object
	 * @return string
	 */
	public function home_url( $lang ) {
    $locale = $this->split_locale_info_from_slug( $lang->slug );
		$base = $this->options['rewrite'] ? '' : 'language/';
		$slug = $this->options['default_lang'] == $lang->slug && $this->options['hide_default'] ? '' : '/' . $this->root . $base . $locale['country'] . '/' . $locale['language'];
		return trailingslashit( $this->home . $slug );
	}

  /**
	 * Optionaly removes 'language' in permalinks so that we get http://www.myblog/gb/en/ instead of http://www.myblog/language/gb/en/
	 *
	 * @since 1.2
	 */
	function add_permastruct() {
		// Language information always in front of the uri ( 'with_front' => false )
		// The 3rd parameter structure has been modified in WP 3.4
		// Leads to error 404 for pages when there is no language created yet
		if ( $this->model->get_languages_list() ) {
			add_permastruct( 'language', $this->options['rewrite'] ? '%country%/%language%' : 'language/%country%/%language%', array( 'with_front' => false ) );
		}
	}

  /**
   * Prepares rewrite rules filters
   *
   * @since 0.1.0
   *
   * @param array $pre not used
   * @return unmodified $pre
   */
  public function prepare_rewrite_rules( $pre ) {
    // Don't modify the rules if there is no languages created yet
    // Make sure to add filter only once and if all custom post types and taxonomies have been registered
    if ( $this->model->get_languages_list() && did_action( 'wp_loaded' ) && ! has_filter( 'language_rewrite_rules', '__return_empty_array' ) ) {
      // Suppress the rules created by WordPress for our taxonomy
      add_filter( 'language_rewrite_rules', '__return_empty_array' );

      foreach ( $this->get_rewrite_rules_filters() as $type ) {
        add_filter( $type . '_rewrite_rules', array( $this, 'rewrite_rules' ) );
      }

      add_filter( 'rewrite_rules_array', array( $this, 'rewrite_rules' ) ); // needed for post type archives
    }
    return $pre;
  }

  public function change_rewrite_rules() {

  }

  /**
	 * The rewrite rules !
	 * always make sure the default language is at the end in case the language information is hidden for default language
	 * thanks to brbrbr http://wordpress.org/support/topic/plugin-polylang-rewrite-rules-not-correct
	 *
	 * @since 0.1.0
	 *
	 * @param array $rules rewrite rules
	 * @return array modified rewrite rules
	 */
	public function rewrite_rules( $rules ) {
		$filter = str_replace( '_rewrite_rules', '', current_filter() );

		global $wp_rewrite;
		$newrules = array();

		$languages = $this->model->get_languages_list( array( 'fields' => 'slug' ) );
		if ( $this->options['hide_default'] ) {
			$languages = array_diff( $languages, array( $this->options['default_lang'] ) );
		}

		if ( ! empty( $languages ) ) {
      $split_locale_info = $this->split_country_lang_info_from_slug( $languages );
			$slug = $wp_rewrite->root . ( $this->options['rewrite'] ? '' : 'language/' ) . '('. implode( '|', $split_locale_info['countries'] ) . ')\/('. implode( '|', $split_locale_info['languages'] ) . ')/';
		}

		// For custom post type archives
		$cpts = array_intersect( $this->model->get_translated_post_types(), get_post_types( array( '_builtin' => false ) ) );
		$cpts = $cpts ? '#post_type=(' . implode( '|', $cpts ) . ')#' : '';

		foreach ( $rules as $key => $rule ) {
			// Special case for translated post types and taxonomies to allow canonical redirection
			if ( $this->options['force_lang'] && in_array( $filter, array_merge( $this->model->get_translated_post_types(), $this->model->get_translated_taxonomies() ) ) ) {

				/**
				 * Filters the rewrite rules to modify
				 *
				 * @since 1.9.1
				 *
				 * @param bool        $modify  whether to modify or not the rule, defaults to true
				 * @param array       $rule    original rewrite rule
				 * @param string      $filter  current set of rules being modified
				 * @param string|bool $archive custom post post type archive name or false if it is not a cpt archive
				 */
				if (  isset( $slug ) && apply_filters( 'pll_modify_rewrite_rule', true, array( $key => $rule ), $filter, false ) ) {
					$newrules[ $slug . str_replace( $wp_rewrite->root, '', $key ) ] = str_replace(
						array( '[8]', '[7]', '[6]', '[5]', '[4]', '[3]', '[2]', '[1]', '?' ),
						array( '[10]', '[9]', '[8]', '[7]', '[6]', '[5]', '[4]', '[3]', '?lang=$matches[2]-$matches[1]&' ),
						$rule
					); // Should be enough!
				}

				$newrules[ $key ] = $rule;
			}

			// Rewrite rules filtered by language
			elseif ( in_array( $filter, $this->always_rewrite ) || in_array( $filter, $this->model->get_filtered_taxonomies() ) || ( $cpts && preg_match( $cpts, $rule, $matches ) && ! strpos( $rule, 'name=' ) ) || ( 'rewrite_rules_array' != $filter && $this->options['force_lang'] ) ) {

				/** This filter is documented in include/links-directory.php */
				if ( apply_filters( 'pll_modify_rewrite_rule', true, array( $key => $rule ), $filter, empty( $matches[1] ) ? false : $matches[1] ) ) {
					if ( isset( $slug ) ) {
						$newrules[ $slug . str_replace( $wp_rewrite->root, '', $key ) ] = str_replace(
							array( '[8]', '[7]', '[6]', '[5]', '[4]', '[3]', '[2]', '[1]', '?' ),
							array( '[10]', '[9]', '[8]', '[7]', '[6]', '[5]', '[4]', '[3]', '?lang=$matches[2]-$matches[1]&' ),
							$rule
						); // Should be enough!
					}

					if ( $this->options['hide_default'] ) {
						$newrules[ $key ] = str_replace( '?', '?lang=' . $this->options['default_lang'] . '&', $rule );
					}
				}	else {
					$newrules[ $key ] = $rule;
				}
			}

			// Unmodified rules
			else {
				$newrules[ $key ] = $rule;
			}
		}

		// The home rewrite rule
		if ( 'root' == $filter && isset( $slug ) ) {
			$newrules[ $slug . '?$' ] = $wp_rewrite->index.'?lang=$matches[2]-$matches[1]';
		}

		return $newrules;
	}
}

function rfp_alter_links_model( $links_model_class ) {
  $links_model_class = 'RFP_Links_Directory_Regions';
  return $links_model_class;
}

add_action('pll_links_model', 'rfp_alter_links_model', 100, 1);
