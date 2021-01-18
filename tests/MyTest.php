<?php


namespace Tests\AlexPeregrina\Flysystem\GoogleDrive;


use League\Flysystem\PathPrefixer;
use PHPUnit\Framework\TestCase;
use function Couchbase\defaultDecoder;

class MyTest extends TestCase
{
    /** @var string */
    private $root;

    public function testA()
    {
        self::assertTrue(true);

        $prefixer = new PathPrefixer('');

        $path = 'Aaa/DDD/bbb.c';

        $a = $prefixer->prefixPath($path);
        $b = $prefixer->prefixDirectoryPath($path);
        $c = $prefixer->stripDirectoryPrefix($path);
        $d = $prefixer->stripPrefix($path);

//        dd($a, $b, $c, $d);

//        dd(pathinfo($a));

        dd($this->sp($a));
    }


    /**
     * Path splits to dirId, fileId or newName
     *
     * @param string $path
     * @param bool   $getParentId True => return only parent id, False => return full path (basically the same as dirname($path))
     * @return array [ $dirId , $fileId|newName ]
     */
    protected function splitPath($path, $getParentId = true)
    {
        $this->root = '';

        if($path === '' || $path === '/') {
            $fileName = $this->root;
            $dirName = '';
        } else {
            $paths = explode('/', $path);
            $fileName = array_pop($paths);
            if($getParentId) {
                $dirName = $paths ? array_pop($paths) : '';
            } else {
                $dirName = implode('/', $paths);
            }
            if($dirName === '') {
                $dirName = $this->root;
            }
        }
        return [
            $dirName,
            $fileName
        ];
    }

    protected function sp($path)
    {
        $pathInfo = pathinfo($path);

        $pathsDir = explode('/', $pathInfo['dirname']);
        $dir = array_pop($pathsDir);

        return [
            'dir' => $dir,
            'basename' => $pathInfo['basename'],
            'filename' => $pathInfo['filename'],
            'extension' => $pathInfo['extension']
        ];
    }
}