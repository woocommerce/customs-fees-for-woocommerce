# WordPress Development Standards & Guidelines

**Version:** 5.0  
**Last Updated:** January 2025  
**Scope:** WordPress & WooCommerce Development  
**PHP Target:** 8.1+ with backward compatibility to 7.4

---

## Table of Contents

1. [Core Development Principles](#core-development-principles)
2. [Plugin Development Best Practices](#plugin-development-best-practices)
3. [UI/UX Guidelines](#uiux-guidelines)
4. [Plugin Headers & Metadata](#plugin-headers--metadata)
5. [Avoiding Naming Collisions](#avoiding-naming-collisions)
6. [File Organization & Architecture](#file-organization--architecture)
7. [WordPress Coding Standards](#wordpress-coding-standards)
8. [Accessibility Standards](#accessibility-standards)
9. [CSS Coding Standards](#css-coding-standards)
10. [HTML Coding Standards](#html-coding-standards)
11. [JavaScript Coding Standards](#javascript-coding-standards)
12. [PHP Documentation Standards](#php-documentation-standards)
13. [JavaScript Documentation Standards](#javascript-documentation-standards)
14. [Security Implementation](#security-implementation)
15. [Data Storage Primer](#data-storage-primer)
16. [WooCommerce CRUD Objects](#woocommerce-crud-objects)
17. [WooCommerce Logging](#woocommerce-logging)
18. [Modern PHP Standards](#modern-php-standards)
19. [Asset Management](#asset-management)
20. [Internationalization & Localization](#internationalization--localization)
21. [Database Operations](#database-operations)
22. [WooCommerce Standards](#woocommerce-standards)
23. [Testing & Quality Assurance](#testing--quality-assurance)
24. [Project Structure](#project-structure)
25. [Workflow Guidelines](#workflow-guidelines)

---

## Core Development Principles

### Code Modification Rules

- **Only modify code that is explicitly requested** to be changed
- When fixing issues, make **targeted changes** only to relevant sections
- **Always test thoroughly** before submitting, especially for existing functionality
- **Preserve existing** coding style, patterns, and architecture
- When unsure, **ask for clarification** rather than making assumptions

### Development Workflow

**Research → Plan → Implement**

1. **Research:** Explore the codebase, understand existing patterns
2. **Plan:** Create a detailed implementation plan and verify approach
3. **Implement:** Execute the plan with validation checkpoints

---

## Plugin Development Best Practices

### Naming Conventions

#### Plugin Names

- **State the feature clearly**: Use functional, descriptive names
- **Avoid core/extension names**: Don't use "WooCommerce" or existing extension names in your title
- **Examples**:
  - ✅ Good: "Appointments", "Inventory Manager", "Customs Fees"
  - ❌ Bad: "VendorXYZ Bookings Plugin for WooCommerce"

#### Code Naming

- **Prefix everything**: Use unique 4-5 character prefixes
- **Avoid WordPress prefixes**: Never use `wp_`, `WordPress`, `__`, or `_`
- **Avoid parent plugin prefixes**: Don't use `woo`, `WooCommerce` as standalone prefixes
- **Examples**:

  ```php
  // ✅ CORRECT: Unique prefix
  function cfwc_calculate_fees() {}
  define( 'CFWC_VERSION', '1.0.0' );
  class CFWC_Calculator {}

  // ❌ INCORRECT: Conflicting prefixes
  function wp_calculate() {}      // Conflicts with WordPress
  function woo_calculate() {}     // Conflicts with WooCommerce
  function calculate_fees() {}    // No prefix - will conflict
  ```

### Avoiding Conflicts

#### Check for Existing Implementations

```php
// Functions
if ( ! function_exists( 'cfwc_init' ) ) {
    function cfwc_init() {
        // Implementation
    }
}

// Classes
if ( ! class_exists( 'CFWC_Plugin' ) ) {
    class CFWC_Plugin {
        // Implementation
    }
}

// Constants
if ( ! defined( 'CFWC_VERSION' ) ) {
    define( 'CFWC_VERSION', '1.0.0' );
}
```

#### Use Namespaces (PHP 5.3+)

```php
namespace CustomsFees\WooCommerce;

class Calculator {
    // No prefix needed within namespace
    public function calculate() {
        // Implementation
    }
}
```

### Security Best Practices

#### Prevent Direct File Access

```php
// Add to the top of every PHP file
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}
```

#### Conditional Loading

```php
// Separate admin and public code
if ( is_admin() ) {
    require_once __DIR__ . '/admin/class-admin.php';
} else {
    require_once __DIR__ . '/public/class-public.php';
}
```

---

## UI/UX Guidelines

### Core Principles

1. **Merchant-First Design**

   - Focus on the merchant's tasks and goals
   - Don't distract with unrelated content
   - Keep the product experience front and center

2. **Reuse Existing UI**

   - Review WordPress interface before creating new UI
   - Follow existing navigation patterns
   - Don't alter core interface shapes or containers

3. **Mobile Responsive**
   - Build for all device sizes
   - Test on mobile devices
   - Stores operate 24/7 - merchants need mobile access

### Content Guidelines

#### Copy Writing

- **Keep it short**: Limit UI instructions to 120-140 characters
- **Use simple language**: Avoid technical jargon
- **Be consistent**: Maintain same tone and terminology
- **Use American English**
- **Avoid excessive punctuation**: Limit exclamation marks
- **Use sentences**: For descriptions, feedback, and headlines
- **Avoid all-caps text**

#### Impersonal Language

- ✅ Good: "Reports", "Settings", "Orders"
- ❌ Bad: "My Reports", "Your Settings"
- Exception: Use "you" when necessary for understanding

### Settings Design

#### Organization Principles

1. **Use smart defaults**: Minimize configuration requirements
2. **Group intuitively**: By frequency or description
3. **Prioritize common settings**: Place at the top
4. **Hide destructive options**: Place at the bottom

#### Best Practices

```php
// ✅ GOOD: Clear, grouped settings
$settings = array(
    'general' => array(
        'title' => __( 'General Settings', 'text-domain' ),
        'fields' => array(
            'enable_feature' => array(
                'title'       => __( 'Enable Feature', 'text-domain' ),
                'type'        => 'checkbox',
                'default'     => 'yes',
                'description' => __( 'Enable this feature on your store.', 'text-domain' ),
            ),
        ),
    ),
);

// ❌ BAD: Long, unorganized list
$settings = array(
    'field1', 'field2', 'field3', 'field4', 'field5',
    'field6', 'field7', 'field8', 'field9', 'field10',
    // Overwhelming and unusable
);
```

### Review Requests

#### Timing Guidelines

- **Never on first launch**: Let users experience the plugin first
- **After successful setup**: When user has configured and used the plugin
- **Non-disruptive placement**: After task completion
- **Respect dismissal**: Don't repeatedly show if dismissed

```php
// Example: Smart review request timing
function maybe_show_review_request() {
    $activation_time = get_option( 'plugin_activated_time' );
    $current_time = time();
    $days_active = ( $current_time - $activation_time ) / DAY_IN_SECONDS;

    // Wait at least 7 days and check for usage
    if ( $days_active > 7 && has_completed_setup() && ! was_dismissed() ) {
        show_review_notice();
    }
}
```

### Color & Accessibility

#### Color Usage

- **Respect admin color schemes**: Use core WordPress colors
- **Follow WCAG AA guidelines**: Minimum 4.5:1 contrast ratio
- **Test contrast ratios**: Use tools to verify compliance

```css
/* ✅ GOOD: High contrast, respects admin colors */
.button-primary {
  background: var(--wp-admin-theme-color);
  color: #fff;
  /* Contrast ratio: 7:1 */
}

/* ❌ BAD: Poor contrast */
.button-bad {
  background: #e0e0e0;
  color: #f0f0f0;
  /* Contrast ratio: 1.2:1 - fails WCAG */
}
```

---

## Plugin Headers & Metadata

### Required Headers

Every plugin must have a properly formatted header comment in the main plugin file:

```php
<?php
/**
 * Plugin Name:       My Custom Plugin
 * Plugin URI:        https://example.com/plugins/my-plugin/
 * Description:       Brief description (max 140 characters) of what the plugin does.
 * Version:           1.0.0
 * Requires at least: 5.2
 * Requires PHP:      7.2
 * Author:            Your Name
 * Author URI:        https://example.com/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       my-plugin
 * Domain Path:       /languages
 * Requires Plugins:  woocommerce
 */

// Plugin code starts here
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
```

### Header Field Descriptions

| Field                 | Required | Description                                         |
| --------------------- | -------- | --------------------------------------------------- |
| **Plugin Name**       | ✅ Yes   | Display name in WordPress admin                     |
| **Plugin URI**        | No       | Plugin homepage (must be unique, not WordPress.org) |
| **Description**       | No       | Brief description (< 140 characters)                |
| **Version**           | No       | Current version (e.g., 1.0.0)                       |
| **Requires at least** | No       | Minimum WordPress version                           |
| **Requires PHP**      | No       | Minimum PHP version                                 |
| **Author**            | No       | Plugin author name(s)                               |
| **Author URI**        | No       | Author website/profile                              |
| **License**           | No       | License slug (e.g., GPL v2 or later)                |
| **License URI**       | No       | Link to license text                                |
| **Text Domain**       | No       | For internationalization (match plugin slug)        |
| **Domain Path**       | No       | Translation files location                          |
| **Network**           | No       | Network-only activation (true/false)                |
| **Update URI**        | No       | Custom update server                                |
| **Requires Plugins**  | No       | Plugin dependencies (WordPress.org slugs)           |

### WooCommerce Specific Headers

For WooCommerce extensions, add:

```php
/**
 * WC requires at least: 3.0
 * WC tested up to: 8.0
 * Woo: 12345:abc123def456
 */
```

---

## Avoiding Naming Collisions

### Prefix Guidelines

#### Minimum Requirements

- **Length**: At least 4-5 characters
- **Uniqueness**: Avoid common English words
- **Format**: Lowercase with underscores for functions/variables

#### What to Prefix

```php
// Functions
function myplugin_activate() {}

// Classes
class MyPlugin_Admin {}

// Constants
define( 'MYPLUGIN_VERSION', '1.0.0' );

// Global Variables
global $myplugin_options;

// Database Options
update_option( 'myplugin_settings', $settings );

// Hooks
do_action( 'myplugin_init' );
add_filter( 'myplugin_data', $data );
```

### Object-Oriented Approach

```php
// Encapsulate in a class to minimize global namespace pollution
if ( ! class_exists( 'MyPlugin' ) ) {
    class MyPlugin {
        private static $instance = null;

        public static function get_instance() {
            if ( null === self::$instance ) {
                self::$instance = new self();
            }
            return self::$instance;
        }

        private function __construct() {
            $this->init();
        }

        private function init() {
            add_action( 'init', array( $this, 'register_post_types' ) );
        }

        public function register_post_types() {
            // Implementation
        }
    }

    // Initialize
    MyPlugin::get_instance();
}
```

---

## File Organization & Architecture

### Directory Structure

#### Standard Plugin Structure

```
/plugin-name/
├── plugin-name.php           # Main plugin file
├── uninstall.php             # Uninstall handler
├── readme.txt                # WordPress.org readme
├── LICENSE                   # License file
├── composer.json             # Composer dependencies
├── package.json              # NPM dependencies
│
├── /includes/                # Core plugin files
│   ├── class-plugin.php     # Main plugin class
│   ├── class-loader.php     # Hook loader
│   ├── class-i18n.php       # Internationalization
│   └── class-activator.php  # Activation hooks
│
├── /admin/                   # Admin-specific files
│   ├── class-admin.php      # Admin functionality
│   ├── /css/                # Admin styles
│   ├── /js/                 # Admin scripts
│   └── /images/             # Admin images
│
├── /public/                  # Public-facing files
│   ├── class-public.php     # Public functionality
│   ├── /css/                # Public styles
│   ├── /js/                 # Public scripts
│   └── /images/             # Public images
│
├── /languages/               # Translation files
│   ├── plugin-name.pot      # Translation template
│   └── plugin-name-{locale}.mo
│
├── /templates/               # Template files
│   ├── /emails/             # Email templates
│   └── /admin/              # Admin templates
│
├── /assets/                  # Source assets
│   ├── /src/                # Source files
│   └── /build/              # Built files
│
└── /tests/                   # Test files
    ├── /unit/               # Unit tests
    └── /integration/        # Integration tests
```

### Architecture Patterns

#### Small Plugins

- Single main file acceptable
- Minimal file separation
- Focus on simplicity

#### Large Plugins

- Separate concerns into classes
- Use dependency injection
- Implement service containers
- Follow MVC or similar patterns

#### File Loading Strategy

```php
class Plugin_Loader {
    public function load_dependencies() {
        // Core dependencies
        require_once plugin_dir_path( __FILE__ ) . 'includes/class-activator.php';
        require_once plugin_dir_path( __FILE__ ) . 'includes/class-deactivator.php';

        // Conditional loading
        if ( is_admin() ) {
            require_once plugin_dir_path( __FILE__ ) . 'admin/class-admin.php';
        } else {
            require_once plugin_dir_path( __FILE__ ) . 'public/class-public.php';
        }
    }
}
```

---

## WordPress Coding Standards

### Code Style & Formatting

```php
// CORRECT: WordPress naming and formatting
if ( 'active' === $plugin_status && $user_can_manage ) {
    my_plugin_activate_feature( $feature_name );
}

// INCORRECT: Non-WordPress style
if ($pluginStatus == "active" && $userCanManage) {
    myPluginActivateFeature($featureName);
}
```

#### Standards Checklist

- ✅ Use **tabs for indentation**, never spaces
- ✅ Keep lines under **100 characters**
- ✅ Use **Yoda conditions**: `if ( 'value' === $variable )`
- ✅ Apply proper **whitespace**: spaces after commas, around operators
- ✅ **Lowercase with underscores** for functions and variables
- ✅ **Prefix everything** with unique plugin/theme identifier
- ✅ Use full `<?php ?>` tags, never shorthand

### PHPCS Integration

```bash
# Run PHPCS with WordPress standards
phpcs --standard=WordPress file-to-check.php

# Auto-fix issues where possible
phpcbf --standard=WordPress file-to-fix.php
```

**Required Rulesets:**

- `WordPress-Core`: Minimum standard for all code
- `WordPress-Extra`: Additional best practices
- `WordPress-Docs`: Documentation validation
- `PHPCompatibility`: Cross-version compatibility

### Documentation Standards

```php
/**
 * Manages plugin debugging functionality.
 *
 * @since 1.0.0
 * @package MyPlugin
 */
class Debug_Manager {
    /**
     * Enables debug logging with specified configuration.
     *
     * @since 1.0.0
     *
     * @param array $config {
     *     Debug configuration options.
     *
     *     @type string $log_level   The minimum log level to capture.
     *     @type string $log_path    Path to the log file.
     *     @type bool   $rotate_logs Whether to rotate log files.
     * }
     * @return bool True on success, false on failure.
     */
    public function enable_debugging( array $config ): bool {
        // Implementation
    }
}
```

**Documentation Requirements:**

- PHPDoc blocks for all classes, methods, functions
- `@since` tags for version tracking
- `@package` for namespace organization
- Type hints for parameters and returns
- Translator comments for ambiguous strings

---

## Accessibility Standards

WordPress code is expected to conform to the **Web Content Accessibility Guidelines (WCAG), version 2.2, at level AA**. This ensures our code is accessible to users with disabilities and works with assistive technologies.

### Core Requirements

- **WCAG 2.2 Level AA**: All WordPress code must meet Level A and AA success criteria
- **ATAG 2.0**: New interfaces should incorporate Authoring Tool Accessibility Guidelines
- **WAI-ARIA 1.1**: Use ARIA attributes appropriately to enhance accessibility

### WCAG Conformance Levels

#### Level A (Required)

- **Minimum accessibility**: Addresses barriers that prevent many people from accessing content
- Examples: Images have alt text, forms have labels, keyboard navigation works

#### Level AA (Required)

- **Enhanced accessibility**: Addresses more complex accessibility needs
- Examples: Sufficient color contrast, consistent navigation, error identification

#### Level AAA (Encouraged)

- **Maximum accessibility**: Addresses very specific needs where applicable
- Examples: Sign language videos, extended audio descriptions

### WCAG 2.2 Four Principles

All accessible content must be:

1. **Perceivable**: Users can perceive content through their available senses
2. **Operable**: Users can interact with all controls and navigation
3. **Understandable**: Users can understand content and interface operation
4. **Robust**: Content works with various assistive technologies

### Implementation Guidelines

#### 1. Perceivable

```php
// CORRECT: Provide text alternatives
echo '<img src="logo.png" alt="' . esc_attr__( 'Company Logo', 'text-domain' ) . '" />';

// CORRECT: Use semantic HTML for structure
echo '<nav aria-label="' . esc_attr__( 'Main Navigation', 'text-domain' ) . '">';

// CORRECT: Ensure sufficient color contrast (4.5:1 for normal text, 3:1 for large text)
.content {
    color: #333;      /* Dark gray on white: contrast ratio 12.6:1 ✓ */
    background: #fff;
}
```

#### 2. Operable

```php
// CORRECT: All interactive elements keyboard accessible
<button onclick="doSomething()">
    <?php esc_html_e( 'Click Me', 'text-domain' ); ?>
</button>

// CORRECT: Skip links for keyboard navigation
<a class="skip-link screen-reader-text" href="#content">
    <?php esc_html_e( 'Skip to content', 'text-domain' ); ?>
</a>

// CORRECT: Focus indicators visible
button:focus {
    outline: 2px solid #0073aa;
    outline-offset: 2px;
}
```

#### 3. Understandable

```php
// CORRECT: Clear labels and instructions
<label for="email">
    <?php esc_html_e( 'Email Address (required)', 'text-domain' ); ?>
</label>
<input type="email" id="email" name="email" required
       aria-describedby="email-error" />
<span id="email-error" class="error" role="alert">
    <?php esc_html_e( 'Please enter a valid email address', 'text-domain' ); ?>
</span>

// CORRECT: Consistent navigation
wp_nav_menu( array(
    'theme_location' => 'primary',
    'menu_id'        => 'primary-menu',
    'container'      => 'nav',
    'container_aria_label' => __( 'Primary Menu', 'text-domain' ),
) );
```

#### 4. Robust

```php
// CORRECT: Valid, semantic HTML
<article>
    <header>
        <h1><?php the_title(); ?></h1>
    </header>
    <div class="entry-content">
        <?php the_content(); ?>
    </div>
</article>

// CORRECT: Proper ARIA usage
<div role="alert" aria-live="polite" aria-atomic="true">
    <?php echo esc_html( $status_message ); ?>
</div>
```

### WordPress-Specific Accessibility Patterns

```php
// Admin notices with proper ARIA
function my_admin_notice() {
    ?>
    <div class="notice notice-success is-dismissible" role="status">
        <p><?php esc_html_e( 'Settings saved successfully.', 'text-domain' ); ?></p>
    </div>
    <?php
}

// Accessible form example
<form method="post" action="">
    <?php wp_nonce_field( 'my_action', 'my_nonce' ); ?>

    <fieldset>
        <legend><?php esc_html_e( 'User Preferences', 'text-domain' ); ?></legend>

        <label for="user-name">
            <?php esc_html_e( 'Name', 'text-domain' ); ?>
            <span class="required" aria-label="<?php esc_attr_e( 'required', 'text-domain' ); ?>">*</span>
        </label>
        <input type="text" id="user-name" name="user_name" required />
    </fieldset>

    <button type="submit">
        <?php esc_html_e( 'Save Changes', 'text-domain' ); ?>
    </button>
</form>
```

### Testing for Accessibility

1. **Keyboard Navigation**: Tab through all interactive elements
2. **Screen Reader Testing**: Use NVDA (Windows), JAWS, or VoiceOver (Mac)
3. **Color Contrast**: Use tools like WAVE or axe DevTools
4. **Automated Testing**: Run axe or WAVE browser extensions
5. **Manual Testing**: Follow WCAG 2.2 Quick Reference checklist

### Resources

**Normative Documents** (Requirements):

- [W3C WCAG 2.2](https://www.w3.org/WAI/WCAG22/Understanding/)
- [W3C ATAG 2.0](https://www.w3.org/WAI/ATAG20/)
- [W3C WAI-ARIA 1.1](https://www.w3.org/TR/wai-aria-1.1/)

**Informative Documents** (Guidance):

- [Understanding WCAG 2.2](https://www.w3.org/WAI/WCAG22/Understanding/)
- [WAI-ARIA Authoring Practices](https://www.w3.org/WAI/ARIA/apg/)
- [WordPress Accessibility Handbook](https://make.wordpress.org/accessibility/handbook/)

---

## CSS Coding Standards

CSS standards ensure consistency, readability, and maintainability across WordPress projects.

### Structure

```css
/* CORRECT: Proper CSS structure */
#selector-1,
#selector-2,
#selector-3 {
  background: #fff;
  color: #000;
}

/* INCORRECT: Poor structure */
#selector-1,
#selector-2,
#selector-3 {
  background: #fff;
  color: #000;
}
```

**Rules:**

- Use **tabs** for indentation, not spaces
- Add **two blank lines** between sections
- Add **one blank line** between blocks in a section
- Each selector on its own line
- Property-value pairs on separate lines
- Closing brace flush left with opening selector

### Selectors

```css
/* CORRECT: Readable selectors */
#comment-form {
  margin: 1em 0;
}

input[type="text"] {
  line-height: 1.1;
}

/* INCORRECT: Poor selector practices */
#commentForm {
  /* Avoid camelCase */
  margin: 0;
}

#comment_form {
  /* Avoid underscores */
  margin: 0;
}

div#comment_form {
  /* Avoid over-qualification */
  margin: 0;
}
```

**Guidelines:**

- Use **lowercase** with **hyphens** for class/ID names
- Use **human-readable** selector names
- Use **double quotes** for attribute selectors
- Avoid over-qualified selectors

### Properties

```css
/* CORRECT: Property formatting */
#selector-1 {
  background: #fff;
  display: block;
  margin: 0;
  margin-left: 20px;
}

/* INCORRECT: Poor property formatting */
#selector-1 {
  background: #ffffff;
  display: BLOCK;
  margin-left: 20px;
  margin: 0; /* Order matters! */
}
```

**Standards:**

- Space after colon, not before
- Lowercase properties and values (except font names)
- Use hex codes for colors: `#fff` not `#FFFFFF`
- Use shorthand when possible
- Consistent property ordering

### Property Ordering

Properties should be grouped logically:

1. **Display & Positioning**
2. **Box Model**
3. **Colors & Typography**
4. **Other**

```css
.element {
  /* Display & Positioning */
  display: block;
  position: absolute;
  top: 0;
  right: 0;
  z-index: 10;

  /* Box Model */
  width: 100%;
  height: auto;
  margin: 10px;
  padding: 10px;

  /* Colors & Typography */
  background: #fff;
  color: #333;
  font-family: sans-serif;
  font-size: 16px;

  /* Other */
  cursor: pointer;
  overflow: hidden;
}
```

### Values

```css
/* CORRECT: Value formatting */
.class {
  background-image: url(images/bg.png);
  font-family: "Helvetica Neue", sans-serif;
  font-weight: 700;
  line-height: 1.4;
  margin: 0;
  text-shadow: 0 -1px 0 rgba(0, 0, 0, 0.5), 0 1px 0 #fff;
}

/* INCORRECT: Poor value formatting */
.class {
  font-family: Times New Roman, serif; /* Quote font names with spaces */
  font-weight: bold; /* Use numeric values */
  line-height: 1.4em; /* Avoid units for line-height */
  margin: 0px; /* No units for 0 */
}
```

### Media Queries

```css
/* CORRECT: Media query formatting */
@media all and (max-width: 699px) and (min-width: 520px) {
  /* Indented content */
  .selector {
    background: #fff;
  }
}
```

### Comments

```css
/**
 * #.# Section Title
 *
 * Description of section, whether or not it has media queries, etc.
 */

.selector {
  float: left;
}

/* This is a comment about this selector */
.another-selector {
  position: absolute;
  top: 0 !important; /* I should explain why this is so !important */
}
```

---

## HTML Coding Standards

HTML standards ensure valid, semantic, and accessible markup.

### Validation

All HTML should validate against the W3C validator. Well-formed markup is the foundation of accessible, maintainable code.

### Self-closing Elements

```html
<!-- CORRECT: Space before self-closing slash -->
<br />
<img src="image.jpg" alt="Description" />
<input type="text" name="field" />

<!-- INCORRECT: No space -->
<br />
<img src="image.jpg" alt="Description" />
```

### Attributes and Tags

```html
<!-- CORRECT: Lowercase tags and attributes -->
<meta http-equiv="content-type" content="text/html; charset=utf-8" />
<a href="http://example.com/" title="Description Here">Example.com</a>

<!-- CORRECT: Boolean attributes -->
<input type="text" name="email" disabled />
<input type="checkbox" name="agree" checked />

<!-- INCORRECT: Various issues -->
<input type="text" name="email" disabled="true" />
<input type="TEXT" name="EMAIL" />
```

### Quotes

Always use quotes for attribute values:

```html
<!-- CORRECT: Quoted attributes -->
<input type="text" name="email" class="form-control" />

<!-- INCORRECT: Unquoted attributes -->
<input type="text" name="email" class="form-control" />
```

### Indentation with PHP

```php
<!-- CORRECT: PHP blocks match HTML indentation -->
<?php if ( ! have_posts() ) : ?>
	<div id="post-404" class="post error404 not-found">
		<h1 class="entry-title"><?php esc_html_e( 'Not Found', 'text-domain' ); ?></h1>
		<div class="entry-content">
			<p><?php esc_html_e( 'Apologies, but no results were found.', 'text-domain' ); ?></p>
			<?php get_search_form(); ?>
		</div>
	</div>
<?php endif; ?>
```

---

## JavaScript Coding Standards

WordPress JavaScript follows adapted jQuery style guidelines with specific modifications.

### Spacing

```javascript
// CORRECT: Proper spacing
var i;

if (condition) {
  doSomething("with a string");
} else if (otherCondition) {
  otherThing({
    key: value,
    otherKey: otherValue,
  });
} else {
  somethingElse(true);
}

// Arrays and function calls
array = [a, b];
foo(arg);
foo("string", object);
foo(options, object[property]);
```

**Rules:**

- Tabs for indentation
- No trailing whitespace
- Lines ≤ 80 characters (soft limit 100)
- Spaces around array elements and arguments
- Space after `!` negation operator

### Objects and Arrays

```javascript
// CORRECT: Object/array declarations
var obj = {
  ready: 9,
  when: 4,
  "you are": 15,
};

var arr = [9, 4, 15];

// Small objects/arrays can be single line
var obj = { ready: 9, when: 4 };
var arr = [9, 4, 15];
```

### Naming Conventions

```javascript
// CORRECT: camelCase for variables and functions
var myVariable = 123;
function doSomething() {
  // Code
}

// CORRECT: Constructors use PascalCase
function MyConstructor() {
  // Code
}

// CORRECT: Constants use SCREAMING_SNAKE_CASE
const MAX_UPLOAD_SIZE = 5242880;
```

### Modern JavaScript (ES2015+)

```javascript
// Use const and let instead of var
const permanentValue = 42;
let changingValue = "initial";

// Arrow functions for callbacks
const filtered = items.filter((item) => item.active);

// Template literals
const message = `Hello, ${userName}!`;

// Destructuring
const { name, email } = userData;
```

### jQuery in WordPress

```javascript
// CORRECT: Pass jQuery to anonymous function
(function ($) {
  // Use $ safely here
  $(document).ready(function () {
    $(".button").on("click", function () {
      // Handle click
    });
  });
})(jQuery);
```

### JSDoc Comments

```javascript
/**
 * Handles form submission.
 *
 * @since 1.0.0
 *
 * @param {Event} event The submit event.
 * @return {boolean} False to prevent submission.
 */
function handleSubmit(event) {
  event.preventDefault();
  // Process form
  return false;
}
```

---

## PHP Documentation Standards

WordPress uses a customized PHPDoc schema for comprehensive code documentation.

### What to Document

- Functions and class methods
- Classes and interfaces
- Class properties and constants
- Hooks (actions and filters)
- File headers
- Inline comments

### Function Documentation

```php
/**
 * Retrieves post data given a post ID or post object.
 *
 * This is a more detailed description that explains what the function
 * does, how it works, and when to use it.
 *
 * @since 1.0.0
 * @since 1.5.0 Added the `$filter` parameter.
 *
 * @see get_post_meta()
 * @see WP_Post
 *
 * @global WP_Post $post Global post object.
 *
 * @param int|WP_Post|null $post   Optional. Post ID or post object.
 *                                 Defaults to global $post.
 * @param string           $output Optional. The required return type.
 *                                 Accepts OBJECT, ARRAY_A, or ARRAY_N.
 *                                 Default OBJECT.
 * @param string           $filter Optional. Type of filter to apply.
 *                                 Default 'raw'.
 * @return WP_Post|array|null Type based on $output value.
 */
function get_post( $post = null, $output = OBJECT, $filter = 'raw' ) {
	// Function implementation
}
```

### Class Documentation

```php
/**
 * Core class used to implement displaying posts in a list table.
 *
 * Extended description providing more context about the class,
 * its purpose, and how it fits into the larger system.
 *
 * @since 3.1.0
 *
 * @see WP_List_Table
 */
class WP_Posts_List_Table extends WP_List_Table {

	/**
	 * Post type.
	 *
	 * @since 3.1.0
	 * @var string
	 */
	public $post_type;

	/**
	 * Constructor.
	 *
	 * @since 3.1.0
	 *
	 * @param array $args An associative array of arguments.
	 */
	public function __construct( $args = array() ) {
		// Constructor implementation
	}
}
```

### Hook Documentation

```php
/**
 * Fires after a post is deleted.
 *
 * @since 2.2.0
 * @since 5.5.0 Added the `$post` parameter.
 *
 * @param int     $post_id Post ID.
 * @param WP_Post $post    Post object.
 */
do_action( 'deleted_post', $post_id, $post );

/**
 * Filters the post content.
 *
 * @since 0.71
 *
 * @param string $content Content of the current post.
 */
$content = apply_filters( 'the_content', $content );
```

### Inline Comments

```php
// Single line comment explaining the next line.
$value = get_option( 'my_option' );

/*
 * Multi-line comment explaining complex logic.
 * Continue description on multiple lines.
 * Note: Don't use /** for multi-line comments (reserved for DocBlocks).
 */
if ( $complex_condition ) {
	// Implementation
}
```

### File Headers

```php
/**
 * User API: WP_User class
 *
 * This file contains the WP_User class which is used to
 * represent a user of the site.
 *
 * @package WordPress
 * @subpackage Users
 * @since 2.0.0
 */
```

---

## JavaScript Documentation Standards

WordPress follows JSDoc 3 standard for JavaScript documentation.

### Function Documentation

```javascript
/**
 * Sends an AJAX request to save user preferences.
 *
 * Detailed description of what the function does,
 * how it works, and when to use it.
 *
 * @since 1.0.0
 * @since 1.2.0 Added support for batch operations.
 *
 * @param {Object} preferences          User preferences object.
 * @param {string} preferences.theme    Theme preference.
 * @param {boolean} preferences.notify  Notification preference.
 * @param {Function} callback           Callback function.
 *
 * @return {Promise} Promise that resolves when save completes.
 */
function saveUserPreferences(preferences, callback) {
  // Implementation
}
```

### Class Documentation (ES6)

```javascript
/**
 * Manages application state.
 *
 * @since 2.0.0
 * @class
 */
class StateManager {
  /**
   * Constructs the state manager.
   *
   * @since 2.0.0
   *
   * @param {Object} initialState Initial state object.
   */
  constructor(initialState) {
    /**
     * Current state.
     *
     * @since 2.0.0
     * @type {Object}
     */
    this.state = initialState;
  }

  /**
   * Updates the state.
   *
   * @since 2.0.0
   *
   * @param {Object} updates Updates to apply.
   * @return {Object} New state.
   */
  setState(updates) {
    // Implementation
  }
}
```

### Event Documentation

```javascript
/**
 * Handles click events on menu items.
 *
 * @since 1.0.0
 *
 * @listens click
 * @fires menuItemSelected
 *
 * @param {Event} event The click event.
 */
function handleMenuClick(event) {
  /**
   * Fired when a menu item is selected.
   *
   * @event menuItemSelected
   * @type {Object}
   * @property {string} itemId The selected item ID.
   */
  trigger("menuItemSelected", { itemId: event.target.id });
}
```

### File Headers

```javascript
/**
 * Handles user authentication and session management.
 *
 * This file provides functions for logging in, logging out,
 * and managing user sessions.
 *
 * @file
 * @since 1.0.0
 */

/* global wp, jQuery */
/* jshint curly: true, eqeqeq: true */
```

---

## Security Implementation

### Security Mindset

When developing for WordPress and WooCommerce, security must be a primary concern. With so much of the web relying on WordPress, security vulnerabilities can have widespread impact.

#### Core Security Principles

1. **Don't trust any data**: Never trust user input, third-party APIs, or even database data without verification
2. **Validate and sanitize**: Always validate input and sanitize before using
3. **Escape on output**: Always escape data when outputting to prevent XSS
4. **Use WordPress APIs**: Rely on WordPress core functions for security
5. **Keep code updated**: Maintain your code and update dependencies regularly
6. **Never assume anything**: Always verify and validate

### Escaping Data

Escaping output is the process of securing output data by stripping out unwanted data, like malformed HTML or script tags. **Always escape as late as possible** - ideally as data is being output.

#### Escaping Functions

```php
// HTML context - removes HTML
echo esc_html( $text );
echo esc_html__( 'Translatable text', 'text-domain' );

// Attribute context - use for HTML attributes
echo '<div class="' . esc_attr( $class ) . '">';
echo '<input type="text" value="' . esc_attr( $value ) . '" />';

// URL context - use for all URLs
echo '<a href="' . esc_url( $url ) . '">Link</a>';
echo '<img src="' . esc_url( $image_url ) . '" />';

// URL for database storage
$clean_url = esc_url_raw( $url );

// JavaScript context
echo '<script>var data = ' . esc_js( $data ) . ';</script>';
echo '<div onclick="doSomething(' . esc_js( $value ) . ')">';

// Textarea context
echo '<textarea>' . esc_textarea( $content ) . '</textarea>';

// XML context
echo '<description>' . esc_xml( $text ) . '</description>';

// Allow specific HTML (use wp_kses)
$allowed_html = array(
    'a' => array(
        'href' => array(),
        'title' => array(),
    ),
    'br' => array(),
    'em' => array(),
    'strong' => array(),
);
echo wp_kses( $html, $allowed_html );

// Allow post content HTML
echo wp_kses_post( $post_content );

// Allow comment HTML
echo wp_kses_data( $comment_text );
```

#### Late Escaping Best Practice

```php
// ✅ GOOD: Escape on output
echo '<a href="' . esc_url( $url ) . '">' . esc_html( $text ) . '</a>';

// ❌ BAD: Escaping too early
$url = esc_url( $url );
$text = esc_html( $text );
echo '<a href="' . $url . '">' . $text . '</a>';

// Exception: When you can't escape late, use descriptive variable names
$title_escaped = esc_html( $title );
$url_safe = esc_url( $url );
```

#### Escaping with Localization

```php
// Combined localization and escaping functions
esc_html_e( 'Hello World', 'text-domain' );
esc_html__( 'Hello World', 'text-domain' );
esc_attr_e( 'Title', 'text-domain' );
esc_attr__( 'Title', 'text-domain' );

// With context
esc_html_x( 'Post', 'noun', 'text-domain' );
esc_attr_x( 'Post', 'verb', 'text-domain' );
```

### Sanitizing Data

Sanitization is the process of cleaning or filtering input data. Unlike validation (which rejects invalid data), sanitization modifies the data to make it safe.

#### Sanitization Functions

```php
// Text fields
$clean_text = sanitize_text_field( wp_unslash( $_POST['text'] ?? '' ) );

// Textareas (preserves line breaks)
$clean_textarea = sanitize_textarea_field( wp_unslash( $_POST['textarea'] ?? '' ) );

// Email addresses
$clean_email = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );

// URLs
$clean_url = esc_url_raw( wp_unslash( $_POST['url'] ?? '' ) );

// File names
$clean_filename = sanitize_file_name( $_FILES['upload']['name'] );

// HTML classes
$clean_class = sanitize_html_class( $class );

// Keys (lowercase alphanumeric, dashes, underscores)
$clean_key = sanitize_key( $key );

// Titles (used in URLs)
$clean_slug = sanitize_title( $title );

// SQL ORDER BY clauses
$clean_orderby = sanitize_sql_orderby( $orderby );

// User names
$clean_username = sanitize_user( $username );

// Numbers
$clean_int = absint( $_GET['id'] ?? 0 );
$clean_float = (float) $_GET['price'];

// Arrays - sanitize recursively
function sanitize_array_recursive( $array ) {
    foreach ( $array as $key => &$value ) {
        if ( is_array( $value ) ) {
            $value = sanitize_array_recursive( $value );
        } else {
            $value = sanitize_text_field( $value );
        }
    }
    return $array;
}
```

#### Complex Sanitization Example

```php
// Handling form submission
function handle_form_submission() {
    // Verify nonce first
    if ( ! isset( $_POST['my_nonce'] ) ||
         ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['my_nonce'] ) ), 'my_action' ) ) {
        wp_die( 'Security check failed' );
    }

    // Check capabilities
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( 'Insufficient permissions' );
    }

    // Sanitize different field types
    $data = array(
        'title'       => sanitize_text_field( wp_unslash( $_POST['title'] ?? '' ) ),
        'content'     => wp_kses_post( wp_unslash( $_POST['content'] ?? '' ) ),
        'email'       => sanitize_email( wp_unslash( $_POST['email'] ?? '' ) ),
        'url'         => esc_url_raw( wp_unslash( $_POST['url'] ?? '' ) ),
        'number'      => absint( $_POST['number'] ?? 0 ),
        'price'       => (float) ( $_POST['price'] ?? 0 ),
        'option'      => sanitize_key( $_POST['option'] ?? '' ),
        'description' => sanitize_textarea_field( wp_unslash( $_POST['description'] ?? '' ) ),
    );

    // Validate after sanitization
    if ( empty( $data['title'] ) ) {
        return new WP_Error( 'missing_title', __( 'Title is required', 'text-domain' ) );
    }

    if ( ! is_email( $data['email'] ) ) {
        return new WP_Error( 'invalid_email', __( 'Invalid email address', 'text-domain' ) );
    }

    // Process the clean data
    return $data;
}
```

### Input Validation & Sanitization

```php
// IMPORTANT: Always use wp_unslash() before sanitizing $_POST/$_GET data
// WordPress may add slashes to quotes in superglobals

// CORRECT: Proper sanitization with unslashing
$clean_text  = sanitize_text_field( wp_unslash( $_POST['text'] ?? '' ) );
$clean_email = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );
$clean_int   = absint( $_GET['number'] ?? 0 ); // Numbers don't need unslashing
$clean_html  = wp_kses_post( wp_unslash( $_POST['content'] ?? '' ) );
$clean_url   = esc_url_raw( wp_unslash( $_POST['url'] ?? '' ) );

// For nonce verification, unslash is required
if ( ! isset( $_POST['nonce'] ) ||
     ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'action' ) ) {
    wp_die( 'Security check failed' );
}

// For complex arrays, unslash first
if ( isset( $_POST['complex_data'] ) && is_array( $_POST['complex_data'] ) ) {
    // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
    $data = wp_unslash( $_POST['complex_data'] );
    // Then sanitize each element
    foreach ( $data as $key => $value ) {
        $clean_data[ $key ] = sanitize_text_field( $value );
    }
}

// Output escaping examples
echo esc_html( $user_input );
echo esc_url( $url );
echo esc_attr( $html_attribute );
echo wp_kses_post( $allowed_html_content );
echo esc_js( $javascript_data );

// Data validation
if ( ! is_email( $email ) ) {
    return new WP_Error( 'invalid_email', __( 'Invalid email address', 'text-domain' ) );
}
```

### Nonce Security

```php
// CORRECT: Complete nonce implementation
// Adding nonce to forms
wp_nonce_field( 'my_plugin_action', 'my_plugin_nonce' );

// Verifying nonce with proper checks
if ( ! isset( $_POST['my_plugin_nonce'] ) ||
     ! wp_verify_nonce( $_POST['my_plugin_nonce'], 'my_plugin_action' ) ) {
    wp_die( __( 'Security check failed', 'my-plugin' ) );
}

// Always combine with capability checks
if ( ! current_user_can( 'manage_options' ) ) {
    wp_die( __( 'Insufficient permissions', 'my-plugin' ) );
}

// For AJAX requests
check_ajax_referer( 'my_plugin_ajax', 'security' );
```

### Form Data Handling

```php
// Handling JSON data from forms
if ( isset( $_POST['my_json_field'] ) ) {
    // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
    $json_data = wp_unslash( $_POST['my_json_field'] );

    // Decode JSON
    $array_data = json_decode( $json_data, true );

    // Validate and sanitize each element
    if ( is_array( $array_data ) ) {
        foreach ( $array_data as $item ) {
            // Sanitize individual elements
            $clean_item = array(
                'field1' => sanitize_text_field( $item['field1'] ?? '' ),
                'field2' => absint( $item['field2'] ?? 0 ),
            );
        }
    }
}

// Reading GET parameters for display (no nonce needed)
// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Reading URL parameter for display only.
$tab = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : '';
```

### Database Security

```php
global $wpdb;

// CORRECT: Prepared statements
$results = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}my_table
         WHERE status = %s AND user_id = %d",
        $status,
        $user_id
    )
);

// CORRECT: Safe insert/update
$wpdb->insert(
    $wpdb->prefix . 'my_table',
    array(
        'column1' => $value1,
        'column2' => $value2
    ),
    array( '%s', '%d' ) // Format specifiers
);

// IMPORTANT: Direct database queries trigger warnings
// When you MUST use direct queries (custom tables, analytics, etc.):

// 1. Add phpcs:ignore comment with justification
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Custom table required for analytics
$wpdb->insert( $table_name, $data, $format );

// 2. Always use caching when reading data
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$results = wp_cache_get( 'my_cache_key', 'my_cache_group' );
if ( false === $results ) {
    $results = $wpdb->get_results( $query );
    wp_cache_set( 'my_cache_key', $results, 'my_cache_group', 3600 );
}

// 3. Clear cache after writes
if ( $wpdb->insert( $table_name, $data ) ) {
    wp_cache_delete( 'my_cache_key', 'my_cache_group' );
}
```

### Security Best Practices

- ✅ Implement **rate limiting** for authentication
- ✅ Use **capability checks** before actions
- ✅ Apply **principle of least privilege**
- ✅ Validate **URLs before redirects**
- ✅ Set **SECURE and HTTPONLY** flags for cookies
- ✅ Use **WordPress transients** for temporary data
- ✅ Avoid `eval()`, `create_function()`, dangerous functions
- ✅ **Never trust user input** - always sanitize

### WooCommerce Security Best Practices

#### Common Security Pitfalls

```php
// ❌ AVOID: SQL Injection vulnerability
$wpdb->query( "SELECT * FROM {$wpdb->prefix}orders WHERE status = '$status'" );

// ✅ CORRECT: Use prepared statements
$wpdb->get_results(
    $wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}orders WHERE status = %s",
        $status
    )
);

// ❌ AVOID: XSS vulnerability
echo '<div>' . $_POST['user_input'] . '</div>';

// ✅ CORRECT: Sanitize and escape
echo '<div>' . esc_html( sanitize_text_field( wp_unslash( $_POST['user_input'] ) ) ) . '</div>';

// ❌ AVOID: File inclusion vulnerability
include $_GET['file'];

// ✅ CORRECT: Whitelist allowed files
$allowed_files = array( 'header.php', 'footer.php' );
$file = sanitize_file_name( $_GET['file'] );
if ( in_array( $file, $allowed_files, true ) ) {
    include $file;
}
```

#### Preventing Data Leaks

**Always check for direct file access:**

```php
// Add to the top of EVERY PHP file
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}
```

#### Security Standards Compliance

- **PHPCS**: No failures against WordPress security sniffs
- **SemGrep**: No security pattern violations
- **Regular audits**: Perform security reviews on all code changes

#### WooCommerce-Specific Security

```php
// Always use WooCommerce CRUD for orders
$order = wc_get_order( $order_id );
if ( $order ) {
    // Safe operations
    $total = $order->get_total();
    $order->update_meta_data( 'custom_field', sanitize_text_field( $value ) );
    $order->save();
}

// Never access order data directly from database
// ❌ AVOID
$order_data = get_post_meta( $order_id );

// ✅ CORRECT
$order = wc_get_order( $order_id );
$order_data = $order->get_data();
```

---

## Data Storage Primer

WordPress provides multiple methods for storing data, each suited for different use cases:

### Options API

The Options API provides simple, persistent storage in the `wp_options` table.

#### When to Use Options

- **Persistent settings** that don't expire
- **Configuration data** for plugins/themes
- **Small amounts of data** that are always needed

```php
// Add/Update options
add_option( 'my_plugin_settings', $value, '', 'yes' ); // autoload = yes
update_option( 'my_plugin_settings', $new_value );

// Get options with default
$settings = get_option( 'my_plugin_settings', $default_value );

// Delete options
delete_option( 'my_plugin_settings' );

// For multisite network-wide options
add_site_option( 'my_network_option', $value );
get_site_option( 'my_network_option', $default );
update_site_option( 'my_network_option', $new_value );
delete_site_option( 'my_network_option' );
```

#### Options Best Practices

```php
// Group related settings in arrays
$plugin_settings = array(
    'version'     => '1.0.0',
    'api_key'     => 'xxx',
    'enable_feature' => true,
    'options'     => array(
        'color' => '#fff',
        'size'  => 'large',
    ),
);
update_option( 'my_plugin_settings', $plugin_settings );

// Use autoload wisely - set to 'no' for large/rarely used options
add_option( 'my_large_data', $data, '', 'no' );

// Sanitize before saving
function save_plugin_settings( $input ) {
    $sanitized = array();
    $sanitized['api_key'] = sanitize_text_field( $input['api_key'] );
    $sanitized['enable_feature'] = ! empty( $input['enable_feature'] );
    return $sanitized;
}
```

### Transients API

Transients provide temporary data caching with expiration times.

#### When to Use Transients

- **Cached API responses**
- **Expensive query results**
- **Temporary data** that can be regenerated
- **Data that expires**

```php
// Set transient with expiration
set_transient( 'special_query_results', $results, 12 * HOUR_IN_SECONDS );

// Get transient (returns false if expired or doesn't exist)
if ( false === ( $results = get_transient( 'special_query_results' ) ) ) {
    // Regenerate the data
    $results = expensive_query();
    set_transient( 'special_query_results', $results, 12 * HOUR_IN_SECONDS );
}

// Delete transient
delete_transient( 'special_query_results' );

// Network-wide transients for multisite
set_site_transient( 'network_data', $data, DAY_IN_SECONDS );
get_site_transient( 'network_data' );
delete_site_transient( 'network_data' );
```

#### Time Constants

```php
MINUTE_IN_SECONDS  // 60
HOUR_IN_SECONDS    // 3600
DAY_IN_SECONDS     // 86400
WEEK_IN_SECONDS    // 604800
MONTH_IN_SECONDS   // 2592000 (30 days)
YEAR_IN_SECONDS    // 31536000 (365 days)
```

#### Transients Best Practices

```php
class My_Plugin_Cache {
    /**
     * Get cached data or regenerate
     */
    public static function get_cached_data( $force_refresh = false ) {
        $cache_key = 'my_plugin_expensive_data';

        // Force refresh or get from cache
        if ( $force_refresh || false === ( $data = get_transient( $cache_key ) ) ) {
            // Expensive operation
            $data = self::generate_expensive_data();

            // Cache for 6 hours
            set_transient( $cache_key, $data, 6 * HOUR_IN_SECONDS );
        }

        return $data;
    }

    /**
     * Clear cache when data changes
     */
    public static function clear_cache() {
        delete_transient( 'my_plugin_expensive_data' );
    }
}

// Hook cache clearing to relevant actions
add_action( 'save_post', array( 'My_Plugin_Cache', 'clear_cache' ) );
```

### WP Object Cache

The Object Cache provides in-memory caching for the duration of a request (non-persistent by default).

#### When to Use Object Cache

- **Frequently accessed data** within a request
- **Computed values** used multiple times
- **Query results** used across functions

```php
// Basic cache operations
wp_cache_set( 'my_key', $data, 'my_group', 3600 ); // expires in 1 hour
$data = wp_cache_get( 'my_key', 'my_group' );
wp_cache_delete( 'my_key', 'my_group' );
wp_cache_flush(); // Clear all cache

// Add to cache only if it doesn't exist
wp_cache_add( 'my_key', $data, 'my_group' );

// Replace only if it exists
wp_cache_replace( 'my_key', $new_data, 'my_group' );

// Increment/decrement numeric values
wp_cache_incr( 'counter', 1, 'my_group' );
wp_cache_decr( 'counter', 1, 'my_group' );

// Multiple operations (if supported)
if ( wp_cache_supports( 'get_multiple' ) ) {
    $values = wp_cache_get_multiple( array( 'key1', 'key2' ), 'my_group' );
}
```

#### Object Cache Best Practices

```php
class My_Plugin_Query {
    /**
     * Get posts with caching
     */
    public static function get_posts( $args ) {
        // Create unique cache key
        $cache_key = 'posts_' . md5( serialize( $args ) );
        $cache_group = 'my_plugin';

        // Try to get from cache
        $posts = wp_cache_get( $cache_key, $cache_group );

        if ( false === $posts ) {
            // Not in cache, run query
            $posts = get_posts( $args );

            // Store in cache for this request
            wp_cache_set( $cache_key, $posts, $cache_group );
        }

        return $posts;
    }
}

// Mark groups as global for multisite
wp_cache_add_global_groups( array( 'my_plugin_global' ) );

// Mark groups as non-persistent (won't be saved by persistent cache plugins)
wp_cache_add_non_persistent_groups( array( 'my_temp_data' ) );
```

### Data Storage Decision Tree

```php
/**
 * Choose the right storage method:
 *
 * 1. Is the data temporary?
 *    YES → Use Transients (with expiration)
 *    NO  → Continue to #2
 *
 * 2. Is it configuration/settings?
 *    YES → Use Options API
 *    NO  → Continue to #3
 *
 * 3. Is it entity data (posts, products, orders)?
 *    YES → Use Custom Post Types or Custom Tables
 *    NO  → Continue to #4
 *
 * 4. Is it categorization/taxonomy?
 *    YES → Use WordPress Taxonomies
 *    NO  → Continue to #5
 *
 * 5. Is it for debugging/logging?
 *    YES → Use WC_Logger or error_log (with debug checks)
 *    NO  → Re-evaluate your data structure
 */
```

---

## WooCommerce CRUD Objects

WooCommerce 3.0+ uses CRUD (Create, Read, Update, Delete) objects for data management.

### CRUD Benefits

1. **Structure**: Pre-defined schemas with validation
2. **Control**: Centralized data flow and validation
3. **Abstraction**: Database structure independence
4. **Consistency**: Unified API across admin, REST, and CLI

### Using CRUD Objects

#### Products

```php
// Create a new product
$product = new WC_Product_Simple();
$product->set_name( 'My Product' );
$product->set_regular_price( '19.99' );
$product->set_description( 'Product description' );
$product->set_status( 'publish' );
$product->set_catalog_visibility( 'visible' );
$product->set_stock_quantity( 100 );
$product->set_manage_stock( true );
$product->save();

// Read a product
$product = wc_get_product( $product_id );
if ( $product ) {
    $name = $product->get_name();
    $price = $product->get_price();
    $stock = $product->get_stock_quantity();
}

// Update a product
$product = wc_get_product( $product_id );
$product->set_sale_price( '14.99' );
$product->set_stock_quantity( 50 );
$product->save();

// Delete a product
$product = wc_get_product( $product_id );
$product->delete( true ); // true = force delete, false = trash
```

#### Orders

```php
// Create an order
$order = wc_create_order( array(
    'customer_id' => $customer_id,
    'status'      => 'pending',
) );

// Add products to order
$order->add_product( wc_get_product( $product_id ), 2 ); // 2 quantity

// Set addresses
$order->set_billing_first_name( 'John' );
$order->set_billing_last_name( 'Doe' );
$order->set_billing_email( 'john@example.com' );
$order->set_billing_phone( '555-1234' );
$order->set_billing_address_1( '123 Main St' );
$order->set_billing_city( 'New York' );
$order->set_billing_state( 'NY' );
$order->set_billing_postcode( '10001' );
$order->set_billing_country( 'US' );

// Calculate totals and save
$order->calculate_totals();
$order->save();

// Update order status
$order->update_status( 'completed', 'Order completed by admin' );

// Add order meta
$order->update_meta_data( 'custom_field', 'custom_value' );
$order->save();
```

#### Customers

```php
// Get customer
$customer = new WC_Customer( $user_id );

// Update customer data
$customer->set_billing_first_name( 'Jane' );
$customer->set_billing_last_name( 'Smith' );
$customer->set_billing_email( 'jane@example.com' );
$customer->save();

// Get customer data
$email = $customer->get_email();
$first_name = $customer->get_first_name();
$last_name = $customer->get_last_name();
$orders = $customer->get_order_count();
$total_spent = $customer->get_total_spent();
```

### Custom Data Stores

You can create custom data stores for your own data types:

```php
/**
 * Custom data store for a custom post type
 */
class My_Custom_Data_Store extends WC_Data_Store_WP implements WC_Object_Data_Store_Interface {

    /**
     * Create a new item
     */
    public function create( &$object ) {
        $object->set_date_created( current_time( 'timestamp', true ) );

        $id = wp_insert_post( array(
            'post_type'   => 'my_custom_type',
            'post_status' => $object->get_status(),
            'post_title'  => $object->get_title(),
        ) );

        if ( $id ) {
            $object->set_id( $id );
            $this->update_post_meta( $object );
            $object->save_meta_data();
            $object->apply_changes();
        }
    }

    /**
     * Read an item
     */
    public function read( &$object ) {
        $object->set_defaults();
        $post = get_post( $object->get_id() );

        if ( ! $post || 'my_custom_type' !== $post->post_type ) {
            throw new Exception( __( 'Invalid item.', 'text-domain' ) );
        }

        $object->set_props( array(
            'title'  => $post->post_title,
            'status' => $post->post_status,
        ) );

        $object->read_meta_data();
        $object->set_object_read( true );
    }

    /**
     * Update an item
     */
    public function update( &$object ) {
        $post_data = array(
            'ID'          => $object->get_id(),
            'post_title'  => $object->get_title(),
            'post_status' => $object->get_status(),
        );

        wp_update_post( $post_data );
        $this->update_post_meta( $object );
        $object->save_meta_data();
        $object->apply_changes();
    }

    /**
     * Delete an item
     */
    public function delete( &$object, $args = array() ) {
        $id = $object->get_id();

        if ( $args['force_delete'] ) {
            wp_delete_post( $id, true );
            $object->set_id( 0 );
        } else {
            wp_trash_post( $id );
        }
    }
}

// Register the data store
add_filter( 'woocommerce_data_stores', function( $stores ) {
    $stores['my_custom_type'] = 'My_Custom_Data_Store';
    return $stores;
} );
```

---

## WooCommerce Logging

WooCommerce provides a robust logging system for debugging and monitoring.

### Using WC_Logger

```php
// Get logger instance
$logger = wc_get_logger();

// Log with different severity levels
$logger->emergency( 'System is down!', array( 'source' => 'my-plugin' ) );
$logger->alert( 'Action required immediately', array( 'source' => 'my-plugin' ) );
$logger->critical( 'Critical error occurred', array( 'source' => 'my-plugin' ) );
$logger->error( 'An error occurred', array( 'source' => 'my-plugin' ) );
$logger->warning( 'Warning: Check this', array( 'source' => 'my-plugin' ) );
$logger->notice( 'Normal but significant', array( 'source' => 'my-plugin' ) );
$logger->info( 'Informational message', array( 'source' => 'my-plugin' ) );
$logger->debug( 'Debug information', array( 'source' => 'my-plugin' ) );
```

### Logging Best Practices

```php
class My_Plugin_Logger {
    private static $source = 'my-plugin';

    /**
     * Log an API error
     */
    public static function log_api_error( $endpoint, $error, $context = array() ) {
        $logger = wc_get_logger();

        $logger->error(
            sprintf( 'API call to %s failed: %s', $endpoint, $error ),
            array(
                'source'   => self::$source,
                'endpoint' => $endpoint,
                'error'    => $error,
                'context'  => $context,
                'backtrace' => true, // Include stack trace
            )
        );
    }

    /**
     * Log successful import
     */
    public static function log_import_success( $type, $count ) {
        $logger = wc_get_logger();

        $logger->info(
            sprintf( 'Successfully imported %d %s items', $count, $type ),
            array(
                'source' => self::$source,
                'type'   => $type,
                'count'  => $count,
            )
        );
    }

    /**
     * Debug log (only when WP_DEBUG is true)
     */
    public static function debug( $message, $data = array() ) {
        if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) {
            return;
        }

        $logger = wc_get_logger();
        $logger->debug( $message, array_merge(
            array( 'source' => self::$source ),
            $data
        ) );
    }
}

// Usage
My_Plugin_Logger::log_api_error( '/orders', 'Connection timeout' );
My_Plugin_Logger::log_import_success( 'products', 150 );
My_Plugin_Logger::debug( 'Processing item', array( 'item_id' => 123 ) );
```

### Custom Log Handlers

```php
/**
 * Custom log handler that sends critical errors to Slack
 */
class My_Slack_Log_Handler extends WC_Log_Handler {

    protected $webhook_url;

    public function __construct( $webhook_url ) {
        $this->webhook_url = $webhook_url;
    }

    public function handle( $timestamp, $level, $message, $context ) {
        // Only handle critical and emergency levels
        if ( ! in_array( $level, array( 'emergency', 'critical' ), true ) ) {
            return;
        }

        // Send to Slack
        wp_remote_post( $this->webhook_url, array(
            'body' => wp_json_encode( array(
                'text' => sprintf( '[%s] %s', strtoupper( $level ), $message ),
                'attachments' => array(
                    array(
                        'color' => 'danger',
                        'fields' => array(
                            array(
                                'title' => 'Source',
                                'value' => $context['source'] ?? 'unknown',
                                'short' => true,
                            ),
                            array(
                                'title' => 'Time',
                                'value' => $timestamp,
                                'short' => true,
                            ),
                        ),
                    ),
                ),
            ) ),
            'headers' => array(
                'Content-Type' => 'application/json',
            ),
        ) );
    }
}

// Register custom handler
add_filter( 'woocommerce_register_log_handlers', function( $handlers ) {
    $handlers[] = new My_Slack_Log_Handler( 'https://hooks.slack.com/xxx' );
    return $handlers;
} );
```

---

## Modern PHP Standards

### PHP 8.1+ Features (When Appropriate)

```php
<?php declare(strict_types=1);

namespace MyPlugin\Core;

// Modern enum usage
enum Log_Level: string {
    case DEBUG = 'debug';
    case INFO = 'info';
    case WARNING = 'warning';
    case ERROR = 'error';
}

// Readonly properties for configuration
readonly class Config {
    public function __construct(
        public string $plugin_path,
        public Log_Level $default_level,
        public bool $debug_mode = false,
    ) {}
}

// Union types for flexibility
class Logger {
    public function log(
        Log_Level $level,
        string|Stringable $message,
        array $context = []
    ): void {
        // Implementation
    }
}

// Named arguments for clarity
$config = new Config(
    plugin_path: __DIR__,
    default_level: Log_Level::INFO,
    debug_mode: WP_DEBUG
);
```

### Object-Oriented Architecture

```php
namespace MyPlugin\Core;

// Interface definition
interface Logger_Interface {
    public function log( string $level, string $message, array $context = [] ): void;
}

// Trait for reusable functionality
trait Timestamp_Trait {
    protected function get_current_timestamp(): string {
        return current_time( 'mysql', true );
    }
}

// Service class with dependency injection
class File_Logger implements Logger_Interface {
    use Timestamp_Trait;

    public function __construct(
        private readonly string $log_path,
        private readonly string $min_level = 'info'
    ) {}

    public function log( string $level, string $message, array $context = [] ): void {
        $entry = sprintf(
            '[%s] %s: %s %s',
            $this->get_current_timestamp(),
            strtoupper( $level ),
            $message,
            $context ? wp_json_encode( $context ) : ''
        );

        error_log( $entry . PHP_EOL, 3, $this->log_path );
    }
}
```

---

## Asset Management

### Script and Style Enqueuing

```php
// CORRECT: Modern script enqueuing with loading strategy
function my_plugin_enqueue_scripts() {
    // Register with dependencies and version
    wp_register_script(
        'my-plugin-app',
        plugin_dir_url( __FILE__ ) . 'assets/js/app.js',
        array( 'jquery', 'wp-api' ),
        '1.0.0',
        array(
            'in_footer' => true,
            'strategy'  => 'defer' // or 'async'
        )
    );

    // Conditional loading
    if ( is_page( 'my-plugin-page' ) ) {
        wp_enqueue_script( 'my-plugin-app' );

        // Localize with proper escaping
        wp_localize_script( 'my-plugin-app', 'myPluginData', array(
            'ajaxUrl' => esc_url_raw( admin_url( 'admin-ajax.php' ) ),
            'nonce'   => wp_create_nonce( 'my_plugin_ajax' ),
            'apiUrl'  => esc_url_raw( rest_url( 'my-plugin/v1/' ) ),
        ) );
    }
}
add_action( 'wp_enqueue_scripts', 'my_plugin_enqueue_scripts' );

// CORRECT: Style enqueuing with inline styles
function my_plugin_enqueue_styles() {
    wp_enqueue_style(
        'my-plugin-style',
        plugin_dir_url( __FILE__ ) . 'assets/css/style.css',
        array(),
        '1.0.0'
    );

    // Dynamic inline styles
    $custom_css = sprintf(
        '.my-plugin-container { background-color: %s; }',
        esc_attr( get_option( 'my_plugin_bg_color', '#ffffff' ) )
    );
    wp_add_inline_style( 'my-plugin-style', $custom_css );
}
add_action( 'wp_enqueue_scripts', 'my_plugin_enqueue_styles' );
```

### Performance Guidelines

- Keep script files **under 500KB** (unminified)
- Keep style files **under 250KB** (unminified)
- Use `defer` or `async` for non-critical scripts
- Load assets **only where needed**
- Combine and minify for production
- Implement proper **caching strategies**
- Use **WordPress object cache**

---

## Internationalization & Localization

Internationalization (i18n) is the process of developing your plugin so it can easily be translated into other languages. There are 18 letters between 'i' and 'n' in 'internationalization'.

### Why Internationalization Matters

WordPress is used globally in countries where English is not the main language. Proper internationalization allows translators to localize your plugin without modifying source code.

### Text Domain Requirements

#### Text Domain Rules

- **Must match plugin slug**: For WordPress.org plugins, must match the slug (e.g., `wordpress.org/plugins/my-plugin` → text domain: `my-plugin`)
- **Use dashes, not underscores**: `my-plugin` not `my_plugin`
- **Lowercase only**: No capitals or spaces
- **Consistent everywhere**: Same in headers, functions, and loading

#### Plugin Header

```php
/**
 * Plugin Name: My Plugin
 * Author: Plugin Author
 * Text Domain: my-plugin
 * Domain Path: /languages
 */
```

**Note**: Since WordPress 4.6, the Text Domain header is optional if it matches the plugin slug, but including it is still good practice.

### Loading Translations

#### For WordPress.org Plugins

```php
// ⚠️ IMPORTANT: Since WordPress 4.6, translations are loaded automatically
// DO NOT use load_plugin_textdomain() for WordPress.org hosted plugins
// This will trigger a PluginCheck warning

// WordPress automatically loads translations from:
// - wp-content/languages/plugins/my-plugin-{locale}.mo
// - Your plugin's /languages/ directory
```

#### For Private/Premium Plugins

```php
// ONLY use for non-WordPress.org plugins:
function my_plugin_load_textdomain() {
    load_plugin_textdomain(
        'my-plugin',
        false,
        dirname( plugin_basename( __FILE__ ) ) . '/languages'
    );
}
add_action( 'init', 'my_plugin_load_textdomain' );
```

#### Custom Translation Loading

```php
// Force loading your own translations over WordPress.org translations:
function my_plugin_load_custom_translation( $mofile, $domain ) {
    if ( 'my-plugin' === $domain && false !== strpos( $mofile, WP_LANG_DIR . '/plugins/' ) ) {
        $locale = apply_filters( 'plugin_locale', determine_locale(), $domain );
        $mofile = WP_PLUGIN_DIR . '/' . dirname( plugin_basename( __FILE__ ) ) . '/languages/' . $domain . '-' . $locale . '.mo';
    }
    return $mofile;
}
add_filter( 'load_textdomain_mofile', 'my_plugin_load_custom_translation', 10, 2 );
```

### Translation Functions

#### Basic Functions

```php
// Return translation
__( 'Settings', 'my-plugin' );

// Echo translation
_e( 'Settings', 'my-plugin' );

// Return translation with context
_x( 'Post', 'noun', 'my-plugin' );
_x( 'Post', 'verb', 'my-plugin' );

// Echo translation with context
_ex( 'Post', 'noun', 'my-plugin' );
```

#### Escaping Functions

Always escape output for security:

```php
// Escape and return
esc_html__( 'Settings', 'my-plugin' );
esc_attr__( 'Title attribute', 'my-plugin' );

// Escape and echo
esc_html_e( 'Settings', 'my-plugin' );
esc_attr_e( 'Title attribute', 'my-plugin' );

// Escape with context
esc_html_x( 'Post', 'noun', 'my-plugin' );
esc_attr_x( 'Post', 'verb', 'my-plugin' );
```

#### Variables and Placeholders

```php
// ✅ CORRECT: Use placeholders
printf(
    /* translators: %s: user name */
    esc_html__( 'Welcome, %s!', 'my-plugin' ),
    esc_html( $user_name )
);

// ✅ CORRECT: Multiple placeholders with argument swapping
printf(
    /* translators: 1: city name, 2: zip code */
    esc_html__( 'Your city is %1$s, and your zip code is %2$s.', 'my-plugin' ),
    esc_html( $city ),
    esc_html( $zipcode )
);

// ❌ WRONG: Variables in strings
$text = "Your city is $city"; // Won't be translated
_e( "Your city is $city", 'my-plugin' ); // WRONG!

// ❌ WRONG: Concatenation
echo esc_html__( 'Welcome, ', 'my-plugin' ) . esc_html( $name ); // WRONG!
```

#### Plural Forms

```php
// Basic pluralization
printf(
    esc_html( _n(
        '%s comment',
        '%s comments',
        $count,
        'my-plugin'
    ) ),
    number_format_i18n( $count )
);

// Special case for singular
if ( 1 === $count ) {
    esc_html_e( 'Last item!', 'my-plugin' );
} else {
    printf(
        esc_html( _n( '%d item', '%d items', $count, 'my-plugin' ) ),
        $count
    );
}

// Deferred pluralization
$message = _n_noop(
    '%s item deleted.',
    '%s items deleted.',
    'my-plugin'
);

// Later in code:
printf(
    translate_nooped_plural( $message, $count, 'my-plugin' ),
    number_format_i18n( $count )
);
```

#### Disambiguation by Context

```php
// When same word has different meanings:
_x( 'Post', 'noun: a blog post', 'my-plugin' );
_x( 'Post', 'verb: to publish', 'my-plugin' );

// With escaping:
esc_html_x( 'May', 'month name', 'my-plugin' );
esc_html_x( 'May', 'verb: expressing possibility', 'my-plugin' );
```

### Translator Comments

Always add context for translators:

```php
/* translators: %s: user display name */
$welcome = sprintf( __( 'Hello %s', 'my-plugin' ), $user_name );

/* translators: 1: start date, 2: end date */
$date_range = sprintf(
    __( 'From %1$s to %2$s', 'my-plugin' ),
    $start_date,
    $end_date
);

/* translators: Draft saved date format, see http://php.net/date */
$saved_date_format = __( 'g:i:s a', 'my-plugin' );
```

### Best Practices

#### String Writing Guidelines

1. **Use proper English**: Minimize slang and abbreviations
2. **Use complete sentences**: Word order varies by language
3. **Split at paragraphs**: Not mid-sentence
4. **No leading/trailing whitespace**
5. **Assume 2x length**: Translations can be longer
6. **Avoid unnecessary HTML**: Keep markup minimal
7. **Use placeholders**: Never concatenate

#### Common Mistakes to Avoid

```php
// ❌ WRONG: Empty strings
__( '', 'my-plugin' ); // Reserved for Gettext

// ❌ WRONG: URLs for translation (unless locale-specific)
__( 'https://example.com', 'my-plugin' );

// ❌ WRONG: Partial sentences
__( 'Posted on', 'my-plugin' ) . ' ' . $date;

// ❌ WRONG: HTML-heavy strings
__( '<div class="notice"><p>Message</p></div>', 'my-plugin' );

// ❌ WRONG: Using variables for text domain
__( 'Text', $text_domain ); // Text domain must be a string literal

// ❌ WRONG: Translation in activation hooks
register_activation_hook( __FILE__, function() {
    add_option( 'default', __( 'Default', 'my-plugin' ) ); // Too early!
});
```

#### Correct Approaches

```php
// ✅ CORRECT: Complete translatable phrases
sprintf(
    /* translators: 1: post date, 2: post author */
    __( 'Posted on %1$s by %2$s', 'my-plugin' ),
    get_the_date(),
    get_the_author()
);

// ✅ CORRECT: Minimal HTML with placeholders
sprintf(
    /* translators: %s: emphasized text */
    __( 'This is %s important.', 'my-plugin' ),
    '<strong>' . esc_html__( 'very', 'my-plugin' ) . '</strong>'
);

// ✅ CORRECT: Deferred translation for options
// Store untranslated:
add_option( 'my_status', 'pending' );

// Translate when displaying:
$statuses = array(
    'pending' => __( 'Pending', 'my-plugin' ),
    'active'  => __( 'Active', 'my-plugin' ),
);
echo esc_html( $statuses[ get_option( 'my_status' ) ] );
```

### JavaScript Localization

```php
// Pass translated strings to JavaScript
wp_localize_script( 'my-script', 'myPluginL10n', array(
    'confirmMessage' => __( 'Are you sure?', 'my-plugin' ),
    'saveButton'     => __( 'Save Changes', 'my-plugin' ),
    'ajax_nonce'     => wp_create_nonce( 'my-plugin-ajax' ),
) );
```

```javascript
// In JavaScript:
if (confirm(myPluginL10n.confirmMessage)) {
  button.text(myPluginL10n.saveButton);
}
```

### Date and Number Functions

```php
// Format numbers for locale
$formatted = number_format_i18n( 1234.56 ); // 1,234.56 or 1.234,56

// Format dates for locale
$date = date_i18n(
    get_option( 'date_format' ),
    strtotime( '2024-01-15' )
);

// Format currency (with WooCommerce)
wc_price( 99.99 ); // $99.99 or 99,99€
```

### Tools and Testing

#### Adding Text Domain Automatically

```bash
# Download add-textdomain.php from WordPress
php add-textdomain.php my-plugin my-plugin.php > new-my-plugin.php

# Update in place
php add-textdomain.php -i my-plugin my-plugin.php

# Process entire directory
php add-textdomain.php -i my-plugin /path/to/plugin/
```

#### Generating POT Files

```bash
# Using WP-CLI
wp i18n make-pot . languages/my-plugin.pot --domain=my-plugin

# Include file headers
wp i18n make-pot . languages/my-plugin.pot --domain=my-plugin --headers
```

### File Naming Conventions

```
/languages/
├── my-plugin.pot                 # Template file
├── my-plugin-de_DE.po           # German translation source
├── my-plugin-de_DE.mo           # German translation compiled
├── my-plugin-fr_FR.po           # French translation source
└── my-plugin-fr_FR.mo           # French translation compiled
```

For WordPress.org plugins in `/wp-content/languages/plugins/`:

- Format: `{text-domain}-{locale}.mo`
- Example: `my-plugin-de_DE.mo`

---

## Database Operations

### Table Creation & Management

```php
class Database_Manager {
    private const DB_VERSION = '1.0.0';

    public function create_tables(): void {
        global $wpdb;

        $table_name = $wpdb->prefix . 'my_plugin_data';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id bigint(20) UNSIGNED NOT NULL,
            meta_key varchar(255) NOT NULL,
            meta_value longtext,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY meta_key (meta_key(191))
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );

        update_option( 'my_plugin_db_version', self::DB_VERSION );
    }

    public function maybe_upgrade(): void {
        $current_version = get_option( 'my_plugin_db_version', '0.0.0' );

        if ( version_compare( $current_version, self::DB_VERSION, '<' ) ) {
            $this->create_tables();
        }
    }
}
```

### Query Optimization

- Use **proper indexes** for WHERE and JOIN columns
- Implement **pagination** for large datasets
- Use **WordPress object cache** for repeated queries
- Prefer **WordPress APIs** over direct SQL
- **Batch operations** when possible

---

## WooCommerce Standards

### Extension Requirements

#### CRITICAL: HPOS Compatibility Declaration

**⚠️ IMPORTANT: HPOS compatibility MUST be declared at the plugin's top level, NOT inside any function or class!**

```php
// ✅ CORRECT: Declare HPOS compatibility directly in main plugin file
// This MUST be at the top level of your main plugin file (e.g., my-plugin.php)
add_action( 'before_woocommerce_init', function() {
    if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
        // Declare HPOS compatibility
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
            'custom_order_tables',
            __FILE__,
            true
        );

        // Declare Cart/Checkout Blocks compatibility
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
            'cart_checkout_blocks',
            __FILE__,
            true
        );

        // Declare Product Block Editor compatibility
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
            'product_block_editor',
            __FILE__,
            true
        );
    }
} );

// ❌ WRONG: Don't declare inside a function that's called later
function my_plugin_init() {
    // This is TOO LATE - WooCommerce has already initialized
    add_action( 'before_woocommerce_init', function() {
        // This won't work properly!
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(...);
    });
}

// ❌ WRONG: Don't hook to other actions first
add_action( 'init', function() {
    // This is TOO LATE
    add_action( 'before_woocommerce_init', function() {
        // This won't work!
    });
});
```

#### Checking HPOS Status

```php
use Automattic\WooCommerce\Utilities\OrderUtil;

// Check if HPOS is enabled
if ( OrderUtil::custom_orders_table_usage_is_enabled() ) {
    // HPOS is active - use WooCommerce CRUD APIs
} else {
    // Legacy post storage - can use WordPress post functions
}
```

### Order Management (HPOS Compatible)

#### Always Use WooCommerce CRUD APIs

```php
// ✅ CORRECT: Using WooCommerce CRUD
$order = wc_get_order( $order_id );
if ( $order ) {
    $total = $order->get_total();
    $email = $order->get_billing_email();

    // Update order meta
    $order->update_meta_data( 'my_custom_field', $value );
    $order->save();
}

// ❌ WRONG: Direct database access
$post = get_post( $order_id ); // Don't use for orders!
$meta = get_post_meta( $order_id, 'key', true ); // Don't use for orders!

// CORRECT: Query orders with wc_get_orders()
$orders = wc_get_orders( array(
    'limit'     => 10,
    'orderby'   => 'date',
    'order'     => 'DESC',
    'status'    => 'completed',
    'meta_query' => array(
        array(
            'key'     => 'my_custom_field',
            'value'   => 'some_value',
            'compare' => '='
        )
    )
) );
```

### Checkout Compatibility

```php
// Support both classic and block-based checkout
class My_Plugin_Checkout {
    public function __construct() {
        // Classic checkout
        add_action( 'woocommerce_after_checkout_billing_form', array( $this, 'add_custom_field' ) );

        // Block-based checkout
        add_action( 'woocommerce_blocks_loaded', array( $this, 'register_block_integration' ) );
    }

    public function add_custom_field(): void {
        woocommerce_form_field( 'my_custom_field', array(
            'type'        => 'text',
            'class'       => array( 'form-row-wide' ),
            'label'       => __( 'Custom Field', 'my-plugin' ),
            'required'    => true,
        ) );
    }

    public function register_block_integration(): void {
        // Register Store API endpoint
        woocommerce_store_api_register_endpoint_data( array(
            'endpoint'      => 'checkout',
            'namespace'     => 'my-plugin',
            'data_callback' => array( $this, 'get_block_data' ),
        ) );
    }
}
```

---

## Testing & Quality Assurance

### PHPUnit Testing

```php
class My_Plugin_Test extends WP_UnitTestCase {
    private $plugin_instance;

    public function setUp(): void {
        parent::setUp();
        $this->plugin_instance = new My_Plugin();
    }

    public function test_plugin_activation(): void {
        $this->plugin_instance->activate();

        // Assert database tables created
        global $wpdb;
        $table_name = $wpdb->prefix . 'my_plugin_data';
        $this->assertEquals(
            $table_name,
            $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" )
        );

        // Assert options set
        $this->assertNotFalse( get_option( 'my_plugin_activated' ) );
    }

    /**
     * @dataProvider sanitization_provider
     */
    public function test_input_sanitization( $input, $expected ): void {
        $result = $this->plugin_instance->sanitize_input( $input );
        $this->assertEquals( $expected, $result );
    }

    public function sanitization_provider(): array {
        return array(
            array( '<script>alert("test")</script>', 'alert("test")' ),
            array( 'valid_text', 'valid_text' ),
            array( '   spaces   ', 'spaces' ),
        );
    }
}
```

### Quality Assurance Checklist

#### Pre-Release Checklist

- [ ] **Security:** All inputs sanitized, outputs escaped, nonces implemented
- [ ] **Performance:** No N+1 queries, proper caching, optimized assets
- [ ] **Compatibility:** Tested with latest WordPress/WooCommerce/PHP
- [ ] **Standards:** PHPCS WordPress-Extra passing
- [ ] **Documentation:** All functions documented with PHPDoc
- [ ] **Testing:** Unit tests covering critical functionality
- [ ] **Internationalization:** All strings properly translatable
- [ ] **Accessibility:** WCAG 2.1 AA compliance
- [ ] **Database:** Proper indexes, efficient queries
- [ ] **Error Handling:** Graceful failure modes

#### Code Review Standards

- **Readability:** Self-documenting with clear variable names
- **Maintainability:** Single Responsibility Principle
- **Extensibility:** Proper hooks and filters
- **Security:** No vulnerabilities in static analysis
- **Performance:** No regressions in benchmarks

---

## Project Structure

### Recommended Plugin Structure

```
my-plugin/
├── my-plugin.php                  # Main plugin file
├── composer.json                   # Composer dependencies
├── package.json                    # NPM dependencies
├── webpack.config.js              # Build configuration
├── phpcs.xml.dist                 # PHPCS configuration
├── phpunit.xml.dist               # PHPUnit configuration
├── .wp-env.json                   # WordPress environment config
├── README.md                      # Development documentation
├── readme.txt                     # WordPress.org readme
├── CHANGELOG.md                   # Version history
│
├── assets/                        # Frontend assets
│   ├── src/                      # Source files
│   │   ├── js/
│   │   ├── css/
│   │   └── images/
│   └── build/                    # Compiled assets
│
├── includes/                      # PHP includes
│   ├── class-plugin.php          # Main plugin class
│   ├── class-admin.php           # Admin functionality
│   ├── class-public.php          # Public functionality
│   ├── class-activator.php       # Activation hooks
│   ├── class-deactivator.php     # Deactivation hooks
│   └── class-uninstaller.php     # Uninstall hooks
│
├── src/                          # PSR-4 autoloaded classes
│   ├── Admin/                    # Admin classes
│   ├── API/                      # REST API classes
│   ├── Blocks/                   # Block editor integration
│   ├── Core/                     # Core functionality
│   ├── Database/                 # Database operations
│   └── Utils/                    # Utility classes
│
├── templates/                     # Template files
│   ├── admin/                    # Admin templates
│   ├── public/                   # Frontend templates
│   └── emails/                   # Email templates
│
├── languages/                     # Translations
│   ├── my-plugin.pot             # Translation template
│   └── my-plugin-{locale}.{po|mo}
│
├── tests/                        # Test files
│   ├── bootstrap.php             # Test bootstrap
│   ├── Unit/                     # Unit tests
│   └── Integration/              # Integration tests
│
└── vendor/                       # Composer packages (gitignored)
```

---

## Workflow Guidelines

### Development Workflow

#### CRITICAL: Always Follow This Sequence

**Research → Plan → Implement**

1. **Research Phase**

   - Explore the codebase thoroughly
   - Understand existing patterns and architecture
   - Identify potential impact areas
   - Check for existing similar implementations

2. **Planning Phase**

   - Create detailed implementation plan
   - Document approach and reasoning
   - Identify potential risks
   - Get verification before proceeding

3. **Implementation Phase**
   - Execute the plan with validation checkpoints
   - Test incrementally
   - Document changes
   - Validate against requirements

### Git Workflow

```bash
# Feature branch workflow
git checkout -b feature/my-feature
git add .
git commit -m "feat: add custom checkout field"
git push origin feature/my-feature

# Commit message format
# type: subject
#
# body (optional)
#
# footer (optional)

# Types: feat, fix, docs, style, refactor, test, chore
```

### Version Management

```php
// Main plugin file
define( 'MY_PLUGIN_VERSION', '1.2.3' );

// Semantic versioning
// MAJOR.MINOR.PATCH
// 1.0.0 - Initial release
// 1.0.1 - Bug fixes
// 1.1.0 - New features (backward compatible)
// 2.0.0 - Breaking changes
```

---

## Plugin Submission Requirements

### WordPress.org Compliance

#### Required Files

- `readme.txt` following WordPress.org format
- Main plugin file with proper headers
- `languages/` directory for translations
- Uninstall routine (uninstall.php or register_uninstall_hook)

#### Prohibited Practices

- ❌ No obfuscated code
- ❌ No external dependencies without user consent
- ❌ No "powered by" links
- ❌ No tracking without opt-in consent
- ❌ No calling home without disclosure
- ❌ No advertisement without clear labeling

#### Security Requirements

- All data must be **validated and sanitized**
- All database queries must use **prepared statements**
- All output must be **properly escaped**
- **Nonces required** for all state-changing operations
- **Capability checks** for all privileged operations

---

## Support & Maintenance

### Error Handling

```php
// Use WP_Error for consistent error handling
function my_plugin_process( $data ) {
    if ( empty( $data ) ) {
        return new WP_Error(
            'invalid_data',
            __( 'Invalid data provided', 'my-plugin' ),
            array( 'status' => 400 )
        );
    }

    try {
        $result = process_data( $data );
        return $result;
    } catch ( Exception $e ) {
        // Log error for debugging
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            error_log( sprintf(
                '[My Plugin] Error in %s: %s',
                __METHOD__,
                $e->getMessage()
            ) );
        }

        return new WP_Error(
            'processing_failed',
            __( 'Unable to process request. Please try again.', 'my-plugin' )
        );
    }
}
```

### Debug Logging

```php
// IMPORTANT: Never use error_log() directly in production code
// Always wrap in WP_DEBUG checks to avoid PluginCheck warnings

// ✅ CORRECT: Debug logging with proper checks
if ( defined( 'WP_DEBUG' ) && WP_DEBUG && defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
    // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Only used in debug mode
    error_log( 'Debug message: ' . $error_details );
}

// ✅ CORRECT: Custom debug logging function
if ( ! function_exists( 'my_plugin_log' ) ) {
    function my_plugin_log( $message, $level = 'info' ) {
        // Only log when both WP_DEBUG and WP_DEBUG_LOG are enabled
        if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ||
             ! defined( 'WP_DEBUG_LOG' ) || ! WP_DEBUG_LOG ) {
            return;
        }

        $log_entry = sprintf(
            '[%s] [%s] %s',
            current_time( 'mysql' ),
            strtoupper( $level ),
            is_string( $message ) ? $message : print_r( $message, true )
        );

        // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Debug only
        error_log( $log_entry );
    }
}

// ❌ WRONG: Direct error_log without checks
error_log( 'This will trigger a PluginCheck warning!' );

// ✅ BETTER: Use WooCommerce logger for production logging
$logger = wc_get_logger();
$logger->info( 'Operation completed', array( 'source' => 'my-plugin' ) );
$logger->error( 'Operation failed: ' . $error_message, array( 'source' => 'my-plugin' ) );
```

---

## Common PluginCheck Warnings & Solutions

### Frequent Issues to Avoid

#### 1. Translation Loading (WordPress 4.6+)

```php
// ❌ WRONG - Triggers warning for WordPress.org plugins
load_plugin_textdomain( 'my-plugin', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

// ✅ CORRECT - Let WordPress handle it
// Simply ensure /languages directory exists
```

#### 2. Missing wp_unslash()

```php
// ❌ WRONG - Missing unslash
$value = sanitize_text_field( $_POST['field'] );

// ✅ CORRECT - Unslash before sanitizing
$value = sanitize_text_field( wp_unslash( $_POST['field'] ?? '' ) );
```

#### 3. Direct Database Queries

```php
// ❌ WRONG - No justification
$wpdb->insert( $table, $data );

// ✅ CORRECT - With phpcs comment
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Custom table required
$wpdb->insert( $table, $data );
```

#### 4. Missing Domain Path Directory

```php
// ❌ WRONG - Directory doesn't exist
* Domain Path: /languages

// ✅ CORRECT - Create the directory
mkdir -p languages && touch languages/.gitkeep
```

#### 5. Discouraged Functions

```php
// ❌ WRONG - Using deprecated functions
mysql_query( $query );  // Never use
create_function();      // Deprecated

// ✅ CORRECT - Use WordPress/modern alternatives
$wpdb->query( $prepared_query );
function() { /* anonymous function */ }
```

#### 6. HPOS Compatibility Issues

```php
// ❌ WRONG - Declaring HPOS compatibility too late
function my_plugin_init() {
    add_action( 'before_woocommerce_init', function() {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(...);
    });
}

// ✅ CORRECT - Declare at top level of main plugin file
add_action( 'before_woocommerce_init', function() {
    if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
            'custom_order_tables', __FILE__, true
        );
    }
} );
```

#### 7. Debug Functions in Production

```php
// ❌ WRONG - Direct error_log triggers warning
error_log( 'Debug message' );
var_dump( $data );
print_r( $array );

// ✅ CORRECT - Wrap in debug checks with phpcs comment
if ( defined( 'WP_DEBUG' ) && WP_DEBUG && defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
    // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Debug only
    error_log( 'Debug message' );
}

// ✅ CORRECT - Remove debug functions entirely for production
// (Best option if debug info isn't needed)
```

### Pre-submission Checklist

- [ ] Run PluginCheck: `npx @wordpress/plugin-check-action`
- [ ] Run PHPCS: `phpcs --standard=WordPress-Extra .`
- [ ] Verify /languages directory exists
- [ ] Check all $_POST/$\_GET have wp_unslash()
- [ ] Justify all direct DB queries with phpcs comments
- [ ] Remove load_plugin_textdomain() for WordPress.org plugins
- [ ] HPOS compatibility declared at plugin top level (not in functions)
- [ ] All order operations use WooCommerce CRUD APIs (not WordPress post functions)
- [ ] Wrap all error_log() calls in WP_DEBUG checks or remove them
- [ ] Remove all var_dump(), print_r(), and other debug functions
- [ ] Test with HPOS enabled and disabled
- [ ] Test with latest WordPress and PHP versions

---

## Final Notes

### Key Principles to Remember

1. **Security First:** Never trust user input
2. **Performance Matters:** Optimize queries and caching
3. **User Experience:** Graceful error handling
4. **Maintainability:** Clear code with documentation
5. **Compatibility:** Test across versions
6. **Standards Compliance:** Follow WordPress guidelines

### Resources

- [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/)
- [Plugin Handbook](https://developer.wordpress.org/plugins/)
- [WooCommerce Developer Docs](https://woocommerce.github.io/code-reference/)
- [WordPress Security Guide](https://developer.wordpress.org/plugins/security/)
- [Plugin Check Documentation](https://wordpress.org/plugins/plugin-check/)

---

**Remember:** This is a living document. Update it as WordPress evolves and new best practices emerge.

---

## Changelog

### Version 5.0 (2024-12-31)

- Added comprehensive Data Storage Primer section
- Added WooCommerce CRUD Objects section with complete examples
- Added WooCommerce Logging section with best practices
- Enhanced Security Implementation with detailed escaping and sanitization
- Enhanced Internationalization & Localization with comprehensive guidelines
- Added more code examples throughout all sections
- Updated table of contents to reflect new sections

### Version 4.0 (2024-12-31)

- Added Plugin Development Best Practices section
- Added UI/UX Guidelines section
- Added Plugin Headers & Metadata section
- Added Avoiding Naming Collisions section
- Added File Organization & Architecture section
- Enhanced WordPress Coding Standards with more examples
- Added comprehensive hooks and filters guidelines

### Version 3.0 (2024-12-31)

- Added comprehensive Accessibility Standards (WCAG 2.2 AA)
- Added CSS Coding Standards section
- Added HTML Coding Standards section
- Added JavaScript Coding Standards section
- Added PHP Documentation Standards section
- Added JavaScript Documentation Standards section
- Enhanced Security Implementation section
- Updated Modern PHP Standards with PHP 8.1+ features

### Version 2.0 (2024-12-31)

- Initial comprehensive documentation structure
- Added WordPress Coding Standards
- Added WooCommerce-specific guidelines
- Added Testing & Quality Assurance section
- Added Project Structure guidelines

### Version 1.0 (2024-12-30)

- Initial document creation
- Basic WordPress coding standards
- Basic security guidelines

---

_End of WordPress & WooCommerce Development Standards_
