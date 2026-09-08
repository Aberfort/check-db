<?php

namespace App\Domain\DbAudit\Services;

use App\Domain\DbAudit\Contracts\DbInputPreparer;
use App\Models\Analysis;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ZipArchive;

class DbInputPreparerService implements DbInputPreparer
{
    public function prepare(Analysis $analysis, string $uploadedPath, string $originalName): array
    {
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        if (in_array($ext, ['db', 'sqlite', 'sqlite3'], true)) {
            return ['path' => $uploadedPath, 'cleanup_paths' => []];
        }

        if ($ext === 'zip') {
            return $this->prepareZip($analysis, $uploadedPath);
        }

        if ($ext === 'sql') {
            return $this->prepareSql($analysis, $uploadedPath);
        }

        throw new \RuntimeException('Непідтримуваний формат файлу.');
    }

    private function prepareZip(Analysis $analysis, string $uploadedPath): array
    {
        $disk = Storage::disk('local');

        $zipAbs = $disk->path($uploadedPath);

        $tmpDirRel = 'uploads/analyses_tmp/'.$analysis->id.'_'.Str::random(8);
        $tmpDirAbs = $disk->path($tmpDirRel);

        if (! is_dir($tmpDirAbs) && ! mkdir($tmpDirAbs, 0775, true) && ! is_dir($tmpDirAbs)) {
            throw new \RuntimeException('Не вдалося створити тимчасову директорію.');
        }

        $zip = new ZipArchive;
        if ($zip->open($zipAbs) !== true) {
            throw new \RuntimeException('Не вдалося відкрити ZIP архів.');
        }

        if (! $zip->extractTo($tmpDirAbs)) {
            $zip->close();
            throw new \RuntimeException('Не вдалося розпакувати ZIP архів.');
        }
        $zip->close();

        $candidates = $this->findDbCandidates($tmpDirAbs);

        if (! $candidates) {
            throw new \RuntimeException('У ZIP не знайдено .db/.sqlite/.sqlite3 файлів.');
        }

        usort($candidates, fn ($a, $b) => filesize($b) <=> filesize($a));
        $pickedAbs = $candidates[0];

        $targetRel = 'uploads/analyses/'.$analysis->id.'/extracted.db';
        $targetAbs = $disk->path($targetRel);

        if (! is_dir(dirname($targetAbs))) {
            mkdir(dirname($targetAbs), 0775, true);
        }

        if (! copy($pickedAbs, $targetAbs)) {
            throw new \RuntimeException('Не вдалося зберегти витягнуту базу.');
        }

        return [
            'path' => $targetRel,
            'cleanup_paths' => [$tmpDirRel],
        ];
    }

