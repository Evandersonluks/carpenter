<?php

namespace Evandersonluks\Carpenter;

use Evandersonluks\Carpenter\Generators\ContentFileWriter;
use Evandersonluks\Carpenter\Generators\InitialContentGenerator;
use Carbon\Carbon;
use Illuminate\Support\Str;

class EntityBuilder
{   
    public function __construct(
        private $builder = []
    ){}
    
    public function init(): array
    {
        $count = 1;
        foreach ($this->builder as $key => $props) {
            [$entity, $entityTranslated] = explode('|', $key);
            $entityFields = $props['fields'];
            $pivot = $props['pivot'] ?? null;
            $pluralEntityTralslated = Str::plural($entityTranslated);

            $this->pluralize($entityTranslated, $pluralEntityTralslated);

            $this->generateFiles($entity, $entityTranslated, $pluralEntityTralslated, $entityFields, $pivot, $count);
            $this->filesWriter($entity, $pluralEntityTralslated, $entityFields);
            $count++;
        }

        return $this->builder;
    }

    public function generateFiles(string $entity, string $entityTranslated, string $pluralEntityTralslated, array $entityFields, mixed $pivot, int $count): void
    {
        $files = ['Model', 'Migration', 'Repository', 'Request', 'Controller', 'Policy', 'ViewComposer', 'Frontend', 'Translate'];

        foreach ($files as $file) {
            switch ($file) {
                case 'Model':
                    $path = file_exists("app/Models/$entity.php") ? null : "app/Models/$entity.php";
                    break;

                case 'Migration':
                    if ($pivot) {
                        $path = [
                            'migration' => glob("database/migrations/*_create_" . Str::lower(Str::plural(Str::snake($entity))) . "_table.php*")
                                ? null
                                : "database/migrations/" . Carbon::now()->addSecond($count)->format('Y_m_d_His') . "_create_" . Str::lower(Str::plural(Str::snake($entity))) . "_table.php",
                            'pivot' => glob("database/migrations/*_create_" . Str::lower(Str::snake($entity)) . '_' . $pivot . "_table.php*")
                                ? null
                                : "database/migrations/" . Carbon::now()->addSecond($count)->format('Y_m_d_His') . "_create_" . Str::lower(Str::snake($entity)) . '_' . $pivot . "_table.php",
                        ];
                    } else {
                        $path = glob("database/migrations/*_create_" . Str::lower(Str::plural(Str::snake($entity))) . "_table.php*")
                            ? null
                            : "database/migrations/" . Carbon::now()->addSecond($count)->format('Y_m_d_His') . "_create_" . Str::lower(Str::plural(Str::snake($entity))) . "_table.php";
                    }

                    break;

                case 'Repository':
                    $path = file_exists('app/Repositories/' . $entity . 'Repository.php') ? null : 'app/Repositories/' . $entity . 'Repository.php';
                    break;

                case 'Controller':
                    $path = file_exists('app/Http/Controllers/' . $entity . 'Controller.php') ? null : 'app/Http/Controllers/' . $entity . 'Controller.php';
                    break;

                case 'Request':
                    $path = file_exists('app/Http/Requests/Save' . $entity . 'Request.php') ? null : 'app/Http/Requests/Save' . $entity . 'Request.php';
                    break;

                case 'Policy':
                    $path = file_exists('app/Policies/' . $entity . 'Policy.php') ? null : 'app/Policies/' . $entity . 'Policy.php';
                    break;

                case 'ViewComposer':
                    $path = file_exists('app/Http/ViewComposers/' . $entity . 'ViewComposer.php')
                        ? null : (count(array_filter($entityFields, function($el) { return (explode('|', $el['type'])[1] == 'select'); })) < 1
                        ? null : 'app/Http/ViewComposers/' . $entity . 'ViewComposer.php');
                    break;

                case 'Frontend':
                    if ( file_exists('resources/views/' . Str::lower(Str::snake(Str::plural($entity)))) ) {
                        $path = null;
                    } else {
                        mkdir('resources/views/' . Str::lower(Str::snake(Str::plural($entity))), 775);
                        $path = [
                            'create' => 'resources/views/' . Str::lower(Str::snake(Str::plural($entity))) . '/create.blade.php',
                            'edit' => 'resources/views/' . Str::lower(Str::snake(Str::plural($entity))) . '/edit.blade.php',
                            'filters' => 'resources/views/' . Str::lower(Str::snake(Str::plural($entity))) . '/filters.blade.php',
                            'form' => 'resources/views/' . Str::lower(Str::snake(Str::plural($entity))) . '/form.blade.php',
                            'index' => 'resources/views/' . Str::lower(Str::snake(Str::plural($entity))) . '/index.blade.php',
                        ];
                    }
                    break;

                default:
                    $path = file_exists('lang/pt-BR/' . Str::kebab(Str::lower($entity)) . '.php')
                    ? null : 'lang/pt-BR/' . Str::kebab(Str::lower($entity)) . '.php';
                    break;
            }

            $method = Str::camel('get-' . $file . '-content');
            $content = (new InitialContentGenerator)->$method($entity, $entityTranslated, $pluralEntityTralslated, $entityFields, $pivot);

            if (is_array($path)) {
                foreach ($path as $key => $value) {
                    if ($value) {
                        $arquivo = fopen($value, 'w');
                        fwrite($arquivo, $content[$key]);
                        fclose($arquivo);
                    }
                }

                continue;
            } else if ($path) {
                $arquivo = fopen($path, 'w');
                fwrite($arquivo, $content);
                fclose($arquivo);
            }
        }
    }

    public function filesWriter(string $entity, string $pluralEntityTralslated, array $entityFields): mixed
    {
        $files = ['Admin', 'Routes', 'Providers', 'Permissions', 'Translations'];

        try {
            foreach ($files as $key => $file) {
                $method = Str::camel('writeTo' . $file);
                (new ContentFileWriter)->$method($entity, $pluralEntityTralslated, $entityFields);
            }

            return true;
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    public function pluralize(&$entityTranslated, &$pluralEntityTralslated): void
    {
        if (substr($entityTranslated, -2) == 'ao') {
            $pluralEntityTralslated = substr($entityTranslated, 0, -2) . 'ões';
            $entityTranslated = substr($entityTranslated, 0, -2) . 'ão';
        } elseif (substr($entityTranslated, -2) == 'al' || substr($entityTranslated, -2) == 'el' || substr($entityTranslated, -2) == 'il' || substr($entityTranslated, -2) == 'ol') {
            if (substr($entityTranslated, -2) == 'il') {
                $pluralEntityTralslated = substr($entityTranslated, 0, -1) . 's';
            } else {
                $pluralEntityTralslated = substr($entityTranslated, 0, -1) . 'is';
            }
        } elseif (substr($entityTranslated, -2) == 'er' || substr($entityTranslated, -2) == 'ir' || substr($entityTranslated, -2) == 'or') {
            $pluralEntityTralslated = $entityTranslated . 'es';
        }
    }
}
