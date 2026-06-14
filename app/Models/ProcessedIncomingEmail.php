<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProcessedIncomingEmail extends Model
{
    public const CREATED_AT = 'processed_at';

    public const UPDATED_AT = null;

    protected $fillable = [
        'message_id',
    ];
}
