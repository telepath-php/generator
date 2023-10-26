<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;
use Symfony\Component\DomCrawler\Crawler;

class CheckForChangesCommand extends Command
{

    public const API_DOCS_URI = 'https://core.telegram.org/bots/api';

    protected $signature = 'check
        {--cache-path= : The path where the page cache should be saved}
        {--no-generate : Do not generate code if changes are detected}
        {path? : The path where the generated code should be saved}
    ';

    protected $description = 'Checks if API docs changed since last run and generates new code if so';

    public function handle(): void
    {
        $this->line('Checking for changes...');

        $pageContent = $this->fetchPageContent();

        // Generate a hash of the page content
        $currentHash = hash('sha256', $pageContent);

        $lastHash = $this->lastHash();
        $shouldGenerate = $this->shouldGenerate($lastHash, $currentHash);

        if ($shouldGenerate && ! $this->option('no-generate')) {

            $this->call('generate', [
                'path' => $this->argument('path'),
            ]);

        }

        $this->persistCache($shouldGenerate, $currentHash, $pageContent);
    }

    protected function cachePath(string $filename = ''): string
    {
        $path = $this->option('cache-path') ?? storage_path('app/page-cache');
        File::ensureDirectoryExists($path);

        return rtrim($path . DIRECTORY_SEPARATOR . $filename, DIRECTORY_SEPARATOR);
    }

    protected function lastHash(): ?string
    {
        // Ensure the file exists and load the last hash
        $hashFile = $this->cachePath('hash.txt');

        if (! file_exists($hashFile)) {
            return null;
        }

        return file_get_contents($hashFile);
    }

    protected function shouldGenerate(?string $lastHash, string $currentHash): bool
    {
        if ($lastHash === null) {
            $this->warn('No previous hash found');

            return true;
        }

        if ($lastHash === $currentHash) {
            $this->error('No changes detected');

            return false;
        }

        $this->info('Changes detected');

        return true;
    }

    protected function persistCache(string $shouldGenerate, string $currentHash, string $pageContent): void
    {
        // Save hash
        file_put_contents($this->cachePath('hash.txt'), $currentHash);

        // Save page content
        $prevPageFile = $this->cachePath('page-before.html');
        $currentPageFile = $this->cachePath('page-after.html');

        if (file_exists($currentPageFile)) {
            rename($currentPageFile, $prevPageFile);
        }
        file_put_contents($currentPageFile, $pageContent);

        // Generate diff
        if (file_exists($prevPageFile) && file_exists($currentPageFile)) {
            $diff = Process::run(['diff', $prevPageFile, $currentPageFile])->output();
            file_put_contents($this->cachePath('diff.txt'), $diff);
        }

        // Save environment variables
        $pageChanged = $shouldGenerate ? 'true' : 'false';
        $apiVersion = $this->identifyLatestApiVersion($pageContent);
        $environment = <<<ENV
page_changed=$pageChanged
api_version=$apiVersion
ENV;
        file_put_contents($this->cachePath('environment.txt'), $environment);
    }

    protected function fetchPageContent(): string
    {
        // Get the page content
        $pageContent = Http::timeout(120)->get(static::API_DOCS_URI)->body();

        // Telegram sends <!-- page generated in 45.12ms --> after the closing </html> tag
        $pageContent = preg_replace('#(</html>).+#s', '$1', $pageContent);

        return $pageContent;
    }

    protected function identifyLatestApiVersion(string $pageContent): string
    {
        $crawler = new Crawler();
        $crawler->addHtmlContent($pageContent);

        $recentChanges = $crawler->filter('[name="recent-changes"]');
        $node = $recentChanges->getNode(0)->parentNode;
        while ($node->nodeName !== 'h4') {
            $node = $node->nextSibling;
        }

        while ($node->nodeName !== 'p') {
            $node = $node->nextSibling;
        }

        $versionString = $node->textContent;

        $success = preg_match('/Bot API (\d+\.\d+)/i', $versionString, $matches) === 1;

        if (! $success) {
            $this->error('Could not identify latest Bot API version.');
            return 'unknown';
        }

        $this->info('Latest Bot API version: ' . $matches[1]);
        return $matches[1];
    }

}
