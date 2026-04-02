<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Location;
use App\Models\Machine;
use App\Models\Device;
use App\Models\PowerReading;
use App\Models\EnvironmentalReading;
use App\Services\EnergyCalculationService;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(UserSeeder::class);

        // 1. Create Locations (Departments / Areas)
        $factory = Location::firstOrCreate(['name' => 'Peroni Karya Sentra', 'description' => 'Main Manufacturing Plant']);
        
        // 2. Define the exact 22 Power Meters (which represent areas/machines)
        $meterList = [
            ['code' => 'PM-01', 'name' => 'BAHAN BAKU'],
            ['code' => 'PM-02', 'name' => 'BAHAN BAKU'],
            ['code' => 'PM-03', 'name' => 'COR PASIR'],
            ['code' => 'PM-04', 'name' => 'COR PASIR'],
            ['code' => 'PM-05', 'name' => 'COR PASIR'],
            ['code' => 'PM-06', 'name' => 'COR LOST WAX'],
            ['code' => 'PM-07', 'name' => 'NETTO FLANGE'],
            ['code' => 'PM-08', 'name' => 'NETTO FITTING'],
            ['code' => 'PM-09', 'name' => 'BUBUT FLANGE'],
            ['code' => 'PM-10', 'name' => 'BUBUT FITTING'],
            ['code' => 'PM-11', 'name' => 'BUBUT BESI'],
            ['code' => 'PM-12', 'name' => 'BOR FLANGE'],
            ['code' => 'PM-13', 'name' => 'FINISH FLANGE'],
            ['code' => 'PM-14', 'name' => 'GUDANG JADI FLANGE'],
            ['code' => 'PM-15', 'name' => 'GUDANG JADI FITTING'],
            ['code' => 'PM-16', 'name' => 'SPECTRO'],
            ['code' => 'PM-17', 'name' => 'HEAT TREATMENT'],
            ['code' => 'PM-18', 'name' => 'KIMIA FITTING'],
            ['code' => 'PM-19', 'name' => 'MAINTENANCE'],
            ['code' => 'PM-20', 'name' => 'TPS NON B3'],
            ['code' => 'PM-21', 'name' => 'MILLING'],
            ['code' => 'PM-22', 'name' => 'CETAK LOST WAX'],
        ];

        // 3. Create Machines (Areas) and Devices
        $devices = [];
        foreach ($meterList as $index => $item) {
            $machine = Machine::firstOrCreate([
                'code' => $item['code'],
            ], [
                'name' => $item['name'],
                'location_id' => $factory->id,
            ]);

            // Add the Modbus Power Meter device for this area
            $devices[] = Device::firstOrCreate([
                'machine_id' => $machine->id,
                'name' => "Meter " . $item['code'],
                'type' => 'power_meter'
            ], [
                'slave_id' => $index + 1,
                'communication_type' => 'RS485'
            ]);
        }

        // Add one environmental sensor
        $envSensor = Device::firstOrCreate([
            'name' => 'Factory Floor DHT Sensor',
            'type' => 'temperature_sensor',
            'machine_id' => null,
        ], [
            'slave_id' => 100,
            'communication_type' => 'RS485'
        ]);

        // 4. Generate 2 days of dummy data (10-minute intervals)
        $start = Carbon::now()->subDays(2)->startOfDay();
        $end = Carbon::now();

        $calcService = new EnergyCalculationService();

        echo "Generating 10-min interval data for 22 power meters...\n";

        // To speed up seeding, we will only generate mock data if it hasn't been generated
        if (PowerReading::count() < 100) {
            foreach ($devices as $device) {
                $current = $start->copy();
                $lastKwh = rand(10000, 50000) / 10; // Start at some random value on the meter
                
                $readings = [];
                while ($current <= $end) {
                    $usage = rand(10, 150) / 10; // slightly higher usage for departments
                    $lastKwh += $usage;

                    $readings[] = [
                        'device_id' => $device->id,
                        'kwh_total' => $lastKwh,
                        'power_kw' => $usage * 6, // avg KW during the 10 min window
                        'voltage' => 380 + (rand(-5, 5)),
                        'current' => rand(10, 100),
                        'power_factor' => rand(85, 99) / 100,
                        'recorded_at' => $current->toDateTimeString(),
                    ];

                    $current->addMinutes(10);
                }
                
                $chunks = array_chunk($readings, 500);
                foreach ($chunks as $chunk) {
                    PowerReading::insert($chunk);
                }
            }

            // Generate environment data
            $current = $start->copy();
            $envReadings = [];
            while ($current <= $end) {
                $envReadings[] = [
                    'device_id' => $envSensor->id,
                    'temperature' => rand(250, 350) / 10,
                    'humidity' => rand(400, 700) / 10,
                    'recorded_at' => $current->toDateTimeString(),
                ];
                $current->addMinutes(10);
            }
            foreach (array_chunk($envReadings, 500) as $chunk) {
                EnvironmentalReading::insert($chunk);
            }

            echo "Calculating daily rollups using the Service class...\n";
            $calcService->calculateDailyUsageForAll(Carbon::now()->subDays(2));
            $calcService->calculateDailyUsageForAll(Carbon::now()->subDays(1));
            $calcService->calculateDailyUsageForAll(Carbon::now());
        }
        
        echo "Seeding completed successfully!\n";
    }
}
