<?php

namespace TvT\PdfToHtml;

use Symfony\Component\CssSelector\Exception\ParseException;

/**
 * This class creates a collection of html pages with some improvements.
 *
 * @property string $file
 * @property string[] $info
 * @property Html $html
 */
class Pdf extends Base
{
    private ?string $file = null;
    private ?array $info = null;
    private ?Html $html = null;
    private ?string $result = null;

    private $defaultOptions = [
        'pdftohtml_path' => '/usr/bin/pdftohtml',
        'pdfinfo_path' => '/usr/bin/pdfinfo',

        'generate' => [
            'singlePage' => false,
            'imageJpeg' => false,
            'ignoreImages' => false,
            'zoom' => 1.5,
            'noFrames' => true,
        ],

        'outputDir' => '',
        'removeOutputDir' => false,
        'clearAfter' => true,

        'html' => [
            'inlineImages' => true,
        ]
    ];

    public function __construct(string $file, array $options = [])
    {
        $this->setOptions(array_replace_recursive($this->defaultOptions, $options));
        $this->setFile($file)->setInfoObject()->setHtmlObject();
    }

    /**
     * Set file.
     * @param  string  $file
     * @return $this
     */
    public function setFile(string $file): static
    {
        $this->file = $file;

        return $this;
    }

    /**
     * Get info of pdf file.
     * @return array|null
     */
    public function getInfo(): ?array
    {
        if($this->info == null) {
            $this->setInfoObject();
        }

        return $this->info;
    }

    /**
     * Get count page in pdf file.
     * @return mixed
     */
    public function countPages(): mixed
    {
        if($this->info == null) {
            $this->setInfoObject();
        }

        return $this->info['pages'];
    }

    /**
     * Get Html object.
     * @return Html
     * @throws ParseException
     */
    public function getHtml(): ?Html
    {
        $this->getContent();

        return $this->html;
    }

    /**
     * Set output dir.
     * @param string $dir
     * @return $this
     */
    public function setOutputDir(string $dir): static
    {
        $this->html?->setOutputDir($dir);

        return parent::setOutputDir($dir);
    }

    /**
     * Get pdf file info using pdfinfo software.
     * @return $this
     */
    private function setInfoObject(): static
    {
        $content = shell_exec($this->getOptions('pdfinfo_path') . ' ' . escapeshellarg($this->file));
        $options = explode("\n", $content);
        $info = [];
        foreach($options as &$item) {
            if(!empty($item)) {
                list($key, $value) = explode(':', $item);
                $info[str_replace([' '], ['_'], strtolower($key))] = trim($value);
            }
        }

        $this->info = $info;

        return $this;
    }

    /**
     * Create and set Html object.
     * @return $this
     */
    private function setHtmlObject(): static
    {
        $this->html = new Html($this->getOptions('html'));

        return $this;
    }

    /**
     * Method does most of work, parses pdf, html files obtained prepares and sends to Html object.
     * @throws ParseException
     */
    private function getContent(): void
    {
        $outputDir = $this->getOptions('outputDir') ? $this->getOptions('outputDir') : dirname(__FILE__) . '/../output/' . uniqid();
        if (!file_exists($outputDir)) {
            mkdir($outputDir, 0777, true);
        }

        $this->setOutputDir($outputDir)->generate();

        $fileInfo = pathinfo($this->file);
        $base_path = $this->getOutputDir() . '/' . $fileInfo['filename'];

        $countPages = $this->countPages();
        if ($countPages) {
            if ($countPages > 1)
                for ($i = 1; $i <= $countPages; $i++) {
                    $content = file_get_contents($base_path . '-' . $i . '.html');
                    $this->html->addPage($i, $content);
                }
            else {
                $content = file_get_contents($base_path . '.html');
                $this->html->addPage(1, $content);
            }
        }

        if ($this->getOptions('clearAfter'))
            $this->clearOutputDir($this->getOptions('removeOutputDir'));
    }

    /**
     * Generating html files using pdftohtml software.
     * @return $this
     */
    private function generate(): static
    {
        $this->result = null;
        $command = $this->getCommand();
        $this->result = exec($command);

        return $this;
    }

    /**
     * Get command for generate html
     * @return string
     */
    public function getCommand(): string
    {
        if ($this->countPages() > 1) {
            $this->setOptions(['generate' => ['noFrames' => false]]);
        }

        $output = $this->getOutputDir() . '/' . preg_replace("/\.pdf$/", '', basename($this->file)) . '.html';
        $options = $this->generateOptions();

        return $this->getOptions('pdftohtml_path') . ' ' . $options . ' ' . escapeshellarg($this->file) . ' ' . escapeshellarg($output);
    }

    /**
     * Get result of generate html
     * @return string|null
     */
    public function getResult(): ?string
    {
        return $this->result;
    }

    /**
     * Generate options based on the preserved options
     * @return string
     */
    private function generateOptions(): string
    {
        $generated = [];
        $generateValue = $this->getOptions('generate');
        array_walk($generateValue, function ($value, $key) use (&$generated) {
            $result = '';
            switch ($key) {
                case 'singlePage':
                    $result = $value ? '-s' : '-c';
                    break;
                case 'imageJpeg':
                    $result = '-fmt ' . ($value ? 'jpg' : 'png');
                    break;
                case 'zoom':
                    $result = '-zoom ' . $value;
                    break;
                case 'ignoreImages':
                    $result = $value ? '-i' : '';
                    break;
                case 'noFrames':
                    $result = $value ? '-noframes' : '';
                    break;
            }

            $generated[] = $result;
        });

        return implode(' ', $generated);
    }

}