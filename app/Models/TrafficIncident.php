<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrafficIncident extends Model
{
    use HasFactory;

    // Add this array to allow Laravel to write data to these columns
    protected $fillable = [
        'incident_type',
        'description',
        'location',
        'latitude',
        'longitude',
        'occurred_at'
    ];
}