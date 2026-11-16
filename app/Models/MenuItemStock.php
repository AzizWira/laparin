<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['menu_item_id', 'stock_date', 'remaining'])]
class MenuItemStock extends Model {}
