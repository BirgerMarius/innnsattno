<?php
namespace App\Services;
use Illuminate\Support\Facades\DB; use Illuminate\Support\Facades\Schema;
class SportsNextSeasonReportService {
 public function pending(): array { if(!Schema::hasTable('sportsnext_season_report_events')) return []; return DB::table('sportsnext_season_report_events')->whereNull('reported_at')->orderBy('created_at')->get()->map(fn($x)=>json_decode($x->payload,true)+['event_key'=>$x->event_key,'kind'=>$x->kind])->all(); }
 public function markReported(array $events): void { if($events) DB::table('sportsnext_season_report_events')->whereIn('event_key',array_column($events,'event_key'))->update(['reported_at'=>now(),'updated_at'=>now()]); }
 public function record(string $key,string $kind,array $payload): void { if(!Schema::hasTable('sportsnext_season_report_events'))return; $hash=sha1($kind.'|'.$key.'|'.json_encode($payload)); DB::table('sportsnext_season_report_events')->updateOrInsert(['event_key'=>$hash],['kind'=>$kind,'competition_key'=>$key,'payload'=>json_encode($payload),'updated_at'=>now(),'created_at'=>now()]); }
}
