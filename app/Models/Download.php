<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'title', 'description', 'file_path', 'original_name', 'extension',
    'file_size', 'download_count', 'is_published', 'sort_order',
])]
class Download extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
            'download_count' => 'integer',
            'is_published' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function humanFileSize(): string
    {
        if ($this->file_size >= 1048576) {
            return number_format($this->file_size / 1048576, 2, ',', '.').' MB';
        }

        return number_format(max($this->file_size / 1024, 0.01), 2, ',', '.').' KB';
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }
}
