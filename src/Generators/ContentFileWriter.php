<?php

namespace Evandersonluks\Carpenter\Generators;

use Illuminate\Support\Str;

class ContentFileWriter
{
    private const MENU = 'config/admin.php';
    private const ROUTES = 'routes/web.php';
    private const AUTH_PROVIDERS = 'app/Providers/AuthServiceProvider.php';
    private const VIEW_PROVIDERS = 'app/Providers/ViewComposerServiceProvider.php';
    private const PERMISSIONS = 'database/seeders/PermissionSeeder.php';
    private const TRANSLATIONS = 'lang/pt-BR/';

    public function writeToAdmin(string $entity, string $pluralEntityTranslated): void
    {
        $fileContent = file_get_contents(self::MENU, true);

        if (!Str::contains($fileContent, "'policy' => ['list', 'App\\\\Models\\\\$entity'],")) {
            $lineNumber = count(file(self::MENU)) - 39;
            $route = 'web.' . Str::lower(Str::snake(Str::plural($entity))) . '.index';

            $lineContent = "                [
                    'label' => '$pluralEntityTranslated',
                    'route' => '$route',
                    'icon' => 'fa-brands fa-laravel',
                    'policy' => ['list', 'App\\\\Models\\\\$entity'],
                ],";

            $this->writer(self::MENU, $lineNumber, $lineContent);
        }
    }

    public function writeToRoutes(string $entity): void
    {
        $fileContent = file_get_contents(self::ROUTES, true);
        $name = Str::lower(Str::snake(Str::plural($entity)));

        if (!Str::contains($fileContent, "Route::get('/$name', [" . $entity . "Controller::class, 'index'])->name('web.$name.index');")) {
            $lineNumber = count(file(self::ROUTES)) - 3;
            $captName = Str::plural($entity);

            $lineContent = "
    // $captName
    Route::get('/$name', [" . $entity . "Controller::class, 'index'])->name('web.$name.index');
    Route::get('/$name/create', [" . $entity . "Controller::class, 'create'])->name('web.$name.create');
    Route::post('/$name', [" . $entity . "Controller::class, 'store'])->name('web.$name.store');
    Route::get('/$name/{id}/edit', [" . $entity . "Controller::class, 'edit'])->name('web.$name.edit');
    Route::put('/$name/{id}/update', [" . $entity . "Controller::class, 'update'])->name('web.$name.update');
    Route::delete('/$name/{id}', [" . $entity . "Controller::class, 'delete'])->name('web.$name.delete');";

            $this->writer(self::ROUTES, $lineNumber, $lineContent);

            $lineContent = "use App\Http\Controllers\\" . $entity . "Controller;";
            $this->writer(self::ROUTES, 2, $lineContent);
        }

    }

    public function writeToProviders(string $entity, string $pluralEntityTranslated, array $entityFields): void
    {
        $fileContent = file_get_contents(self::AUTH_PROVIDERS, true);

        if (!Str::contains($fileContent, "'App\\\\Models\\\\$entity' => 'App\\\\Policies\\\\" . $entity . "Policy',")) {
            $lineNumber = count(file(self::AUTH_PROVIDERS)) - 14;
            $lineContent = "        'App\\\\Models\\\\$entity' => 'App\\\\Policies\\\\" . $entity . "Policy',";

            $this->writer(self::AUTH_PROVIDERS, $lineNumber, $lineContent);
        }

        $fileContent = file_get_contents(self::VIEW_PROVIDERS, true);

        if ((count(array_filter($entityFields, function($el) { return (explode('|', $el['type'])[1] == 'select'); })) > 0)) {
            if (!Str::contains($fileContent, 'use App\Http\ViewComposers\\' . $entity . 'ViewComposer;')) {
                $lineNumber = count(file(self::VIEW_PROVIDERS)) - 11;
                $lineContent = "        View::composer('" . Str::lower(Str::snake(Str::plural($entity))) . ".*', " . $entity . "ViewComposer::class);";

                $this->writer(self::VIEW_PROVIDERS, $lineNumber, $lineContent);
                $this->writer(self::VIEW_PROVIDERS, 4, 'use App\Http\ViewComposers\\' . $entity . 'ViewComposer;');
            }
        }
    }

    public function writeToPermissions(string $entity): void
    {
        $fileContent = file_get_contents(self::PERMISSIONS, true);
        $name = Str::lower(Str::snake(Str::plural($entity)));

        if (!Str::contains($fileContent, "'$name' => [],")) {
            $lineNumber = count(file(self::PERMISSIONS)) - 29;

            $lineContent = "            '$name' => [],";

            $this->writer(self::PERMISSIONS, $lineNumber, $lineContent);
        }
    }

    public function writeToTranslations(string $entity, string $pluralEntityTranslated): void
    {
        $modelFileContent = file_get_contents(self::TRANSLATIONS . 'models.php', true);
        $name = Str::lower($entity);

        if (!Str::contains($modelFileContent, "'App\\\\Models\\\\$entity' => require_once(base_path('lang/pt-BR/$name.php')),")) {
            $lineNumber = count(file(self::TRANSLATIONS . 'models.php')) - 1;
            $lineContent = "    'App\\\\Models\\\\$entity' => require_once(base_path('lang/pt-BR/$name.php')),";

            $this->writer(self::TRANSLATIONS . 'models.php', $lineNumber, $lineContent);
        }

        $defaultFileContent = file_get_contents(self::TRANSLATIONS . 'default.php', true);

        if (!Str::contains($defaultFileContent, "'" . Str::plural($name) . "' => '$pluralEntityTranslated',")) {
            $lineNumber = count(file(self::TRANSLATIONS . 'default.php')) - 24;
            $name = Str::lower($entity);
            $lineContent = "        '" . Str::plural($name) . "' => '$pluralEntityTranslated',";

            $this->writer(self::TRANSLATIONS . 'default.php', $lineNumber, $lineContent);
        }
    }

    public function writer(string $file, string $lineNumber, string $lineContent): void
    {
        $lines = file($file);
        $final_array = array_splice($lines, $lineNumber);
        $lines[] = $lineContent . "\n";
        $lines = array_merge($lines, $final_array);
        file_put_contents($file, $lines);
    }
}
