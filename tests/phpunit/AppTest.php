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
}
