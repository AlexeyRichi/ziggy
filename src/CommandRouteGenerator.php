<?php

namespace Tightenco\Ziggy;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Routing\Router;
use Tightenco\Ziggy\BladeRouteGenerator;
use Tightenco\Ziggy\RoutePayload;

class CommandRouteGenerator extends Command
{
    protected $signature = 'ziggy:generate {path=./resources/assets/js/ziggy.js}';

    protected $description = 'Generate js file for including in build process';

    protected $baseUrl;
    protected $baseProtocol;
    protected $baseDomain;
    protected $basePort;
    protected $router;

    public function __construct(Router $router, Filesystem $files)
    {
        parent::__construct();

        $this->router = $router;
        $this->files = $files;
    }

    public function handle()
    {
        $path = $this->argument('path');

        $generatedRoutes = $this->generate();

        $this->makeDirectory($path);

        $this->files->put($path, $generatedRoutes);
    }

    public function generate($group = false)
    {
        $this->prepareDomain();

        $json = $this->getRoutePayload($group)->toJson();

        return <<<EOT
    var Ziggy = {
        namedRoutes: $json,
        baseUrl: '{$this->baseUrl}',
        baseProtocol: '{$this->baseProtocol}',
        baseDomain: '{$this->baseDomain}',
        basePort: {$this->basePort}
    };

    export {
        Ziggy
    }

EOT;
    }

    private function prepareDomain()
    {
        $url = url('/');
        $parsedUrl = parse_url($url);

        $this->baseUrl = $url . '/';
        $this->baseProtocol = array_key_exists('scheme', $parsedUrl) ? $parsedUrl['scheme'] : 'http';
        $this->baseDomain = array_key_exists('host', $parsedUrl) ? $parsedUrl['host'] : '';
        $this->basePort = array_key_exists('port', $parsedUrl) ? $parsedUrl['port'] : 'false';
    }

    public function getRoutePayload($group = false)
    {
        return RoutePayload::compile($this->router, $group);
    }

    protected function makeDirectory($path)
    {
        if (! $this->files->isDirectory(dirname($path))) {
            $this->files->makeDirectory(dirname($path), 0777, true, true);
        }
        return $path;
    }
}
