<?php

namespace backend\components;

use DateInterval;
use Psr\SimpleCache\CacheInterface;

class SpreadsheetFileCache implements CacheInterface
{
    private string $baseDir;

    public function __construct(string $baseDir)
    {
        $this->baseDir = rtrim($baseDir, '/\\');
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $file = $this->fileForKey($key);
        if (!is_file($file)) {
            return $default;
        }

        $raw = @file_get_contents($file);
        if ($raw === false) {
            return $default;
        }

        $value = @unserialize($raw);

        return $value === false && $raw !== serialize(false) ? $default : $value;
    }

    public function set(string $key, mixed $value, null|int|DateInterval $ttl = null): bool
    {
        $file = $this->fileForKey($key);
        $payload = serialize($value);

        return @file_put_contents($file, $payload, LOCK_EX) !== false;
    }

    public function delete(string $key): bool
    {
        $file = $this->fileForKey($key);

        return !is_file($file) || @unlink($file);
    }

    public function clear(): bool
    {
        if (!is_dir($this->baseDir)) {
            return true;
        }

        $ok = true;
        $files = scandir($this->baseDir);
        if ($files === false) {
            return false;
        }

        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $path = $this->baseDir . DIRECTORY_SEPARATOR . $file;
            if (is_file($path) && !@unlink($path)) {
                $ok = false;
            }
        }

        return $ok;
    }

    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = $this->get((string)$key, $default);
        }

        return $result;
    }

    public function setMultiple(iterable $values, null|int|DateInterval $ttl = null): bool
    {
        $ok = true;
        foreach ($values as $key => $value) {
            if (!$this->set((string)$key, $value, $ttl)) {
                $ok = false;
            }
        }

        return $ok;
    }

    public function deleteMultiple(iterable $keys): bool
    {
        $ok = true;
        foreach ($keys as $key) {
            if (!$this->delete((string)$key)) {
                $ok = false;
            }
        }

        return $ok;
    }

    public function has(string $key): bool
    {
        return is_file($this->fileForKey($key));
    }

    private function fileForKey(string $key): string
    {
        return $this->baseDir . DIRECTORY_SEPARATOR . md5($key) . '.cache';
    }
}

