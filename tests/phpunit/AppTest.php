<?php

namespace Pipes\Tests;

use PHPUnit\Framework\TestCase;
use Pipes\App;
use ReflectionClass;

class AppTest extends TestCase {
    private App $app;

    protected function setUp(): void {
        $reflection = new ReflectionClass( App::class );
        $this->app  = $reflection->newInstanceWithoutConstructor();

        $GLOBALS['pipes_test_registered_abilities'] = [];
    }

    public function test_debug_output_sink_returns_primary_value_and_all_values(): void {
        $result = $this->app->ability_debug_output_sink( [
            'value'          => 'primary',
            'value_2'        => [ 'secondary' ],
            'filtered_items' => [
                [ 'title' => 'Example' ],
            ],
        ] );

        $this->assertSame( 'primary', $result['value'] );
        $this->assertSame(
            [
                'value'          => 'primary',
                'value_2'        => [ 'secondary' ],
                'filtered_items' => [
                    [ 'title' => 'Example' ],
                ],
            ],
            $result['values']
        );
    }

    public function test_debug_output_sink_handles_non_array_input(): void {
        $this->assertSame(
            [
                'value'  => null,
                'values' => [],
            ],
            $this->app->ability_debug_output_sink( 'not-an-array' )
        );
    }

    public function test_debug_output_ability_accepts_additional_properties(): void {
        $this->app->register_abilities();

        $ability = $GLOBALS['pipes_test_registered_abilities']['pipes/output-debug'] ?? null;

        $this->assertIsArray( $ability );
        $this->assertSame( [ $this->app, 'ability_debug_output_sink' ], $ability['execute_callback'] );
        $this->assertSame(
            [
                'type'        => [ 'object', 'array', 'string', 'number', 'integer', 'boolean', 'null' ],
                'description' => 'Additional values to inspect. Use names such as value_2 or filtered_items.',
            ],
            $ability['input_schema']['additionalProperties']
        );
        $this->assertSame(
            [
                'type'                 => 'object',
                'additionalProperties' => [
                    'type' => [ 'object', 'array', 'string', 'number', 'integer', 'boolean', 'null' ],
                ],
            ],
            $ability['output_schema']['properties']['values']
        );
    }

    public function test_dashboard_output_abilities_expose_rendered_html_output(): void {
        $this->app->register_abilities();

        $text = $GLOBALS['pipes_test_registered_abilities']['pipes/output-dashboard-text'] ?? null;
        $list = $GLOBALS['pipes_test_registered_abilities']['pipes/output-dashboard-list'] ?? null;
        $menu = $GLOBALS['pipes_test_registered_abilities']['pipes/output-masterbar-menu'] ?? null;

        $this->assertSame( 'string', $text['output_schema']['properties']['html']['type'] );
        $this->assertSame( 'string', $list['output_schema']['properties']['html']['type'] );
        $this->assertSame(
            [ 'object', 'array', 'string', 'number', 'integer', 'boolean', 'null' ],
            $list['output_schema']['properties']['raw_value']['type']
        );
        $this->assertArrayNotHasKey( 'html', $menu['output_schema']['properties'] );
    }

    public function test_dashboard_list_output_result_includes_rendered_html(): void {
        $method = ( new ReflectionClass( App::class ) )->getMethod( 'decorate_output_result' );
        $method->setAccessible( true );

        $result = $method->invoke(
            $this->app,
            'pipes/output-dashboard-list',
            [ 'columns' => [ 'title' ] ],
            [
                'value' => [
                    [ 'title' => 'First item', 'url' => 'https://example.test/first' ],
                ],
            ]
        );

        $this->assertSame(
            [
                'value'     => '<table><thead><tr><th>title</th></tr></thead><tbody><tr><td>First item</td></tr></tbody></table>',
                'raw_value' => [
                    [ 'title' => 'First item', 'url' => 'https://example.test/first' ],
                ],
                'html'  => '<table><thead><tr><th>title</th></tr></thead><tbody><tr><td>First item</td></tr></tbody></table>',
            ],
            $result
        );
    }
}
