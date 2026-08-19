<?php
declare(strict_types=1);

namespace YuvaClub\Delivery;

use DateTimeImmutable;
use DateTimeZone;
use RuntimeException;

final class MediaRetentionService
{
    /**
     * @param callable(string):bool $validStudent
     * @param callable(string,string):bool $isProcessing
     * @param callable(array<string,mixed>):void $audit
     * @param null|callable(string):bool $deleteFile
     * @param null|callable():DateTimeImmutable $clock
     */
    public function __construct(
        private readonly string $metadataFile,
        private readonly string $uploadsRoot,
        private readonly mixed $validStudent,
        private readonly mixed $isProcessing,
        private readonly mixed $audit,
        private readonly mixed $deleteFile = null,
        private readonly mixed $clock = null,
    ) {}

    /** @return array<string,mixed> */
    public function run(?int $retentionDays, bool $dryRun, string $runId): array
    {
        $summary = ['run_id'=>$runId,'mode'=>$dryRun?'dry-run':'execute','retention_days'=>$retentionDays,'scanned'=>0,'eligible'=>0,'deleted'=>0,'skipped'=>0,'failed'=>0,'items'=>[]];
        if ($retentionDays === null || $retentionDays < 1) {
            $summary['status'] = 'disabled';
            return $summary;
        }

        $lockPath = $this->metadataFile . '.retention.lock';
        $lock = fopen($lockPath, 'c');
        if ($lock === false || !flock($lock, LOCK_EX | LOCK_NB)) {
            if (is_resource($lock)) fclose($lock);
            throw new RuntimeException('Retention run is already active.');
        }

        try {
            $records = $this->readRecords();
            foreach ($records as $yuvaId => $record) {
                $summary['scanned']++;
                $decision = $this->evaluate((string)$yuvaId, $record, $retentionDays);
                if (!$decision['eligible']) {
                    $summary['skipped']++;
                    continue;
                }
                $summary['eligible']++;
                $safe = $decision['safe'];
                $safe['run_id'] = $runId;
                if ($dryRun) {
                    $safe['result'] = 'would_delete';
                    $summary['items'][] = $safe;
                    continue;
                }

                $delete = $this->deleteFile ?? static fn(string $path): bool => unlink($path);
                if (!$delete($decision['path'])) {
                    $summary['failed']++;
                    $safe['result'] = 'failed';
                    $safe['failure_category'] = 'physical_delete_failed';
                    $summary['items'][] = $safe;
                    ($this->audit)($safe);
                    continue;
                }

                $deletedAt = $this->now()->format(DATE_ATOM);
                $records[$yuvaId]['retention_status'] = 'Deleted';
                $records[$yuvaId]['media_deleted_at'] = $deletedAt;
                $records[$yuvaId]['deletion_reason'] = 'retention_expired';
                $records[$yuvaId]['updated_at'] = $deletedAt;
                try {
                    $this->writeRecords($records);
                    $summary['deleted']++;
                    $safe['result'] = 'deleted';
                    $safe['deleted_at'] = $deletedAt;
                } catch (\Throwable) {
                    $summary['failed']++;
                    $safe['result'] = 'failed';
                    $safe['failure_category'] = 'metadata_update_failed_after_delete';
                }
                $summary['items'][] = $safe;
                ($this->audit)($safe);
            }
            $summary['status'] = $summary['failed'] === 0 ? 'ok' : 'partial';
            return $summary;
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    /** @return array{eligible:bool,path?:string,safe?:array<string,mixed>} */
    private function evaluate(string $yuvaId, mixed $record, int $days): array
    {
        if (!is_array($record) || ($record['retention_status'] ?? 'Active') !== 'Active') return ['eligible'=>false];
        if (!(($this->validStudent)($yuvaId))) return ['eligible'=>false];
        $stored = (string)($record['stored_filename'] ?? '');
        $sha = strtolower((string)($record['sha256'] ?? ''));
        if ($stored === '' || basename($stored) !== $stored || preg_match('/^[a-f0-9]{64}$/', $sha) !== 1) return ['eligible'=>false];
        $uploadedAt = $this->timestamp((string)($record['uploaded_at'] ?? $record['acknowledged_at'] ?? ''));
        if ($uploadedAt === null) return ['eligible'=>false];
        $ageSeconds = $this->now()->getTimestamp() - $uploadedAt->getTimestamp();
        if ($ageSeconds < $days * 86400) return ['eligible'=>false];
        $safeId = preg_replace('/[^A-Za-z0-9_-]/', '_', $yuvaId) ?? '';
        if ($safeId === '' || $safeId !== $yuvaId) return ['eligible'=>false];
        $directory = realpath(rtrim($this->uploadsRoot, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$safeId.DIRECTORY_SEPARATOR.'media');
        $path = $directory === false ? false : realpath($directory.DIRECTORY_SEPARATOR.$stored);
        if ($directory === false || $path === false || !$this->contained($path, $directory) || !is_file($path)) return ['eligible'=>false];
        $actualSha = hash_file('sha256', $path);
        if (!is_string($actualSha) || !hash_equals($sha, strtolower($actualSha))) return ['eligible'=>false];
        $reference = (string)($record['media_reference'] ?? ($safeId.'/media/'.$stored));
        if ($reference !== $safeId.'/media/'.$stored || ($this->isProcessing)($yuvaId, $reference)) return ['eligible'=>false];
        return ['eligible'=>true,'path'=>$path,'safe'=>[
            'run_id'=>'','yuva_id'=>$yuvaId,'media_reference'=>$reference,'sha256'=>$sha,
            'uploaded_at'=>$uploadedAt->format(DATE_ATOM),'retention_age_days'=>(int)floor($ageSeconds/86400),
        ]];
    }

    private function timestamp(string $value): ?DateTimeImmutable
    {
        if ($value === '') return null;
        try { return new DateTimeImmutable($value, new DateTimeZone('UTC')); }
        catch (\Throwable) { return null; }
    }

    private function now(): DateTimeImmutable
    {
        $now = $this->clock === null ? new DateTimeImmutable('now', new DateTimeZone('UTC')) : ($this->clock)();
        return $now->setTimezone(new DateTimeZone('UTC'));
    }

    /** @return array<string,mixed> */
    private function readRecords(): array
    {
        if (!is_file($this->metadataFile)) return [];
        $raw = file_get_contents($this->metadataFile);
        $data = is_string($raw) ? json_decode($raw, true) : null;
        if (!is_array($data)) throw new RuntimeException('Media metadata is unavailable.');
        return $data;
    }

    /** @param array<string,mixed> $records */
    private function writeRecords(array $records): void
    {
        $json = json_encode($records, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if (!is_string($json)) throw new RuntimeException('Media metadata encoding failed.');
        $temp = $this->metadataFile.'.'.bin2hex(random_bytes(8)).'.tmp';
        if (file_put_contents($temp, $json, LOCK_EX) === false || !rename($temp, $this->metadataFile)) {
            @unlink($temp);
            throw new RuntimeException('Media metadata update failed.');
        }
    }

    private function contained(string $file, string $directory): bool
    {
        $file=str_replace('\\','/',$file);$directory=rtrim(str_replace('\\','/',$directory),'/').'/';
        if(DIRECTORY_SEPARATOR==='\\'){$file=strtolower($file);$directory=strtolower($directory);}
        return str_starts_with($file,$directory);
    }
}
