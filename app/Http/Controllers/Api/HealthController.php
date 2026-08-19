<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;use Illuminate\Support\Facades\DB;use Throwable;
class HealthController extends Controller {public function __invoke(){try{DB::select('select 1');return response()->json(['status'=>'ok','database'=>'ok','timestamp'=>now()->toIso8601String()]);}catch(Throwable $e){report($e);return response()->json(['status'=>'unhealthy','database'=>'failed','timestamp'=>now()->toIso8601String()],503);}}}
