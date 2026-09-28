<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ErrorLog extends Model
{
    public const USER_TYPE_DOCTOR = 'doctor';
    public const USER_TYPE_MEDICINE_ORGANIZATION = 'medicine_organization';
    public const USER_TYPE_MASTER_ADMIN = 'master_admin';
    public const USER_TYPE_GUEST = 'guest';

    public const PLATFORMS = ['android', 'ios', 'web', 'server'];
    public const SEVERITIES = ['low', 'medium', 'high', 'critical'];
    public const STATUSES = ['open', 'in_progress', 'resolved', 'ignored'];

    // Lives on the default (master) connection; the doctor tenant
    // middleware only swaps the separate `doctor` connection.

    protected $fillable = [
        'user_type',
        'login_user_id',
        'name',
        'platform',
        'screen_name',
        'function_name',
        'error_description',
        'error_code',
        'exception_class',
        'stack_trace',
        'severity',
        'api_endpoint',
        'http_method',
        'http_status',
        'request_payload',
        'app_version',
        'device_info',
        'ip_address',
        'user_agent',
        'status',
        'resolved_by',
        'resolved_at',
        'resolution_note',
    ];

    protected function casts(): array
    {
        return [
            'login_user_id' => 'integer',
            'http_status' => 'integer',
            'request_payload' => 'array',
            'resolved_at' => 'datetime',
        ];
    }

    public function resolver()
    {
        return $this->belongsTo(MasterAdmin::class, 'resolved_by');
    }
}
