<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;

class Sample extends Model
{
    use Searchable;
    protected $table = 'sample';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'title', 'content', 'thumb_url', 'tags', 'image_urls', 'created_at', 'updated_at'
    ];

    public function searchableAs()
    {
        return 'text_index';
    }
}
