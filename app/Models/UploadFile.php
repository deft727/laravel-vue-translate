<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UploadFile extends Model
{
    protected $table = 'upload_files';

    protected $fillable = [
        'document_id', 'file_type', 'file_name', 'file_size', 'target_lang', 'status'
    ];
}
