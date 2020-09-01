<?php
namespace UfmcpBundle\Service;

use Symfony\Component\HttpFoundation\File\UploadedFile;

class FileUploader
{
    private $targetDir;

    public function __construct($targetDir)
    {
        $this->targetDir = $targetDir;
        if(!is_dir($this->targetDir)) {
            mkdir($this->targetDir, 0755, true);
        }
    }

    public function upload(UploadedFile $file, $fileName = '')
    {
        $fileName = (empty($fileName) ? md5(uniqid()) : $fileName).'.'.$file->guessExtension();

        $file->move($this->targetDir, $fileName);

        return $fileName;
    }
}