<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Storage;

class Resident extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'fullname',
        'ktp_image',
        'resident_status',
        'phone_number',
        'marriage_status',
    ];

    protected $appends = ['ktp_image_url'];

    public function getKtpImageUrlAttribute(): ?string
    {
        if (!$this->ktp_image) {
            return null;
        }

        if (str_starts_with($this->ktp_image, 'http://') || str_starts_with($this->ktp_image, 'https://')) {
            return $this->ktp_image;
        }

        return asset('storage/' . $this->ktp_image);
    }

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
