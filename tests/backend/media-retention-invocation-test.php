<?php
declare(strict_types=1);
// This subprocess is permitted only in the disposable validation application.
$root=realpath(__DIR__.'/../..');
if (getenv('APP_ENV')!=='test' || realpath((string)getenv('YUVA_TEST_ISOLATED_ROOT'))!==$root) {
    throw new RuntimeException('Disposable validation root required.');
}
$paths=['portal-data','portal-uploads','submissions'];
$env=getenv();
foreach (array_keys($env) as $key) {
    if (str_starts_with($key,'DB_') || str_starts_with($key,'WEBSITE_') || str_starts_with($key,'YUVA_TEST_DB_')) unset($env[$key]);
}
$env['APP_ENV']='test';$env['DB_DRIVER']='mysql';$env['YUVA_MEDIA_RETENTION_DAYS']='30';
foreach (['YUVA_PORTAL_DATA_PATH','YUVA_PORTAL_UPLOADS_PATH','YUVA_SUBMISSIONS_PATH'] as $i=>$key) $env[$key]=$root.DIRECTORY_SEPARATOR.$paths[$i];
// Existing suites may populate disposable data; snapshot it to prove no deletion.
function invocationInventory(string $root): array {
    $files=[];
    foreach (['portal-data','portal-uploads','submissions'] as $directory) {
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root.'/'.$directory,FilesystemIterator::SKIP_DOTS)) as $file) {
            if ($file->isFile() && !str_ends_with($file->getPathname(),'.lock')) $files[$file->getPathname()]=hash_file('sha256',$file->getPathname());
        }
    }
    ksort($files);return $files;
}
// Bootstrap may initialize absent files, so warm up using the same dry-run first.
foreach ([0,1] as $attempt) {
    $before=invocationInventory($root);
    $process=proc_open([PHP_BINARY,$root.'/App_Data/jobs/triggered/yuva-media-retention/run.php','--dry-run'],[1=>['pipe','w'],2=>['pipe','w']],$pipes,$root,$env);
    if (!is_resource($process)) throw new RuntimeException('Job did not start');
    $out=stream_get_contents($pipes[1]);$err=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);$code=proc_close($process);
    $result=json_decode(trim($out),true);
    if ($code!==0 || ($result['mode']??'')!=='dry-run' || ($result['retention_days']??null)!==30 || ($result['deleted']??-1)!==0) throw new RuntimeException('Scheduled dry-run failed: '.$err);
    if ($attempt===1 && $before!==invocationInventory($root)) throw new RuntimeException('Dry-run changed existing media or metadata');
}
echo "PASS actual scheduled entry invokes authoritative runner in non-destructive 30-day mode\n";
