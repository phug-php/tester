<?php

namespace Phug\Tester;

use Phug\Ast\NodeInterface as AstNodeInterface;
use Phug\Parser\Node\DocumentNode;
use Phug\Parser\NodeInterface;
use Phug\Renderer;
use Phug\Util\SourceLocationInterface;
use SplObjectStorage;

class Coverage
{
    const VERSION = '0.1.0';

    /**
     * @var array
     */
    protected static $assets = [
        'css/style.css',
        'css/bootstrap.min.css',
        'fonts/glyphicons-halflings-regular.eot',
        'fonts/glyphicons-halflings-regular.svg',
        'fonts/glyphicons-halflings-regular.ttf',
        'fonts/glyphicons-halflings-regular.woff',
        'fonts/glyphicons-halflings-regular.woff2',
    ];

    /**
     * @var static
     */
    protected static $singleton = null;

    /**
     * @var Renderer
     */
    protected $renderer;

    /**
     * @var float
     */
    protected $threshold = 0;

    /**
     * @var float
     */
    protected $lastCoverageRate = 0;

    /**
     * @var array
     */
    protected $lastCoverageData = null;

    /**
     * @var bool
     */
    protected $started = false;

    /**
     * @var bool
     */
    protected $coverageStoppingAllowed = true;

    /**
     * @var array
     */
    protected $coverage = [];

    /**
     * @var array
     */
    protected $tree = [];

    public static function get(): self
    {
        if (!static::$singleton) {
            static::$singleton = new static();
        }

        return static::$singleton;
    }

    public static function reset()
    {
        static::$singleton = null;
    }

    public static function getStatus(int $covered, int $total): string
    {
        if ($covered / max(1, $total) >= 0.9) {
            return 'success';
        }

        if ($covered / max(1, $total) >= 0.5) {
            return 'warning';
        }

        return 'danger';
    }

    protected static function emptyDirectory(string $dir)
    {
        if (!is_dir($dir)) {
            return;
        }

        foreach (scandir($dir) as $file) {
            if ($file !== '.' && $file !== '..') {
                $path = $dir.'/'.$file;
                if (is_dir($path)) {
                    static::emptyDirectory($path);
                    rmdir($path);

                    continue;
                }

                unlink($path);
            }
        }
    }

    protected static function removeDirectory(string $dir)
    {
        static::emptyDirectory($dir);
        if (is_dir($dir)) {
            rmdir($dir);
        }
    }

    protected static function addEmptyDirectory(string $dir)
    {
        if (file_exists($dir)) {
            is_dir($dir) ? static::emptyDirectory($dir) : unlink($dir);

            return;
        }

        mkdir($dir, 0777, true);
    }

    public function emptyCache()
    {
        static::emptyDirectory($this->renderer->getOption('cache_dir'));
    }

    public function removeCache()
    {
        static::removeDirectory($this->renderer->getOption('cache_dir'));
    }

    public function initializeCache()
    {
        $cache = $this->renderer->getOption('cache_dir');
        static::addEmptyDirectory($cache);
        $this->renderer->setOption('cache_dir', realpath($cache));
    }

    /**
     * @return bool
     */
    public function isCoverageAllowedToStop(): bool
    {
        return $this->coverageStoppingAllowed;
    }

    public function allowCoverageStopping()
    {
        $this->coverageStoppingAllowed = true;
    }

    public function disallowCoverageStopping()
    {
        $this->coverageStoppingAllowed = false;
    }

    protected function getPaths(): array
    {
        return $this->renderer->getOption('paths');
    }

    public function runXDebug()
    {
        // @codeCoverageIgnoreStart
        if (!function_exists('xdebug_code_coverage_started')) {
            throw new \BadFunctionCallException('You need to install XDebug to use coverage feature.');
        }
        $this->started = !xdebug_code_coverage_started();
        if ($this->started) {
            xdebug_start_code_coverage();
        }
        // @codeCoverageIgnoreEnd
    }

    public function storeCoverage(array $data)
    {
        $this->lastCoverageData = $data;
    }

    /**
     * @return array
     */
    public function getLastCoverageData(): array
    {
        return $this->lastCoverageData;
    }

    protected function getCoverageData(): array
    {
        if (xdebug_code_coverage_started()) {
            $data = xdebug_get_code_coverage();
            // @codeCoverageIgnoreStart
            if ($this->started && $this->isCoverageAllowedToStop()) {
                $this->started = false;
                xdebug_stop_code_coverage();
            }
            // @codeCoverageIgnoreEnd

            return $data;
        }

        // @codeCoverageIgnoreStart
        return static::get()->getLastCoverageData();
        // @codeCoverageIgnoreEnd
    }

    private function getLocationPath(string $path): string
    {
        foreach ($this->getPaths() as $base) {
            $realBase = realpath($base);
            if ($realBase) {
                $realPath = realpath($path);
                if ($realPath) {
                    $realBase .= DIRECTORY_SEPARATOR;
                    $len = strlen($realBase);
                    if (substr($realPath, 0, $len) === $realBase) {
                        return substr($realPath, $len);
                    }
                }
            }
        }

        // @codeCoverageIgnoreStart
        return $path;
        // @codeCoverageIgnoreEnd
    }

