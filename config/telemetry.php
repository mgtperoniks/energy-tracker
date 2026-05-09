<?php

return [
    /**
     * Polling interval for telemetry devices in minutes.
     * Default: 10 minutes (144 readings per device per day)
     */
    'poll_interval_minutes' => 10,

    /**
     * Expected standby power baseline for machines in kW.
     * Anything above this during idle gaps is considered 'waste'.
     */
    'standby_baseline_kw' => 10,
];
