<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Tenant = 'tenant';
    case Pelanggan = 'pelanggan';
}
