<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'interval',
        'amount',
        'status',
    ];

    /**
     * A plan can have many orders.
     */
    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}
