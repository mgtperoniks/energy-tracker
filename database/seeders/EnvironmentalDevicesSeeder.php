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

        // 4. Provision Device 30 (SHT40 Lab 3)
        $d30 = Device::find(30);
        if ($d30) {
            // Update existing record
            $d30->name = 'SHT40 Lab 3';
            $d30->type = 'temperature_sensor';
            $d30->slave_id = 54;
            $d30->communication_type = 'TCP';
            $d30->status = 1;
            if (empty($d30->api_token)) {
                $d30->api_token = Str::random(60);
            }
            $d30->save();
        } else {
            // Create new record with ID 30
            $d30 = new Device();
            $d30->id = 30;
            $d30->name = 'SHT40 Lab 3';
            $d30->type = 'temperature_sensor';
            $d30->slave_id = 54;
            $d30->communication_type = 'TCP';
            $d30->status = 1;
            $d30->api_token = Str::random(60);
            $d30->save();
        }

        // 5. Provision Device 31 (SHT40 Lab 4)
        $d31 = Device::find(31);
        if ($d31) {
            // Update existing record
            $d31->name = 'SHT40 Lab 4';
            $d31->type = 'temperature_sensor';
            $d31->slave_id = 55;
            $d31->communication_type = 'TCP';
            $d31->status = 1;
            if (empty($d31->api_token)) {
                $d31->api_token = Str::random(60);
            }
            $d31->save();
        } else {
            // Create new record with ID 31
            $d31 = new Device();
            $d31->id = 31;
            $d31->name = 'SHT40 Lab 4';
            $d31->type = 'temperature_sensor';
            $d31->slave_id = 55;
            $d31->communication_type = 'TCP';
            $d31->status = 1;
            $d31->api_token = Str::random(60);
            $d31->save();
        }
    }
}
