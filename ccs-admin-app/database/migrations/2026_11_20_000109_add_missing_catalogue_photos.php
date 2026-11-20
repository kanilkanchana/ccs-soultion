<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $images = [
            'catalogue-v110-toyota-prius-phv-prime' => 'https://www.tochigitoyota.com/file/cms/-1/9496/zvw52-ahxgb_0_30.png',
            'catalogue-v110-toyota-probox-hybrid' => 'https://global.toyota/pages/news/images/2026/09/04/1330/20260904_01_ogp.jpg',
            'catalogue-v110-toyota-corolla-sedan-hybrid' => 'https://fastly.clutch.ca/stc-photos/27ae2a6d-1808-488f-b45f-0bb0a366f1d3.png?class=small',
        ];

        DB::transaction(function () use ($images): void {
            foreach ($images as $slug => $url) {
                $row = DB::table('ccs_website_content')
                    ->where('kind', 'models')
                    ->where('slug', $slug)
                    ->first(['id', 'data', 'revision']);

                if (!$row) {
                    continue;
                }

                $data = json_decode($row->data, true, 512, JSON_THROW_ON_ERROR);
                $changed = false;
                foreach (['image', 'full_image'] as $key) {
                    if (!isset($data[$key]) || trim((string) $data[$key]) === '') {
                        $data[$key] = $url;
                        $changed = true;
                    }
                }

                if (!$changed) {
                    continue;
                }

                $updated = DB::table('ccs_website_content')
                    ->where('id', $row->id)
                    ->where('revision', $row->revision)
                    ->update([
                        'data' => json_encode($data, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                        'revision' => (int) $row->revision + 1,
                        'updated_at' => now(),
                    ]);

                if ($updated !== 1) {
                    throw new RuntimeException("Could not safely update catalogue image for {$slug}.");
                }
            }
        });
    }

    public function down(): void
    {
        // Keep images and dashboard edits in place on rollback.
    }
};
