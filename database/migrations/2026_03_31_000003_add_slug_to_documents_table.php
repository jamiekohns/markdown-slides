<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->string('slug')->nullable()->after('title');
        });

        $documents = DB::table('documents')
            ->select(['id', 'title'])
            ->orderBy('id')
            ->get();

        foreach ($documents as $document) {
            $baseSlug = Str::slug((string) $document->title);

            if ($baseSlug === '') {
                $baseSlug = 'presentation';
            }

            $slug = $baseSlug;
            $counter = 2;

            while (DB::table('documents')->where('slug', $slug)->where('id', '!=', $document->id)->exists()) {
                $slug = "{$baseSlug}-{$counter}";
                ++$counter;
            }

            DB::table('documents')
                ->where('id', $document->id)
                ->update(['slug' => $slug]);
        }

        Schema::table('documents', function (Blueprint $table) {
            $table->unique('slug');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropColumn('slug');
        });
    }
};
