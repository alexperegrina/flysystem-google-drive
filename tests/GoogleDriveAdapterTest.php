<?php
declare(strict_types=1);

namespace Tests\AlexPeregrina\Flysystem\GoogleDrive;

use AlexPeregrina\Flysystem\GoogleDrive\GoogleDriveAdapter;
use League\Flysystem\AdapterTestUtilities\FilesystemAdapterTestCase;
use League\Flysystem\FilesystemAdapter;

class GoogleDriveAdapterTest extends FilesystemAdapterTestCase
{
    protected static function createFilesystemAdapter(): FilesystemAdapter
    {
        $googleDriveAdapter = new GoogleDriveAdapter();
    }
}