<?php
/**
 * Rule Matcher for complex tariff calculations
 *
 * Handles matching products to rules based on:
 * - Product categories
 * - HS codes (exact and prefix matching)
 * - Country pairs
 * - Priority ordering
 *
 * @package CustomsFeesWooCommerce
 * @since 1.1.4
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Rule Matcher class.
 *
 * @since 1.1.4
 */
class CFWC_Rule_Matcher {

	/**
	 * Match types for rules.
	 */
	const MATCH_ALL      = 'all';
	const MATCH_CATEGORY = 'category';
	const MATCH_HS_CODE  = 'hs_code';
	const MATCH_COMBINED = 'combined';

	/**
	 * Stacking modes for rules.
	 */
	const STACK_ADD       = 'add';        // Add to other rules (default).
	const STACK_OVERRIDE  = 'override'; // Replace lower priority rules.
	const STACK_EXCLUSIVE = 'exclusive'; // Only this rule applies.

	/**
	 * Rule IDs that matched the cart item but were removed by stacking modes
	 * (override/exclusive) during the most recent find_matching_rules() call.
	 *
	 * Lets the calculator tell a deliberately dropped base_includes dependency
	 * apart from one that simply never matched, so it can warn about the former.
	 *
	 * @since 1.2.0
	 * @var array<int,string>
	 */
	private $last_stacking_dropped = array();

	/**
	 * Find matching rules for a product.
	 *
	 * @since 1.1.4
	 * @param WC_Product $product The product to match.
	 * @param string     $from_country Country of origin.
	 * @param string     $to_country Destination country.
	 * @param array      $all_rules All available rules.
	 * @return array Matching rules sorted by priority.
	 */
	public function find_matching_rules( $product, $from_country, $to_country, $all_rules ) {
		$matching_rules = array();

		foreach ( $all_rules as $key => $rule ) {
			if ( $this->does_rule_match( $rule, $product, $from_country, $to_country ) ) {
				if ( empty( $rule['rule_id'] ) ) {
					$rule['rule_id'] = (string) $key;
				}
				$matching_rules[] = $rule;
			}
		}

		// Sort by priority (higher priority first).
		usort( $matching_rules, array( $this, 'sort_by_priority' ) );

		// Debug: Log matching rules with their specificity scores.
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG && defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
			foreach ( $matching_rules as $rule ) {
				$specificity = $this->calculate_specificity( $rule );
				$priority    = isset( $rule['priority'] ) ? (int) $rule['priority'] : 0;
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Debug only
				error_log(
					sprintf(
						'[CFWC] Matched rule: %s | HS: %s | Priority: %d | Specificity: %d',
						$rule['label'] ?? 'Unknown',
						$rule['hs_code_pattern'] ?? 'All',
						$priority,
						$specificity
					)
				);
			}
		}

		// Handle stacking modes.
		$final_rules = $this->apply_stacking_modes( $matching_rules );

		// Record which matched rules were removed by stacking so the calculator
		// can warn when a surviving rule depends (via base_includes) on one of
		// them — otherwise that dependency is silently dropped and the dependent
		// fee is computed on an un-compounded base.
		$this->record_stacking_dropped( $matching_rules, $final_rules );

