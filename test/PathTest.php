<?php

use PHPUnit\Framework\TestCase;

class PathTest extends TestCase {

    public function testPathIsAbsolute() {
        // Check empty inputs
        $this->assertEquals(false, path_is_absolute(''), 'path_is_absolute returns false for empty string input');

        // Test unix paths
        $this->assertEquals(true, path_is_absolute('/some/absolute/path'), 'unix-style absolute path');
        $this->assertEquals(false, path_is_absolute('../../rel/path'), 'unix-style non-absolute paths');
        $this->assertEquals(false, path_is_absolute('path/is/relative'), 'path_is_absolute returns false for unix-style non-absolute paths');

        // Test windows paths
        $this->assertEquals(true, path_is_absolute('\server\share\asdf'), 'windows-style absolute path without drive letter');
        $this->assertEquals(true, path_is_absolute('C:\Users\someuser'), 'windows-style absolute path with drive letter');
        $this->assertEquals(true, path_is_absolute('c:\Users\someuser2'), 'windows-style absolute path with lowercase drive letter');
    }

    public function testPathNormalizeResolve() {
        $test = ['a', 'b', 'c'];
        $this->assertEquals($test, path_normalize_reduce($test, '.'), 'item is single dot');
        $this->assertEquals(['a', 'b', 'c', 'd'], path_normalize_reduce($test, 'd'), 'item is regular path name');
        $this->assertEquals(['..'], path_normalize_reduce([], '..'), 'array is empty and item is ..');
        $this->assertEquals(['a', 'b'], path_normalize_reduce($test, '..'), 'item is ..');
        $this->assertEquals(['..', '..'], path_normalize_reduce(['..'], '..'), 'array is [..], item is ..');
    }

    public function testPathJoinFirstArgEmpty() {
        // path_join should throw exception if first argument is empty
        $this->expectException(\InvalidArgumentException::class);

        path_join('', 'foo', 'bar');
    }

    public function testPathJoinInvalidPath() {
        $this->expectException(\InvalidArgumentException::class);

        path_join('C:\foo', '..', '..');
    }

    public function testPathJoin() {
        $this->assertEquals('a/b/c', path_join('a', 'b', 'c'), 'simple case');
        $this->assertEquals('a/b/c/d', path_join('a\\', '/b/c/', '\d'), 'simple case with prepending/trailing slashes');
        $this->assertEquals('/a/b/c', path_join('/a/b', './c/'), 'simple case with unix absolute path');
        $this->assertEquals('/a/b', path_join('/', 'a', 'b'), 'simple case with slash as first arg');
        $this->assertEquals('/a/b', path_join('\\', 'a', 'b'), 'simple case with windows slash as first arg');
        $this->assertEquals('C:/a/b/c', path_join('C:\a\b', './c/'), 'simple case with windows absolute path');
        $this->assertEquals('a/c', path_join('a/b', './d/', '../../c/'), 'moderate case with ..');
        $this->assertEquals('../b/c', path_join('a', '..', '../b/', 'c'), 'moderate case with .. removing first arg');
    }

    public function testPathResolve() {
        $cwd = getcwd();

        $this->assertEquals("{$cwd}/a/b", path_resolve('a', 'b'), 'without passing absolute path');
        $this->assertEquals('/a/b/c', path_resolve('/a', 'b/c'), 'unix-style absolute path');
        $this->assertEquals('/a/b/c', path_resolve('\\a', 'b/c'), 'windows-style absolute path w/o drive letter');
        $this->assertEquals('C:/a/b/c', path_resolve('C:\a', 'b/c'), 'windows-style absolute path w/ drive letter');
    }
}
