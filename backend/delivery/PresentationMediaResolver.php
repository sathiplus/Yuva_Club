<?php
declare(strict_types=1);

namespace YuvaClub\Delivery;

use RuntimeException;

final class PresentationMediaResolver
{
    public function __construct(private readonly string $uploadsRoot, private readonly MediaUploadValidator $validator=new MediaUploadValidator()){}
    public function resolve(string $studentId,array $record): PresentationMedia
    {
        $stored=(string)($record['stored_filename']??'');$original=(string)($record['original_filename']??'');
        if($stored===''||$original===''||basename($stored)!==$stored||basename($original)!==$original)throw new RuntimeException('invalid_media');
        $safe=preg_replace('/[^A-Za-z0-9_-]/','_',$studentId)??'';$directory=realpath(rtrim($this->uploadsRoot,DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$safe.DIRECTORY_SEPARATOR.'media');$file=$directory===false?false:realpath($directory.DIRECTORY_SEPARATOR.$stored);
        if($directory===false||$file===false||!$this->contained($file,$directory)||!is_file($file))throw new RuntimeException('invalid_media');
        $size=filesize($file);$mime=(new \finfo(FILEINFO_MIME_TYPE))->file($file);$prefix=file_get_contents($file,false,null,0,16);
        $valid=$this->validator->validate($original,is_int($size)?$size:0,UPLOAD_ERR_OK,is_string($mime)?$mime:'',is_string($prefix)?$prefix:'');if(!($valid['ok']??false))throw new RuntimeException((string)($valid['code']??'invalid_media'));
        $sha=hash_file('sha256',$file);if(!is_string($sha)||!hash_equals((string)($record['sha256']??''),$sha))throw new RuntimeException('source_changed');
        return new PresentationMedia($file,$safe.'/media/'.$stored,$original,strtolower((string)$mime),(int)$size,$sha);
    }
    private function contained(string $file,string $directory):bool{$f=str_replace('\\','/',$file);$d=rtrim(str_replace('\\','/',$directory),'/').'/';if(DIRECTORY_SEPARATOR==='\\'){$f=strtolower($f);$d=strtolower($d);}return str_starts_with($f,$d);}
}