		return $final_rules;
	}

	/**
	 * Record the rule IDs that matched but were dropped by stacking modes.
	 *
	 * Stores the set difference between the matched rules (pre-stacking) and the
	 * surviving rules (post-stacking) in $last_stacking_dropped.
	 *
	 * @since 1.2.0
	 * @param array $matched   Rules that matched before stacking was applied.
	 * @param array $survivors Rules that remain after stacking was applied.
	 */
	private function record_stacking_dropped( $matched, $survivors ) {
		$surviving_ids = array();
		foreach ( $survivors as $rule ) {
			if ( ! empty( $rule['rule_id'] ) ) {
				$surviving_ids[ $rule['rule_id'] ] = true;
			}
		}

		$dropped = array();
		foreach ( $matched as $rule ) {
			$rid = isset( $rule['rule_id'] ) ? $rule['rule_id'] : '';
			if ( '' !== $rid && ! isset( $surviving_ids[ $rid ] ) ) {
				$dropped[ $rid ] = true;
			}
		}

		$this->last_stacking_dropped = array_keys( $dropped );
	}

	/**
	 * Get the rule IDs dropped by stacking during the last find_matching_rules() call.
	 *
	 * @since 1.2.0
	 * @return array<int,string> Dropped rule IDs (empty when nothing was stacking-filtered).
	 */
	public function get_last_stacking_dropped() {
		return $this->last_stacking_dropped;
	}

	/**
	 * Check if a rule matches the given criteria.
	 *
	 * @since 1.1.4
	 * @param array      $rule The rule to check.
	 * @param WC_Product $product The product.
	 * @param string     $from_country Origin country.
	 * @param string     $to_country Destination country.
	 * @return bool True if rule matches.
	 */
	private function does_rule_match( $rule, $product, $from_country, $to_country ) {
		// First check country match (backward compatibility).
		if ( ! $this->check_country_match( $rule, $from_country, $to_country ) ) {
			return false;
		}

		// Get match type (default to 'all' for backward compatibility).
		$match_type = $rule['match_type'] ?? self::MATCH_ALL;

		switch ( $match_type ) {
			case self::MATCH_ALL:
				// Matches all products from this country pair.
				return true;

			case self::MATCH_CATEGORY:
				return $this->check_category_match( $rule, $product );

			case self::MATCH_HS_CODE:
				return $this->check_hs_code_match( $rule, $product );

			case self::MATCH_COMBINED:
				// Must match BOTH category AND HS code.
				return $this->check_category_match( $rule, $product )
					&& $this->check_hs_code_match( $rule, $product );

			default:
				return false;
		}
	}

	/**
	 * Check country match.
	 *
	 * @since 1.1.4
	 * @param array  $rule Rule to check.
	 * @param string $from_country Origin country.
	 * @param string $to_country Destination country.
	 * @return bool True if countries match.
	 */
	private function check_country_match( $rule, $from_country, $to_country ) {
		// EU member countries (as of 2024).
		$eu_countries = array(
			'AT',
			'BE',
			'BG',
			'HR',
			'CY',
			'CZ',
			'DK',
			'EE',
			'FI',
			'FR',
			'DE',
			'GR',
			'HU',
			'IE',
			'IT',
			'LV',
			'LT',
			'LU',
			'MT',
			'NL',
			'PL',
			'PT',
			'RO',
			'SK',
			'SI',
			'ES',
			'SE',
		);

		// Handle backward compatibility - 'country' field is the destination.
		$rule_from = $rule['from_country'] ?? $rule['origin_country'] ?? '';
		$rule_to   = $rule['to_country'] ?? $rule['country'] ?? '';

		// Check FROM country.
		$from_match = false;
		if ( empty( $rule_from ) ) {
			$from_match = true; // Any origin.
		} elseif ( 'EU' === $rule_from ) {
			// Check if origin is an EU country.
			$from_match = in_array( $from_country, $eu_countries, true );
		} else {
			$from_match = ( $rule_from === $from_country );
		}

		// Check TO country.
		$to_match = false;
		if ( empty( $rule_to ) ) {
			$to_match = true; // Any destination.
		} elseif ( 'EU' === $rule_to ) {
			// Check if destination is an EU country.
			$to_match = in_array( $to_country, $eu_countries, true );
		} else {
			$to_match = ( $rule_to === $to_country );
		}

		return $from_match && $to_match;
	}

	/**
	 * Check category match.
	 *
	 * @since 1.1.4
	 * @param array      $rule Rule to check.
	 * @param WC_Product $product Product to check.
	 * @return bool True if categories match.
	 */
	private function check_category_match( $rule, $product ) {
		if ( empty( $rule['category_ids'] ) ) {
			return true; // No category restriction.
		}

		// Get product categories.
		$product_cats = array();

		// For variations, get parent product categories.
		$product_id = $product->get_parent_id() ? $product->get_parent_id() : $product->get_id();
		$terms      = get_the_terms( $product_id, 'product_cat' );

		if ( $terms && ! is_wp_error( $terms ) ) {
			foreach ( $terms as $term ) {
				$product_cats[] = $term->term_id;
				// Also include parent categories.
				$ancestors    = get_ancestors( $term->term_id, 'product_cat' );
				$product_cats = array_merge( $product_cats, $ancestors );
			}
		}

		// Check if any product category matches rule categories.
		$rule_categories = is_array( $rule['category_ids'] )
			? $rule['category_ids']
			: json_decode( $rule['category_ids'], true );

		if ( ! is_array( $rule_categories ) ) {
			return true; // Invalid category data, don't restrict.
		}

		return ! empty( array_intersect( $product_cats, $rule_categories ) );
	}

	/**
	 * Check HS code match.
	 *
	 * @since 1.1.4
	 * @param array      $rule Rule to check.
	 * @param WC_Product $product Product to check.
	 * @return bool True if HS code matches.
	 */
	private function check_hs_code_match( $rule, $product ) {
		if ( empty( $rule['hs_code_pattern'] ) ) {
			return true; // No HS code restriction.
		}

		// Get product HS code using centralized helper for proper variation support.
		if ( class_exists( 'CFWC_Products_Variation_Support' ) ) {
			$customs_data = CFWC_Products_Variation_Support::get_product_customs_data( $product );
			$hs_code      = $customs_data['hs_code'];
		} else {
			// Fallback to direct meta lookup.
			$product_id = $product->get_id();
			$hs_code    = get_post_meta( $product_id, '_cfwc_hs_code', true );

			if ( empty( $hs_code ) ) {
				// Check parent for variations.
				if ( $product->get_parent_id() ) {
					$hs_code = get_post_meta( $product->get_parent_id(), '_cfwc_hs_code', true );
				}
			}
		}

		if ( empty( $hs_code ) ) {
			return false; // No HS code on product.
		}

		$pattern = $rule['hs_code_pattern'];

		// Handle comma-separated patterns FIRST (may contain wildcards).
		if ( strpos( $pattern, ',' ) !== false ) {
			// Multiple patterns (e.g., "61,62" or "72*,73*").
			$patterns = array_map( 'trim', explode( ',', $pattern ) );
			foreach ( $patterns as $p ) {
				// Check each pattern individually (may contain wildcards).
				if ( $this->match_single_hs_pattern( $p, $hs_code ) ) {
					return true;
				}
			}
			return false;
		}

		// Single pattern (may contain wildcard).
		return $this->match_single_hs_pattern( $pattern, $hs_code );
	}

	/**
	 * Match a single HS code pattern against an HS code.
	 *
	 * @since 1.3.0
	 * @param string $pattern Single HS code pattern (may contain wildcard).
	 * @param string $hs_code HS code to match against.
	 * @return bool True if pattern matches.
	 */
	private function match_single_hs_pattern( $pattern, $hs_code ) {
		if ( strpos( $pattern, '*' ) !== false ) {
			// Wildcard pattern (e.g., "72*" matches "7216", "7201.10", etc.).
			// Replace * with .* for regex and escape other special chars.
			$regex_pattern = str_replace( '\*', '.*', preg_quote( $pattern, '/' ) );
			// Match from start of string.
			return (bool) preg_match( '/^' . $regex_pattern . '/i', $hs_code );
		} else {
			// Exact match or prefix match (no wildcard).
			return $hs_code === $pattern || strpos( $hs_code, $pattern ) === 0;
		}
	}

	/**
	 * Sort rules by priority.
	 *
	 * @since 1.1.4
	 * @param array $a First rule.
	 * @param array $b Second rule.
	 * @return int Sort order.
	 */
	private function sort_by_priority( $a, $b ) {
		$priority_a = isset( $a['priority'] ) ? (int) $a['priority'] : 0;
		$priority_b = isset( $b['priority'] ) ? (int) $b['priority'] : 0;

		// Higher priority first.
		if ( $priority_a === $priority_b ) {
			// If same priority, more specific rules first.
			$specificity_a = $this->calculate_specificity( $a );
			$specificity_b = $this->calculate_specificity( $b );
			return $specificity_b - $specificity_a;
		}

		return $priority_b - $priority_a;
	}

	/**
	 * Calculate rule specificity.
	 *
	 * @since 1.1.4
	 * @param array $rule Rule to check.
	 * @return int Specificity score.
	 */
	private function calculate_specificity( $rule ) {
		$score = 0;

		// HS code is most specific - calculate based on pattern complexity.
		if ( ! empty( $rule['hs_code_pattern'] ) ) {
			$pattern = $rule['hs_code_pattern'];

			// Check for comma-separated patterns (more specific).
			if ( strpos( $pattern, ',' ) !== false ) {
				$patterns      = array_map( 'trim', explode( ',', $pattern ) );
				$pattern_count = count( $patterns );

				// Multiple specific patterns get higher score.
				$score += 80 + ( $pattern_count * 5 );

				// Add score based on the specificity of each pattern.
				foreach ( $patterns as $p ) {
					if ( strpos( $p, '*' ) === false ) {
						// Exact code in the list.
						$score += 10;
					} else {
						// Pattern with wildcard - more digits before * = more specific.
						$digits_before_wildcard = strpos( $p, '*' );
						$score                 += $digits_before_wildcard * 2;
					}
				}
			} elseif ( strpos( $pattern, '*' ) === false ) {
				// Single exact HS code (most specific).
				$score += 100;
			} else {
				// Single pattern with wildcard - score based on specificity.
				$digits_before_wildcard = strpos( $pattern, '*' );
				// Base score of 50, plus 5 points per digit before wildcard.
				// So "8506*" (4 digits) = 50 + 20 = 70.
				// And "85*" (2 digits) = 50 + 10 = 60.
				$score += 50 + ( $digits_before_wildcard * 5 );
			}
		}

		// Category is moderately specific.
		if ( ! empty( $rule['category_ids'] ) ) {
			$score += 25;
		}

		// Specific destination country.
		if ( ! empty( $rule['to_country'] ) ) {
			$score += 10;
		}

		// Specific origin country.
		if ( ! empty( $rule['from_country'] ) || ! empty( $rule['country'] ) ) {
			$score += 5;
		}

		return $score;
	}

	/**
	 * Apply stacking modes to filter rules.
	 *
	 * @since 1.1.4
	 * @param array $rules Sorted rules.
	 * @return array Filtered rules based on stacking modes.
	 */
	private function apply_stacking_modes( $rules ) {
		if ( empty( $rules ) ) {
			return $rules;
		}

		$final_rules     = array();
		$exclusive_found = false;

		foreach ( $rules as $rule ) {
			$stacking_mode = $rule['stacking_mode'] ?? self::STACK_ADD;

			if ( $exclusive_found ) {
				// Already found an exclusive rule, skip others.
				break;
			}

			if ( self::STACK_EXCLUSIVE === $stacking_mode ) {
				// This is the only rule that applies.
				return array( $rule );
			}

			if ( self::STACK_OVERRIDE === $stacking_mode ) {
				// This rule replaces all previous rules but allows subsequent ones.
				$final_rules = array( $rule );
				// Do NOT set exclusive_found - that would prevent subsequent rules!
			} else {
				// STACK_ADD - add to existing rules.
				$final_rules[] = $rule;
			}
		}

		return $final_rules;
	}

	/**
	 * Detect base_includes dependencies that are broken by stacking modes.
	 *
	 * Case (a) of the stacking/compounding interaction: a rule still applies
	 * after stacking, but a rule it depends on (via base_includes) was removed
	 * by a higher-priority override/exclusive rule for the same destination — so
	 * the surviving rule's fee is computed on an incomplete (un-compounded) base.
	 *
	 * This runs at save time, so it only inspects rules that PROVABLY co-occur
	 * for some concrete product: general rules (match_type 'all'), grouped by
	 * destination and then by overlapping origin scope. Within a destination,
	 * an origin-restricted rule co-occurs with any-origin rules (and same-origin
	 * rules) for products of that origin, so each origin is analysed as
	 * "any-origin rules + that origin's rules" — exactly the set such a product
	 * matches. Rules restricted to *different* origins never co-occur and are
	 * never analysed together, so there are no false positives. Category/HS-code
	 * rules match per-product and are left to the calculator's runtime warning.
	 *
	 * The inverse case — where the dependent rule is itself dropped, so the fee
	 * disappears entirely — is intentionally NOT reported here: replacing rules
	 * is the documented purpose of override/exclusive stacking, so flagging it
	 * would nag on deliberate configurations.
	 *
	 * @since 1.2.0
	 * @param array $rules Rule set to inspect (typically the saved cfwc_rules).
	 * @return array<int,string> Labels of surviving rules with a stacking-dropped dependency.
	 */
	public static function detect_broken_dependencies( $rules ) {
		if ( ! is_array( $rules ) || empty( $rules ) ) {
			return array();
		}

		$matcher        = new self();
		$by_destination = array();

		foreach ( $rules as $key => $rule ) {
			// Only general rules can be statically proven to co-occur.
			$match_type = $rule['match_type'] ?? self::MATCH_ALL;
			if ( self::MATCH_ALL !== $match_type ) {
				continue;
			}

			$dest = $rule['to_country'] ?? $rule['country'] ?? '';
			if ( '' === $dest ) {
				continue;
			}

			if ( empty( $rule['rule_id'] ) ) {
				$rule['rule_id'] = (string) $key;
			}

			$by_destination[ $dest ][] = $rule;
		}

		$broken = array();

		foreach ( $by_destination as $group ) {
			// Split by origin scope: any-origin rules co-occur with everything;
			// origin-restricted rules co-occur only within their own origin.
			$any_origin = array();
			$by_origin  = array();
			foreach ( $group as $rule ) {
				$from = $rule['from_country'] ?? $rule['origin_country'] ?? '';
				if ( '' === $from ) {
					$any_origin[] = $rule;
				} else {
					$by_origin[ $from ][] = $rule;
				}
			}

			// One analysis set per concrete origin (plus the any-origin baseline
			// when no origin-restricted rules exist for this destination).
			$sets = array();
			if ( empty( $by_origin ) ) {
				$sets[] = $any_origin;
			} else {
				foreach ( $by_origin as $origin_rules ) {
					$sets[] = array_merge( $any_origin, $origin_rules );
				}
			}

			foreach ( $sets as $set ) {
				foreach ( $matcher->find_broken_dependencies_in_set( $set ) as $label ) {
					$broken[] = $label;
				}
			}
		}

		return array_values( array_unique( $broken ) );
	}

	/**
	 * Find surviving rules whose dependency is dropped by stacking, in one set.
	 *
	 * The caller passes a set of rules that all provably co-occur for some
	 * product (same destination, overlapping origin). This reproduces the
	 * runtime ordering + stacking and returns the labels of any surviving rule
	 * whose base_includes references a rule that stacking removed (case (a)).
	 *
	 * @since 1.2.0
	 * @param array $set Co-occurring rules to analyse.
	 * @return array<int,string> Labels of surviving rules with a dropped dependency.
	 */
	private function find_broken_dependencies_in_set( $set ) {
		if ( count( $set ) < 2 ) {
			return array();
		}

		usort( $set, array( $this, 'sort_by_priority' ) );
		$survivors = $this->apply_stacking_modes( $set );

		$survivor_ids = array();
		foreach ( $survivors as $survivor ) {
			if ( ! empty( $survivor['rule_id'] ) ) {
				$survivor_ids[ $survivor['rule_id'] ] = true;
			}
		}

		// Dropped = matched in this set but not surviving.
		$dropped = array();
		foreach ( $set as $candidate ) {
			$candidate_id = $candidate['rule_id'] ?? '';
			if ( '' !== $candidate_id && ! isset( $survivor_ids[ $candidate_id ] ) ) {
				$dropped[ $candidate_id ] = true;
			}
		}

		if ( empty( $dropped ) ) {
			return array();
		}

		$found = array();
		foreach ( $survivors as $survivor ) {
			$deps = isset( $survivor['base_includes'] ) && is_array( $survivor['base_includes'] )
				? $survivor['base_includes']
				: array();

			foreach ( $deps as $dep_id ) {
				if ( isset( $dropped[ $dep_id ] ) ) {
					$found[] = ( isset( $survivor['label'] ) && '' !== $survivor['label'] )
						? $survivor['label']
						: $survivor['rule_id'];
					break;
				}
			}
		}

		return $found;
	}

	/**
	 * Derive a stable signature for a set of broken-dependency labels.
	 *
	 * Used to key a merchant's dismissal of the broken-dependency notice to the
	 * specific conflict they saw: if the set of affected rules later changes,
	 * the signature changes and the notice re-surfaces. Order-independent.
	 *
	 * @since 1.2.0
	 * @param array $labels Labels returned by detect_broken_dependencies().
	 * @return string Signature hash (empty string for an empty set).
	 */
	public static function broken_dependency_signature( $labels ) {
		$labels = array_map( 'strval', (array) $labels );
		if ( empty( $labels ) ) {
			return '';
		}
		sort( $labels );
		return md5( implode( '|', $labels ) );
	}

	/**
	 * Get human-readable match description.
	 *
	 * @since 1.1.4
	 * @param array $rule Rule to describe.
	 * @return string Match description.
	 */
	public function get_match_description( $rule ) {
		$parts = array();

		// Country info.
		$from = $rule['from_country'] ?? $rule['country'] ?? '';
		$to   = $rule['to_country'] ?? '';

		if ( $from && $to ) {
			$parts[] = sprintf( '%s → %s', $from, $to );
		} elseif ( $from ) {
			$parts[] = sprintf( 'From %s', $from );
		} elseif ( $to ) {
			$parts[] = sprintf( 'To %s', $to );
		}

		// Category info.
		if ( ! empty( $rule['category_ids'] ) ) {
			$category_ids = is_array( $rule['category_ids'] )
				? $rule['category_ids']
				: json_decode( $rule['category_ids'], true );

			if ( is_array( $category_ids ) && count( $category_ids ) > 0 ) {
				$category_names = array();
				foreach ( $category_ids as $cat_id ) {
					$term = get_term( $cat_id, 'product_cat' );
					if ( $term && ! is_wp_error( $term ) ) {
						$category_names[] = $term->name;
					}
				}
				if ( ! empty( $category_names ) ) {
					$parts[] = 'Categories: ' . implode( ', ', $category_names );
				}
			}
		}

		// HS code info.
		if ( ! empty( $rule['hs_code_pattern'] ) ) {
			$parts[] = 'HS Code: ' . $rule['hs_code_pattern'];
		}

		return ! empty( $parts ) ? implode( ' | ', $parts ) : 'All products';
	}
}
