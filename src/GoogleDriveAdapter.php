<?php
declare(strict_types=1);

namespace AlexPeregrina\Flysystem\GoogleDrive;

use Google_Service_Drive;
use Google_Service_Drive_DriveFile;
use League\Flysystem\Config;
use League\Flysystem\FileAttributes;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\FilesystemException;
use League\Flysystem\InvalidVisibilityProvided;
use League\Flysystem\PathPrefixer;
use League\Flysystem\UnableToCheckFileExistence;
use League\Flysystem\UnableToCopyFile;
use League\Flysystem\UnableToCreateDirectory;
use League\Flysystem\UnableToDeleteDirectory;
use League\Flysystem\UnableToDeleteFile;
use League\Flysystem\UnableToMoveFile;
use League\Flysystem\UnableToReadFile;
use League\Flysystem\UnableToRetrieveMetadata;
use League\Flysystem\UnableToWriteFile;

class GoogleDriveAdapter implements FilesystemAdapter
{
    /** @var Google_Service_Drive */
    private $service;

    /** @var PathPrefixer */
    private $prefixer;

    /** @var string */
    private $root;

    public function __construct(
        Google_Service_Drive $service
    ) {
        $this->service = $service;
        $this->prefixer = new PathPrefixer('', DIRECTORY_SEPARATOR);
        $this->root = '';
    }

    /**
     * @inheritDoc
     */
    public function fileExists(string $path): bool
    {
        $parent = $this->parentFolderFromPath($path);
        $fileName = $this->filenameFromPath($path);

        if (!$this->existFolder($parent)) {
            return false;
        }

        $rootFolder = $this->folder($parent);
        $files = $this->filesFromRoot($rootFolder);

        $driveFile = null;
        foreach ($files as $file) {
            if ($file->getName() == $fileName) {
                $driveFile = $file;
            }
        }

        return !is_null($driveFile);
    }

    /**
     * @inheritDoc
     */
    public function write(string $path, string $contents, Config $config): void
    {
        $parent = $this->parentFolderFromPath($path);
        $fileName = $this->filenameFromPath($path);

        $root = $this->folder($parent);

        $fileMetadata = new Google_Service_Drive_DriveFile(
            [
                'name' => $fileName,
                'parents' => [$root->getId()]
            ]
        );

        $this->service->files->create(
            $fileMetadata,
            [
                'data' => $contents,
                'mimeType' => $config->get('mimetype'),
                'uploadType' => 'multipart',
                'fields' => 'id'
            ]
        );
    }

    /**
     * @inheritDoc
     */
    public function writeStream(string $path, $contents, Config $config): void
    {
        $this->write($path, stream_get_contents($contents), $config);
    }

    /**
     * @inheritDoc
     */
    public function read(string $path): string
    {
        // TODO: Implement read() method.
    }

    /**
     * @inheritDoc
     */
    public function readStream(string $path)
    {
        // TODO: Implement readStream() method.
    }

    /**
     * @inheritDoc
     */
    public function delete(string $path): void
    {
        // TODO: Implement delete() method.
    }

    /**
     * @inheritDoc
     */
    public function deleteDirectory(string $path): void
    {
        // TODO: Implement deleteDirectory() method.
    }

    /**
     * @inheritDoc
     */
    public function createDirectory(string $path, Config $config): void
    {
        // TODO: Implement createDirectory() method.
    }

    /**
     * @inheritDoc
     */
    public function setVisibility(string $path, string $visibility): void
    {
        // TODO: Implement setVisibility() method.
    }

    /**
     * @inheritDoc
     */
    public function visibility(string $path): FileAttributes
    {
        // TODO: Implement visibility() method.
    }

    /**
     * @inheritDoc
     */
    public function mimeType(string $path): FileAttributes
    {
        // TODO: Implement mimeType() method.
    }

    /**
     * @inheritDoc
     */
    public function lastModified(string $path): FileAttributes
    {
        // TODO: Implement lastModified() method.
    }

    /**
     * @inheritDoc
     */
    public function fileSize(string $path): FileAttributes
    {
        // TODO: Implement fileSize() method.
    }

    /**
     * @inheritDoc
     */
    public function listContents(string $path, bool $deep): iterable
    {
        // TODO: Implement listContents() method.
    }

