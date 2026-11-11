<?php

declare(strict_types=1);

namespace NumenCode\BlogExtension\Tests;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Winter\Storm\Support\Arr;

class LangTest extends BlogExtensionTestCase
{
    /**
     * @return array<int, string>
     */
    protected function keys(string $locale): array
    {
        $keys = array_keys(Arr::dot(require __DIR__ . '/../lang/' . $locale . '/lang.php'));
        sort($keys);

        return $keys;
    }

    public function testAllLanguagesHaveTheSameKeys(): void
    {
        $english = $this->keys('en');

        foreach (glob(__DIR__ . '/../lang/*', GLOB_ONLYDIR) as $directory) {
            $locale = basename($directory);

            $this->assertSame($english, $this->keys($locale), "lang/{$locale} must have the same keys as lang/en");
        }
    }

    public function testAllUsedTranslationKeysExist(): void
    {
        $root = dirname(__DIR__);
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, RecursiveDirectoryIterator::SKIP_DOTS));
        $checked = 0;

        foreach ($files as $file) {
            $path = str_replace('\\', '/', $file->getPathname());

            if (str_contains($path, '/tests/') || str_contains($path, '/.git/') || !preg_match('/\.(php|yaml|htm)$/', $path)) {
                continue;
            }

            // Complete keys only (not prefixes built in code, e.g. self::LANG . 'properties.' . $type)
            preg_match_all("/(numencode\.(?:blogextension|fundamentals)::lang\.[a-z_.]*[a-z_])['\"]/", file_get_contents($path), $matches);

            // Keys built from the components' LANG prefix: self::LANG . 'tag_list.name'
            preg_match_all("/self::LANG \. '([a-z_.]*[a-z_])'(?! \.)/", file_get_contents($path), $prefixed);
            $matches[1] = array_merge($matches[1], array_map(fn ($key) => 'numencode.blogextension::lang.' . $key, $prefixed[1]));

            foreach ($matches[1] as $key) {
                $this->assertNotSame($key, trans($key), "Missing translation {$key} in " . substr($path, strlen($root)));
                $checked++;
            }
        }

        $this->assertGreaterThan(30, $checked);
    }
}
