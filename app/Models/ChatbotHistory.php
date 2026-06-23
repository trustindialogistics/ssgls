<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatbotHistory extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'messages'];
    protected $casts = ['messages' => 'array'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
