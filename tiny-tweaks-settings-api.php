<?php
/*
 * Plugin Name:       Tiny Tweaks - Settings API
 * Plugin URI:        https://github.com/qriouslad/tiny-tweaks-settings-api
 * Description:       Tiny tweaks for WordPress
 * Version:           1.0.0
 * Requires at least: 5.2
 * Requires PHP:      7.2
 * Author:            Bowo
 * Author URI:        https://bowo.io/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Update URI:        https://github.com/qriouslad/tiny-tweaks-settings-api
 * Text Domain:       admin-footer-text-no-settings
 * Domain Path:       /languages
 * Requires Plugins:  
 */

// Prevent direct access to the PHP file, without going through WordPress where 
// the constant ABSPATH is defined (in wp-config.php)
if ( ! defined( 'ABSPATH' ) ) {
    die( 'Invalid request' );
}



// ========== Add settings page as a submenu of the Settings menu ==========

add_action( 'admin_menu', 'ttsa_admin_menu' );

function ttsa_admin_menu() {
    // Add a submenu in the Settings menu for our plugin
    add_options_page( 
        'Tiny Tweaks', // Page title
        'Tiny Tweaks', // Menu title
        'manage_options', // User capability required to use the settings page
        'ttsa-tiny-tweaks', // Menu slug
        'ttsa_settings_page' // Function to output settings page content. See below.
    );
}

function ttsa_settings_page() {
    ?>
    <div class="wrap">
        <h2><?php echo esc_html( get_admin_page_title() ); ?></h2>
        <form action="options.php" method="post">
            <?php 
            // Let's output the settings sections and fields
            settings_fields( 'ttsa_options' );
            do_settings_sections( 'ttsa-tiny-tweaks' );

            submit_button(
                'Save Settings', // Button copy
                'primary', // Type: 'primary', 'small', or 'large'
                'submit', // The 'name' attribute
                true, // Whether to wrap in <p> tag
                array( 'id' => 'ttsa-submit' ) // Additional attributes
            );
            ?>
        </form>
    </div>
    <?php
}



// ========== Register the options to be used in the settings page ==========

add_action( 'admin_init', 'ttsa_register_settings' );

function ttsa_register_settings() {

    register_setting( 
        'ttsa_options', // Option group name
        'tiny_tweaks_sa', // Option name in wp_options table
        array(
            'type'                  => 'array', // Options is stored as an array
            'description'           => '',
            'sanitize_callback'     => 'ttsa_sanitize_settings_input', // Function that sanitize input values before saving to wp_options
            'show_in_rest'          => false, // No need to show in REST API
            'default'               => array(), // Default value
        )
    );
    
    // Add "Admin Footer" section
    add_settings_section(
        'admin-footer-tweaks', // Settings page section ID
        'Admin Footer', // Section title
        'output_admin_footer_tweaks_intro', // Name of function to output intro below the section title
        'ttsa-tiny-tweaks' // Slug of the settings page to output the section into
    );
    
    // Add option to set custom text for left-side of admin footer
    add_settings_field(
        'admin_footer_left_text', // ID of the option
        'Left-Side Text', // Label for the text input field
        'ttsa_render_text_field', // Function to render the text input field
        'ttsa-tiny-tweaks', // Slug of the settings page to output the option into
        'admin-footer-tweaks', // Settings page section ID
    );

    // Add option to hide right-side WP versiopn number of admin footer
    add_settings_field(
        'admin_footer_hide_wp_version', // ID of the option
        'WordPress Version Number', // Label for the text input field
        'ttsa_render_checkbox_field', // Function to render the text input field
        'ttsa-tiny-tweaks', // Slug of the settings page to output the option into
        'admin-footer-tweaks', // Settings page section ID
        array(
            'field_id'      => 'admin_footer_hide_wp_version',
        ),
    );

    // Add "Login Page" section
    add_settings_section(
        'login-page-tweaks', // Settings page section ID
        'Login Page', // Section title
        'output_login_page_tweaks_intro', // Name of function to output intro below the section title
        'ttsa-tiny-tweaks' // Slug of the settings page to output the section into
    );

    // Add option to use Site Icon (when already set) as the login page logo
    add_settings_field(
        'login_page_use_site_icon', // ID of the option
        'Use Site Icon as Logo', // Label for the text input field
        'ttsa_render_checkbox_field', // Function to render the text input field
        'ttsa-tiny-tweaks', // Slug of the settings page to output the option into
        'login-page-tweaks', // Settings page section ID
        array(
            'field_id'      => 'login_page_use_site_icon',
        ),
    );

}



