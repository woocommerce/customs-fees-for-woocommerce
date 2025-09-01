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
 * @since 1.2.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Rule Matcher class.
 *
 * @since 1.2.0
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
	const STACK_ADD       = 'add';        // Add to other rules (default)
	const STACK_OVERRIDE  = 'override'; // Replace lower priority rules
	const STACK_EXCLUSIVE = 'exclusive'; // Only this rule applies

	/**
	 * Find matching rules for a product.
	 *
	 * @since 1.2.0
	 * @param WC_Product $product The product to match.
	 * @param string     $from_country Country of origin.
	 * @param string     $to_country Destination country.
	 * @param array      $all_rules All available rules.
	 * @return array Matching rules sorted by priority.
	 */
	public function find_matching_rules( $product, $from_country, $to_country, $all_rules ) {
		$matching_rules = array();

		foreach ( $all_rules as $rule_id => $rule ) {
			if ( $this->does_rule_match( $rule, $product, $from_country, $to_country ) ) {
				$rule['rule_id']  = $rule_id;
				$matching_rules[] = $rule;
			}
		}

		// Sort by priority (higher priority first).
		usort( $matching_rules, array( $this, 'sort_by_priority' ) );

		// Handle stacking modes.
		return $this->apply_stacking_modes( $matching_rules );
	}

	/**
	 * Check if a rule matches the given criteria.
	 *
	 * @since 1.2.0
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
	 * @since 1.2.0
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
	 * @since 1.2.0
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
	 * @since 1.2.0
	 * @param array      $rule Rule to check.
	 * @param WC_Product $product Product to check.
	 * @return bool True if HS code matches.
	 */
	private function check_hs_code_match( $rule, $product ) {
		if ( empty( $rule['hs_code_pattern'] ) ) {
			return true; // No HS code restriction.
		}

		// Get product HS code.
		$product_id = $product->get_id();
		$hs_code    = get_post_meta( $product_id, '_cfwc_hs_code', true );

		if ( empty( $hs_code ) ) {
			// Check parent for variations.
			if ( $product->get_parent_id() ) {
				$hs_code = get_post_meta( $product->get_parent_id(), '_cfwc_hs_code', true );
			}
		}

		if ( empty( $hs_code ) ) {
			return false; // No HS code on product.
		}

		$pattern = $rule['hs_code_pattern'];

		// Handle different pattern types.
		if ( strpos( $pattern, '*' ) !== false ) {
			// Wildcard pattern (e.g., "6109*" matches "6109.10.00").
			$regex_pattern = str_replace( '*', '.*', preg_quote( $pattern, '/' ) );
			return preg_match( '/^' . $regex_pattern . '$/i', $hs_code );
		} elseif ( strpos( $pattern, ',' ) !== false ) {
			// Multiple patterns (e.g., "61,62" matches codes starting with 61 or 62).
			$patterns = array_map( 'trim', explode( ',', $pattern ) );
			foreach ( $patterns as $p ) {
				if ( strpos( $hs_code, $p ) === 0 ) {
					return true;
				}
			}
			return false;
		} else {
			// Exact match or prefix match.
			return $hs_code === $pattern || strpos( $hs_code, $pattern ) === 0;
		}
	}

	/**
	 * Sort rules by priority.
	 *
	 * @since 1.2.0
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
	 * @since 1.2.0
	 * @param array $rule Rule to check.
	 * @return int Specificity score.
	 */
	private function calculate_specificity( $rule ) {
		$score = 0;

		// HS code is most specific.
		if ( ! empty( $rule['hs_code_pattern'] ) && strpos( $rule['hs_code_pattern'], '*' ) === false ) {
			$score += 100; // Exact HS code.
		} elseif ( ! empty( $rule['hs_code_pattern'] ) ) {
			$score += 50; // HS code pattern.
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
	 * @since 1.2.0
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
	 * Get human-readable match description.
	 *
	 * @since 1.2.0
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
