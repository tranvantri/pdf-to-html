<?php

namespace TvT\PdfToHtml;

/**
 * This is base class with common properties and methods.
 *
 * @property string $outputDir
 * @property array $options
 * @property array $defaultOptions
 */
class Base
{
    private string $outputDir = '';
    private array $options = [];

    /**
     * Get all options or one option by key.
     * @param  string|null  $key
     * @return mixed
     */
    public function getOptions(?string $key = null): mixed
    {
        if ($key) {
            return $this->options[$key] ?? null;
        }

        return $this->options;
    }

    /**
     * Set options as array or pair key-value.
     * @param $key
     * @param  string|null  $value
     */
    public function setOptions($key, ?string $value = null): void
    {
        if (is_array($key)) {
            $this->options = array_replace_recursive($this->options, $key);
        }

        if (is_string($key)) {
            $this->options[$key] = $value;
        }
    }

    /**
     * Get output dir.
     * @return string
     */
    public function getOutputDir(): string
    {
        return $this->outputDir;
    }

    /**
     * Set output dir.
     * @param  string  $dir
     * @return $this
     */
    public function setOutputDir(string $dir): static
    {
        $this->setOptions('outputDir', $dir);
        $this->outputDir = $dir;

        return $this;
    }

    /**
     * Clear all files that has been generated by pdftohtml.
     * Make sure directory ONLY contain generated files from pdftohtml,
     * because it remove all contents under preserved output directory
     * @param  bool|bool  $removeSelf
     * @return $this
     */
    public function clearOutputDir(bool $removeSelf = false): static
    {
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->getOutputDir(),
            \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($files as $file) {
            $path = (string) $file;
            $basename = basename($path);

            if ($basename != '..') {
                if (is_file($path) && file_exists($path)) {
                    unlink($path);
                } elseif (is_dir($path) && file_exists($path)) {
                    rmdir($path);
                }
            }
        }

        if ($removeSelf) {
            rmdir($this->getOutputDir());
        }

        return $this;
    }
}