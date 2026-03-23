<?php

use App\Support\DocumentSlideContent;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::transaction(function (): void {
            DB::table('documents')
                ->select(['id', 'content'])
                ->orderBy('id')
                ->chunkById(100, function ($documents): void {
                    foreach ($documents as $document) {
                        $bodies = DocumentSlideContent::extractSlideBodies((string) $document->content);

                        foreach ($bodies as $index => $body) {
                            DB::table('slides')->insert([
                                'document_id' => (int) $document->id,
                                'sort_order' => $index + 1,
                                'content' => $body,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                        }
                    }
                }, column: 'id');
        });

        if (Schema::hasColumn('documents', 'content')) {
            Schema::table('documents', function ($table): void {
                $table->dropColumn('content');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasColumn('documents', 'content')) {
            Schema::table('documents', function ($table): void {
                $table->longText('content')->nullable()->after('description');
            });
        }

        DB::transaction(function (): void {
            DB::table('documents')
                ->select(['id'])
                ->orderBy('id')
                ->chunkById(100, function ($documents): void {
                    foreach ($documents as $document) {
                        $slideBodies = DB::table('slides')
                            ->where('document_id', (int) $document->id)
                            ->orderBy('sort_order')
                            ->pluck('content')
                            ->all();

                        DB::table('documents')
                            ->where('id', (int) $document->id)
                            ->update([
                                'content' => DocumentSlideContent::buildDeckMarkup($slideBodies),
                                'updated_at' => now(),
                            ]);
                    }
                }, column: 'id');

            DB::table('slides')->delete();
        });
    }
};
