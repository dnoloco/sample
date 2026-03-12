<?php
/**
 * Calendar Block Registrar — The "Skin" for the Calendar Module
 *
 * Registers the simplepco/calendar-events Gutenberg block and handles
 * server-side rendering. Uses the Event Repository (not the API model
 * directly) for data access.
 *
 * @package SimplePCO
 * @since 3.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SimplePCO_Calendar_Block_Registrar implements SimplePCO_Block_Registrar_Interface {

    /**
     * @var SimplePCO_Event_Repository
     */
    protected $event_repository;

    /**
     * @param SimplePCO_Event_Repository $event_repository The event repository.
     */
    public function __construct( SimplePCO_Event_Repository $event_repository ) {
        $this->event_repository = $event_repository;
    }

    /**
     * Register Gutenberg blocks.
     *
     * @return void
     */
    public function register_blocks() {
        $asset_file = SIMPLEPCO_PLUGIN_DIR . 'build/blocks.asset.php';

        if ( ! file_exists( $asset_file ) ) {
            // Build not yet run — blocks will be unavailable.
            return;
        }

        $asset = include $asset_file;

        wp_register_script(
            'simplepco-blocks',
            SIMPLEPCO_PLUGIN_URL . 'build/blocks.js',
            $asset['dependencies'],
            $asset['version'],
            true
        );

        wp_register_style(
            'simplepco-blocks-editor',
            SIMPLEPCO_PLUGIN_URL . 'assets/admin/css/blocks-editor.css',
            [],
            SIMPLEPCO_VERSION
        );

        register_block_type( 'simplepco/calendar-events', [
            'editor_script'   => 'simplepco-blocks',
            'editor_style'    => 'simplepco-blocks-editor',
            'attributes'      => [
                'count' => [
                    'type'    => 'number',
                    'default' => 5,
                ],
                'view' => [
                    'type'    => 'string',
                    'default' => 'list',
                ],
                'showImages' => [
                    'type'    => 'boolean',
                    'default' => true,
                ],
                'showDescription' => [
                    'type'    => 'boolean',
                    'default' => true,
                ],
            ],
            'render_callback' => [ $this, 'render_calendar_events_block' ],
        ] );
    }

    /**
     * Server-side render callback for the calendar events block.
     *
     * @param array $attributes Block attributes.
     * @return string Rendered HTML.
     */
    public function render_calendar_events_block( $attributes ) {
        $count    = isset( $attributes['count'] ) ? (int) $attributes['count'] : 5;
        $view     = isset( $attributes['view'] ) ? $attributes['view'] : 'list';
        $show_img = isset( $attributes['showImages'] ) ? $attributes['showImages'] : true;
        $show_desc = isset( $attributes['showDescription'] ) ? $attributes['showDescription'] : true;

        $events = $this->event_repository->find_featured( $count );

        if ( empty( $events ) ) {
            return '<div class="simplepco-events-block"><p>' . esc_html__( 'No upcoming events.', 'simplepco-online' ) . '</p></div>';
        }

        $classes = 'simplepco-events-block simplepco-events-' . esc_attr( $view );
        $output  = '<div class="' . $classes . '">';

        foreach ( $events as $event ) {
            $output .= '<div class="simplepco-event-item">';

            if ( $show_img && ! empty( $event['image_url'] ) ) {
                $output .= '<img src="' . esc_url( $event['image_url'] ) . '" alt="' . esc_attr( $event['name'] ) . '" class="simplepco-event-image" />';
            }

            $output .= '<div class="simplepco-event-details">';
            $output .= '<h4 class="simplepco-event-title">' . esc_html( $event['name'] ) . '</h4>';
            $output .= '<time class="simplepco-event-date">' . esc_html( $event['starts_at'] ) . '</time>';

            if ( $show_desc && ! empty( $event['description'] ) ) {
                $output .= '<p class="simplepco-event-description">' . wp_kses_post( $event['description'] ) . '</p>';
            }

            if ( ! empty( $event['location'] ) ) {
                $output .= '<span class="simplepco-event-location">' . esc_html( $event['location'] ) . '</span>';
            }

            $output .= '</div></div>';
        }

        $output .= '</div>';

        return $output;
    }
}
