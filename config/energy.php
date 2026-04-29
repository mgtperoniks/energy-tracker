<?php

return [
    'auto_reset_max_new_raw' => env('ENERGY_AUTO_RESET_MAX_NEW_RAW', 100),
    
    // Low Voltage Operation Anomaly Settings
    'low_voltage_threshold'  => env('ENERGY_LOW_VOLTAGE_THRESHOLD', 20),
    'low_voltage_duration'   => env('ENERGY_LOW_VOLTAGE_DURATION', 5),
    'min_operating_kw'       => env('ENERGY_MIN_OPERATING_KW', 1),
    
    // Idle Consumption Detector Settings
    'idle_power_threshold'   => env('ENERGY_IDLE_POWER_THRESHOLD', 2),
    'idle_duration'          => env('ENERGY_IDLE_DURATION', 10),
    'operational_start'      => env('ENERGY_OPERATIONAL_START', '08:00'),
    'operational_end'        => env('ENERGY_OPERATIONAL_END', '17:00'),
    
    // Carbon dayOfWeek: 0 (Sunday), 1 (Monday), ... 6 (Saturday)
    'non_operational_days'   => [0], // Default: Hari Minggu libur
];
