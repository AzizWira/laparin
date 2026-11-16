<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['menu_item_id', 'name', 'unit_price', 'quantity'])]
class OrderItem extends Model
{
    public $timestamps = false;
}