// ========== Functions used for the settings page ==========

function output_admin_footer_tweaks_intro() {
    echo '<p>Make simple modifications to the left-side credits text and the right-side WordPress version number.</p>';
}

function output_login_page_tweaks_intro() {
    echo '<p>Make simple modifications to the login page.</p>';
}

function ttsa_render_text_field() {
    $options = get_option( 'tiny_tweaks_sa' );
    $admin_footer_left_text = isset( $options['admin_footer_left_text'] ) ? $options['admin_footer_left_text'] : '';

    echo '<input id="admin_footer_left_text" name="tiny_tweaks_sa[admin_footer_left_text]" size="40" type="text" value="' . esc_attr( $admin_footer_left_text ) . '" />';
}

function ttsa_render_checkbox_field( $args ) {
    $field_id = isset( $args['field_id'] ) ? $args['field_id'] : '';
    $field_namne = 'tiny_tweaks_sa[' . $field_id . ']';
    
    switch ( $field_id ) {
        case 'admin_footer_hide_wp_version';
            $field_label = 'Let\'s hide it';
            break;

        case 'login_page_use_site_icon';
            $field_label = 'If Site Icon is set in <a href="/wp-admin/options-general.php">Settings >> General</a>, let\'s use it to replace the default WordPress logo';
            break;
    }
    
    $options = get_option( 'tiny_tweaks_sa' );
    $stored_value = isset( $options[$field_id] ) ? $options[$field_id] : false;
    
    echo '<input type="checkbox" id="' . $field_id . '" name="' . $field_namne . '" ' . checked( $stored_value, true, false ) . '>';
    echo '<label for="' . $field_namne . '">' . wp_kses_post( $field_label ) . '</label>';
}

function ttsa_sanitize_settings_input( $input ) {
    // We sanitize and normalize data input from the settings page before saving it to the tiny_tweaks_sa option in the wp_options table
    $input['admin_footer_left_text'] = ! empty( $input['admin_footer_left_text'] ) ? wp_kses_post( trim( $input['admin_footer_left_text'] ) ) : '';

    $input['admin_footer_hide_wp_version'] = ( 'on' == $input['admin_footer_hide_wp_version'] ? true : false );

    $input['login_page_use_site_icon'] = ( 'on' == $input['login_page_use_site_icon'] ? true : false );

    return $input;
}



// ========== Change left-side footer credits ==========

add_filter( 'admin_footer_text', 'ttsa_return_new_admin_footer_text' );

function ttsa_return_new_admin_footer_text( $text ) {
    $options = get_option( 'tiny_tweaks_sa' );
    $text = isset( $options['admin_footer_left_text'] ) ? $options['admin_footer_left_text'] : $text;

    return $text;
}



// ========== Remove right-side footer WP version number ==========

add_filter( 'update_footer', 'ttsa_remove_footer_wp_version', 20 );

function ttsa_remove_footer_wp_version( $content ) {
    $options = get_option( 'tiny_tweaks_sa' );
    $hide_wp_version_number = isset( $options['admin_footer_hide_wp_version'] ) ? $options['admin_footer_hide_wp_version'] : false;

    if ( $hide_wp_version_number ) {
        echo ''; // Output nothing
    } else {
        echo $content;
    }
}



// ========== Use site icon for login page logo ==========

add_action( 'login_head', 'ttsa_use_site_icon_as_login_page_logo' );

function ttsa_use_site_icon_as_login_page_logo() {
    $options = get_option( 'tiny_tweaks_sa' );
    $login_page_use_site_icon = isset( $options['login_page_use_site_icon'] ) ? $options['login_page_use_site_icon'] : false;

    if ( has_site_icon() && $login_page_use_site_icon ) { 
        ?>
        <style type="text/css">
                .login h1 a {
                        background-image: url('<?php site_icon_url( 180 ); ?>');
                }
        </style>
        <?php
    }
}