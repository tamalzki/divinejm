<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PackerSessionLog extends Model
{
    protected $fillable = [
        'packer_report_id',
        'snapshot',
        'saved_by',
        'notes',
    ];

    protected $casts = [
        'snapshot' => 'array',
    ];

    public function packerReport()
    {
        return $this->belongsTo(PackerReport::class);
    }

    public function savedBy()
    {
        return $this->belongsTo(User::class, 'saved_by');
    }
}
