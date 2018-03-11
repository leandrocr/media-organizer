<?php

namespace LeandroCR\MediaOrganizer;

use FFMpeg;

class MediaOrganizer
{
    private $dryRun = true;
    private $movieSupport = true;
    private $ffprobe = null;

    public function __construct()
    {
        @$photosDir = $_SERVER['argv']['1'];
        @$destinationDir = $_SERVER['argv']['2'];

        if(substr($photosDir, -1) != '/') {
            $photosDir = $photosDir . '/';
        }

        if(substr($destinationDir, -1) != '/') {
            $destinationDir = $destinationDir . '/';
        }

        $this->ffprobe = FFMpeg\FFProbe::create();

        if (empty($photosDir) || empty($destinationDir)) {
            throw new \Exception("Mandatory arguments not set");
        }

        $files = scandir($photosDir);
        foreach ($files as $file) {
            if ($file == '.' || $file == '..') {
                continue;
            }
            $originalFilePath = "$photosDir$file";
            if (is_dir($originalFilePath)) {
                continue;
            }
            $originalFileExtension = strtolower(pathinfo($originalFilePath, PATHINFO_EXTENSION));

            $subDir = $this->getSubDir(
                $this->isPicture($originalFileExtension),
                $this->isMovie($originalFileExtension),
                $originalFilePath
            );

            $newDir = "$destinationDir$subDir";
            $file = $this->getFileNameAsSha($photosDir, $file);
            $newFile = $newDir . "/$file";
            echo "Moving $originalFilePath to $newFile" . PHP_EOL;

            if (false === $this->dryRun) {
                @mkdir($newDir);
                rename($originalFilePath, $newFile);
            }
        }
    }

    private function getSubDir(bool $isPicture, bool $isMovie, string $originalFilePath)
    {
        $subDir = 'other';

        if ($isPicture) {
            $photoDate = $this->getPhotoDate($originalFilePath);
            return $photoDate[0] . $photoDate[1];
        }

        if ($isMovie && $this->movieSupport) {
            $movieDate = $this->getMovieDate($originalFilePath);
            return $movieDate[0] . $movieDate[1];
        }

        return $subDir;
    }

    private function getFileNameAsSha(string $photosDir, string $file)
    {
        $oldFile = "$photosDir$file";
        $extension = strtolower(pathinfo($oldFile, PATHINFO_EXTENSION));
        $hash = hash_file('sha256', $oldFile);
        $newFile = "$hash.$extension";
        return $newFile;
    }

    public function scanDirectoryRecursively(string $folderPath)
    {
        $result = array();

        $cdir = scandir($folderPath);
        foreach ($cdir as $key => $value) {
            if (!in_array($value, array(".",".."))) {
                if (is_dir($folderPath . DIRECTORY_SEPARATOR . $value)) {
                    $result[$folderPath . '/' . $value] = $this->scanDirectoryRecursively($folderPath . DIRECTORY_SEPARATOR . $value);
                } else {
                    $result[] = $value;
                }
            }
        }

        return $result;
    }

    public function isPicture(string $fileExtension)
    {
        $fileExtension = strtolower($fileExtension);

        if ($fileExtension == 'jpg' || $fileExtension == 'jpeg' || $fileExtension == 'nef') {
            return true;
        }

        return false;
    }

    public function isMovie(string $fileExtension)
    {
        $fileExtension = strtolower($fileExtension);

        if ($fileExtension == 'mp4') {
            return true;
        }

        return false;
    }

    public function getPhotoDate(string $filePath)
    {
        $exifData = exif_read_data($filePath);

        if (!isset($exifData['DateTimeOriginal'])) {
            return explode('-', date(DATE_ATOM, $exifData['FileDateTime']));
        }

        return explode(':', $exifData['DateTimeOriginal']);
    }

    public function getMovieDate(string $filePath)
    {
        $movieMetadata = $this->ffprobe
            ->format($filePath)
            ->get('tags');

        return explode('-', $movieMetadata['creation_time']);
    }
}