    protected function getTemplateFile(string $file, array $vars): string
    {
        $__php = file_get_contents(__DIR__."/../../template/$file");
        extract($vars);
        ob_start();
        eval("?>$__php");

        return ob_get_clean();
    }

    protected function writeFile(string $path, string $contents): bool
    {
        $base = dirname($path);
        if (!is_dir($base)) {
            mkdir($base, 0777, true);
        }

        return is_int(file_put_contents($path, $contents));
    }

    protected function recordLocation(SourceLocationInterface $location = null, int $covered = 0)
    {
        if ($location) {
            $locationPath = realpath($location->getPath());
            $locationLine = $location->getLine() - 1;
            if (!isset($this->coverage[$locationPath])) {
                $this->coverage[$locationPath] = [];
            }
            if (!isset($this->coverage[$locationPath][$locationLine])) {
                $this->coverage[$locationPath][$locationLine] = [];
            }
            $this->coverage[$locationPath][$locationLine][$location->getOffset() - 1] = $covered;
        }
    }

    protected function listNodes(SplObjectStorage $list, $node)
    {
        if ($node instanceof NodeInterface && !($node instanceof DocumentNode) && !$list->offsetExists($node)) {
            $list->attach($node);
            $this->recordLocation($node->getSourceLocation());
        }
        if ($node instanceof AstNodeInterface) {
            foreach ($node->getChildren() as $child) {
                $this->listNodes($list, $child);
            }
        }
    }

    protected function countFileNodes(string $file): int
    {
        $compiler = $this->renderer->getCompiler();
        $file = $compiler->resolve($file);
        $contents = $compiler->getFileContents($file);
        $document = $this->renderer->getCompiler()->getParser()->parse($contents, $file);
        $list = new SplObjectStorage();

        $this->listNodes($list, $document);

        return count($list);
    }

    protected function writeSummaries(string $directory, array $tree = null, string $path = '')
    {
        $tree = $tree ?: $this->tree;
        foreach ($tree as $subPath => list($coveredNodesCount, $fileNodesCount, $children)) {
            if ($children !== []) {
                $path .= "/$subPath";
                $html = $this->getTemplateFile('dashboard.html', [
                    'path'              => $path,
                    'coveredNodesCount' => $coveredNodesCount,
                    'fileNodesCount'    => $fileNodesCount,
                    'children'          => $children,
                    'percentage'        => number_format(100 * $coveredNodesCount / $fileNodesCount, 2),
                    'getStatus'         => [static::class, 'getStatus'],
                    'status'            => static::getStatus($coveredNodesCount, $fileNodesCount),
                    'phugTesterVersion' => static::VERSION,
                ]);

                $this->writeFile($directory.$path.'/index.html', $html);
                $this->writeSummaries($directory, $children, $path);
            }
        }
    }

