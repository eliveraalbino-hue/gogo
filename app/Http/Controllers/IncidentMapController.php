<?php

namespace App\Http\Controllers;

use App\Models\TrafficIncident;
use Illuminate\Http\Request;

class IncidentMapController extends Controller 
{
    /**
     * Renders the initial dashboard page view with clean grouped categories
     */
    public function index() 
    {
        $types = [
            'Vehicular Accident',
            'Stalled Vehicle',
            'Roadwork & Obstruction',
            'Other'
        ];

        return view('map', compact('types'));
    }

    /**
     * API Endpoint serving map marker datasets
     */
    public function getIncidents(Request $request) 
    {
        $query = TrafficIncident::query();

        // Safe fuzzy clean matching rules
        if ($request->filled('type')) {
            $query->where('incident_type', 'LIKE', '%' . trim($request->type) . '%');
        }

        return response()->json(
            $query->select([
                'incident_type', 
                'description', 
                'location', 
                'latitude', 
                'longitude', 
                'occurred_at'
            ])->get()
        );
    }
}