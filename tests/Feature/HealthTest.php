<?php

namespace Tests\Feature;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use PDOException;
use Tests\TestCase;

class HealthTest extends TestCase
{
    public function test_health_reports_a_working_database_connection(): void
    {
        $this->getJson('/health')->assertOk()->assertExactJson([
            'status' => 'ok',
            'database' => 'connected',
        ]);
    }

    public function test_health_reports_an_unavailable_database(): void
    {
        DB::shouldReceive('selectFromWriteConnection')
            ->once()
            ->with('select 1')
            ->andThrow(new QueryException('sqlite', 'select 1', [], new PDOException));

        $this->getJson('/health')->assertServiceUnavailable()->assertExactJson([
            'status' => 'unavailable',
            'database' => 'disconnected',
        ]);
    }
}
