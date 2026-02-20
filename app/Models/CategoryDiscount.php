<?php

namespace App\Models;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CategoryDiscount extends Model
{
    use HasFactory;

    protected $fillable = ['category_id', 'discount_percentage', 'is_active'];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
