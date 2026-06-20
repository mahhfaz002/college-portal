<?php

namespace App\Http\Controllers;

use App\Models\Route as BusRoute;
use App\Models\TransportAssignment;
use App\Models\Vehicle;
use Illuminate\Http\Request;

class TransportationController extends Controller
{
    public function index()
    {
        $routes = BusRoute::all();
        $vehicles = Vehicle::all();

        return view('transport.index', compact('routes', 'vehicles'));
    }

    public function assignStudent(Request $request)
    {
        $data = $request->validate([
            'vehicle_id' => 'required|exists:vehicles,id',
            'route_id'   => 'nullable|exists:routes,id',
            'student_id' => 'required|exists:students,id',
        ]);

        $vehicle = Vehicle::findOrFail($data['vehicle_id']);

        // Check capacity
        $currentAssignments = TransportAssignment::where('vehicle_id', $vehicle->id)->count();
        if ($currentAssignments >= $vehicle->capacity) {
            return back()->with('error', 'Vehicle is at maximum capacity.');
        }

        TransportAssignment::create($data);

        return back()->with('success', 'Student assigned to route successfully.');
    }
}
