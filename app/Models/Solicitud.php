<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Enums\EstadosSolicitud;

class Solicitud extends Model
{
    /** @use HasFactory<\Database\Factories\SolicitudFactory> */
    use HasFactory;
    use SoftDeletes;
    protected $table    = 'requests';
    protected $fillable = [
        'title',
        'description',
        'status',
        'llm_summary',
    ];
    protected $casts = [
        'status' => EstadosSolicitud::class,
    ];
    protected $hidden = ['created_at','updated_at','deleted_at'];
}
