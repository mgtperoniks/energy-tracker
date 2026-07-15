<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\Machine;
use App\Models\Device;
use App\Models\Location;
use App\Models\OperationalEventTag;
use App\Services\CycleReconstructionService;
use Carbon\Carbon;
use Illuminate\Support\Str;

class CycleReconstructionTest extends TestCase
{
    use DatabaseTransactions;

    private $service;
    private $machine;
    private $device;

    protected function setUp(): void
    {
        putenv('DB_CONNECTION=mysql');
        $_ENV['DB_CONNECTION'] = 'mysql';
        putenv('DB_DATABASE=energy_tracker');
        $_ENV['DB_DATABASE'] = 'energy_tracker';
        putenv('DB_URL=');
        $_ENV['DB_URL'] = '';
        
        parent::setUp();
        
        $this->service = app(CycleReconstructionService::class);
        
        $location = Location::first() ?? Location::create([
            'name' => 'Test Location',
        ]);

        $this->machine = Machine::create([
            'location_id' => $location->id,
            'name' => 'Test Furnace 1',
            'code' => 'TF-01',
            'type' => 'Furnace',
        ]);

        $maxSlaveId = Device::max('slave_id') ?? 100;
        $slaveId = $maxSlaveId + rand(1, 1000);
        
        $this->device = Device::create([
            'machine_id' => $this->machine->id,
            'name' => 'PM-TF01',
            'type' => 'power_meter',
            'slave_id' => $slaveId,
            'api_token' => Str::random(60),
            'ip_address' => '127.0.0.1',
            'port' => 502,
            'unit_id' => 1,
            'is_online' => true,
        ]);
    }

    public function test_reconstruct_melting_pour_closed()
    {
        $start = Carbon::parse('2026-07-15 08:00:00');
        $end = Carbon::parse('2026-07-15 12:00:00');

        OperationalEventTag::create([
            'device_id' => $this->device->id,
            'event_type' => 'start',
            'event_time' => Carbon::parse('2026-07-15 08:05:00'),
            'tagged_by' => 1,
        ]);

        OperationalEventTag::create([
            'device_id' => $this->device->id,
            'event_type' => 'melting',
            'event_time' => Carbon::parse('2026-07-15 08:15:00'),
            'tagged_by' => 1,
        ]);

        OperationalEventTag::create([
            'device_id' => $this->device->id,
            'event_type' => 'pour',
            'event_time' => Carbon::parse('2026-07-15 09:00:00'),
            'tagged_by' => 1,
        ]);

        $cycles = $this->service->reconstruct($this->device->id, $start, $end);

        $this->assertCount(1, $cycles);
        $this->assertEquals('CLOSED', $cycles[0]['status']);
    }

    public function test_reconstruct_downtime_start_pour_closed()
    {
        $start = Carbon::parse('2026-07-15 08:00:00');
        $end = Carbon::parse('2026-07-15 12:00:00');

        OperationalEventTag::create([
            'device_id' => $this->device->id,
            'event_type' => 'start',
            'event_time' => Carbon::parse('2026-07-15 08:05:00'),
            'tagged_by' => 1,
        ]);

        OperationalEventTag::create([
            'device_id' => $this->device->id,
            'event_type' => 'melting',
            'event_time' => Carbon::parse('2026-07-15 08:15:00'),
            'tagged_by' => 1,
        ]);

        OperationalEventTag::create([
            'device_id' => $this->device->id,
            'event_type' => 'downtime',
            'event_time' => Carbon::parse('2026-07-15 08:30:00'),
            'tagged_by' => 1,
        ]);

        OperationalEventTag::create([
            'device_id' => $this->device->id,
            'event_type' => 'start',
            'event_time' => Carbon::parse('2026-07-15 08:45:00'),
            'tagged_by' => 1,
        ]);

        OperationalEventTag::create([
            'device_id' => $this->device->id,
            'event_type' => 'pour',
            'event_time' => Carbon::parse('2026-07-15 09:15:00'),
            'tagged_by' => 1,
        ]);

        $cycles = $this->service->reconstruct($this->device->id, $start, $end);

        $this->assertCount(1, $cycles);
        $this->assertEquals('CLOSED', $cycles[0]['status']);
    }

    public function test_reconstruct_melting_end_incomplete()
    {
        $start = Carbon::parse('2026-07-15 08:00:00');
        $end = Carbon::parse('2026-07-15 12:00:00');

        OperationalEventTag::create([
            'device_id' => $this->device->id,
            'event_type' => 'start',
            'event_time' => Carbon::parse('2026-07-15 08:05:00'),
            'tagged_by' => 1,
        ]);

        OperationalEventTag::create([
            'device_id' => $this->device->id,
            'event_type' => 'melting',
            'event_time' => Carbon::parse('2026-07-15 08:15:00'),
            'tagged_by' => 1,
        ]);

        OperationalEventTag::create([
            'device_id' => $this->device->id,
            'event_type' => 'end',
            'event_time' => Carbon::parse('2026-07-15 09:30:00'),
            'tagged_by' => 1,
        ]);

        $cycles = $this->service->reconstruct($this->device->id, $start, $end);

        $this->assertCount(1, $cycles);
        $this->assertEquals('INCOMPLETE', $cycles[0]['status']);
    }

    public function test_reconstruct_downtime_end_aborted()
    {
        $start = Carbon::parse('2026-07-15 08:00:00');
        $end = Carbon::parse('2026-07-15 12:00:00');

        OperationalEventTag::create([
            'device_id' => $this->device->id,
            'event_type' => 'start',
            'event_time' => Carbon::parse('2026-07-15 08:05:00'),
            'tagged_by' => 1,
        ]);

        OperationalEventTag::create([
            'device_id' => $this->device->id,
            'event_type' => 'melting',
            'event_time' => Carbon::parse('2026-07-15 08:15:00'),
            'tagged_by' => 1,
        ]);

        OperationalEventTag::create([
            'device_id' => $this->device->id,
            'event_type' => 'downtime',
            'event_time' => Carbon::parse('2026-07-15 08:30:00'),
            'tagged_by' => 1,
        ]);

        OperationalEventTag::create([
            'device_id' => $this->device->id,
            'event_type' => 'end',
            'event_time' => Carbon::parse('2026-07-15 09:00:00'),
            'tagged_by' => 1,
        ]);

        $cycles = $this->service->reconstruct($this->device->id, $start, $end);

        $this->assertCount(1, $cycles);
        $this->assertEquals('ABORTED', $cycles[0]['status']);
    }
}
