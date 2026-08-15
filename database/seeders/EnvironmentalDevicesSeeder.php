<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Device;
use Illuminate\Support\Str;

class EnvironmentalDevicesSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Provision Device 23 (SHT40 Lab)
        $d23 = Device::find(23);
        if ($d23) {
            // Update existing record
            $d23->name = 'SHT40 Lab';
            $d23->type = 'temperature_sensor';
            $d23->slave_id = 1;
            $d23->communication_type = 'TCP';
            $d23->status = 1;
            if (empty($d23->api_token)) {
                $d23->api_token = Str::random(60);
            }
            $d23->save();
        } else {
            // Create new record with ID 23
            $d23 = new Device();
            $d23->id = 23;
            $d23->name = 'SHT40 Lab';
            $d23->type = 'temperature_sensor';
            $d23->slave_id = 1;
            $d23->communication_type = 'TCP';
            $d23->status = 1;
            $d23->api_token = Str::random(60);
            $d23->save();
        }

        // 2. Provision Device 28 (SHT40 Field)
        $d28 = Device::find(28);
        if ($d28) {
            // Update existing record
            $d28->name = 'SHT40 Field';
            $d28->type = 'temperature_sensor';
            $d28->slave_id = 1;
            $d28->communication_type = 'TCP';
            $d28->status = 1;
            if (empty($d28->api_token)) {
                $d28->api_token = Str::random(60);
            }
            $d28->save();
        } else {
            // Create new record with ID 28
            $d28 = new Device();
            $d28->id = 28;
            $d28->name = 'SHT40 Field';
            $d28->type = 'temperature_sensor';
            $d28->slave_id = 1;
            $d28->communication_type = 'TCP';
            $d28->status = 1;
            $d28->api_token = Str::random(60);
            $d28->save();
        }

        // 3. Provision Device 29 (SHT40 Lab 2)
        $d29 = Device::find(29);
        if ($d29) {
            // Update existing record
            $d29->name = 'SHT40 Lab 2';
            $d29->type = 'temperature_sensor';
            $d29->slave_id = 53;
            $d29->communication_type = 'TCP';
            $d29->status = 1;
            if (empty($d29->api_token)) {
                $d29->api_token = Str::random(60);
            }
            $d29->save();
        } else {
            // Create new record with ID 29
            $d29 = new Device();
            $d29->id = 29;
            $d29->name = 'SHT40 Lab 2';
            $d29->type = 'temperature_sensor';
            $d29->slave_id = 53;
            $d29->communication_type = 'TCP';
            $d29->status = 1;
            $d29->api_token = Str::random(60);
            $d29->save();
        }
    }
}
