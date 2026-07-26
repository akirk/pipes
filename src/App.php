<?php

namespace Pipes;

use WpApp\BaseApp;
use WpApp\WpApp;

class App extends BaseApp {
    public const POST_TYPE = 'pipes_pipe';
    public const REST_NAMESPACE = 'pipes/v1';
    public const STARTER_META_KEY = '_pipes_starter_id';

    public function __construct() {
        $this->app = new WpApp( $this->get_template_dir(), $this->get_url_path(), [
            'require_login'      => true,
            'require_capability' => 'read',
            'app_name'           => 'Pipes',
            'my_apps'            => 'Pipes',
        ] );

        add_action( 'init', [ $this, 'register_post_types' ] );
        add_action( 'rest_api_init', [ $this, 'register_rest_routes' ] );
        add_action( 'wp_abilities_api_categories_init', [ $this, 'register_ability_category' ] );
        add_action( 'wp_abilities_api_init', [ $this, 'register_abilities' ] );
        add_filter( 'ai_assistant_ability_domains', [ $this, 'register_ai_assistant_ability_domains' ] );
    }

    protected function get_url_path(): string {
        return 'pipes';
    }

    protected function get_template_dir(): string {
        return dirname( __DIR__ ) . '/templates';
    }

    protected function setup_database(): void {}

    protected function setup_routes(): void {}

    protected function setup_menu(): void {}

    public function register_post_types(): void {
        register_post_type( self::POST_TYPE, [
            'labels'       => [
                'name'          => __( 'Pipes', 'pipes' ),
                'singular_name' => __( 'Pipe', 'pipes' ),
                'add_new_item'  => __( 'Add New Pipe', 'pipes' ),
                'edit_item'     => __( 'Edit Pipe', 'pipes' ),
            ],
            'public'       => false,
            'show_ui'      => true,
            'show_in_rest' => false,
            'supports'     => [ 'title', 'author' ],
            'menu_icon'    => 'dashicons-randomize',
        ] );
    }

