<?php
/**
 * Plugin Name: Background Music Manager
 * Description: Manage background music playback on your website.
 * Version: 1.0
 * Author: Lion
 * License: GPL2
 * Text Domain: background-music-manager
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Background_Music_Manager {

    public function __construct() {
        add_action( 'admin_init', array( $this, 'bmmw_register_settings' ) );
        add_action( 'admin_menu', array( $this, 'bmmw_add_admin_menu' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'bmmw_enqueue_scripts' ) );
        add_action( 'wp_footer', array( $this, 'bmmw_add_audio_player' ) );
    }

    public function bmmw_register_settings() {
        register_setting( 'bmmw_settings_group', 'bmmw_options', array( $this, 'bmmw_sanitize' ) );

        add_settings_section(
            'bmmw_main_section',
            __( 'Main Settings', 'background-music-manager' ),
            array( $this, 'bmmw_main_section_callback' ),
            'bmmw_settings_page'
        );

        add_settings_field(
            'bmmw_enable',
            __( 'Enable Music', 'background-music-manager' ),
            array( $this, 'bmmw_enable_callback' ),
            'bmmw_settings_page',
            'bmmw_main_section'
        );

        add_settings_field(
            'bmmw_home_only',
            __( 'Play Only on Home Page', 'background-music-manager' ),
            array( $this, 'bmmw_home_only_callback' ),
            'bmmw_settings_page',
            'bmmw_main_section'
        );

        add_settings_field(
            'bmmw_play_time',
            __( 'Playback Time (seconds)', 'background-music-manager' ),
            array( $this, 'bmmw_play_time_callback' ),
            'bmmw_settings_page',
            'bmmw_main_section'
        );

        add_settings_field(
            'bmmw_loop',
            __( 'Continuous Playback', 'background-music-manager' ),
            array( $this, 'bmmw_loop_callback' ),
            'bmmw_settings_page',
            'bmmw_main_section'
        );

        add_settings_field(
            'bmmw_volume',
            __( 'Volume (0.0 - 1.0)', 'background-music-manager' ),
            array( $this, 'bmmw_volume_callback' ),
            'bmmw_settings_page',
            'bmmw_main_section'
        );

        add_settings_field(
            'bmmw_music_file',
            __( 'Upload Track', 'background-music-manager' ),
            array( $this, 'bmmw_music_file_callback' ),
            'bmmw_settings_page',
            'bmmw_main_section'
        );
    }

    public function bmmw_main_section_callback() {
        echo '<div class="notice notice-warning is-dismissible">';
        echo '<p>' . esc_html__( 'Note: Due to modern browser restrictions, music will only play after user interaction with the site (e.g., clicking or pressing a key).', 'background-music-manager' ) . '</p>';
        echo '</div>';
    }

    public function bmmw_sanitize( $input ) {
        $nonce = isset( $_POST['bmmw_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['bmmw_nonce'] ) ) : '';
        if ( ! wp_verify_nonce( $nonce, 'bmmw_options_save' ) ) {
            add_settings_error( 'bmmw_options', 'nonce_error', __( 'Invalid nonce. Please try again.', 'background-music-manager' ) );
            return array();
        }

        $new_input = array();

        if ( isset( $input['enable'] ) ) {
            $new_input['enable'] = absint( $input['enable'] );
        }

        if ( isset( $input['home_only'] ) ) {
            $new_input['home_only'] = absint( $input['home_only'] );
        }

        if ( isset( $input['play_time'] ) ) {
            $new_input['play_time'] = absint( $input['play_time'] );
        }

        if ( isset( $input['loop'] ) ) {
            $new_input['loop'] = absint( $input['loop'] );
        }

        if ( isset( $input['volume'] ) ) {
            $volume = floatval( $input['volume'] );
            $new_input['volume'] = ( $volume >= 0.0 && $volume <= 1.0 ) ? $volume : 0.5;
        }

        if ( isset( $_FILES['music_file']['name'] ) && ! empty( $_FILES['music_file']['name'] ) ) {
            $uploaded = media_handle_upload( 'music_file', 0 );
            if ( is_numeric( $uploaded ) ) {
                $new_input['music_file'] = wp_get_attachment_url( $uploaded );
            } else {
                add_settings_error( 'bmmw_options', 'music_file_error', __( 'File upload error.', 'background-music-manager' ) );
            }
        } elseif ( isset( $input['music_file_existing'] ) ) {
            $new_input['music_file'] = esc_url_raw( $input['music_file_existing'] );
        }

        return $new_input;
    }

    public function bmmw_add_admin_menu() {
        add_options_page(
            esc_html__( 'Background Music Manager', 'background-music-manager' ),
            esc_html__( 'Background Music Manager', 'background-music-manager' ),
            'manage_options',
            'background-music-manager',
            array( $this, 'bmmw_options_page' )
        );
    }

    public function bmmw_options_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
            <form method="post" enctype="multipart/form-data" action="options.php">
                <?php
                settings_fields( 'bmmw_settings_group' );
                wp_nonce_field( 'bmmw_options_save', 'bmmw_nonce' );
                do_settings_sections( 'bmmw_settings_page' );
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    public function bmmw_enable_callback() {
        $options = get_option( 'bmmw_options' );
        ?>
        <input type="checkbox" id="enable" name="bmmw_options[enable]" value="1" <?php checked( isset( $options['enable'] ) && $options['enable'], 1 ); ?> />
        <label for="enable"><?php esc_html_e( 'Enable background music site-wide', 'background-music-manager' ); ?></label>
        <?php
    }

    public function bmmw_home_only_callback() {
        $options = get_option( 'bmmw_options' );
        ?>
        <input type="checkbox" id="home_only" name="bmmw_options[home_only]" value="1" <?php checked( isset( $options['home_only'] ) && $options['home_only'], 1 ); ?> />
        <label for="home_only"><?php esc_html_e( 'Play only on the home page', 'background-music-manager' ); ?></label>
        <?php
    }

    public function bmmw_play_time_callback() {
        $options = get_option( 'bmmw_options' );
        ?>
        <input type="number" id="play_time" name="bmmw_options[play_time]" value="<?php echo isset( $options['play_time'] ) ? esc_attr( $options['play_time'] ) : esc_attr( 30 ); ?>" min="0" />
        <p class="description"><?php esc_html_e( 'Playback time in seconds (0 for infinite playback).', 'background-music-manager' ); ?></p>
        <?php
    }

    public function bmmw_loop_callback() {
        $options = get_option( 'bmmw_options' );
        ?>
        <input type="checkbox" id="loop" name="bmmw_options[loop]" value="1" <?php checked( isset( $options['loop'] ) && $options['loop'], 1 ); ?> />
        <label for="loop"><?php esc_html_e( 'Loop the track continuously', 'background-music-manager' ); ?></label>
        <?php
    }

    public function bmmw_volume_callback() {
        $options = get_option( 'bmmw_options' );
        ?>
        <input type="number" id="volume" name="bmmw_options[volume]" value="<?php echo isset( $options['volume'] ) ? esc_attr( $options['volume'] ) : esc_attr( 0.5 ); ?>" step="0.1" min="0" max="1" />
        <p class="description"><?php esc_html_e( 'Music playback volume (0.0 to 1.0).', 'background-music-manager' ); ?></p>
        <?php
    }

    public function bmmw_music_file_callback() {
        $options = get_option( 'bmmw_options' );
        ?>
        <?php if ( isset( $options['music_file'] ) ) : ?>
            <p><?php esc_html_e( 'Current Track:', 'background-music-manager' ); ?> 
                <a href="<?php echo esc_url( $options['music_file'] ); ?>" target="_blank"><?php echo esc_html( basename( wp_parse_url( $options['music_file'], PHP_URL_PATH ) ) ); ?></a>
            </p>
            <input type="hidden" name="bmmw_options[music_file_existing]" value="<?php echo esc_url( $options['music_file'] ); ?>" />
        <?php endif; ?>
        <input type="file" id="music_file" name="music_file" accept="audio/*" />
        <p class="description"><?php esc_html_e( 'Upload an audio file to play.', 'background-music-manager' ); ?></p>
        <?php
    }

    public function bmmw_enqueue_scripts() {
        $options = get_option( 'bmmw_options' );

        if ( ! isset( $options['enable'] ) || ! $options['enable'] ) {
            return;
        }

        if ( isset( $options['home_only'] ) && $options['home_only'] && ! is_front_page() ) {
            return;
        }

        if ( ! isset( $options['music_file'] ) || empty( $options['music_file'] ) ) {
            return;
        }

        wp_enqueue_script( 'bmmw_script', plugin_dir_url( __FILE__ ) . 'js/bmmw-script.js', array(), '1.0', true );

        wp_localize_script( 'bmmw_script', 'bmmw_settings', array(
            'music_url' => esc_url( $options['music_file'] ),
            'play_time' => isset( $options['play_time'] ) ? intval( $options['play_time'] ) : 30,
            'loop'      => isset( $options['loop'] ) && $options['loop'] ? true : false,
            'volume'    => isset( $options['volume'] ) ? floatval( $options['volume'] ) : 0.5,
        ) );
    }

    public function bmmw_add_audio_player() {
        $options = get_option( 'bmmw_options' );

        if ( ! isset( $options['enable'] ) || ! $options['enable'] ) {
            return;
        }

        if ( isset( $options['home_only'] ) && $options['home_only'] && ! is_front_page() ) {
            return;
        }

        if ( ! isset( $options['music_file'] ) || empty( $options['music_file'] ) ) {
            return;
        }

        echo '<audio id="bmmw-audio" src="' . esc_url( $options['music_file'] ) . '"></audio>';
    }
}

new Background_Music_Manager();
?>
