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
        add_action( 'wp_dashboard_setup', [ $this, 'register_dashboard_widgets' ] );
        add_action( 'admin_bar_menu', [ $this, 'register_admin_bar_outputs' ], 120 );
        add_action( 'admin_head', [ $this, 'output_styles' ] );
        add_action( 'wp_head', [ $this, 'output_styles' ] );
        add_action( 'send_headers', [ $this, 'send_app_no_cache_headers' ] );
        add_action( 'admin_post_pipes_run_dashboard_output', [ $this, 'handle_dashboard_output_submission' ] );
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

    public function send_app_no_cache_headers(): void {
        $path = trim( (string) wp_parse_url( $_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH ), '/' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        if ( $this->get_url_path() !== $path ) {
            return;
        }

        nocache_headers();
    }

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

        $this->register_pipe_ability( 'pipes/list-pipes', [
            'label'               => __( 'List Pipes', 'pipes' ),
            'description'         => __( 'Lists saved Pipes workflows owned by the current user.', 'pipes' ),
            'input_schema'        => [
                'type'                 => 'object',
                'properties'           => [
                    'search' => [
                        'type'        => 'string',
                        'description' => __( 'Optional title search text.', 'pipes' ),
                    ],
                    'limit'  => [
                        'type'        => 'integer',
                        'description' => __( 'Maximum pipes to return. Defaults to 20.', 'pipes' ),
                        'default'     => 20,
                    ],
                ],
                'additionalProperties' => false,
            ],
            'output_schema'       => [
                'type'       => 'object',
                'properties' => [
                    'pipes' => [
                        'type'  => 'array',
                        'items' => $this->pipe_summary_schema(),
                    ],
                    'total' => [ 'type' => 'integer' ],
                ],
            ],
            'execute_callback'    => [ $this, 'ability_list_pipes' ],
            'meta'                => $this->ability_meta( true, false, true, __( 'Use this before updating an existing pipe when the user names a pipe but does not provide its ID.', 'pipes' ) ),
        ] );

        $this->register_pipe_ability( 'pipes/get-pipe', [
            'label'               => __( 'Get Pipe', 'pipes' ),
            'description'         => __( 'Returns one saved Pipes workflow, including its editable graph JSON.', 'pipes' ),
            'input_schema'        => [
                'type'                 => 'object',
                'required'             => [ 'pipe_id' ],
                'properties'           => [
                    'pipe_id' => [
                        'type'        => 'integer',
                        'description' => __( 'Saved pipe post ID from pipes/list-pipes or a prior create/update result.', 'pipes' ),
                    ],
                ],
                'additionalProperties' => false,
            ],
            'output_schema'       => [
                'type'       => 'object',
                'properties' => [
                    'pipe' => $this->pipe_schema( true ),
                ],
            ],
            'execute_callback'    => [ $this, 'ability_get_pipe' ],
            'meta'                => $this->ability_meta( true, false, true, __( 'Inspect the existing graph before updating it. Preserve unrelated nodes, args, bindings, and output nodes unless the user asks to change them.', 'pipes' ) ),
        ] );

        $this->register_pipe_ability( 'pipes/create-pipe', [
            'label'               => __( 'Create Pipe', 'pipes' ),
            'description'         => __( 'Creates a saved Pipes workflow from a title and graph definition.', 'pipes' ),
            'input_schema'        => [
                'type'                 => 'object',
                'required'             => [ 'title', 'graph' ],
                'properties'           => [
                    'title' => [
                        'type'        => 'string',
                        'description' => __( 'Human-readable pipe name.', 'pipes' ),
                    ],
                    'graph' => $this->pipe_graph_schema(),
                ],
                'additionalProperties' => false,
            ],
            'output_schema'       => [
                'type'       => 'object',
                'properties' => [
                    'pipe' => $this->pipe_schema( true ),
                ],
            ],
            'execute_callback'    => [ $this, 'ability_create_pipe' ],
            'permission_callback' => [ $this, 'can_edit_pipes' ],
            'meta'                => $this->ability_meta( false, false, false, __( 'Create complete workflows with nodes and bindings. Prefer output nodes such as pipes/output-dashboard-list or pipes/output-dashboard-text when the user wants visible WordPress output. Use stable node IDs like search, filter, output; bind downstream inputs to upstream output paths.', 'pipes' ) ),
        ] );

        $this->register_pipe_ability( 'pipes/update-pipe', [
            'label'               => __( 'Update Pipe', 'pipes' ),
            'description'         => __( 'Updates an existing saved Pipes workflow title and graph definition.', 'pipes' ),
            'input_schema'        => [
                'type'                 => 'object',
                'required'             => [ 'pipe_id', 'graph' ],
                'properties'           => [
                    'pipe_id' => [
                        'type'        => 'integer',
                        'description' => __( 'Saved pipe post ID to update.', 'pipes' ),
                    ],
                    'title'   => [
                        'type'        => 'string',
                        'description' => __( 'Optional replacement title. Omit to keep the existing title.', 'pipes' ),
                    ],
                    'graph'   => $this->pipe_graph_schema(),
                ],
                'additionalProperties' => false,
            ],
            'output_schema'       => [
                'type'       => 'object',
                'properties' => [
                    'pipe' => $this->pipe_schema( true ),
                ],
            ],
            'execute_callback'    => [ $this, 'ability_update_pipe' ],
            'permission_callback' => [ $this, 'can_edit_pipes' ],
            'meta'                => $this->ability_meta( false, false, false, __( 'Call pipes/get-pipe first unless the user explicitly provides the complete replacement graph. Preserve unrelated workflow behavior and keep graph node order aligned with execution order.', 'pipes' ) ),
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

        $this->register_pipe_ability( 'pipes/search-replace-items', [
            'label'               => __( 'Search Replace Items', 'pipes' ),
            'description'         => __( 'Searches and replaces text at a dot-path inside every item in a list.', 'pipes' ),
            'input_schema'        => [
                'type'                 => 'object',
                'required'             => [ 'items', 'path', 'search' ],
                'properties'           => [
                    'items'          => [
                        'type'        => 'array',
                        'description' => __( 'Items to change.', 'pipes' ),
                        'items'       => [ 'type' => [ 'object', 'array', 'string', 'number', 'integer', 'boolean', 'null' ] ],
                    ],
                    'path'           => [
                        'type'        => 'string',
                        'description' => __( 'Dot path inside each item, such as title or author.name.', 'pipes' ),
                    ],
                    'search'         => [
                        'type'        => 'string',
                        'description' => __( 'Text or regex pattern to search for in the field value.', 'pipes' ),
                    ],
                    'replace'        => [
                        'type'        => 'string',
                        'description' => __( 'Replacement text. Leave empty to remove the search text.', 'pipes' ),
                        'default'     => '',
                    ],
                    'regex'          => [
                        'type'        => 'boolean',
                        'description' => __( 'Treat search as a regular expression pattern.', 'pipes' ),
                        'default'     => false,
                    ],
                    'case_sensitive' => [
                        'type'        => 'boolean',
                        'description' => __( 'Match uppercase and lowercase exactly.', 'pipes' ),
                        'default'     => true,
                    ],
                ],
                'additionalProperties' => false,
            ],
            'output_schema'       => [
                'type'       => 'object',
                'properties' => [
                    'items'   => [
                        'type'  => 'array',
                        'items' => [ 'type' => [ 'object', 'array', 'string', 'number', 'integer', 'boolean', 'null' ] ],
                    ],
                    'total'   => [ 'type' => 'integer' ],
                    'changed' => [ 'type' => 'integer' ],
                ],
            ],
            'execute_callback'    => [ $this, 'ability_search_replace_items' ],
            'meta'                => $this->ability_meta( true, false, true, __( 'Use this to clean up labels, titles, URLs, or other text fields before output.', 'pipes' ) ),
        ] );

        $this->register_pipe_ability( 'pipes/format-date-field', [
            'label'               => __( 'Format Date Field', 'pipes' ),
            'description'         => __( 'Formats a date value, or one date field inside every item in a list.', 'pipes' ),
            'input_schema'        => [
                'type'                 => 'object',
                'properties'           => [
                    'value'        => [
                        'type'        => [ 'string', 'number', 'integer' ],
                        'description' => __( 'Single date value to format, such as 2026-01-12.', 'pipes' ),
                    ],
                    'items'        => [
                        'type'        => 'array',
                        'description' => __( 'Items to change.', 'pipes' ),
                        'items'       => [ 'type' => [ 'object', 'array', 'string', 'number', 'integer', 'boolean', 'null' ] ],
                    ],
                    'path'         => [
                        'type'        => 'string',
                        'description' => __( 'Dot path inside each item, such as date, start_date, or trip.departure_date.', 'pipes' ),
                    ],
                    'date_format'  => [
                        'type'        => 'string',
                        'description' => __( 'PHP date format for output. Defaults to the site date format.', 'pipes' ),
                        'default'     => 'F j, Y',
                    ],
                    'input_format' => [
                        'type'        => 'string',
                        'description' => __( 'Optional PHP date format for parsing source dates, for example Y-m-d.', 'pipes' ),
                    ],
                ],
                'additionalProperties' => false,
            ],
            'output_schema'       => [
                'type'       => 'object',
                'properties' => [
                    'items'   => [
                        'type'  => 'array',
                        'items' => [ 'type' => [ 'object', 'array', 'string', 'number', 'integer', 'boolean', 'null' ] ],
                    ],
                    'value'   => [
                        'type' => [ 'string', 'null' ],
                    ],
                    'total'   => [ 'type' => 'integer' ],
                    'changed' => [ 'type' => 'integer' ],
                ],
            ],
            'execute_callback'    => [ $this, 'ability_format_date_field' ],
            'meta'                => $this->ability_meta( true, false, true, __( 'Use this to turn machine-readable date fields like 2026-01-12 into human-readable dates before output.', 'pipes' ) ),
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

        $this->register_pipe_ability( 'pipes/format-text', [
            'label'               => __( 'Format Text', 'pipes' ),
            'description'         => __( 'Builds a text value from a template with named placeholders.', 'pipes' ),
            'input_schema'        => [
                'type'                 => 'object',
                'required'             => [ 'template' ],
                'properties'           => [
                    'template' => [
                        'type'        => 'string',
                        'description' => __( 'Text template with placeholders such as {title} or {{ summary }}. Each placeholder becomes a bindable input.', 'pipes' ),
                    ],
                ],
                'additionalProperties' => true,
            ],
            'output_schema'       => [
                'type'       => 'object',
                'properties' => [
                    'text'         => [ 'type' => 'string' ],
                    'value'        => [ 'type' => 'string' ],
                    'placeholders' => [
                        'type'  => 'array',
                        'items' => [ 'type' => 'string' ],
                    ],
                ],
            ],
            'execute_callback'    => [ $this, 'ability_format_text' ],
            'meta'                => $this->ability_meta( true, false, true, __( 'Use this when the user wants to compose a sentence, prompt, message, or label from values produced by earlier pipe steps.', 'pipes' ) ),
        ] );

        $this->register_pipe_ability( 'pipes/output-debug', [
            'label'               => __( 'Debug Output', 'pipes' ),
            'description'         => __( 'Displays bound pipe values in the builder run preview without publishing them.', 'pipes' ),
            'input_schema'        => [
                'type'                 => 'object',
                'required'             => [ 'value' ],
                'properties'           => [
                    'value' => [
                        'type'        => [ 'object', 'array', 'string', 'number', 'integer', 'boolean', 'null' ],
                        'description' => __( 'Value to inspect. Bind this to an upstream output field.', 'pipes' ),
                    ],
                ],
                'additionalProperties' => [
                    'type'        => [ 'object', 'array', 'string', 'number', 'integer', 'boolean', 'null' ],
                    'description' => __( 'Additional values to inspect. Use names such as value_2 or filtered_items.', 'pipes' ),
                ],
            ],
            'output_schema'       => [
                'type'       => 'object',
                'properties' => [
                    'value' => [
                        'type' => [ 'object', 'array', 'string', 'number', 'integer', 'boolean', 'null' ],
                    ],
                    'values' => [
                        'type'                 => 'object',
                        'additionalProperties' => [
                            'type' => [ 'object', 'array', 'string', 'number', 'integer', 'boolean', 'null' ],
                        ],
                    ],
                ],
            ],
            'execute_callback'    => [ $this, 'ability_debug_output_sink' ],
            'meta'                => $this->ability_meta( true, false, true, __( 'Use this while building a pipe to inspect one or more upstream values.', 'pipes' ) ),
        ] );

        foreach ( $this->output_ability_labels() as $ability_id => $label ) {
            $input_schema = [
                'type'                 => 'object',
                'required'             => [ 'value' ],
                'properties'           => [
                    'value' => [
                        'type'        => [ 'object', 'array', 'string', 'number', 'integer', 'boolean', 'null' ],
                        'description' => __( 'Value to publish. Bind this to an upstream output field.', 'pipes' ),
                    ],
                ],
                'additionalProperties' => false,
            ];

            if ( 'pipes/output-dashboard-list' === $ability_id ) {
                $input_schema['properties']['columns'] = [
                    'type'        => 'array',
                    'description' => __( 'Object fields to show as table columns. Leave empty to show the first six fields.', 'pipes' ),
                    'items'       => [ 'type' => 'string' ],
                ];
            }

            $output_schema = [
                'type'       => 'object',
                'properties' => [
                    'value' => [
                        'type' => [ 'object', 'array', 'string', 'number', 'integer', 'boolean', 'null' ],
                    ],
                ],
            ];
            if ( in_array( $ability_id, [ 'pipes/output-dashboard-text', 'pipes/output-dashboard-list' ], true ) ) {
                $output_schema['properties']['raw_value'] = [
                    'type'        => [ 'object', 'array', 'string', 'number', 'integer', 'boolean', 'null' ],
                    'description' => __( 'Original value before dashboard rendering.', 'pipes' ),
                ];
                $output_schema['properties']['html'] = [
                    'type'        => 'string',
                    'description' => __( 'Rendered dashboard HTML for this output.', 'pipes' ),
                ];
            }

            $this->register_pipe_ability( $ability_id, [
                'label'               => $label,
                'description'         => __( 'Publishes a bound pipe value into a WordPress surface.', 'pipes' ),
                'input_schema'        => $input_schema,
                'output_schema'       => $output_schema,
                'execute_callback'    => [ $this, 'ability_output_sink' ],
                'meta'                => $this->ability_meta( true, false, true, __( 'Use this as the last box in a pipe to make its output visible in WordPress.', 'pipes' ) ),
            ] );
        }
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

        $post = $this->save_pipe_post( $post_id, $title, $graph );
        if ( is_wp_error( $post ) ) {
            return $post;
        }

        return rest_ensure_response( [ 'pipe' => $this->format_pipe( $post, true ) ] );
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

        $has_payload_graph = isset( $payload['graph'] ) && is_array( $payload['graph'] );
        if ( ! empty( $payload['pipe_id'] ) ) {
            $post = $this->get_pipe_post( (int) $payload['pipe_id'] );
            if ( is_wp_error( $post ) ) {
                return $post;
            }
            $pipe_id = $post->ID;
        } else {
            $pipe_id = 0;
        }

        $graph = $has_payload_graph ? $this->sanitize_graph( $payload['graph'] ) : ( isset( $post ) ? $this->get_pipe_graph( $post ) : [] );

        $result = $this->run_graph( $graph, ! empty( $payload['confirm_destructive'] ), is_array( $payload['user_answers'] ?? null ) ? $payload['user_answers'] : [] );
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

    public function ability_list_pipes( $input ): array {
        $input  = is_array( $input ) ? $input : [];
        $limit  = max( 1, min( 100, isset( $input['limit'] ) ? absint( $input['limit'] ) : 20 ) );
        $search = isset( $input['search'] ) ? sanitize_text_field( (string) $input['search'] ) : '';
        $args   = [
            'post_type'      => self::POST_TYPE,
            'post_status'    => [ 'publish', 'draft', 'private' ],
            'posts_per_page' => $limit,
            'orderby'        => 'modified',
            'order'          => 'DESC',
            'author'         => get_current_user_id(),
        ];
        if ( '' !== $search ) {
            $args['s'] = $search;
        }

        $query = new \WP_Query( $args );
        $pipes = [];
        foreach ( $query->posts as $post ) {
            $pipes[] = $this->format_pipe_for_ability( $post, false );
        }

        return [
            'pipes' => $pipes,
            'total' => (int) $query->found_posts,
        ];
    }

    public function ability_get_pipe( $input ) {
        $input = is_array( $input ) ? $input : [];
        $post  = $this->get_pipe_post( (int) ( $input['pipe_id'] ?? 0 ) );
        if ( is_wp_error( $post ) ) {
            return $post;
        }

        return [
            'pipe' => $this->format_pipe_for_ability( $post, true ),
        ];
    }

    public function ability_create_pipe( $input ) {
        if ( ! $this->can_edit_pipes() ) {
            return new \WP_Error( 'pipes_forbidden', __( 'You cannot create pipes.', 'pipes' ) );
        }

        $input = is_array( $input ) ? $input : [];
        $title = isset( $input['title'] ) ? sanitize_text_field( (string) $input['title'] ) : '';
        $graph = isset( $input['graph'] ) && is_array( $input['graph'] ) ? $input['graph'] : [];
        $post  = $this->save_pipe_post( 0, $title, $graph );
        if ( is_wp_error( $post ) ) {
            return $post;
        }

        return [
            'pipe' => $this->format_pipe_for_ability( $post, true ),
        ];
    }

    public function ability_update_pipe( $input ) {
        if ( ! $this->can_edit_pipes() ) {
            return new \WP_Error( 'pipes_forbidden', __( 'You cannot update pipes.', 'pipes' ) );
        }

        $input   = is_array( $input ) ? $input : [];
        $pipe_id = (int) ( $input['pipe_id'] ?? 0 );
        $post    = $this->get_pipe_post( $pipe_id );
        if ( is_wp_error( $post ) ) {
            return $post;
        }

        $title = isset( $input['title'] ) && '' !== (string) $input['title'] ? sanitize_text_field( (string) $input['title'] ) : $post->post_title;
        $graph = isset( $input['graph'] ) && is_array( $input['graph'] ) ? $input['graph'] : $this->get_pipe_graph( $post );
        $post  = $this->save_pipe_post( $pipe_id, $title, $graph );
        if ( is_wp_error( $post ) ) {
            return $post;
        }

        return [
            'pipe' => $this->format_pipe_for_ability( $post, true ),
        ];
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

    public function ability_search_replace_items( $input ) {
        $input          = is_array( $input ) ? $input : [];
        $items          = isset( $input['items'] ) && is_array( $input['items'] ) ? array_values( $input['items'] ) : [];
        $path           = (string) ( $input['path'] ?? '' );
        $search         = (string) ( $input['search'] ?? '' );
        $replace        = (string) ( $input['replace'] ?? '' );
        $regex          = ! empty( $input['regex'] );
        $case_sensitive = array_key_exists( 'case_sensitive', $input ) ? (bool) $input['case_sensitive'] : true;
        $changed        = 0;

        if ( '' === $path || '' === $search ) {
            return [
                'items'   => $items,
                'total'   => count( $items ),
                'changed' => 0,
            ];
        }

        $pattern = '';
        if ( $regex ) {
            $pattern = $this->build_regex_pattern( $search, $case_sensitive );
            set_error_handler( static function(): bool {
                return true;
            } );
            $valid = false !== preg_match( $pattern, '' );
            restore_error_handler();
            if ( ! $valid ) {
                return new \WP_Error( 'pipes_invalid_regex', __( 'Search is not a valid regular expression.', 'pipes' ) );
            }
        }

        foreach ( $items as &$item ) {
            $value = $this->get_path_value( $item, $path );
            if ( null === $value || is_array( $value ) || is_object( $value ) ) {
                continue;
            }

            $text = $this->stringify_glue_value( $value );
            if ( $regex ) {
                $next = preg_replace( $pattern, $replace, $text, -1, $count );
                if ( null === $next ) {
                    continue;
                }
            } else {
                $next = $case_sensitive ? str_replace( $search, $replace, $text, $count ) : str_ireplace( $search, $replace, $text, $count );
            }
            if ( 0 === $count ) {
                continue;
            }

            $this->set_path_value( $item, $path, $next );
            $changed++;
        }
        unset( $item );

        return [
            'items'   => array_values( $items ),
            'total'   => count( $items ),
            'changed' => $changed,
        ];
    }

    public function ability_format_date_field( $input ): array {
        $input        = is_array( $input ) ? $input : [];
        $path         = (string) ( $input['path'] ?? '' );
        $date_format  = (string) ( $input['date_format'] ?? '' );
        $input_format = (string) ( $input['input_format'] ?? '' );
        $changed      = 0;

        if ( '' === $date_format ) {
            $date_format = (string) get_option( 'date_format' );
        }

        if ( array_key_exists( 'value', $input ) ) {
            $formatted = $this->format_date_value( $input['value'], $date_format, $input_format );

            return [
                'value'   => $formatted,
                'items'   => [],
                'total'   => null === $formatted ? 0 : 1,
                'changed' => null === $formatted || $formatted === $this->stringify_glue_value( $input['value'] ) ? 0 : 1,
            ];
        }

        $items = isset( $input['items'] ) && is_array( $input['items'] ) ? array_values( $input['items'] ) : [];
        if ( '' === $path ) {
            return [
                'value'   => null,
                'items'   => $items,
                'total'   => count( $items ),
                'changed' => 0,
            ];
        }

        foreach ( $items as &$item ) {
            $value = $this->get_path_value( $item, $path );
            if ( null === $value || is_array( $value ) || is_object( $value ) ) {
                continue;
            }

            $formatted = $this->format_date_value( $value, $date_format, $input_format );
            if ( null === $formatted ) {
                continue;
            }

            if ( $formatted === $this->stringify_glue_value( $value ) ) {
                continue;
            }

            if ( $this->set_path_value( $item, $path, $formatted ) ) {
                $changed++;
            }
        }
        unset( $item );

        return [
            'value'   => null,
            'items'   => array_values( $items ),
            'total'   => count( $items ),
            'changed' => $changed,
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

    public function ability_format_text( $input ): array {
        $input        = is_array( $input ) ? $input : [];
        $template     = (string) ( $input['template'] ?? '' );
        $placeholders = $this->extract_format_text_placeholders( $template );

        $text = preg_replace_callback(
            '/\{\{\s*([A-Za-z_][A-Za-z0-9_.-]*)\s*\}\}|\{([A-Za-z_][A-Za-z0-9_.-]*)\}/',
            function( array $matches ) use ( $input ): string {
                $name = '' !== ( $matches[1] ?? '' ) ? $matches[1] : ( $matches[2] ?? '' );
                if ( '' === $name || ! array_key_exists( $name, $input ) ) {
                    return $matches[0];
                }

                return $this->stringify_glue_value( $input[ $name ] );
            },
            $template
        );

        $text = null === $text ? $template : $text;

        return [
            'text'         => $text,
            'value'        => $text,
            'placeholders' => $placeholders,
        ];
    }

    public function ability_output_sink( $input ): array {
        $input = is_array( $input ) ? $input : [];

        return [
            'value' => $input['value'] ?? null,
        ];
    }

    public function ability_debug_output_sink( $input ): array {
        $input = is_array( $input ) ? $input : [];

        return [
            'value'  => $input['value'] ?? null,
            'values' => $input,
        ];
    }

    public function register_dashboard_widgets(): void {
        if ( ! function_exists( 'wp_add_dashboard_widget' ) ) {
            return;
        }

        foreach ( $this->get_output_pipes( 'dashboard' ) as $post ) {
            foreach ( $this->get_output_targets( $post, 'dashboard' ) as $target ) {
                wp_add_dashboard_widget(
                    'pipes_output_' . $post->ID . '_' . $target['node_id'],
                    sprintf( __( 'Pipe: %s', 'pipes' ), $target['label'] ),
                    [ $this, 'render_dashboard_output_widget' ],
                    null,
                    [
                        'post_id'    => $post->ID,
                        'output_key' => 'dashboard',
                        'node_id'    => $target['node_id'],
                    ]
                );
            }
        }
    }

    public function render_dashboard_output_widget( $post, array $args = [] ): void {
        $post_id = isset( $args['args']['post_id'] ) ? (int) $args['args']['post_id'] : 0;
        $node_id = isset( $args['args']['node_id'] ) ? (string) $args['args']['node_id'] : '';
        $pipe    = get_post( $post_id );
        if ( ! $pipe || self::POST_TYPE !== $pipe->post_type ) {
            echo '<p>' . esc_html__( 'Pipe not found.', 'pipes' ) . '</p>';
            return;
        }

        $target = $this->get_output_target( $pipe, 'dashboard', $node_id );
        if ( null === $target ) {
            echo '<p>' . esc_html__( 'Output node not found.', 'pipes' ) . '</p>';
            return;
        }

        $run = $this->get_cached_pipe_run( $pipe );
        if ( is_wp_error( $run ) && 'pipes_user_input_required' === $run->get_error_code() ) {
            echo '<div class="pipes-output pipes-output-dashboard">';
            echo $this->render_dashboard_query_form( $pipe, $target, (array) $run->get_error_data() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            echo '</div>';
            return;
        }

        echo '<div class="pipes-output pipes-output-dashboard">';
        echo $this->render_pipe_output_html( $pipe, $target, $run ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo $this->render_dashboard_output_footer( $pipe, $run ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo '</div>';
    }

    public function handle_dashboard_output_submission(): void {
        $post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $node_id = isset( $_POST['node_id'] ) ? sanitize_key( (string) wp_unslash( $_POST['node_id'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
        check_admin_referer( 'pipes_dashboard_output_' . $post_id . '_' . $node_id );

        $post = $this->get_pipe_post( $post_id );
        if ( is_wp_error( $post ) ) {
            wp_die( esc_html( $post->get_error_message() ) );
        }

        $answers = isset( $_POST['user_answers'] ) && is_array( $_POST['user_answers'] ) ? $this->sanitize_json_value( wp_unslash( $_POST['user_answers'] ) ) : []; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        $result  = $this->run_graph( $this->get_pipe_graph( $post ), false, $answers );
        if ( is_wp_error( $result ) ) {
            wp_die( esc_html( $result->get_error_message() ) );
        }

        $result['generated_at'] = time();
        $result['cached']       = false;
        set_transient( $this->get_pipe_output_cache_key( $post ), $result, 5 * MINUTE_IN_SECONDS );

        wp_safe_redirect( wp_get_referer() ?: admin_url( 'index.php' ) );
        exit;
    }

    public function register_admin_bar_outputs( $wp_admin_bar ): void {
        if ( ! is_user_logged_in() || ! is_admin_bar_showing() ) {
            return;
        }

        $menu_pipes  = $this->get_output_pipes( 'masterbar_menu' );
        $graph_pipes = $this->get_output_pipes( 'masterbar_graph' );

        if ( [] === $menu_pipes && [] === $graph_pipes ) {
            return;
        }

        $wp_admin_bar->add_node( [
            'id'    => 'pipes-outputs',
            'title' => esc_html__( 'Pipes', 'pipes' ),
            'href'  => home_url( '/pipes/' ),
        ] );

        foreach ( $graph_pipes as $pipe ) {
            foreach ( $this->get_output_targets( $pipe, 'masterbar_graph' ) as $target ) {
                $value = $this->get_pipe_output_value( $pipe, $target );
                $wp_admin_bar->add_node( [
                    'id'     => 'pipes-graph-' . $pipe->ID . '-' . $target['node_id'],
                    'parent' => 'pipes-outputs',
                    'title'  => $this->render_sparkline_title( $target['label'], $value ),
                    'href'   => $this->get_pipe_edit_url( $pipe ),
                    'meta'   => [
                        'class' => 'pipes-masterbar-graph',
                    ],
                ] );
            }
        }

        foreach ( $menu_pipes as $pipe ) {
            foreach ( $this->get_output_targets( $pipe, 'masterbar_menu' ) as $target ) {
                $wp_admin_bar->add_node( [
                    'id'     => 'pipes-menu-' . $pipe->ID . '-' . $target['node_id'],
                    'parent' => 'pipes-outputs',
                    'title'  => esc_html( $target['label'] ),
                    'href'   => $this->get_pipe_edit_url( $pipe ),
                ] );

                foreach ( $this->get_masterbar_menu_items( $pipe, $target ) as $index => $item ) {
                    $wp_admin_bar->add_node( [
                        'id'     => 'pipes-menu-' . $pipe->ID . '-' . $target['node_id'] . '-' . $index,
                        'parent' => 'pipes-menu-' . $pipe->ID . '-' . $target['node_id'],
                        'title'  => esc_html( $item ),
                        'href'   => $this->get_pipe_edit_url( $pipe ),
                    ] );
                }
            }
        }
    }

    public function output_styles(): void {
        if ( ! is_user_logged_in() ) {
            return;
        }
        ?>
        <style>
            .pipes-output table { width: 100%; border-collapse: collapse; }
            .pipes-output th, .pipes-output td { border-bottom: 1px solid #dcdcde; padding: 6px 8px; text-align: left; vertical-align: top; }
            .pipes-output ul { margin: 0 0 0 1.2em; }
            .pipes-output-footer { align-items: center; border-top: 1px solid #dcdcde; color: #646970; display: flex; flex-wrap: wrap; font-size: 12px; gap: 8px; justify-content: space-between; margin-top: 12px; padding-top: 8px; }
            #wpadminbar .pipes-masterbar-graph svg { display: inline-block; margin-left: 6px; vertical-align: middle; }
        </style>
        <?php
    }

    public function register_ai_assistant_ability_domains( array $domains ): array {
        $domains['pipes'] = 'pipes, workflows, flows, yahoo pipes, connect abilities, ability pipeline, automation, create pipe, edit pipe, dashboard output';
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

    private function pipe_summary_schema(): array {
        return [
            'type'       => 'object',
            'properties' => [
                'id'       => [ 'type' => 'integer' ],
                'title'    => [ 'type' => 'string' ],
                'modified' => [ 'type' => 'string' ],
                'edit_url' => [ 'type' => 'string' ],
            ],
        ];
    }

    private function pipe_schema( bool $include_graph ): array {
        $schema = $this->pipe_summary_schema();
        if ( $include_graph ) {
            $schema['properties']['graph'] = $this->pipe_graph_schema();
        }

        return $schema;
    }

    private function pipe_graph_schema(): array {
        return [
            'type'                 => 'object',
            'description'          => __( 'Pipes graph. Nodes execute in dependency order from edges/bindings; list mode displays nodes in array order.', 'pipes' ),
            'properties'           => [
                'nodes' => [
                    'type'        => 'array',
                    'description' => __( 'Workflow boxes. Use ability_id values from the WordPress Abilities API, including glue abilities like pipes/filter-items and output abilities like pipes/output-dashboard-list.', 'pipes' ),
                    'items'       => [
                        'type'                 => 'object',
                        'properties'           => [
                            'id'         => [
                                'type'        => 'string',
                                'description' => __( 'Stable node ID used by bindings, e.g. search, filter, output.', 'pipes' ),
                            ],
                            'ability_id' => [
                                'type'        => 'string',
                                'description' => __( 'Registered ability ID to execute at this node.', 'pipes' ),
                            ],
                            'label'      => [
                                'type'        => 'string',
                                'description' => __( 'User-facing node label.', 'pipes' ),
                            ],
                            'args'       => [
                                'type'        => 'object',
                                'description' => __( 'Base input arguments for the ability. Bound inputs should usually be omitted here.', 'pipes' ),
                            ],
                            'bindings'   => [
                                'type'        => 'array',
                                'description' => __( 'Input bindings from upstream node result paths.', 'pipes' ),
                                'items'       => [
                                    'type'                 => 'object',
                                    'properties'           => [
                                        'target' => [
                                            'type'        => 'string',
                                            'description' => __( 'Input property name on this node.', 'pipes' ),
                                        ],
                                        'source' => [
                                            'type'        => 'string',
                                            'description' => __( 'Upstream node ID.', 'pipes' ),
                                        ],
                                        'path'   => [
                                            'type'        => 'string',
                                            'description' => __( 'Dot path within the upstream result; empty means the whole result.', 'pipes' ),
                                        ],
                                    ],
                                    'required'             => [ 'target', 'source' ],
                                    'additionalProperties' => false,
                                ],
                            ],
                            'position'   => [
                                'type'                 => 'object',
                                'description'          => __( 'Visual canvas position.', 'pipes' ),
                                'properties'           => [
                                    'x' => [ 'type' => 'integer' ],
                                    'y' => [ 'type' => 'integer' ],
                                ],
                                'additionalProperties' => false,
                            ],
                        ],
                        'required'             => [ 'id', 'ability_id' ],
                        'additionalProperties' => false,
                    ],
                ],
                'edges' => [
                    'type'        => 'array',
                    'description' => __( 'Visual/dependency edges between node IDs. Bindings also create execution dependencies.', 'pipes' ),
                    'items'       => [
                        'type'                 => 'object',
                        'properties'           => [
                            'from' => [ 'type' => 'string' ],
                            'to'   => [ 'type' => 'string' ],
                        ],
                        'required'             => [ 'from', 'to' ],
                        'additionalProperties' => false,
                    ],
                ],
            ],
            'required'             => [ 'nodes' ],
            'additionalProperties' => false,
        ];
    }

    private function output_ability_labels(): array {
        return [
            'pipes/output-dashboard-text'   => __( 'Dashboard Text Output', 'pipes' ),
            'pipes/output-dashboard-list'   => __( 'Dashboard List Output', 'pipes' ),
            'pipes/output-masterbar-menu'   => __( 'Masterbar Menu Output', 'pipes' ),
            'pipes/output-masterbar-graph'  => __( 'Masterbar Graph Output', 'pipes' ),
        ];
    }

    private function output_abilities_for_key( string $output_key ): array {
        $map = [
            'dashboard'       => [ 'pipes/output-dashboard-text', 'pipes/output-dashboard-list' ],
            'masterbar_menu'  => [ 'pipes/output-masterbar-menu' ],
            'masterbar_graph' => [ 'pipes/output-masterbar-graph' ],
        ];

        return $map[ $output_key ] ?? [];
    }

    private function get_output_pipes( string $output_key ): array {
        if ( ! is_user_logged_in() ) {
            return [];
        }

        $query = new \WP_Query( [
            'post_type'      => self::POST_TYPE,
            'post_status'    => [ 'publish', 'draft', 'private' ],
            'posts_per_page' => 50,
            'orderby'        => 'title',
            'order'          => 'ASC',
            'author'         => get_current_user_id(),
        ] );

        $pipes = [];
        foreach ( $query->posts as $post ) {
            if ( [] !== $this->get_output_targets( $post, $output_key ) ) {
                $pipes[] = $post;
            }
        }

        return $pipes;
    }

    private function get_output_targets( \WP_Post $post, string $output_key ): array {
        $graph = $this->get_pipe_graph( $post );
        $ability_ids = $this->output_abilities_for_key( $output_key );
        $targets = [];
        foreach ( (array) ( $graph['nodes'] ?? [] ) as $node ) {
            $ability_id = (string) ( $node['ability_id'] ?? '' );
            if ( ! in_array( $ability_id, $ability_ids, true ) ) {
                continue;
            }

            $targets[] = [
                'output_key'  => $output_key,
                'ability_id'  => $ability_id,
                'node_id'     => (string) ( $node['id'] ?? '' ),
                'label'       => (string) ( $node['label'] ?: $post->post_title ),
                'args'        => is_array( $node['args'] ?? null ) ? $node['args'] : [],
                'render_mode' => $this->render_mode_for_output_ability( $ability_id ),
            ];
        }

        return $targets;
    }

    private function get_output_target( \WP_Post $post, string $output_key, string $node_id ): ?array {
        foreach ( $this->get_output_targets( $post, $output_key ) as $target ) {
            if ( $target['node_id'] === $node_id ) {
                return $target;
            }
        }

        return null;
    }

    private function render_mode_for_output_ability( string $ability_id ): string {
        if ( 'pipes/output-dashboard-text' === $ability_id ) {
            return 'text';
        }

        if ( 'pipes/output-dashboard-list' === $ability_id || 'pipes/output-masterbar-menu' === $ability_id ) {
            return 'list';
        }

        if ( 'pipes/output-masterbar-graph' === $ability_id ) {
            return 'graph';
        }

        return 'auto';
    }

    private function get_pipe_output_value( \WP_Post $post, array $target, $run = null ) {
        $run = null === $run ? $this->get_cached_pipe_run( $post ) : $run;
        if ( is_wp_error( $run ) ) {
            return $run;
        }

        $node_id = (string) ( $target['node_id'] ?? '' );
        if ( '' !== $node_id ) {
            $node_result = $run['results'][ $node_id ]['result'] ?? null;
            if ( is_array( $node_result ) && array_key_exists( 'raw_value', $node_result ) ) {
                return $node_result['raw_value'];
            }
            if ( is_array( $node_result ) && array_key_exists( 'value', $node_result ) ) {
                return $node_result['value'];
            }

            return $node_result;
        }

        return null;
    }

    private function get_pipe_output_cache_key( \WP_Post $post ): string {
        return 'pipes_output_' . get_current_user_id() . '_' . $post->ID . '_' . md5( $post->post_modified_gmt );
    }

    private function get_cached_pipe_run( \WP_Post $post ) {
        $cache_key = $this->get_pipe_output_cache_key( $post );
        $cached    = get_transient( $cache_key );
        if ( false !== $cached ) {
            if ( is_array( $cached ) ) {
                $cached['cached'] = true;
            }
            return $cached;
        }

        $result = $this->run_graph( $this->get_pipe_graph( $post ), false );
        if ( is_wp_error( $result ) ) {
            return $result;
        }
        if ( is_array( $result ) ) {
            $result['generated_at'] = time();
            $result['cached']       = false;
        }
        set_transient( $cache_key, $result, 5 * MINUTE_IN_SECONDS );

        return $result;
    }

    private function render_dashboard_output_footer( \WP_Post $post, $run = null ): string {
        $run = null === $run ? $this->get_cached_pipe_run( $post ) : $run;
        $generated_at = is_array( $run ) ? (int) ( $run['generated_at'] ?? 0 ) : 0;
        if ( $generated_at > 0 ) {
            $generated = sprintf(
                __( 'Generated %1$s ago (%2$s).', 'pipes' ),
                human_time_diff( $generated_at, time() ),
                wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $generated_at )
            );
        } else {
            $generated = __( 'Generated on demand.', 'pipes' );
        }

        return sprintf(
            '<div class="pipes-output-footer"><span>%1$s %2$s</span><a href="%3$s">%4$s</a></div>',
            esc_html( $generated ),
            esc_html__( 'Cached for up to 5 minutes.', 'pipes' ),
            esc_url( $this->get_pipe_edit_url( $post ) ),
            esc_html__( 'Edit pipe', 'pipes' )
        );
    }

    private function render_dashboard_query_form( \WP_Post $post, array $target, array $error_data ): string {
        $questions = isset( $error_data['questions'] ) && is_array( $error_data['questions'] ) ? $error_data['questions'] : [];
        if ( [] === $questions ) {
            return '<p>' . esc_html__( 'This pipe needs user input before it can run.', 'pipes' ) . '</p>';
        }

        $html = '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" class="pipes-query-form">';
        $html .= '<input type="hidden" name="action" value="pipes_run_dashboard_output">';
        $html .= '<input type="hidden" name="post_id" value="' . esc_attr( (string) $post->ID ) . '">';
        $html .= '<input type="hidden" name="node_id" value="' . esc_attr( (string) $target['node_id'] ) . '">';
        $html .= wp_nonce_field( 'pipes_dashboard_output_' . $post->ID . '_' . $target['node_id'], '_wpnonce', true, false );
        foreach ( $questions as $question ) {
            $key = (string) ( $question['key'] ?? '' );
            if ( '' === $key ) {
                continue;
            }
            $html .= '<p><label><strong>' . esc_html( (string) ( $question['question'] ?? $key ) ) . '</strong>';
            $html .= '<input type="text" name="user_answers[' . esc_attr( $key ) . ']" value="" style="width:100%;margin-top:6px;"></label></p>';
        }
        $html .= '<p><button class="button button-primary" type="submit">' . esc_html__( 'Run pipe', 'pipes' ) . '</button></p>';
        $html .= '</form>';

        return $html;
    }

    private function get_pipe_edit_url( \WP_Post $post ): string {
        return add_query_arg( 'pipe', (string) $post->ID, home_url( '/pipes/' ) );
    }

    private function render_pipe_output_html( \WP_Post $post, array $target, $run = null ): string {
        $value = $this->get_pipe_output_value( $post, $target, $run );

        if ( is_wp_error( $value ) ) {
            return '<p>' . esc_html( $value->get_error_message() ) . '</p>';
        }

        if ( 'text' === ( $target['render_mode'] ?? '' ) ) {
            return '<p>' . esc_html( $this->stringify_glue_value( $value ) ) . '</p>';
        }

        $columns = [];
        if ( 'pipes/output-dashboard-list' === ( $target['ability_id'] ?? '' ) && is_array( $target['args']['columns'] ?? null ) ) {
            $columns = array_values( array_filter(
                array_map( 'strval', $target['args']['columns'] ),
                static fn( string $column ): bool => '' !== $column
            ) );
        }

        return $this->render_value_html( $value, $columns );
    }

    private function render_value_html( $value, array $selected_columns = [] ): string {
        if ( null === $value ) {
            return '<p><em>' . esc_html__( 'No output.', 'pipes' ) . '</em></p>';
        }

        if ( is_scalar( $value ) ) {
            return '<p>' . esc_html( (string) $value ) . '</p>';
        }

        if ( is_array( $value ) && $this->is_list_array( $value ) ) {
            if ( [] === $value ) {
                return '<p><em>' . esc_html__( 'No items.', 'pipes' ) . '</em></p>';
            }

            $first = reset( $value );
            if ( is_array( $first ) ) {
                $available_columns = array_keys( $first );
                $columns = [] === $selected_columns ? array_slice( $available_columns, 0, 6 ) : array_values( array_intersect( $selected_columns, $available_columns ) );
                if ( [] === $columns ) {
                    $columns = array_slice( $available_columns, 0, 6 );
                }
                $html = '<table><thead><tr>';
                foreach ( $columns as $column ) {
                    $html .= '<th>' . esc_html( (string) $column ) . '</th>';
                }
                $html .= '</tr></thead><tbody>';
                foreach ( array_slice( $value, 0, 10 ) as $row ) {
                    $html .= '<tr>';
                    foreach ( $columns as $column ) {
                        $html .= '<td>' . esc_html( $this->stringify_glue_value( $row[ $column ] ?? '' ) ) . '</td>';
                    }
                    $html .= '</tr>';
                }
                $html .= '</tbody></table>';
                return $html;
            }

            $html = '<ul>';
            foreach ( array_slice( $value, 0, 10 ) as $item ) {
                $html .= '<li>' . esc_html( $this->stringify_glue_value( $item ) ) . '</li>';
            }
            $html .= '</ul>';
            return $html;
        }

        if ( is_array( $value ) ) {
            $html = '<table><tbody>';
            foreach ( array_slice( $value, 0, 12, true ) as $key => $item ) {
                $html .= '<tr><th>' . esc_html( (string) $key ) . '</th><td>' . esc_html( $this->stringify_glue_value( $item ) ) . '</td></tr>';
            }
            $html .= '</tbody></table>';
            return $html;
        }

        return '<pre>' . esc_html( $this->stringify_glue_value( $value ) ) . '</pre>';
    }

    private function get_masterbar_menu_items( \WP_Post $post, array $target ): array {
        $value = $this->get_pipe_output_value( $post, $target );
        if ( is_wp_error( $value ) ) {
            return [ $value->get_error_message() ];
        }

        if ( is_array( $value ) && isset( $value['items'] ) && is_array( $value['items'] ) ) {
            $value = $value['items'];
        }

        if ( is_array( $value ) && $this->is_list_array( $value ) ) {
            $items = [];
            foreach ( array_slice( $value, 0, 8 ) as $item ) {
                if ( is_array( $item ) ) {
                    $items[] = (string) ( $item['title'] ?? $item['name'] ?? $item['label'] ?? $this->stringify_glue_value( $item ) );
                } else {
                    $items[] = $this->stringify_glue_value( $item );
                }
            }
            return $items;
        }

        return [ $this->stringify_glue_value( $value ) ];
    }

    private function render_sparkline_title( string $title, $value ): string {
        $numbers = $this->extract_numbers( $value );
        if ( count( $numbers ) < 2 ) {
            return esc_html( $title );
        }

        $width = 74;
        $height = 20;
        $min = min( $numbers );
        $max = max( $numbers );
        $range = max( 1, $max - $min );
        $points = [];
        foreach ( array_values( $numbers ) as $index => $number ) {
            $x = count( $numbers ) > 1 ? ( $index / ( count( $numbers ) - 1 ) ) * $width : 0;
            $y = $height - ( ( $number - $min ) / $range ) * $height;
            $points[] = round( $x, 1 ) . ',' . round( $y, 1 );
        }

        $svg = '<svg width="' . esc_attr( (string) $width ) . '" height="' . esc_attr( (string) $height ) . '" viewBox="0 0 ' . esc_attr( (string) $width ) . ' ' . esc_attr( (string) $height ) . '" aria-hidden="true"><polyline fill="none" stroke="currentColor" stroke-width="2" points="' . esc_attr( implode( ' ', $points ) ) . '"/></svg>';

        return esc_html( $title ) . ' ' . $svg;
    }

    private function extract_numbers( $value ): array {
        if ( is_wp_error( $value ) ) {
            return [];
        }

        if ( is_numeric( $value ) ) {
            return [ (float) $value ];
        }

        if ( is_array( $value ) && isset( $value['values'] ) && is_array( $value['values'] ) ) {
            $value = $value['values'];
        }

        if ( ! is_array( $value ) ) {
            return [];
        }

        $numbers = [];
        foreach ( $value as $item ) {
            if ( is_numeric( $item ) ) {
                $numbers[] = (float) $item;
            } elseif ( is_array( $item ) ) {
                foreach ( $item as $candidate ) {
                    if ( is_numeric( $candidate ) ) {
                        $numbers[] = (float) $candidate;
                        break;
                    }
                }
            }
        }

        return array_slice( $numbers, 0, 24 );
    }

    private function extract_format_text_placeholders( string $template ): array {
        preg_match_all( '/\{\{\s*([A-Za-z_][A-Za-z0-9_.-]*)\s*\}\}|\{([A-Za-z_][A-Za-z0-9_.-]*)\}/', $template, $matches, PREG_SET_ORDER );

        $placeholders = [];
        foreach ( $matches as $match ) {
            $name = '' !== ( $match[1] ?? '' ) ? $match[1] : ( $match[2] ?? '' );
            if ( '' !== $name && ! in_array( $name, $placeholders, true ) ) {
                $placeholders[] = $name;
            }
        }

        return $placeholders;
    }

    private function is_list_array( array $value ): bool {
        if ( [] === $value ) {
            return true;
        }

        return array_keys( $value ) === range( 0, count( $value ) - 1 );
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

    private function format_pipe_for_ability( \WP_Post $post, bool $include_graph = false ): array {
        $pipe = $this->format_pipe( $post, $include_graph );
        $pipe['edit_url'] = $this->get_pipe_edit_url( $post );
        return $pipe;
    }

    private function save_pipe_post( int $post_id, string $title, array $graph ) {
        $title = sanitize_text_field( $title );
        if ( '' === $title ) {
            return new \WP_Error( 'pipes_title_required', __( 'Pipe title is required.', 'pipes' ), [ 'status' => 400 ] );
        }

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

        $post = get_post( (int) $result );
        if ( ! $post instanceof \WP_Post ) {
            return new \WP_Error( 'pipes_save_failed', __( 'Pipe could not be loaded after saving.', 'pipes' ), [ 'status' => 500 ] );
        }

        return $post;
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

    private function run_graph( array $graph, bool $confirm_destructive = false, array $user_answers = [] ) {
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
            foreach ( $input as $key => $value ) {
                if ( ! $this->is_user_query_arg( $value ) ) {
                    continue;
                }
                $answer_key = $node['id'] . '.' . $key;
                if ( ! array_key_exists( $answer_key, $user_answers ) ) {
                    return new \WP_Error(
                        'pipes_user_input_required',
                        __( 'This pipe needs user input before it can run.', 'pipes' ),
                        [
                            'questions' => $this->get_user_query_questions( $graph ),
                        ]
                    );
                }
                $input[ $key ] = $user_answers[ $answer_key ];
            }
            foreach ( (array) ( $node['bindings'] ?? [] ) as $binding ) {
                $source_id = (string) ( $binding['source'] ?? '' );
                $target    = (string) ( $binding['target'] ?? '' );
                if ( '' === $source_id || '' === $target || ! array_key_exists( $source_id, $results ) ) {
                    continue;
                }
                $input[ $target ] = $this->get_path_value( $results[ $source_id ]['result'], (string) ( $binding['path'] ?? '' ) );
            }

            if ( $this->should_skip_missing_items_node( $details, $input ) ) {
                continue;
            }

            $result = $ability->execute( $input );
            if ( is_wp_error( $result ) ) {
                return new \WP_Error(
                    'pipes_ability_failed',
                    sprintf( __( '%1$s failed: %2$s', 'pipes' ), $details['label'], $result->get_error_message() ),
                    [
                        'status'  => 500,
                        'results' => $results,
                    ]
                );
            }

            $result = $this->decorate_output_result( $details['id'], $input, $this->normalize_result( $result ) );

            $results[ $node['id'] ] = [
                'node_id'    => $node['id'],
                'ability_id' => $details['id'],
                'label'      => $details['label'],
                'input'      => $input,
                'result'     => $result,
            ];
        }

        return [
            'success' => true,
            'results' => $results,
        ];
    }

    private function should_skip_missing_items_node( array $details, array $input ): bool {
        $schema = is_array( $details['input_schema'] ?? null ) ? $details['input_schema'] : [];
        if ( ! in_array( 'items', (array) ( $schema['required'] ?? [] ), true ) ) {
            return false;
        }

        $items_schema = $schema['properties']['items'] ?? null;
        if ( ! is_array( $items_schema ) ) {
            return false;
        }

        $types = (array) ( $items_schema['type'] ?? [] );
        if ( ! in_array( 'array', $types, true ) ) {
            return false;
        }

        return ! isset( $input['items'] ) || ! is_array( $input['items'] );
    }

    private function is_user_query_arg( $value ): bool {
        return is_array( $value ) && ! empty( $value['__pipes_user_query'] );
    }

    private function get_user_query_questions( array $graph ): array {
        $questions = [];
        foreach ( (array) ( $graph['nodes'] ?? [] ) as $node ) {
            if ( ! is_array( $node ) || ! is_array( $node['args'] ?? null ) ) {
                continue;
            }
            foreach ( $node['args'] as $key => $value ) {
                if ( ! $this->is_user_query_arg( $value ) ) {
                    continue;
                }
                $questions[] = [
                    'key'      => (string) ( $node['id'] ?? '' ) . '.' . (string) $key,
                    'node_id'  => (string) ( $node['id'] ?? '' ),
                    'input'    => (string) $key,
                    'question' => (string) ( $value['question'] ?? $key ),
                ];
            }
        }

        return $questions;
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

    private function set_path_value( &$value, string $path, $replacement ): bool {
        $parts = array_values( array_filter( explode( '.', trim( $path ) ), static fn( string $part ): bool => '' !== $part ) );
        if ( [] === $parts ) {
            $value = $replacement;
            return true;
        }

        $cursor =& $value;
        foreach ( $parts as $index => $part ) {
            $is_last = count( $parts ) - 1 === $index;
            if ( is_array( $cursor ) ) {
                if ( ! array_key_exists( $part, $cursor ) ) {
                    return false;
                }
                if ( $is_last ) {
                    $cursor[ $part ] = $replacement;
                    return true;
                }
                $cursor =& $cursor[ $part ];
                continue;
            }

            if ( is_object( $cursor ) ) {
                if ( ! isset( $cursor->{$part} ) ) {
                    return false;
                }
                if ( $is_last ) {
                    $cursor->{$part} = $replacement;
                    return true;
                }
                $cursor =& $cursor->{$part};
                continue;
            }

            return false;
        }

        return false;
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

    private function format_date_value( $value, string $date_format, string $input_format ): ?string {
        if ( null === $value || is_array( $value ) || is_object( $value ) ) {
            return null;
        }

        $timestamp = $this->parse_field_date_timestamp( $this->stringify_glue_value( $value ), $input_format );
        if ( null === $timestamp ) {
            return null;
        }

        return wp_date( $date_format, $timestamp );
    }

    private function parse_field_date_timestamp( string $text, string $input_format ): ?int {
        $text = trim( $text );
        if ( '' === $text ) {
            return null;
        }

        if ( '' !== $input_format ) {
            $date = \DateTimeImmutable::createFromFormat( '!' . $input_format, $text, wp_timezone() );
            if ( $date instanceof \DateTimeImmutable ) {
                return $date->getTimestamp();
            }
            return null;
        }

        $timestamp = strtotime( $text );
        return false === $timestamp ? null : $timestamp;
    }

    private function build_regex_pattern( string $pattern, bool $case_sensitive ): string {
        return '~' . str_replace( '~', '\\~', $pattern ) . '~u' . ( $case_sensitive ? '' : 'i' );
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

    private function decorate_output_result( string $ability_id, array $input, $result ) {
        if ( ! is_array( $result ) ) {
            $result = [ 'value' => $result ];
        }

        if ( 'pipes/output-dashboard-text' === $ability_id ) {
            $raw_value           = $result['value'] ?? null;
            $result['raw_value'] = $raw_value;
            $result['html']      = '<p>' . esc_html( $this->stringify_glue_value( $raw_value ) ) . '</p>';
            $result['value']     = $result['html'];
            return $result;
        }

        if ( 'pipes/output-dashboard-list' === $ability_id ) {
            $raw_value = $result['value'] ?? null;
            $columns = is_array( $input['columns'] ?? null ) ? array_values( array_filter(
                array_map( 'strval', $input['columns'] ),
                static fn( string $column ): bool => '' !== $column
            ) ) : [];

            $result['raw_value'] = $raw_value;
            $result['html']      = $this->render_value_html( $raw_value, $columns );
            $result['value']     = $result['html'];
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
                'id'          => 'latest-flight-logs-dashboard',
                'title'       => __( 'Latest Flight Logs Dashboard', 'pipes' ),
                'description' => __( 'Show the latest five logged flights in a named WordPress dashboard widget.', 'pipes' ),
                'requires'    => [ 'flight-log/search-flights', 'pipes/limit-items', 'pipes/output-dashboard-list' ],
                'graph'       => [
                    'nodes' => [
                        [
                            'id'         => 'flights',
                            'ability_id' => 'flight-log/search-flights',
                            'label'      => __( 'Find logged flights', 'pipes' ),
                            'args'       => [
                                'planned' => false,
                                'limit'   => 25,
                            ],
                            'bindings'   => [],
                            'position'   => [ 'x' => 28, 'y' => 64 ],
                        ],
                        [
                            'id'         => 'latest-five',
                            'ability_id' => 'pipes/limit-items',
                            'label'      => __( 'Keep latest five', 'pipes' ),
                            'args'       => [ 'limit' => 5 ],
                            'bindings'   => [
                                [ 'target' => 'items', 'source' => 'flights', 'path' => 'flights' ],
                            ],
                            'position'   => [ 'x' => 328, 'y' => 64 ],
                        ],
                        [
                            'id'         => 'dashboard',
                            'ability_id' => 'pipes/output-dashboard-list',
                            'label'      => __( 'Latest 5 Flight Logs', 'pipes' ),
                            'args'       => [
                                'columns' => [ 'date', 'aircraft', 'departure', 'arrival', 'duration' ],
                            ],
                            'bindings'   => [
                                [ 'target' => 'value', 'source' => 'latest-five', 'path' => 'items' ],
                            ],
                            'position'   => [ 'x' => 628, 'y' => 64 ],
                        ],
                    ],
                    'edges' => [
                        [ 'from' => 'flights', 'to' => 'latest-five' ],
                        [ 'from' => 'latest-five', 'to' => 'dashboard' ],
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
