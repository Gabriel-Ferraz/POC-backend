<?php

declare(strict_types=1);

namespace Tests\Helpers;

use Illuminate\Http\UploadedFile;

trait StorageHelper
{
    /**
     * Create a fake audio file for testing.
     *
     * @param string $extension The file extension (wav, mp3, webm, ogg)
     * @param int $sizeInKilobytes The file size in kilobytes
     * @return \Illuminate\Http\UploadedFile
     */
    protected function createFakeAudioFile(string $extension = 'wav', int $sizeInKilobytes = 100): UploadedFile
    {
        $mimeTypes = [
            'wav' => 'audio/wav',
            'mp3' => 'audio/mpeg',
            'webm' => 'audio/webm',
            'ogg' => 'audio/ogg',
        ];

        $mimeType = $mimeTypes[$extension] ?? 'audio/wav';

        return UploadedFile::fake()->create(
            "test_audio.{$extension}",
            $sizeInKilobytes,
            $mimeType
        );
    }

    /**
     * Create multiple fake audio files for testing.
     *
     * @param int $count The number of files to create
     * @param string $extension The file extension (wav, mp3, webm, ogg)
     * @param int $sizeInKilobytes The file size in kilobytes
     * @return array<\Illuminate\Http\UploadedFile>
     */
    protected function createFakeAudioFiles(int $count = 3, string $extension = 'wav', int $sizeInKilobytes = 100): array
    {
        $files = [];

        for ($i = 1; $i <= $count; $i++) {
            $files[] = $this->createFakeAudioFile($extension, $sizeInKilobytes);
        }

        return $files;
    }
}
