<?php
declare(strict_types=1);

namespace YuvaClub\Delivery;

final class PresentationMediaManager
{
    public function __construct(private readonly string $uploadsRoot) {}

    public function delete(string $studentId,array $record): bool
    {
        $stored=(string)($record['stored_filename']??'');
        if($stored===''||basename($stored)!==$stored)return false;
        $safe=preg_replace('/[^A-Za-z0-9_-]/','_',$studentId)??'';
        $directory=realpath(rtrim($this->uploadsRoot,DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$safe.DIRECTORY_SEPARATOR.'media');
        $file=$directory===false?false:realpath($directory.DIRECTORY_SEPARATOR.$stored);
        if($directory===false||$file===false||!$this->contained($file,$directory)||!is_file($file))return false;
        return unlink($file);
    }

    private function contained(string $file,string $directory):bool
    {
        $file=str_replace('\\','/',$file);$directory=rtrim(str_replace('\\','/',$directory),'/').'/';
        if(DIRECTORY_SEPARATOR==='\\'){$file=strtolower($file);$directory=strtolower($directory);}
        return str_starts_with($file,$directory);
    }
}
