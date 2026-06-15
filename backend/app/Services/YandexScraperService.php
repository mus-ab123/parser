<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class YandexScraperService
{
    public function scrape(string $url, int $limit = 600): array
    {
        $scraperPath = base_path('scraper/scraper.js');
        $nodePath = 'node';
        
        if (file_exists('/usr/bin/node')) {
            $nodePath = '/usr/bin/node';
        } elseif (file_exists('/usr/local/bin/node')) {
            $nodePath = '/usr/local/bin/node';
        }

        $env = [];
        if (env('PUPPETEER_EXECUTABLE_PATH')) {
            $env['PUPPETEER_EXECUTABLE_PATH'] = env('PUPPETEER_EXECUTABLE_PATH');
            $env['PUPPETEER_SKIP_CHROMIUM_DOWNLOAD'] = 'true';
        }

        $process = new Process([$nodePath, $scraperPath, $url, (string)$limit], null, $env);
        $process->setTimeout(180);
        
        try {
            $process->mustRun();
            $output = $process->getOutput();
            $data = json_decode($output, true);
            
            if (!$data) {
                $data = $this->extractJson($output);
            }

            if (!$data || !isset($data['success'])) {
                throw new \Exception("Invalid output structure received from scraper: " . substr($output, 0, 500));
            }

            if (!$data['success']) {
                throw new \Exception("Scraper reported error: " . ($data['error'] ?? 'Unknown error'));
            }

            return $data;
        } catch (ProcessFailedException $e) {
            Log::error("[ScraperService] Process failed: " . $e->getMessage());
            throw new \Exception("Scraper process failed to execute: " . $e->getMessage());
        } catch (\Exception $e) {
            throw $e;
        }
    }

    private function extractJson(string $text): ?array
    {
        $start = strpos($text, '{"success"');
        if ($start === false) {
            $start = strpos($text, '{');
        }

        if ($start === false) {
            return null;
        }

        $length = strlen($text);
        $depth = 0;
        
        for ($i = $start; $i < $length; $i++) {
            $char = $text[$i];
            if ($char === '{') {
                $depth++;
            } elseif ($char === '}') {
                $depth--;
                if ($depth === 0) {
                    $jsonContent = substr($text, $start, $i - $start + 1);
                    return json_decode($jsonContent, true);
                }
            }
        }

        return null;
    }
}
