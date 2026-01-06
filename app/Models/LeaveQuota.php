<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaveQuota extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'year',
        'total',
        'used',
        'remaining',
    ];

    protected $casts = [
        'year' => 'integer',
        'total' => 'integer',
        'used' => 'integer',
        'remaining' => 'integer',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Helper methods
    public function canTakeLeave($days)
    {
        return $this->remaining >= $days;
    }

    public function deductQuota($days)
    {
        $this->used += $days;
        $this->remaining -= $days;
        $this->save();
    }

    public function restoreQuota($days)
    {
        $this->used -= $days;
        $this->remaining += $days;
        $this->save();
    }
}