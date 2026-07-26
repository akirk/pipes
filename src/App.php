<?php

namespace Pipes;

use WpApp\BaseApp;
use WpApp\WpApp;

class App extends BaseApp {
    public const POST_TYPE = 'pipes_pipe';
    public const REST_NAMESPACE = 'pipes/v1';

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

        if ( function_exists( 'wp_has_ability' ) && wp_has_ability( 'pipes/run-pipe' ) ) {
            return;
        }

        wp_register_ability( 'pipes/run-pipe', [
            'label'               => __( 'Run Pipe', 'pipes' ),
            'description'         => __( 'Runs a saved Pipes flow by ID and returns every node result.', 'pipes' ),
            'category'            => 'pipes',
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
            'permission_callback' => function() {
                return current_user_can( 'read' );
            },
            'meta'                => [
                'annotations' => [
                    'readonly'     => false,
                    'destructive'  => false,
                    'idempotent'   => false,
                    'instructions' => __( 'Return a compact summary of each node result and note any failed node.', 'pipes' ),
                ],
            ],
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

    public function register_ai_assistant_ability_domains( array $domains ): array {
        $domains['pipes'] = 'pipes, workflows, flows, yahoo pipes, connect abilities, ability pipeline, automation';
        return $domains;
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
            'title'    => get_the_title( $post ),
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

    public function activate(): void {
        $this->register_post_types();
        flush_rewrite_rules();
    }

    public function deactivate(): void {
        flush_rewrite_rules();
    }
}