    /**
     * @inheritDoc
     */
    public function move(string $source, string $destination, Config $config): void
    {
        // TODO: Implement move() method.
    }

    /**
     * @inheritDoc
     */
    public function copy(string $source, string $destination, Config $config): void
    {
        // TODO: Implement copy() method.
    }

    protected function parentFolderFromPath(string $path): string
    {
        $pathInfo = pathinfo($path);
        $pathsDir = explode('/', $pathInfo['dirname']);
        $dir = array_pop($pathsDir);

        return $dir;
    }

    protected function basenameFromPath(string $path): string
    {
        $pathInfo = pathinfo($path);
        return $pathInfo['basename'];
    }

    protected function filenameFromPath(string $path): string
    {
        $pathInfo = pathinfo($path);
        return $pathInfo['filename'];
    }

    // ---- PRIVATE

    /**
     * @throws UnableToCheckFileExistence
     */
    protected function guardExistFolder(string $nameFolder): void
    {
        if (!$this->existFolder($nameFolder)) {
            throw new UnableToCheckFileExistence($nameFolder);
        }
    }

    /**
     * @return bool
     */
    protected function existFolder(string $nameFolder): bool
    {
        $params['q'] = "mimeType='application/vnd.google-apps.folder' and trashed=false and name contains '$nameFolder'";

        $files = $this->service->files->listFiles($params);

        return ($files->count() !== 0);
    }

    /**
     * @throws UnableToCheckFileExistence
     */
    protected function folder(string $nameFolder): Google_Service_Drive_DriveFile
    {
        $this->guardExistFolder($nameFolder);

        $params['q'] = "mimeType='application/vnd.google-apps.folder' and trashed=false and name contains '$nameFolder'";

        $files = $this->service->files->listFiles($params);

        return $files->getFiles()[0];
    }

    /**
     * @return Google_Service_Drive_DriveFile[]
     */
    protected function filesFromRoot(Google_Service_Drive_DriveFile $root): array
    {
        $params['q'] = "trashed = false AND '{$root->id}' IN parents ";

        $files = $this->service->files->listFiles($params);

        $result = [];

        if ($files->count() !== 0) {
            $result = $files->getFiles();
        }

        return $result;
    }

//    /**
//     * Get file oblect Google_Service_Drive_DriveFile
//     *
//     * @param string $path     itemId path
//     * @param bool   $checkDir do check hasdir
//     *
//     * @return Google_Service_Drive_DriveFile|null
//     */
//    public function getFileObject($path, $checkDir = false)
//    {
//        [, $itemId] = $this->splitPath($path);
//        if(isset($this->cacheFileObjects[$itemId])) {
//            return $this->cacheFileObjects[$itemId];
//        }
//
//        $service = $this->service;
//        $client = $service->getClient();
//
//        $client->setUseBatch(true);
//        try {
//            $batch = $service->createBatch();
//
//            $opts = [
//                'fields' => self::FETCHFIELDS_GET
//            ];
//
//            /** @var RequestInterface $request */
//            $request = $this->service->files->get($itemId, $opts);
//            $batch->add($request, 'obj');
//
//            if($checkDir && $this->useHasDir) {
//                /** @var RequestInterface $request */
//                $request = $service->files->listFiles($this->applyDefaultParams([
//                    'pageSize' => 1,
//                    'orderBy'  => 'folder,modifiedTime,name',
//                    'q'        => sprintf('trashed = false and "%s" in parents and mimeType = "%s"', $itemId, self::DIRMIME)
//                ], 'files.list'));
//
//                $batch->add($request, 'hasdir');
//            }
//            $results = array_values($batch->execute());
//
//            [$fileObj, $hasdir] = array_pad($results, 2, null);
//        } finally {
//            $client->setUseBatch(false);
//        }
//
//        if($fileObj instanceof Google_Service_Drive_DriveFile) {
//            if($hasdir && $fileObj->mimeType === self::DIRMIME) {
//                if($hasdir instanceof Google_Service_Drive_FileList) {
//                    $this->cacheHasDirs[$fileObj->getId()] = (bool)$hasdir->getFiles();
//                }
//            }
//        } else {
//            $fileObj = null;
//        }
//
//        if($fileObj !== null) {
//            $this->cacheFileObjects[$itemId] = $fileObj;
//            $this->cacheObjects([$itemId => $fileObj]);
//        }
//
//        return $fileObj;
//    }
}