    public function dumpCoverage(bool $output = false, string $directory = null)
    {
        if ($directory) {
            static::addEmptyDirectory($directory);
            foreach (static::$assets as $asset) {
                $this->writeFile(
                    "$directory/$asset",
                    file_get_contents(__DIR__."/../../template/$asset")
                );
            }
        }
        if ($output) {
            echo "\n| Coverage:\n|\n";
        }
        $formatter = $this->renderer->getCompiler()->getFormatter();
        $cache = realpath($this->renderer->getOption('cache_dir')).DIRECTORY_SEPARATOR;
        $len = strlen($cache);
        $coveredNodes = [];
        $counts = [];

        foreach ($this->getCoverageData() as $file => $results) {
            if (substr(realpath($file), 0, $len) === $cache) {
                $lines = file($file);
                foreach ($lines as $number => $line) {
                    if (isset($results[$number]) && preg_match('/^\/\/ PUG_DEBUG:(\d+)$/', $line, $match)) {
                        $debugId = intval($match[1]);
                        if ($formatter->debugIdExists($debugId)) {
                            for ($node = $formatter->getNodeFromDebugId($debugId);
                                $node instanceof NodeInterface;
                                $node = $node->getOuterNode() ?: $node->getParent()) {
                                if (!($node instanceof DocumentNode) && ($location = $node->getSourceLocation())) {
                                    $locationPath = $location->getPath();
                                    if (!isset($counts[$locationPath])) {
                                        $counts[$locationPath] = $this->countFileNodes($locationPath);
                                    }
                                    if (!isset($coveredNodes[$locationPath])) {
                                        $coveredNodes[$locationPath] = new SplObjectStorage();
                                    }
                                    if (!isset($coveredNodes[$locationPath][$node])) {
                                        $coveredNodes[$locationPath]->attach($node);
                                    }
                                    $this->recordLocation($location, 1);
                                }
                            }
                        }
                    }
                }
            }
        }

        $files = [];
        $this->tree = [];
        foreach ($this->getPaths() as $path) {
            if (!is_dir($path)) {
                continue;
            }

            foreach ($this->renderer->scanDirectory($path) as $file) {
                $coveredState = 0;
                $file = realpath($file);
                $fileNodesCount = isset($counts[$file]) ? $counts[$file] : $this->countFileNodes($file);
                $fileCoverage = isset($this->coverage[$file]) ? $this->coverage[$file] : [];
                $filePath = $this->getLocationPath($file);
                $html = '';
                $lines = file($file);
                $count = count($lines);
                $pad = strlen(strval($count));
                foreach ($lines as $number => $line) {
                    $id = $number + 1;
                    $html .= '<div><a style="color: gray;" id="L'.$id.'" href="#L'.$id.'">'.
                        str_pad($id, $pad, ' ', STR_PAD_LEFT).
                        ' &nbsp;</a>';
                    $lineCoverage = isset($fileCoverage[$number]) ? $fileCoverage[$number] : [];
                    $currentOffset = 0;
                    foreach ($lineCoverage as $offset => $covered) {
                        if ($offset > $currentOffset && $coveredState !== $covered) {
                            $class = $coveredState ? 'covered' : 'uncovered';
                            $html .= '<span class="'.$class.' chunk">'.
                                mb_substr($line, $currentOffset, $offset).
                                '</span>';
                            $currentOffset = $offset;
                        }
                        $coveredState = $covered;
                    }
                    if (mb_strlen($line) > $currentOffset) {
                        $class = $coveredState ? 'covered' : 'uncovered';
                        $html .= '<span class="'.$class.' chunk">'.
                            mb_substr($line, $currentOffset).
                            '</span>';
                    }
                    $html .= '</div>';
                }
                if ($directory) {
                    $html = $this->getTemplateFile('file.html', [
                        'path'              => $filePath,
                        'coverage'          => $html,
                        'phugTesterVersion' => static::VERSION,
                    ]);

                    $this->writeFile("$directory/$filePath.html", $html);
                }
                $coveredNodesCount = isset($coveredNodes[$file]) ? count($coveredNodes[$file]) : 0;
                $files[$filePath] = [$coveredNodesCount, $fileNodesCount];
                $base = &$this->tree;
                foreach (preg_split('/[\\\\\\/]+/', "/$filePath") as $subPath) {
                    if (!isset($base[$subPath])) {
                        $base[$subPath] = [0, 0, []];
                    }
                    $base = &$base[$subPath];
                    $base[0] += $coveredNodesCount;
                    $base[1] += $fileNodesCount;
                    $base = &$base[2];
                }
            }
        }
        if ($directory) {
            $this->writeSummaries($directory);
        }
        $pad = max(array_pad(array_map(function ($path) {
            return strlen($path);
        }, array_keys($files)), 1, 0)) + 3;
        $coveredNodes = 0;
        $nodes = 0;
        foreach ($files as $file => $stats) {
            list($_coveredNodes, $_nodes) = $stats;
            $coveredNodes += $_coveredNodes;
            $nodes += $_nodes;
            if ($output) {
                echo '| '.str_pad($file, $pad, ' ', STR_PAD_RIGHT).
                    str_pad($_coveredNodes, 3, ' ', STR_PAD_LEFT).' / '.
                    str_pad($_nodes, 3, ' ', STR_PAD_LEFT).'   '.
                    str_pad(
                        number_format($_coveredNodes * 100 / max(1, $_nodes), 1),
                        5,
                        ' ',
                        STR_PAD_LEFT
                    )."%\n";
            }
        }
        $this->lastCoverageRate = $coveredNodes * 100 / max(1, $nodes);
        if ($output) {
            echo '|'.str_repeat('-', $pad + 19)."\n| ".
                str_pad('Total', $pad, ' ', STR_PAD_RIGHT).
                str_pad($coveredNodes, 3, ' ', STR_PAD_LEFT).' / '.
                str_pad($nodes, 3, ' ', STR_PAD_LEFT).'   '.
                str_pad(
                    number_format($this->lastCoverageRate, 1),
                    5,
                    ' ',
                    STR_PAD_LEFT
                )."%\n\n";
        }
    }

    /**
     * @return Renderer
     */
    public function createRenderer($renderer, array $options = []): Renderer
    {
        if (is_string($renderer)) {
            $renderer = new $renderer($options);
        }

        $this->renderer = $renderer;
        $this->initializeCache();

        return $renderer;
    }

    /**
     * @return Renderer
     */
    public function getRenderer(): Renderer
    {
        return $this->renderer;
    }

    /**
     * @param int $threshold
     */
    public function setThreshold($threshold)
    {
        $this->threshold = floatval($threshold);
    }

    public function isThresholdReached(): bool
    {
        if ($this->threshold) {
            echo "\nOverall coverage is {$this->lastCoverageRate}%.\n";
            echo "\nExpected threshold {$this->threshold}%";

            if ($this->lastCoverageRate < $this->threshold) {
                echo " not reached.\n";

                return false;
            }

            echo " reached.\n";
        }

        return true;
    }
}
