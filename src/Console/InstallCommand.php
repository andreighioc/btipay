<?php

namespace AndreiGhioc\BtiPay\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class InstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'BtiPay:install
                            {--force : Overwrite existing files}
                            {--controller : Publish only the controller}
                            {--routes : Publish only the routes}
                            {--views : Publish only the views}';

    /**
     * The console command description.
     */
    protected $description = 'Install the BT iPay payment package (controller, routes, views, config, migrations)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('');
        $this->info('  ╔══════════════════════════════════════════╗');
        $this->info('  ║      BT iPay - Laravel Package           ║');
        $this->info('  ║      Banca Transilvania                  ║');
        $this->info('  ╚══════════════════════════════════════════╝');
        $this->info('');

        $publishAll = ! $this->option('controller')
                   && ! $this->option('routes')
                   && ! $this->option('views');

        $force = $this->option('force');

        // 1. Config
        if ($publishAll) {
            $this->callSilently('vendor:publish', [
                '--tag'   => 'BtiPay-config',
                '--force' => $force,
            ]);
            $this->components->info('Config [config/BtiPay.php] published.');
        }

        // 2. Migrations
        if ($publishAll) {
            $this->callSilently('vendor:publish', [
                '--tag'   => 'BtiPay-migrations',
                '--force' => $force,
            ]);
            $this->components->info('Migrations published.');
        }

        // 3. Controller
        if ($publishAll || $this->option('controller')) {
            $this->publishController($force);
        }

        // 4. Routes
        if ($publishAll || $this->option('routes')) {
            $this->publishRoutes($force);
        }

        // 5. Views
        if ($publishAll || $this->option('views')) {
            $this->publishViews($force);
        }

        $this->info('');
        $this->components->info('BT iPay package installed successfully!');
        $this->info('');
        $this->line('  <fg=yellow>Next steps:</>');
        $this->line('  1. Set your credentials in <fg=cyan>.env</>:');
        $this->line('     BTIPAY_USERNAME=your_api_user');
        $this->line('     BTIPAY_PASSWORD=your_api_password');
        $this->line('     BTIPAY_RETURN_URL=https://your-site.ro/BtiPay/finish');
        $this->line('  2. Run <fg=cyan>php artisan migrate</>');
        $this->line('  3. Include the routes in your app (see routes/BtiPay.php)');
        $this->line('  4. Customize the controller and views as needed');
        $this->info('');

        return self::SUCCESS;
    }

    /**
     * Publish the payment controller.
     */
    protected function publishController(bool $force): void
    {
        $stubPath = __DIR__ . '/../../stubs/BtiPayController.php.stub';
        $targetPath = app_path('Http/Controllers/BtiPayController.php');

        if (File::exists($targetPath) && ! $force) {
            $this->components->warn('Controller already exists. Use --force to overwrite.');

            return;
        }

        File::ensureDirectoryExists(app_path('Http/Controllers'));
        File::copy($stubPath, $targetPath);

        $this->components->info('Controller [app/Http/Controllers/BtiPayController.php] created.');
    }

    /**
     * Publish the routes file.
     */
    protected function publishRoutes(bool $force): void
    {
        $stubPath = __DIR__ . '/../../stubs/BtiPay-routes.php.stub';
        $targetPath = base_path('routes/BtiPay.php');

        if (File::exists($targetPath) && ! $force) {
            $this->components->warn('Routes file already exists. Use --force to overwrite.');

            return;
        }

        File::ensureDirectoryExists(base_path('routes'));
        File::copy($stubPath, $targetPath);

        $this->components->info('Routes [routes/BtiPay.php] created.');
        $this->line('');
        $this->line('  <fg=yellow>Register the routes</> in <fg=cyan>bootstrap/app.php</> (Laravel 11+):');
        $this->line('');
        $this->line("    ->withRouting(");
        $this->line("        web: __DIR__.'/../routes/web.php',");
        $this->line("        then: function () {");
        $this->line("            require base_path('routes/BtiPay.php');");
        $this->line("        },");
        $this->line("    )");
        $this->line('');
        $this->line('  Or in <fg=cyan>routes/web.php</> add:');
        $this->line("    require __DIR__.'/BtiPay.php';");
    }

    /**
     * Publish the blade views.
     */
    protected function publishViews(bool $force): void
    {
        $stubsDir = __DIR__ . '/../../stubs/views';
        $targetDir = resource_path('views/BtiPay');

        if (File::isDirectory($targetDir) && ! $force) {
            $this->components->warn('Views directory already exists. Use --force to overwrite.');

            return;
        }

        File::ensureDirectoryExists($targetDir);

        $views = ['pay.blade.php', 'finish.blade.php'];

        foreach ($views as $view) {
            File::copy("{$stubsDir}/{$view}.stub", "{$targetDir}/{$view}");
        }

        $this->components->info('Views [resources/views/BtiPay/] created.');
    }
}
