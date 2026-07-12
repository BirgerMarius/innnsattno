<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddAdminNoteToFeedbackSubmissionsTable extends Migration
{
    public function up()
    {
        Schema::table('feedback_submissions', function (Blueprint $table) {
            if (! Schema::hasColumn('feedback_submissions', 'status')) {
                $table->string('status')->default('new')->after('email');
            }
        });

        Schema::table('feedback_submissions', function (Blueprint $table) {
            if (! Schema::hasColumn('feedback_submissions', 'admin_note')) {
                $table->text('admin_note')->nullable()->after('status');
            }
        });

        DB::table('feedback_submissions')
            ->whereNull('status')
            ->update(['status' => 'new']);
    }

    public function down()
    {
        Schema::table('feedback_submissions', function (Blueprint $table) {
            if (Schema::hasColumn('feedback_submissions', 'admin_note')) {
                $table->dropColumn('admin_note');
            }
        });
    }
}