    public function register_rest_routes(): void {
        register_rest_route( self::REST_NAMESPACE, '/abilities', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'rest_get_abilities' ],
            'permission_callback' => [ $this, 'can_use_app' ],
        ] );

        register_rest_route( self::REST_NAMESPACE, '/examples', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'rest_get_examples' ],
            'permission_callback' => [ $this, 'can_use_app' ],
        ] );

        register_rest_route( self::REST_NAMESPACE, '/examples/(?P<id>[a-z0-9-]+)', [
            'methods'             => 'POST',
            'callback'            => [ $this, 'rest_load_example' ],
            'permission_callback' => [ $this, 'can_edit_pipes' ],
        ] );

        register_rest_route( self::REST_NAMESPACE, '/pipes', [
            [
                'methods'             => 'GET',
                'callback'            => [ $this, 'rest_list_pipes' ],
                'permission_callback' => [ $this, 'can_use_app' ],
            ],
            [
                'methods'             => 'POST',
                'callback'            => [ $this, 'rest_save_pipe' ],
                'permission_callback' => [ $this, 'can_edit_pipes' ],
            ],
        ] );

        register_rest_route( self::REST_NAMESPACE, '/pipes/(?P<id>\d+)', [
            [
                'methods'             => 'GET',
                'callback'            => [ $this, 'rest_get_pipe' ],
                'permission_callback' => [ $this, 'can_use_app' ],
            ],
            [
                'methods'             => 'POST',
                'callback'            => [ $this, 'rest_save_pipe' ],
                'permission_callback' => [ $this, 'can_edit_pipes' ],
            ],
            [
                'methods'             => 'DELETE',
                'callback'            => [ $this, 'rest_delete_pipe' ],
                'permission_callback' => [ $this, 'can_edit_pipes' ],
            ],
        ] );

        register_rest_route( self::REST_NAMESPACE, '/run', [
            'methods'             => 'POST',
            'callback'            => [ $this, 'rest_run_pipe' ],
            'permission_callback' => [ $this, 'can_use_app' ],
        ] );
    }

    public function can_use_app(): bool {
        return current_user_can( 'read' );
    }

    public function can_edit_pipes(): bool {
        return current_user_can( 'edit_posts' );
    }

    public function register_ability_category(): void {
        if ( ! function_exists( 'wp_register_ability_category' ) ) {
            return;
        }

        if ( function_exists( 'wp_has_ability_category' ) && wp_has_ability_category( 'pipes' ) ) {
            return;
        }

        wp_register_ability_category( 'pipes', [
            'label'       => __( 'Pipes', 'pipes' ),
            'description' => __( 'Compose and execute flows that connect WordPress abilities.', 'pipes' ),
        ] );
    }

    public function register_abilities(): void {
        if ( ! function_exists( 'wp_register_ability' ) ) {
            return;
        }

        $this->register_pipe_ability( 'pipes/run-pipe', [
            'label'               => __( 'Run Pipe', 'pipes' ),
            'description'         => __( 'Runs a saved Pipes flow by ID and returns every node result.', 'pipes' ),
            'input_schema'        => [
                'type'                 => 'object',
                'required'             => [ 'pipe_id' ],
                'properties'           => [
                    'pipe_id' => [
                        'type'        => 'integer',
                        'description' => __( 'Saved pipe post ID.', 'pipes' ),
                    ],
                    'confirm_destructive' => [
                        'type'        => 'boolean',
                        'description' => __( 'Required to run flows containing destructive abilities.', 'pipes' ),
                        'default'     => false,
                    ],
                ],
                'additionalProperties' => false,
            ],
            'output_schema'       => [
                'type'       => 'object',
                'properties' => [
                    'pipe_id' => [ 'type' => 'integer' ],
                    'results' => [ 'type' => 'object' ],
                ],
            ],
            'execute_callback'    => [ $this, 'ability_run_pipe' ],
            'meta'                => $this->ability_meta( false, false, false, __( 'Return a compact summary of each node result and note any failed node.', 'pipes' ) ),
        ] );

        $this->register_pipe_ability( 'pipes/extract-path', [
            'label'               => __( 'Extract Path', 'pipes' ),
            'description'         => __( 'Extracts one dot-path value from an object, array, or scalar.', 'pipes' ),
            'input_schema'        => [
                'type'                 => 'object',
                'required'             => [ 'value', 'path' ],
                'properties'           => [
                    'value' => [
                        'type'        => [ 'object', 'array', 'string', 'number', 'integer', 'boolean', 'null' ],
                        'description' => __( 'Source value to read from.', 'pipes' ),
                    ],
                    'path'  => [
                        'type'        => 'string',
                        'description' => __( 'Dot path such as articles.0.page_id. Empty returns the whole value.', 'pipes' ),
                    ],
                ],
                'additionalProperties' => false,
            ],
            'output_schema'       => [
                'type'       => 'object',
                'properties' => [
                    'value' => [
                        'type'        => [ 'object', 'array', 'string', 'number', 'integer', 'boolean', 'null' ],
                        'description' => __( 'Extracted value.', 'pipes' ),
                    ],
                ],
            ],
            'execute_callback'    => [ $this, 'ability_extract_path' ],
            'meta'                => $this->ability_meta( true, false, true, __( 'Use this to make an intermediate output path explicit and reusable.', 'pipes' ) ),
        ] );

        $this->register_pipe_ability( 'pipes/limit-items', [
            'label'               => __( 'Limit Items', 'pipes' ),
            'description'         => __( 'Takes a list and returns a slice of it.', 'pipes' ),
            'input_schema'        => [
                'type'                 => 'object',
                'required'             => [ 'items' ],
                'properties'           => [
                    'items'  => [
                        'type'        => 'array',
                        'description' => __( 'Items to slice.', 'pipes' ),
                        'items'       => [ 'type' => [ 'object', 'array', 'string', 'number', 'integer', 'boolean', 'null' ] ],
                    ],
                    'limit'  => [
                        'type'        => 'integer',
                        'description' => __( 'Maximum items to return. Defaults to 10.', 'pipes' ),
                        'default'     => 10,
                    ],
                    'offset' => [
                        'type'        => 'integer',
                        'description' => __( 'Number of items to skip. Defaults to 0.', 'pipes' ),
                        'default'     => 0,
                    ],
                ],
                'additionalProperties' => false,
            ],
            'output_schema'       => $this->items_output_schema(),
            'execute_callback'    => [ $this, 'ability_limit_items' ],
            'meta'                => $this->ability_meta( true, false, true, __( 'Use this after search or list abilities before downstream detail calls.', 'pipes' ) ),
        ] );

        $this->register_pipe_ability( 'pipes/filter-items', [
            'label'               => __( 'Filter Items', 'pipes' ),
            'description'         => __( 'Filters a list by checking a dot-path value on each item.', 'pipes' ),
            'input_schema'        => [
                'type'                 => 'object',
                'required'             => [ 'items', 'path' ],
                'properties'           => [
                    'items'    => [
                        'type'        => 'array',
                        'description' => __( 'Items to filter.', 'pipes' ),
                        'items'       => [ 'type' => [ 'object', 'array', 'string', 'number', 'integer', 'boolean', 'null' ] ],
                    ],
                    'path'     => [
                        'type'        => 'string',
                        'description' => __( 'Dot path inside each item, such as title or author.name.', 'pipes' ),
                    ],
                    'contains' => [
                        'type'        => 'string',
                        'description' => __( 'Keep items whose path value contains this text.', 'pipes' ),
                    ],
                    'equals'   => [
                        'type'        => 'string',
                        'description' => __( 'Keep items whose path value equals this text.', 'pipes' ),
                    ],
                ],
                'additionalProperties' => false,
            ],
            'output_schema'       => $this->items_output_schema(),
            'execute_callback'    => [ $this, 'ability_filter_items' ],
            'meta'                => $this->ability_meta( true, false, true, __( 'Use this to narrow list results before mapping, joining, or detail lookups.', 'pipes' ) ),
        ] );

        $this->register_pipe_ability( 'pipes/pluck-field', [
            'label'               => __( 'Pluck Field', 'pipes' ),
            'description'         => __( 'Reads one dot-path from each item in a list and returns the collected values.', 'pipes' ),
            'input_schema'        => [
                'type'                 => 'object',
                'required'             => [ 'items', 'path' ],
                'properties'           => [
                    'items'  => [
                        'type'        => 'array',
                        'description' => __( 'Items to read from.', 'pipes' ),
                        'items'       => [ 'type' => [ 'object', 'array', 'string', 'number', 'integer', 'boolean', 'null' ] ],
                    ],
                    'path'   => [
                        'type'        => 'string',
                        'description' => __( 'Dot path to read from each item.', 'pipes' ),
                    ],
                    'unique' => [
                        'type'        => 'boolean',
                        'description' => __( 'Remove duplicate scalar values.', 'pipes' ),
                        'default'     => false,
                    ],
                ],
                'additionalProperties' => false,
            ],
            'output_schema'       => [
                'type'       => 'object',
                'properties' => [
                    'values' => [
                        'type'  => 'array',
                        'items' => [ 'type' => [ 'object', 'array', 'string', 'number', 'integer', 'boolean', 'null' ] ],
                    ],
                    'total'  => [ 'type' => 'integer' ],
                ],
            ],
            'execute_callback'    => [ $this, 'ability_pluck_field' ],
            'meta'                => $this->ability_meta( true, false, true, __( 'Use this to turn a list of objects into a list of titles, URLs, IDs, or other fields.', 'pipes' ) ),
        ] );

        $this->register_pipe_ability( 'pipes/join-text', [
            'label'               => __( 'Join Text', 'pipes' ),
            'description'         => __( 'Turns an array of values or item fields into one text string.', 'pipes' ),
            'input_schema'        => [
                'type'                 => 'object',
                'required'             => [ 'items' ],
                'properties'           => [
                    'items'     => [
                        'type'        => 'array',
                        'description' => __( 'Values or items to join.', 'pipes' ),
                        'items'       => [ 'type' => [ 'object', 'array', 'string', 'number', 'integer', 'boolean', 'null' ] ],
                    ],
                    'path'      => [
                        'type'        => 'string',
                        'description' => __( 'Optional dot path to read from each item before joining.', 'pipes' ),
                    ],
                    'separator' => [
                        'type'        => 'string',
                        'description' => __( 'Text between values. Defaults to a newline.', 'pipes' ),
                        'default'     => "\n",
                    ],
                    'prefix'    => [
                        'type'        => 'string',
                        'description' => __( 'Optional text before every value.', 'pipes' ),
                    ],
                    'suffix'    => [
                        'type'        => 'string',
                        'description' => __( 'Optional text after every value.', 'pipes' ),
                    ],
                ],
                'additionalProperties' => false,
            ],
            'output_schema'       => [
                'type'       => 'object',
                'properties' => [
                    'text'  => [ 'type' => 'string' ],
                    'total' => [ 'type' => 'integer' ],
                ],
            ],
            'execute_callback'    => [ $this, 'ability_join_text' ],
            'meta'                => $this->ability_meta( true, false, true, __( 'Use this to make a digest, prompt, note, or compact text summary from list output.', 'pipes' ) ),
        ] );
    }

    public function rest_get_abilities() {
        if ( ! function_exists( 'wp_get_abilities' ) ) {
            return new \WP_Error( 'pipes_abilities_unavailable', __( 'The WordPress Abilities API is not available.', 'pipes' ), [ 'status' => 501 ] );
        }

        $abilities = [];
        foreach ( wp_get_abilities() as $id => $ability ) {
            $details = $this->get_ability_details( $id, $ability );
            if ( 'pipes/run-pipe' === $details['id'] ) {
                continue;
            }
            $abilities[] = $details;
        }

        usort( $abilities, static function( array $a, array $b ): int {
            return strcasecmp( $a['label'], $b['label'] );
        } );

        return rest_ensure_response( [ 'abilities' => $abilities ] );
    }

    public function rest_get_examples() {
        if ( ! function_exists( 'wp_get_abilities' ) ) {
            return rest_ensure_response( [ 'examples' => [] ] );
        }

        $registered = [];
        foreach ( wp_get_abilities() as $id => $ability ) {
            $registered[ (string) $id ] = true;
            $registered[ $this->get_ability_id( $id, $ability ) ] = true;
        }
        $examples = [];

        foreach ( $this->get_starter_pipes() as $example ) {
            $missing = [];
            foreach ( $example['requires'] as $ability_id ) {
                if ( empty( $registered[ $ability_id ] ) ) {
                    $missing[] = $ability_id;
                }
            }

            if ( [] !== $missing ) {
                continue;
            }

            unset( $example['requires'] );
            $example['pipe_id'] = $this->find_user_starter_pipe_id( $example['id'] );
            $examples[] = $example;
        }

        return rest_ensure_response( [ 'examples' => $examples ] );
    }

    public function rest_load_example( \WP_REST_Request $request ) {
        $example = $this->get_starter_pipe( (string) $request['id'] );
        if ( null === $example ) {
            return new \WP_Error( 'pipes_example_not_found', __( 'Starter pipe not found.', 'pipes' ), [ 'status' => 404 ] );
        }

        $post_id = $this->find_user_starter_pipe_id( $example['id'] );
        if ( $post_id > 0 ) {
            $post = $this->get_pipe_post( $post_id );
            if ( is_wp_error( $post ) ) {
                return $post;
            }

            return rest_ensure_response( [ 'pipe' => $this->format_pipe( $post, true ) ] );
        }

        $post_id = wp_insert_post( wp_slash( [
            'post_type'    => self::POST_TYPE,
            'post_status'  => 'private',
            'post_title'   => (string) $example['title'],
            'post_content' => wp_json_encode( $this->sanitize_graph( $example['graph'] ) ),
            'post_author'  => get_current_user_id(),
        ] ), true );

        if ( is_wp_error( $post_id ) ) {
            return $post_id;
        }

        update_post_meta( $post_id, self::STARTER_META_KEY, sanitize_key( $example['id'] ) );

        return rest_ensure_response( [ 'pipe' => $this->format_pipe( get_post( $post_id ), true ) ] );
    }

    public function rest_list_pipes() {
        $query = new \WP_Query( [
            'post_type'      => self::POST_TYPE,
            'post_status'    => [ 'publish', 'draft', 'private' ],
            'posts_per_page' => 100,
            'orderby'        => 'modified',
            'order'          => 'DESC',
            'author'         => get_current_user_id(),
        ] );

        $pipes = [];
        foreach ( $query->posts as $post ) {
            $pipes[] = $this->format_pipe( $post );
        }

        return rest_ensure_response( [ 'pipes' => $pipes ] );
    }

    public function rest_get_pipe( \WP_REST_Request $request ) {
        $post = $this->get_pipe_post( (int) $request['id'] );
        if ( is_wp_error( $post ) ) {
            return $post;
        }

        return rest_ensure_response( [ 'pipe' => $this->format_pipe( $post, true ) ] );
    }

    public function rest_save_pipe( \WP_REST_Request $request ) {
        $payload = $request->get_json_params();
        $payload = is_array( $payload ) ? $payload : [];
        $title   = isset( $payload['title'] ) ? sanitize_text_field( $payload['title'] ) : '';
        $graph   = isset( $payload['graph'] ) && is_array( $payload['graph'] ) ? $payload['graph'] : [];

        if ( '' === $title ) {
            return new \WP_Error( 'pipes_title_required', __( 'Pipe title is required.', 'pipes' ), [ 'status' => 400 ] );
        }

        $post_id = isset( $request['id'] ) ? (int) $request['id'] : 0;
        if ( $post_id > 0 ) {
            $post = $this->get_pipe_post( $post_id );
            if ( is_wp_error( $post ) ) {
                return $post;
            }
        }

        $postarr = [
            'post_type'    => self::POST_TYPE,
            'post_status'  => 'private',
            'post_title'   => $title,
            'post_content' => wp_json_encode( $this->sanitize_graph( $graph ) ),
            'post_author'  => get_current_user_id(),
        ];

        if ( $post_id > 0 ) {
            $postarr['ID'] = $post_id;
            $result = wp_update_post( wp_slash( $postarr ), true );
        } else {
            $result = wp_insert_post( wp_slash( $postarr ), true );
        }

        if ( is_wp_error( $result ) ) {
            return $result;
        }

        return rest_ensure_response( [ 'pipe' => $this->format_pipe( get_post( $result ), true ) ] );
    }

    public function rest_delete_pipe( \WP_REST_Request $request ) {
        $post = $this->get_pipe_post( (int) $request['id'] );
        if ( is_wp_error( $post ) ) {
            return $post;
        }

        wp_delete_post( $post->ID, true );

        return rest_ensure_response( [ 'deleted' => true ] );
    }

    public function rest_run_pipe( \WP_REST_Request $request ) {
        $payload = $request->get_json_params();
        $payload = is_array( $payload ) ? $payload : [];

        if ( ! empty( $payload['pipe_id'] ) ) {
            $post = $this->get_pipe_post( (int) $payload['pipe_id'] );
            if ( is_wp_error( $post ) ) {
                return $post;
            }
            $graph = $this->get_pipe_graph( $post );
            $pipe_id = $post->ID;
        } else {
            $graph = isset( $payload['graph'] ) && is_array( $payload['graph'] ) ? $this->sanitize_graph( $payload['graph'] ) : [];
            $pipe_id = 0;
        }

        $result = $this->run_graph( $graph, ! empty( $payload['confirm_destructive'] ) );
        if ( is_wp_error( $result ) ) {
            return $result;
        }

        $result['pipe_id'] = $pipe_id;

        return rest_ensure_response( $result );
    }

    public function ability_run_pipe( $input ): array {
        $input = is_array( $input ) ? $input : [];
        $post = $this->get_pipe_post( (int) ( $input['pipe_id'] ?? 0 ) );
        if ( is_wp_error( $post ) ) {
            return [
                'success' => false,
                'message' => $post->get_error_message(),
            ];
        }

        $result = $this->run_graph( $this->get_pipe_graph( $post ), ! empty( $input['confirm_destructive'] ) );
        if ( is_wp_error( $result ) ) {
            return [
                'success' => false,
                'message' => $result->get_error_message(),
            ];
        }

        $result['pipe_id'] = $post->ID;

        return $result;
    }

    public function ability_extract_path( $input ): array {
        $input = is_array( $input ) ? $input : [];
        return [
            'value' => $this->get_path_value( $input['value'] ?? null, (string) ( $input['path'] ?? '' ) ),
        ];
    }

    public function ability_limit_items( $input ): array {
        $input  = is_array( $input ) ? $input : [];
        $items  = isset( $input['items'] ) && is_array( $input['items'] ) ? array_values( $input['items'] ) : [];
        $total  = count( $items );
        $limit  = max( 0, min( 500, isset( $input['limit'] ) ? absint( $input['limit'] ) : 10 ) );
        $offset = max( 0, isset( $input['offset'] ) ? absint( $input['offset'] ) : 0 );

        return [
            'items'  => array_values( array_slice( $items, $offset, $limit ) ),
            'total'  => $total,
            'offset' => $offset,
            'limit'  => $limit,
        ];
    }

    public function ability_filter_items( $input ): array {
        $input    = is_array( $input ) ? $input : [];
        $items    = isset( $input['items'] ) && is_array( $input['items'] ) ? array_values( $input['items'] ) : [];
        $path     = (string) ( $input['path'] ?? '' );
        $contains = array_key_exists( 'contains', $input ) ? strtolower( (string) $input['contains'] ) : '';
        $equals   = array_key_exists( 'equals', $input ) ? strtolower( (string) $input['equals'] ) : '';

        $filtered = [];
        foreach ( $items as $item ) {
            $value = $this->get_path_value( $item, $path );
            $text  = strtolower( $this->stringify_glue_value( $value ) );

            if ( '' !== $equals && $text !== $equals ) {
                continue;
            }

            if ( '' !== $contains && false === strpos( $text, $contains ) ) {
                continue;
            }

            $filtered[] = $item;
        }

        return [
            'items'   => array_values( $filtered ),
            'total'   => count( $items ),
            'matched' => count( $filtered ),
        ];
    }

    public function ability_pluck_field( $input ): array {
        $input  = is_array( $input ) ? $input : [];
        $items  = isset( $input['items'] ) && is_array( $input['items'] ) ? array_values( $input['items'] ) : [];
        $path   = (string) ( $input['path'] ?? '' );
        $unique = ! empty( $input['unique'] );
        $values = [];
        $seen   = [];

        foreach ( $items as $item ) {
            $value = $this->get_path_value( $item, $path );
            if ( $unique && ( is_scalar( $value ) || null === $value ) ) {
                $key = (string) $value;
                if ( isset( $seen[ $key ] ) ) {
                    continue;
                }
                $seen[ $key ] = true;
            }
            $values[] = $value;
        }

        return [
            'values' => $values,
            'total'  => count( $values ),
        ];
    }

    public function ability_join_text( $input ): array {
        $input     = is_array( $input ) ? $input : [];
        $items     = isset( $input['items'] ) && is_array( $input['items'] ) ? array_values( $input['items'] ) : [];
        $path      = (string) ( $input['path'] ?? '' );
        $separator = array_key_exists( 'separator', $input ) ? (string) $input['separator'] : "\n";
        $prefix    = (string) ( $input['prefix'] ?? '' );
        $suffix    = (string) ( $input['suffix'] ?? '' );
        $parts     = [];

        foreach ( $items as $item ) {
            $value = '' === $path ? $item : $this->get_path_value( $item, $path );
            $text  = $this->stringify_glue_value( $value );
            if ( '' === $text ) {
                continue;
            }
            $parts[] = $prefix . $text . $suffix;
        }

        return [
            'text'  => implode( $separator, $parts ),
            'total' => count( $parts ),
        ];
    }

    public function register_ai_assistant_ability_domains( array $domains ): array {
        $domains['pipes'] = 'pipes, workflows, flows, yahoo pipes, connect abilities, ability pipeline, automation';
        return $domains;
    }

    private function register_pipe_ability( string $ability_id, array $args ): void {
        if ( function_exists( 'wp_has_ability' ) && wp_has_ability( $ability_id ) ) {
            return;
        }

        $args['category'] = $args['category'] ?? 'pipes';
        $args['permission_callback'] = $args['permission_callback'] ?? function() {
            return current_user_can( 'read' );
        };

        wp_register_ability( $ability_id, $args );
    }

    private function ability_meta( bool $readonly, bool $destructive, bool $idempotent, string $instructions ): array {
        return [
            'show_in_rest' => true,
            'annotations'  => [
                'readonly'     => $readonly,
                'destructive'  => $destructive,
                'idempotent'   => $idempotent,
                'instructions' => $instructions,
            ],
        ];
    }

    private function items_output_schema(): array {
        return [
            'type'       => 'object',
            'properties' => [
                'items' => [
                    'type'  => 'array',
                    'items' => [ 'type' => [ 'object', 'array', 'string', 'number', 'integer', 'boolean', 'null' ] ],
                ],
                'total' => [
                    'type' => 'integer',
                ],
            ],
        ];
    }

    private function get_pipe_post( int $post_id ) {
        $post = get_post( $post_id );
        if ( ! $post || self::POST_TYPE !== $post->post_type ) {
            return new \WP_Error( 'pipes_not_found', __( 'Pipe not found.', 'pipes' ), [ 'status' => 404 ] );
        }

        if ( (int) $post->post_author !== get_current_user_id() && ! current_user_can( 'edit_post', $post_id ) ) {
            return new \WP_Error( 'pipes_forbidden', __( 'You cannot access this pipe.', 'pipes' ), [ 'status' => 403 ] );
        }

        return $post;
    }

    private function format_pipe( \WP_Post $post, bool $include_graph = false ): array {
        $pipe = [
            'id'       => $post->ID,
            'title'    => $post->post_title,
            'modified' => get_post_modified_time( 'c', false, $post ),
        ];

        if ( $include_graph ) {
            $pipe['graph'] = $this->get_pipe_graph( $post );
        }

        return $pipe;
    }

    private function get_pipe_graph( \WP_Post $post ): array {
        $graph = json_decode( $post->post_content, true );
        return is_array( $graph ) ? $this->sanitize_graph( $graph ) : [ 'nodes' => [], 'edges' => [] ];
    }

    private function sanitize_graph( array $graph ): array {
        $nodes = [];
        foreach ( (array) ( $graph['nodes'] ?? [] ) as $node ) {
            if ( ! is_array( $node ) ) {
                continue;
            }

            $nodes[] = [
                'id'         => sanitize_key( (string) ( $node['id'] ?? wp_generate_uuid4() ) ),
                'ability_id' => sanitize_text_field( (string) ( $node['ability_id'] ?? '' ) ),
                'label'      => sanitize_text_field( (string) ( $node['label'] ?? '' ) ),
                'args'       => $this->sanitize_json_value( $node['args'] ?? [] ),
                'bindings'   => $this->sanitize_bindings( $node['bindings'] ?? [] ),
                'position'   => [
                    'x' => (int) ( $node['position']['x'] ?? 0 ),
                    'y' => (int) ( $node['position']['y'] ?? 0 ),
                ],
            ];
        }

        $edges = [];
        foreach ( (array) ( $graph['edges'] ?? [] ) as $edge ) {
            if ( ! is_array( $edge ) ) {
                continue;
            }

            $edges[] = [
                'from' => sanitize_key( (string) ( $edge['from'] ?? '' ) ),
                'to'   => sanitize_key( (string) ( $edge['to'] ?? '' ) ),
            ];
        }

        return [
            'nodes' => $nodes,
            'edges' => $edges,
        ];
    }

    private function sanitize_bindings( $bindings ): array {
        $clean = [];
        foreach ( (array) $bindings as $binding ) {
            if ( ! is_array( $binding ) ) {
                continue;
            }

            $clean[] = [
                'target' => sanitize_text_field( (string) ( $binding['target'] ?? '' ) ),
                'source' => sanitize_key( (string) ( $binding['source'] ?? '' ) ),
                'path'   => sanitize_text_field( (string) ( $binding['path'] ?? '' ) ),
            ];
        }

        return $clean;
    }

    private function sanitize_json_value( $value ) {
        if ( is_array( $value ) ) {
            $clean = [];
            foreach ( $value as $key => $item ) {
                $clean[ is_int( $key ) ? $key : sanitize_text_field( (string) $key ) ] = $this->sanitize_json_value( $item );
            }
            return $clean;
        }

        if ( is_string( $value ) ) {
            return sanitize_textarea_field( $value );
        }

        if ( is_bool( $value ) || is_int( $value ) || is_float( $value ) || null === $value ) {
            return $value;
        }

        return sanitize_text_field( (string) $value );
    }

    private function run_graph( array $graph, bool $confirm_destructive = false ) {
        if ( ! function_exists( 'wp_get_ability' ) ) {
            return new \WP_Error( 'pipes_abilities_unavailable', __( 'The WordPress Abilities API is not available.', 'pipes' ), [ 'status' => 501 ] );
        }

        $nodes = $this->order_nodes( $graph );
        if ( is_wp_error( $nodes ) ) {
            return $nodes;
        }

        $results = [];
        foreach ( $nodes as $node ) {
            if ( empty( $node['ability_id'] ) ) {
                return new \WP_Error( 'pipes_missing_ability', __( 'A node is missing an ability.', 'pipes' ), [ 'status' => 400 ] );
            }

            $ability = wp_get_ability( $node['ability_id'] );
            if ( null === $ability ) {
                return new \WP_Error( 'pipes_unknown_ability', sprintf( __( 'Ability not found: %s', 'pipes' ), $node['ability_id'] ), [ 'status' => 404 ] );
            }

            $details = $this->get_ability_details( $node['ability_id'], $ability );
            if ( $details['destructive'] && ! $confirm_destructive ) {
                return new \WP_Error( 'pipes_destructive_confirmation_required', sprintf( __( '%s is destructive and requires confirmation.', 'pipes' ), $details['label'] ), [ 'status' => 400 ] );
            }

            $input = is_array( $node['args'] ?? null ) ? $node['args'] : [];
            foreach ( (array) ( $node['bindings'] ?? [] ) as $binding ) {
                $source_id = (string) ( $binding['source'] ?? '' );
                $target    = (string) ( $binding['target'] ?? '' );
                if ( '' === $source_id || '' === $target || ! array_key_exists( $source_id, $results ) ) {
                    continue;
                }
                $input[ $target ] = $this->get_path_value( $results[ $source_id ]['result'], (string) ( $binding['path'] ?? '' ) );
            }

            $result = $ability->execute( $input );
            if ( is_wp_error( $result ) ) {
                return new \WP_Error( 'pipes_ability_failed', sprintf( __( '%1$s failed: %2$s', 'pipes' ), $details['label'], $result->get_error_message() ), [ 'status' => 500 ] );
            }

            $results[ $node['id'] ] = [
                'node_id'    => $node['id'],
                'ability_id' => $details['id'],
                'label'      => $details['label'],
                'input'      => $input,
                'result'     => $this->normalize_result( $result ),
            ];
        }

        return [
            'success' => true,
            'results' => $results,
        ];
    }

    private function order_nodes( array $graph ) {
        $nodes = [];
        foreach ( (array) ( $graph['nodes'] ?? [] ) as $node ) {
            if ( is_array( $node ) && ! empty( $node['id'] ) ) {
                $nodes[ (string) $node['id'] ] = $node;
            }
        }

        $incoming = array_fill_keys( array_keys( $nodes ), 0 );
        $outgoing = array_fill_keys( array_keys( $nodes ), [] );
        foreach ( (array) ( $graph['edges'] ?? [] ) as $edge ) {
            $from = (string) ( $edge['from'] ?? '' );
            $to   = (string) ( $edge['to'] ?? '' );
            if ( isset( $nodes[ $from ], $nodes[ $to ] ) ) {
                $incoming[ $to ]++;
                $outgoing[ $from ][] = $to;
            }
        }

        uasort( $nodes, static function( array $a, array $b ): int {
            return ( $a['position']['x'] ?? 0 ) <=> ( $b['position']['x'] ?? 0 );
        } );

        $queue = [];
        foreach ( $nodes as $id => $node ) {
            if ( 0 === $incoming[ $id ] ) {
                $queue[] = $id;
            }
        }

        $ordered = [];
        while ( $queue ) {
            $id = array_shift( $queue );
            $ordered[] = $nodes[ $id ];
            foreach ( $outgoing[ $id ] as $to ) {
                $incoming[ $to ]--;
                if ( 0 === $incoming[ $to ] ) {
                    $queue[] = $to;
                }
            }
        }

        if ( count( $ordered ) !== count( $nodes ) ) {
            return new \WP_Error( 'pipes_cycle', __( 'Pipe connections cannot contain a cycle.', 'pipes' ), [ 'status' => 400 ] );
        }

        return $ordered;
    }

    private function get_path_value( $value, string $path ) {
        $path = trim( $path );
        if ( '' === $path ) {
            return $value;
        }

        foreach ( explode( '.', $path ) as $part ) {
            if ( is_array( $value ) && array_key_exists( $part, $value ) ) {
                $value = $value[ $part ];
                continue;
            }

            if ( is_object( $value ) && isset( $value->{$part} ) ) {
                $value = $value->{$part};
                continue;
            }

            return null;
        }

        return $value;
    }

    private function stringify_glue_value( $value ): string {
        if ( null === $value ) {
            return '';
        }

        if ( is_bool( $value ) ) {
            return $value ? 'true' : 'false';
        }

        if ( is_scalar( $value ) ) {
            return (string) $value;
        }

        $json = wp_json_encode( $value );
        return is_string( $json ) ? $json : '';
    }

    private function normalize_result( $result ) {
        if ( $result instanceof \JsonSerializable ) {
            return $result->jsonSerialize();
        }

        if ( is_object( $result ) ) {
            return get_object_vars( $result );
        }

        return $result;
    }

    private function get_ability_details( $id, $ability ): array {
        $ability_id  = $this->get_ability_id( $id, $ability );
        $meta        = $this->get_ability_meta( $ability );
        $annotations = is_array( $meta['annotations'] ?? null ) ? $meta['annotations'] : [];

        return [
            'id'            => $ability_id,
            'label'         => $this->get_ability_string( $ability, 'get_label', 'label', $ability_id ),
            'description'   => $this->get_ability_string( $ability, 'get_description', 'description', '' ),
            'category'      => $this->get_ability_string( $ability, 'get_category', 'category', '' ),
            'input_schema'  => $this->get_ability_schema( $ability, 'get_input_schema', 'input_schema' ),
            'output_schema' => $this->get_ability_schema( $ability, 'get_output_schema', 'output_schema' ),
            'readonly'      => ! empty( $annotations['readonly'] ) && empty( $annotations['destructive'] ),
            'destructive'   => ! empty( $annotations['destructive'] ),
            'instructions'  => (string) ( $annotations['instructions'] ?? '' ),
        ];
    }

    private function get_ability_id( $id, $ability ): string {
        if ( is_object( $ability ) && method_exists( $ability, 'get_name' ) ) {
            return (string) $ability->get_name();
        }

        if ( is_object( $ability ) && isset( $ability->name ) ) {
            return (string) $ability->name;
        }

        return (string) $id;
    }

    private function get_ability_string( $ability, string $method, string $property, string $fallback ): string {
        if ( is_object( $ability ) && method_exists( $ability, $method ) ) {
            return (string) $ability->{$method}();
        }

        if ( is_object( $ability ) && isset( $ability->{$property} ) ) {
            return (string) $ability->{$property};
        }

        if ( is_array( $ability ) && isset( $ability[ $property ] ) ) {
            return (string) $ability[ $property ];
        }

        return $fallback;
    }

    private function get_ability_schema( $ability, string $method, string $property ): array {
        if ( is_object( $ability ) && method_exists( $ability, $method ) ) {
            $schema = $ability->{$method}();
            return is_array( $schema ) ? $schema : [];
        }

        if ( is_object( $ability ) && isset( $ability->{$property} ) && is_array( $ability->{$property} ) ) {
            return $ability->{$property};
        }

        if ( is_array( $ability ) && isset( $ability[ $property ] ) && is_array( $ability[ $property ] ) ) {
            return $ability[ $property ];
        }

        return [];
    }

    private function get_ability_meta( $ability ): array {
        if ( is_object( $ability ) && method_exists( $ability, 'get_meta' ) ) {
            $meta = $ability->get_meta();
            return is_array( $meta ) ? $meta : [];
        }

        if ( is_object( $ability ) && isset( $ability->meta ) && is_array( $ability->meta ) ) {
            return $ability->meta;
        }

        if ( is_array( $ability ) && isset( $ability['meta'] ) && is_array( $ability['meta'] ) ) {
            return $ability['meta'];
        }

        return [];
    }

    private function get_starter_pipe( string $starter_id ): ?array {
        $starter_id = sanitize_key( $starter_id );
        foreach ( $this->get_starter_pipes() as $starter ) {
            if ( $starter_id === $starter['id'] ) {
                return $starter;
            }
        }

        return null;
    }

    private function find_user_starter_pipe_id( string $starter_id ): int {
        $posts = get_posts( [
            'post_type'      => self::POST_TYPE,
            'post_status'    => [ 'publish', 'draft', 'private' ],
            'posts_per_page' => 1,
            'author'         => get_current_user_id(),
            'fields'         => 'ids',
            'meta_key'       => self::STARTER_META_KEY,
            'meta_value'     => sanitize_key( $starter_id ),
        ] );

        return $posts ? (int) $posts[0] : 0;
    }

    private function get_starter_pipes(): array {
        return [
            [
                'id'          => 'wordopedia-research-brief',
                'title'       => __( 'Wordopedia Research Brief', 'pipes' ),
                'description' => __( 'Search Wikipedia, limit results, fetch the top article, and list media for that article.', 'pipes' ),
                'requires'    => [ 'wordopedia/search-wikipedia', 'pipes/limit-items', 'wordopedia/get-article', 'wordopedia/list-article-media' ],
                'graph'       => [
                    'nodes' => [
                        [
                            'id'         => 'search',
                            'ability_id' => 'wordopedia/search-wikipedia',
                            'label'      => __( 'Search topic', 'pipes' ),
                            'args'       => [
                                'query'    => 'WordPress',
                                'language' => 'en',
                                'limit'    => 3,
                            ],
                            'bindings'   => [],
                            'position'   => [ 'x' => 28, 'y' => 64 ],
                        ],
                        [
                            'id'         => 'top-results',
                            'ability_id' => 'pipes/limit-items',
                            'label'      => __( 'Keep top result', 'pipes' ),
                            'args'       => [
                                'limit' => 1,
                            ],
                            'bindings'   => [
                                [ 'target' => 'items', 'source' => 'search', 'path' => 'articles' ],
                            ],
                            'position'   => [ 'x' => 328, 'y' => 64 ],
                        ],
                        [
                            'id'         => 'article',
                            'ability_id' => 'wordopedia/get-article',
                            'label'      => __( 'Fetch top article', 'pipes' ),
                            'args'       => [],
                            'bindings'   => [
                                [ 'target' => 'page_id', 'source' => 'top-results', 'path' => 'items.0.page_id' ],
                                [ 'target' => 'language', 'source' => 'search', 'path' => 'language' ],
                            ],
                            'position'   => [ 'x' => 628, 'y' => 46 ],
                        ],
                        [
                            'id'         => 'media',
                            'ability_id' => 'wordopedia/list-article-media',
                            'label'      => __( 'List article media', 'pipes' ),
                            'args'       => [
                                'mime' => 'image/svg+xml',
                            ],
                            'bindings'   => [
                                [ 'target' => 'page_id', 'source' => 'top-results', 'path' => 'items.0.page_id' ],
                                [ 'target' => 'language', 'source' => 'search', 'path' => 'language' ],
                            ],
                            'position'   => [ 'x' => 928, 'y' => 84 ],
                        ],
                    ],
                    'edges' => [
                        [ 'from' => 'search', 'to' => 'top-results' ],
                        [ 'from' => 'top-results', 'to' => 'article' ],
                        [ 'from' => 'top-results', 'to' => 'media' ],
                    ],
                ],
            ],
            [
                'id'          => 'friends-feed-review',
                'title'       => __( 'Friends Feed Review', 'pipes' ),
                'description' => __( 'List subscriptions, then inspect the first subscription and its cached feed items.', 'pipes' ),
                'requires'    => [ 'friends/list-subscriptions', 'friends/get-subscription', 'friends/list-feed-items' ],
                'graph'       => [
                    'nodes' => [
                        [
                            'id'         => 'subscriptions',
                            'ability_id' => 'friends/list-subscriptions',
                            'label'      => __( 'List subscriptions', 'pipes' ),
                            'args'       => [ 'limit' => 10 ],
                            'bindings'   => [],
                            'position'   => [ 'x' => 28, 'y' => 70 ],
                        ],
                        [
                            'id'         => 'subscription',
                            'ability_id' => 'friends/get-subscription',
                            'label'      => __( 'Inspect first subscription', 'pipes' ),
                            'args'       => [],
                            'bindings'   => [
                                [ 'target' => 'subscription_id', 'source' => 'subscriptions', 'path' => 'subscriptions.0.id' ],
                            ],
                            'position'   => [ 'x' => 328, 'y' => 40 ],
                        ],
                        [
                            'id'         => 'items',
                            'ability_id' => 'friends/list-feed-items',
                            'label'      => __( 'Read latest items', 'pipes' ),
                            'args'       => [ 'limit' => 10 ],
                            'bindings'   => [
                                [ 'target' => 'subscription_id', 'source' => 'subscriptions', 'path' => 'subscriptions.0.id' ],
                            ],
                            'position'   => [ 'x' => 628, 'y' => 92 ],
                        ],
                    ],
                    'edges' => [
                        [ 'from' => 'subscriptions', 'to' => 'subscription' ],
                        [ 'from' => 'subscriptions', 'to' => 'items' ],
                    ],
                ],
            ],
            [
                'id'          => 'flight-log-dashboard',
                'title'       => __( 'Flight Log Dashboard', 'pipes' ),
                'description' => __( 'Fetch flight statistics and a short list of recent matching flights.', 'pipes' ),
                'requires'    => [ 'flight-log/get-summary', 'flight-log/search-flights' ],
                'graph'       => [
                    'nodes' => [
                        [
                            'id'         => 'summary',
                            'ability_id' => 'flight-log/get-summary',
                            'label'      => __( 'Get flight summary', 'pipes' ),
                            'args'       => [],
                            'bindings'   => [],
                            'position'   => [ 'x' => 28, 'y' => 52 ],
                        ],
                        [
                            'id'         => 'recent',
                            'ability_id' => 'flight-log/search-flights',
                            'label'      => __( 'Find recent flights', 'pipes' ),
                            'args'       => [ 'limit' => 10 ],
                            'bindings'   => [],
                            'position'   => [ 'x' => 328, 'y' => 92 ],
                        ],
                    ],
                    'edges' => [
                        [ 'from' => 'summary', 'to' => 'recent' ],
                    ],
                ],
            ],
            [
                'id'          => 'travel-plan-review',
                'title'       => __( 'Travel Plan Review', 'pipes' ),
                'description' => __( 'List travel plans and fetch the first plan for detailed review.', 'pipes' ),
                'requires'    => [ 'travel-app/list-trips', 'travel-app/get-trip' ],
                'graph'       => [
                    'nodes' => [
                        [
                            'id'         => 'trips',
                            'ability_id' => 'travel-app/list-trips',
                            'label'      => __( 'List travel plans', 'pipes' ),
                            'args'       => [],
                            'bindings'   => [],
                            'position'   => [ 'x' => 28, 'y' => 64 ],
                        ],
                        [
                            'id'         => 'trip',
                            'ability_id' => 'travel-app/get-trip',
                            'label'      => __( 'Open first plan', 'pipes' ),
                            'args'       => [],
                            'bindings'   => [
                                [ 'target' => 'id', 'source' => 'trips', 'path' => 'trips.0.id' ],
                            ],
                            'position'   => [ 'x' => 328, 'y' => 64 ],
                        ],
                    ],
                    'edges' => [
                        [ 'from' => 'trips', 'to' => 'trip' ],
                    ],
                ],
            ],
        ];
    }

    public function activate(): void {
        $this->register_post_types();
        flush_rewrite_rules();
    }

    public function deactivate(): void {
        flush_rewrite_rules();
    }
}
