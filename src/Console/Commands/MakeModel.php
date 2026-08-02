<?php

namespace VivereStage\LaravelApiFeeds\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class MakeModel extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'viverestage:api:make:model
                            {name : The name of the model}
                            {--force : Overwrite the file if it already exists}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Make new VivereStage API Feed model';

    /**
     * Execute the console command.
     */
    public function handle(Filesystem $files): int
    {
        $name = Str::studly($this->argument('name'));

        $namespace = config(
            'vivere-api-feeds.models.namespace',
            \App\Support\VivereStage\ApiFeeds::class
        );
        $namespace .= '\\Models';

        $directory = app_path(
            (string) str($namespace)->replaceFirst('App\\', '')->replace('\\', '/')
        );

        $path = $directory.'/'.$name.'.php';

        if ($files->exists($path) && ! $this->option('force')) {
            $this->components->error("Model [{$name}] already exists.");

            return self::FAILURE;
        }

        $stub = $files->get(__DIR__.'/../../../stubs/model.stub');

        $contents = str_replace(
            ['{{ namespace }}', '{{ class }}'],
            [$namespace, $name],
            $stub
        );

        $files->ensureDirectoryExists($directory);
        $files->put($path, $contents);

        $this->components->info("Model [{$path}] created successfully.");

        return self::SUCCESS;
    }
}
