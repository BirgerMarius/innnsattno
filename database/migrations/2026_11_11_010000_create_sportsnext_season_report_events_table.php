<?php
use Illuminate\Database\Migrations\Migration; use Illuminate\Database\Schema\Blueprint; use Illuminate\Support\Facades\Schema;
class CreateSportsnextSeasonReportEventsTable extends Migration { public function up(){ Schema::create('sportsnext_season_report_events',function(Blueprint $t){$t->id();$t->string('event_key',190)->unique();$t->string('kind',30);$t->string('competition_key',80);$t->text('payload');$t->timestamp('reported_at')->nullable();$t->timestamps();}); } public function down(){Schema::dropIfExists('sportsnext_season_report_events');} }