    /** @return string[] абсолютні шляхи */
    private function findDbCandidates(string $dirAbs): array
    {
        $out = [];
        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dirAbs));

        foreach ($it as $file) {
            if (! $file->isFile()) {
                continue;
            }
            $ext = strtolower($file->getExtension());
            if (in_array($ext, ['db', 'sqlite', 'sqlite3'], true)) {
                $out[] = $file->getPathname();
            }
        }

        return $out;
    }

    private function prepareSql(Analysis $analysis, string $uploadedPath): array
    {
        $disk = Storage::disk('local');

        $sqlAbs = $disk->path($uploadedPath);

        // цільова db
        $targetRel = 'uploads/analyses/'.$analysis->id.'/imported.db';
        $targetAbs = $disk->path($targetRel);

        if (! is_dir(dirname($targetAbs))) {
            mkdir(dirname($targetAbs), 0775, true);
        }

        // створюємо порожню sqlite (файл буде створено при підключенні)
        $pdo = new \PDO('sqlite:'.$targetAbs);
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $pdo->exec('PRAGMA foreign_keys = OFF;');
        $pdo->exec('PRAGMA journal_mode = WAL;');
        $pdo->exec('PRAGMA synchronous = NORMAL;');

        $pdo->beginTransaction();

        $count = 0;
        foreach ($this->iterateStatements($sqlAbs) as $stmt) {
            $stmt = $this->normalizeStatement($stmt);
            if ($stmt === '') {
                continue;
            }

            // SQLite не любить деякі mysql-специфіки
            // Ми “мʼяко” пропускаємо те, що не виконується
            try {
                $pdo->exec($stmt);
            } catch (\Throwable $e) {
                // пропускаємо типові непотрібні штуки дампа
                // але якщо це CREATE/INSERT і воно валиться — краще знати
                $upper = strtoupper($stmt);
                $isImportant = str_starts_with($upper, 'CREATE') || str_starts_with($upper, 'INSERT') || str_starts_with($upper, 'ALTER');

                if ($isImportant) {
                    $pdo->rollBack();
                    throw new \RuntimeException('SQL імпорт: помилка виконання statement: '.$e->getMessage());
                }
                // інше мовчки ігноруємо
            }

            $count++;
            if ($count % 200 === 0) {
                $pdo->commit();
                $pdo->beginTransaction();
            }
        }

        $pdo->commit();
        $pdo = null;

        return [
            'path' => $targetRel,
            'cleanup_paths' => [],
        ];
    }

    /**
     * Стрімово читає sql файл, повертає statements (розділення по ';' з урахуванням рядків).
     *
     * @return \Generator<int, string>
     */
    private function iterateStatements(string $sqlAbs): \Generator
    {
        $fh = fopen($sqlAbs, 'rb');
        if (! $fh) {
            throw new \RuntimeException('Не вдалося відкрити .sql файл.');
        }

        $buffer = '';
        $inSingle = false;
        $inDouble = false;
        $inBacktick = false;
        $inLineComment = false;
        $inBlockComment = false;
        $prev = '';

        while (! feof($fh)) {
            $chunk = fread($fh, 1024 * 1024); // 1MB
            if ($chunk === false) {
                break;
            }

            $len = strlen($chunk);
            for ($i = 0; $i < $len; $i++) {
                $ch = $chunk[$i];
                $next = $i + 1 < $len ? $chunk[$i + 1] : '';

                // обробка коментарів
                if ($inLineComment) {
                    if ($ch === "\n") {
                        $inLineComment = false;
                    }
                    $prev = $ch;

                    continue;
                }
                if ($inBlockComment) {
                    if ($prev === '*' && $ch === '/') {
                        $inBlockComment = false;
                    }
                    $prev = $ch;

                    continue;
                }

                // старт коментарів (лише якщо не в рядку)
                if (! $inSingle && ! $inDouble && ! $inBacktick) {
                    if ($ch === '-' && $next === '-') { // -- comment
                        $inLineComment = true;
                        $i++; // пропустити наступний '-'
                        $prev = '';

                        continue;
                    }
                    if ($ch === '#') { // # comment
                        $inLineComment = true;
                        $prev = $ch;

                        continue;
                    }
                    if ($ch === '/' && $next === '*') { // /* comment */
                        $inBlockComment = true;
                        $i++; // пропустити '*'
                        $prev = '';

                        continue;
                    }
                }

                // трекінг рядкових літералів
                if ($ch === "'" && ! $inDouble && ! $inBacktick) {
                    if ($prev !== '\\') {
                        $inSingle = ! $inSingle;
                    }
                } elseif ($ch === '"' && ! $inSingle && ! $inBacktick) {
                    if ($prev !== '\\') {
                        $inDouble = ! $inDouble;
                    }
                } elseif ($ch === '`' && ! $inSingle && ! $inDouble) {
                    $inBacktick = ! $inBacktick;
                }

                // кінець statement
                if ($ch === ';' && ! $inSingle && ! $inDouble && ! $inBacktick) {
                    $stmt = trim($buffer);
                    $buffer = '';
                    if ($stmt !== '') {
                        yield $stmt;
                    }
                    $prev = '';

                    continue;
                }

                $buffer .= $ch;
                $prev = $ch;
            }
        }

        fclose($fh);

        $tail = trim($buffer);
        if ($tail !== '') {
            yield $tail;
        }
    }

    /**
     * Нормалізація MySQL dump -> більш дружньо для SQLite (MVP).
     */
    private function normalizeStatement(string $stmt): string
    {
        $s = trim($stmt);
        if ($s === '') {
            return '';
        }

        // прибрати SET/LOCK/UNLOCK/DELIMITER/START TRANSACTION з дампів
        $upper = strtoupper($s);
        $skipPrefixes = [
            'SET ',
            'LOCK TABLES',
            'UNLOCK TABLES',
            'DELIMITER ',
            'START TRANSACTION',
            'COMMIT',
            'ROLLBACK',
        ];
        foreach ($skipPrefixes as $p) {
            if (str_starts_with($upper, $p)) {
                return '';
            }
        }

        // MySQL backticks -> SQLite double quotes
        $s = str_replace('`', '"', $s);

        // ENGINE=..., DEFAULT CHARSET=..., COLLATE=... (у CREATE TABLE)
        $s = preg_replace('/\)\s*ENGINE=.*$/i', ')', $s) ?? $s;
        $s = preg_replace('/DEFAULT\s+CHARSET\s*=\s*\w+/i', '', $s) ?? $s;
        $s = preg_replace('/COLLATE\s*=\s*\w+/i', '', $s) ?? $s;

        // AUTO_INCREMENT
        $s = preg_replace('/AUTO_INCREMENT\s*=\s*\d+/i', '', $s) ?? $s;

        // UNSIGNED, ZEROFILL, COMMENT, ON UPDATE ...
        $s = preg_replace('/\bUNSIGNED\b/i', '', $s) ?? $s;
        $s = preg_replace('/\bZEROFILL\b/i', '', $s) ?? $s;
        $s = preg_replace('/COMMENT\s+\'[^\']*\'/i', '', $s) ?? $s;
        $s = preg_replace('/ON\s+UPDATE\s+CURRENT_TIMESTAMP/i', '', $s) ?? $s;

        // типи (дуже грубо, але працює)
        $s = preg_replace('/\bTINYINT\(\d+\)\b/i', 'INTEGER', $s) ?? $s;
        $s = preg_replace('/\bINT\(\d+\)\b/i', 'INTEGER', $s) ?? $s;
        $s = preg_replace('/\bBIGINT\(\d+\)\b/i', 'INTEGER', $s) ?? $s;
        $s = preg_replace('/\bDOUBLE\b/i', 'REAL', $s) ?? $s;
        $s = preg_replace('/\bFLOAT\b/i', 'REAL', $s) ?? $s;
        $s = preg_replace('/\bDECIMAL\(\d+,\d+\)\b/i', 'REAL', $s) ?? $s;
        $s = preg_replace('/\bDATETIME\b/i', 'TEXT', $s) ?? $s;
        $s = preg_replace('/\bTIMESTAMP\b/i', 'TEXT', $s) ?? $s;

        // INDEX/KEY у CREATE TABLE — SQLite це не любить у такому вигляді
        // залишимо тільки PRIMARY KEY
        if (str_starts_with(strtoupper(trim($s)), 'CREATE TABLE')) {
            // видалити рядки типу: KEY "idx" ("col"), UNIQUE KEY ...
            $s = preg_replace('/,\s*(UNIQUE\s+)?KEY\s+"[^"]+"\s*\([^\)]*\)\s*/i', '', $s) ?? $s;
        }

        return trim($s);
    }
}
