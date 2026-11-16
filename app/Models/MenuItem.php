<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['name', 'slug', 'description', 'price', 'photo_path'])]
class MenuItem extends Model
{
    use SoftDeletes;

    /** @return BelongsTo<Tenant, $this> */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /** @return HasOne<MenuItemStock, $this> */
    public function todayStock(): HasOne
    {
        return $this->hasOne(MenuItemStock::class)->where('stock_date', self::stockDate());
    }

    public static function stockDate(): string
    {
        return today('Asia/Jakarta')->toDateString();
    }
}
