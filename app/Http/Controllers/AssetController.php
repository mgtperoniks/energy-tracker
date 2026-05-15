<?php

namespace App\Http\Controllers;

use App\Models\Machine;
use App\Models\Device;
use App\Models\Department;
use Illuminate\Http\Request;

class AssetController extends Controller
{
    public function machines()
    {
        $machines = Machine::with('location')->orderBy('code')->get();
        return view('assets.machines', compact('machines'));
    }

    public function updateMachine(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:machines,id',
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50',
        ]);

        $machine = Machine::findOrFail($request->id);
        $machine->update([
            'name' => $request->name,
            'code' => $request->code,
        ]);

        return back()->with('success', "Machine {$machine->code} configuration updated.");
    }

    public function devices()
    {
        $devices = Device::with('machine')->orderBy('slave_id')->get();
        return view('assets.devices', compact('devices'));
    }

    public function departments()
    {
        // Department mapping might be handled via locations or a dedicated model
        // Assuming Department model exists based on route
        $departments = class_exists(Department::class) ? Department::all() : collect();
        return view('assets.departments', compact('departments'));
    }

    public function sensors()
    {
        return view('assets.sensors');
    }
}
