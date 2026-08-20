<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\User;
use App\Models\Device;
use App\Models\Machine;
use App\Models\Location;
use App\Models\PowerReadingDaily;
use Carbon\Carbon;

class OperationalReportTest extends TestCase
{
    use DatabaseTransactions;

    private $user;
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

        $this->user = User::factory()->create();

        $location = Location::first() ?? Location::create(['name' => 'Test Location']);
        $machine = Machine::create([
            'location_id' => $location->id,
            'name' => 'Test Furnace 1',
            'code' => 'TF-01',
            'type' => 'Furnace',
        ]);

        $this->device = Device::create([
            'machine_id' => $machine->id,
            'name' => 'PM-TF01',
            'type' => 'power_meter',
            'slave_id' => (Device::max('slave_id') ?? 100) + rand(1, 1000),
            'api_token' => \Illuminate\Support\Str::random(60),
            'ip_address' => '127.0.0.1',
            'port' => 502,
            'unit_id' => 1,
            'is_online' => true,
        ]);

        PowerReadingDaily::create([
            'device_id' => $this->device->id,
            'recorded_date' => Carbon::now('Asia/Jakarta')->subDays(2)->toDateString(),
            'kwh_total' => 12500.50,
            'kwh_usage' => 500.25,
            'avg_power_kw' => 25.5,
            'min_power_kw' => 5.0,
            'max_power_kw' => 45.2,
            'avg_voltage' => 380.5,
            'avg_current' => 40.2,
            'avg_power_factor' => 0.85,
            'total_sample_count' => 1440,
            'data_source' => 'live',
            'tariff_rate_snapshot' => 1500.00,
        ]);
    }

    public function test_full_default_report()
    {
        $response = $this->actingAs($this->user)
            ->get(route('analytics.operational', [
                'start_date' => Carbon::now('Asia/Jakarta')->subDays(5)->toDateString(),
                'end_date' => Carbon::now('Asia/Jakarta')->toDateString(),
            ]));

        $response->assertStatus(200);

        $response->assertSee('Usage (kWh)');
        $response->assertSee('Peak Load (kW)');
        $response->assertSee('Avg Voltage (V)');
        $response->assertSee('Avg PF');
        $response->assertSee('Samples');
    }

    public function test_usage_only_report()
    {
        $response = $this->actingAs($this->user)
            ->get(route('analytics.operational', [
                'start_date' => Carbon::now('Asia/Jakarta')->subDays(5)->toDateString(),
                'end_date' => Carbon::now('Asia/Jakarta')->toDateString(),
                'metrics' => ['usage'],
            ]));

        $response->assertStatus(200);

        // Verify HTML headers for selected columns
        $response->assertSee('<th class="px-6 py-4 text-right">Usage (kWh)</th>', false);
        $response->assertDontSee('<th class="px-6 py-4 text-right">Peak Load (kW)</th>', false);
        $response->assertDontSee('<th class="px-6 py-4 text-right">Avg Voltage (V)</th>', false);
        $response->assertDontSee('<th class="px-6 py-4 text-right">Avg PF</th>', false);
        $response->assertDontSee('<th class="px-6 py-4 text-right">Samples</th>', false);
    }

    public function test_usage_and_peak_report()
    {
        $response = $this->actingAs($this->user)
            ->get(route('analytics.operational', [
                'start_date' => Carbon::now('Asia/Jakarta')->subDays(5)->toDateString(),
                'end_date' => Carbon::now('Asia/Jakarta')->toDateString(),
                'metrics' => ['usage', 'peak'],
            ]));

        $response->assertStatus(200);

        // Verify HTML headers for selected columns
        $response->assertSee('<th class="px-6 py-4 text-right">Usage (kWh)</th>', false);
        $response->assertSee('<th class="px-6 py-4 text-right">Peak Load (kW)</th>', false);
        $response->assertDontSee('<th class="px-6 py-4 text-right">Avg Voltage (V)</th>', false);
        $response->assertDontSee('<th class="px-6 py-4 text-right">Avg PF</th>', false);
        $response->assertDontSee('<th class="px-6 py-4 text-right">Samples</th>', false);
    }

    public function test_invalid_metric_is_ignored_or_rejected()
    {
        $response = $this->actingAs($this->user)
            ->from(route('analytics.operational'))
            ->get(route('analytics.operational', [
                'start_date' => Carbon::now('Asia/Jakarta')->subDays(5)->toDateString(),
                'end_date' => Carbon::now('Asia/Jakarta')->toDateString(),
                'metrics' => ['invalid_metric_name'],
            ]));

        $response->assertRedirect();
        $response->assertSessionHasErrors(['date_range']);
    }

    public function test_empty_metric_is_ignored_or_rejected()
    {
        $response = $this->actingAs($this->user)
            ->from(route('analytics.operational'))
            ->get(route('analytics.operational', [
                'start_date' => Carbon::now('Asia/Jakarta')->subDays(5)->toDateString(),
                'end_date' => Carbon::now('Asia/Jakarta')->toDateString(),
                'metrics' => [''],
            ]));

        $response->assertRedirect();
        $response->assertSessionHasErrors(['date_range']);
    }

    public function test_pdf_export_receives_correct_metrics_and_streams()
    {
        $response = $this->actingAs($this->user)
            ->get(route('analytics.operational.pdf', [
                'start_date' => Carbon::now('Asia/Jakarta')->subDays(5)->toDateString(),
                'end_date' => Carbon::now('Asia/Jakarta')->toDateString(),
                'metrics' => ['usage', 'peak'],
            ]));

        $response->assertStatus(200);
        $this->assertEquals('application/pdf', $response->headers->get('content-type'));
    }

    public function test_pdf_view_renders_correct_columns_and_summary()
    {
        $reports = PowerReadingDaily::where('device_id', $this->device->id)->get();

        $html1 = view('exports.operational_pdf', [
            'reports' => $reports,
            'startDate' => Carbon::now('Asia/Jakarta')->subDays(5)->toDateString(),
            'endDate' => Carbon::now('Asia/Jakarta')->toDateString(),
            'deviceName' => 'PM-TF01',
            'selectedMetrics' => ['usage', 'peak'],
        ])->render();

        $this->assertStringContainsString('Usage (kWh)', $html1);
        $this->assertStringContainsString('Peak Load (kW)', $html1);
        $this->assertStringNotContainsString('Avg Volt (V)', $html1);
        $this->assertStringNotContainsString('Avg PF', $html1);
        $this->assertStringNotContainsString('Samples', $html1);

        $this->assertStringContainsString('Total Energy:', $html1);
        $this->assertStringContainsString('Max Peak:', $html1);

        $html2 = view('exports.operational_pdf', [
            'reports' => $reports,
            'startDate' => Carbon::now('Asia/Jakarta')->subDays(5)->toDateString(),
            'endDate' => Carbon::now('Asia/Jakarta')->toDateString(),
            'deviceName' => 'PM-TF01',
            'selectedMetrics' => ['usage'],
        ])->render();

        $this->assertStringContainsString('Usage (kWh)', $html2);
        $this->assertStringNotContainsString('Peak Load (kW)', $html2);

        $this->assertStringContainsString('Total Energy:', $html2);
        $this->assertStringNotContainsString('Max Peak:', $html2);
    }
}
