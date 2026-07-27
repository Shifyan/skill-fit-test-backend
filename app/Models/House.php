<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class House extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'house_number',
        'house_status',
    ];

    public function histories(): HasMany
    {
        return $this->hasMany(HouseHistory::class)->orderBy('start_date', 'desc');
    }

    public function currentHistory(): HasOne
    {
        return $this->hasOne(HouseHistory::class)->whereNull('end_date')->latestOfMany();
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }
}
