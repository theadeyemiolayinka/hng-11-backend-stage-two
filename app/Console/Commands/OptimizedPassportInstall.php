<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Laravel\Passport\Passport;

class OptimizedPassportInstall extends Command
{
    protected $signature = 'passport:optimized-install';
    protected $description = 'Optimized installation of Laravel Passport without republishing migrations';

    public function handle()
    {
        $migrationPatterns = [
            '*_create_oauth_auth_codes_table.php',
            '*_create_oauth_access_tokens_table.php',
            '*_create_oauth_refresh_tokens_table.php',
            '*_create_oauth_clients_table.php',
            '*_create_oauth_personal_access_clients_table.php',
        ];

        $migrationsPath = database_path('migrations');
        $migrationsExist = true;

        foreach ($migrationPatterns as $pattern) {
            if (empty(glob($migrationsPath . '/' . $pattern))) {
                $migrationsExist = false;
                break;
            }
        }

        if (!$migrationsExist) {
            $this->info('Passport migrations do not exist. Publishing migrations...');
            Artisan::call('vendor:publish', ['--tag' => 'passport-migrations']);
            config(['passport.client_uuids' => true]);

            Passport::setClientUuids(true);

            $this->replaceInFile(config_path('passport.php'), '\'client_uuids\' => false', '\'client_uuids\' => true');
            $this->replaceInFile(database_path('migrations/*_create_oauth_auth_codes_table.php'), '$table->unsignedBigInteger(\'client_id\');', '$table->uuid(\'client_id\');');
            $this->replaceInFile(database_path('migrations/*_create_oauth_access_tokens_table.php'), '$table->unsignedBigInteger(\'client_id\');', '$table->uuid(\'client_id\');');
            $this->replaceInFile(database_path('migrations/*_create_oauth_clients_table.php'), '$table->bigIncrements(\'id\');', '$table->uuid(\'id\')->primary();');
            $this->replaceInFile(database_path('migrations/*_create_oauth_personal_access_clients_table.php'), '$table->unsignedBigInteger(\'client_id\');', '$table->uuid(\'client_id\');');
        } else {
            $this->info('Passport migrations already exist. Skipping publishing migrations...');
        }

        $this->info('Installing Passport...');

        Artisan::call('passport:keys', ['--force' => false, '--no-interaction' => true]);

        $provider = in_array('users', array_keys(config('auth.providers'))) ? 'users' : null;

        $this->call('passport:client', ['--personal' => true, '--name' => config('app.name') . ' Personal Access Client']);
        $this->call('passport:client', ['--password' => true, '--name' => config('app.name') . ' Password Grant Client', '--provider' => $provider]);

        $this->info('Passport installed successfully.');
    }

    /**
     * Replace a given string in a given file.
     *
     * @param  string  $path
     * @param  string  $search
     * @param  string  $replace
     * @return void
     */
    protected function replaceInFile($path, $search, $replace)
    {
        foreach (glob($path) as $file) {
            file_put_contents(
                $file,
                str_replace($search, $replace, file_get_contents($file))
            );
        }
    }
}
