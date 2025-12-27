<?php

namespace App\Services;

use Symfony\Component\HttpFoundation\File\UploadedFile;

class UploadFileService
{
    private $targetDirectory;

    public function __construct(string $targetDirectory)
    {
        $this->targetDirectory = $targetDirectory;
    }

    public function upload(UploadedFile $file)
    {
        $newFilename = uniqid() . '.' . $file->guessExtension();
        $file->move($this->targetDirectory, $newFilename);
        return $newFilename;
    }

    public function deleteFile(string $img) {
        $filepath = $this->targetDirectory . '/' . $img;
        if (file_exists($filepath)) {
            unlink($filepath);
        }
    }
}