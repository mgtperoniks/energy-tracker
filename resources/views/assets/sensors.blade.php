@extends('layouts.app')

@section('content')
<main class="md:ml-64 pt-16 pb-20 md:pb-8 min-h-screen bg-surface">
    <div class="p-6 md:p-8 max-w-7xl mx-auto">
        <div class="mb-8">
            <h1 class="text-3xl font-extrabold tracking-tight text-on-surface">Environmental Sensors</h1>
            <p class="text-on-surface-variant text-sm mt-1">Manajemen sensor suhu, kelembaban, dan parameter lingkungan.</p>
        </div>

        <div class="bg-surface-container-lowest rounded-xl shadow-lg overflow-hidden border border-surface-container-low p-20 text-center">
            <span class="material-symbols-outlined text-outline/20 text-8xl mb-4">sensors</span>
            <h3 class="text-xl font-bold text-on-surface">Sensor Integration Pending</h3>
            <p class="text-outline text-sm mt-2">Dukungan untuk sensor lingkungan (DHT/Thermocouple) akan segera hadir.</p>
        </div>
    </div>
</main>
@endsection
