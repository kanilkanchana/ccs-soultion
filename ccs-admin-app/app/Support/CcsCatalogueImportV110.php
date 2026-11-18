<?php
/** Import public catalogue families only; never touches customer vehicles or stock. */
final class CcsCatalogueImportV110
{
    public static function apply(PDO $pdo, array $vehicles, bool $dryRun = false): array
    {
        $rows = $pdo->query("SELECT * FROM ccs_website_content WHERE kind='models'")->fetchAll(PDO::FETCH_ASSOC);
        $allSlugs = $pdo->query('SELECT slug FROM ccs_website_content')->fetchAll(PDO::FETCH_COLUMN);
        $slugs = array_fill_keys($allSlugs, true);
        $bySlug = []; $byIdentity = [];
        foreach ($rows as $row) {
            $data = json_decode($row['data'], true, 512, JSON_THROW_ON_ERROR);
            $bySlug[$row['slug']] = $row;
            $byIdentity[self::identity($data)] = $row;
        }
        $stats = ['inserted'=>0, 'enriched'=>0, 'preserved'=>0];
        $now = gmdate('Y-m-d H:i:s');
        foreach ($vehicles as $position => $vehicle) {
            $slug = 'catalogue-v110-'.$vehicle['id'];
            $original = 'original-model-'.$vehicle['id'];
            $existing = $bySlug[$slug] ?? $bySlug[$original] ?? $byIdentity[self::identity($vehicle)] ?? null;
            if ($existing) {
                // Keep all owner edits, disabled states, image URLs and core model fields.
                $old = json_decode($existing['data'], true, 512, JSON_THROW_ON_ERROR);
                $data = $old;
                foreach (['variants','photo','photoLabel','note','evidence','sources','page'] as $key) {
                    if (!array_key_exists($key, $data) && array_key_exists($key, $vehicle)) $data[$key] = $vehicle[$key];
                }
                if ($data === $old) { $stats['preserved']++; continue; }
                $stats['enriched']++;
                if (!$dryRun) {
                    $q=$pdo->prepare('UPDATE ccs_website_content SET data=?, revision=revision+1, updated_at=? WHERE id=? AND revision=?');
                    $q->execute([json_encode($data,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR),$now,$existing['id'],$existing['revision']]);
                    if ($q->rowCount()!==1) throw new RuntimeException('Catalogue changed during import. Retry.');
                }
                continue;
            }
            if (isset($slugs[$slug])) throw new RuntimeException('Catalogue slug collision: '.$slug);
            $stats['inserted']++;
            if (!$dryRun) {
                $data=$vehicle; unset($data['id']);
                $data['description']=$vehicle['note']??'';
                $q=$pdo->prepare('INSERT INTO ccs_website_content (kind,slug,title,active,sort_order,data,revision,updated_by,created_at,updated_at) VALUES (?,?,?,?,?,?,1,NULL,?,?)');
                $q->execute(['models',$slug,$vehicle['make'].' '.$vehicle['model'],1,min(9999,1000+$position),json_encode($data,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR),$now,$now]);
            }
            $slugs[$slug]=true;
        }
        return $stats;
    }
    private static function identity(array $data): string
    {
        return strtolower(trim($data['make']??'')).'|'.strtolower(trim($data['model']??'')).'|'.strtolower(trim($data['code']??''));
    }
}
