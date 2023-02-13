<?php

namespace Evandersonluks\Carpenter\Generators;

use Illuminate\Support\Str;

class InitialContentGenerator
{

public function getModelContent(string $entity, string $entityTranslated, string $pluralEntityTranslated, array $entityFields): string
{
$content = '';
foreach ($entityFields as $key => $name) {
    $content .= " '" . explode('|', $key)[0] . "',";
}
return
"<?php

namespace App\Models;

class $entity extends Model
{
    /**
     * List of headers for the admin listing table.
     *
     * @return array
     */
    public function getAdminColumns()
    {
        return ['id'," . $content . " 'created_at'];
    }

    /**
     * List of headers for the admin listing table.
     *
     * @return array
     */
    public function getOrderColumns()
    {
        return ['id', 'created_at'];
    }
}
";
}

public function getMigrationContent(string $entity, string $entityTranslated, string $pluralEntityTranslated, array $entityFields, string|null $pivot = null): string|array
{
$content = '';
$tableName = Str::lower(Str::plural(Str::snake($entity)));
foreach ($entityFields as $name => $props) {
    $type = explode('|', $props['type'])[0];
    $content .= "\$table->" . $type . "('" . explode('|', $name)[0] . "')";
    if (isset($props['migration'])) {
        foreach ($props['migration'] as $key => $value) {
            $content .= '->' . $value;
        }
    }
    $content .= ";\n            ";
}
$content .= "\$table->timestamps();";

$initialContent =
"<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('" . $tableName . "', function (Blueprint \$table) {
            \$table->id();
            " . $content . "
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('" . Str::lower(Str::plural($entity)) . "');
    }
};
";

if ($pivot) {
$arrayContent['migration'] = $initialContent;
$arrayContent['pivot'] =
"<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('" . Str::lower(Str::snake($entity)) . '_' . $pivot . "', function (Blueprint \$table) {
            \$table->id();
            \$table->foreignId('" . Str::lower(Str::snake($entity)) . "_id')->constrained();
            \$table->foreignId('" . Str::singular($pivot) . "_id')->constrained();
            \$table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('" . Str::lower(Str::snake($entity)) . '_' . $pivot . "');
    }
};";

return $arrayContent;
}

return $initialContent;
}

public function getRepositoryContent(string $entity, string $entityTranslated, string $pluralEntityTranslated, array $entityFields): string
{
$columnLabel = explode('|', key($entityFields))[0];
return
"<?php

namespace App\Repositories;

use App\Models\\$entity;

use App\Repositories\Concerns\WithSelectOptions;

class " . $entity . "Repository extends CrudRepository
{
    use WithSelectOptions;

    /**
     * Type of the resource to manage.
     *
     * @var string
     */
    protected \$resourceType = $entity::class;

    /**
     * Return the resource main column.
     *
     * @return string
     */
    public function getResourceLabel()
    {
        return '$columnLabel';
    }
}
";
}

public function getRequestContent(string $entity, string $entityTranslated, string $pluralEntityTranslated, array $entityFields): string
{
$content = '';
$merge = '';
foreach ($entityFields as $name => $props) {
    $nextContent = next($entityFields)['type'] ?? null;
    $column = explode('|', $name)[0];
    if (isset($props['request'])) {
        if (isset($props['migration'])) {
            $content .= "'" . $column . "' => [";
        } else {
            $content .= "'" . $column . "' => ['required', ";
        }
        foreach ($props['request'] as $key => $value) {
            if ($value == 'boolean') {
                if ($nextContent && (explode("|", $nextContent)[0] == 'boolean' || explode("|", $nextContent)[0] == 'date_format:Y-m-d')) {
                    $merge .= "'" . $column . "' => \$this->$column == 'false' ? false : true,\n";
                } else {
                    $merge .= "'" . $column . "' => \$this->$column == 'false' ? false : true,";
                }
            }
            if ($value == 'date_format:Y-m-d') {
                if ($nextContent && (explode("|", $nextContent)[0] == 'boolean' || explode("|", $nextContent)[0] == 'date_format:Y-m-d')) {
                    $merge .= "'$column' => \$this->$column ? Carbon::createFromFormat('d/m/Y', \$this->$column)->format('Y-m-d') : null,\n";
                } else {
                    $merge .= "'$column' => \$this->$column ? Carbon::createFromFormat('d/m/Y', \$this->$column)->format('Y-m-d') : null,";
                }
            }
            if ($key < 1) {
                $content .= "'$value'";
            } else {
                $content .= ", '$value'";
            }
        }
        if (!empty(next($entityFields))) {
            $content .= "],\n            ";
        } else {
            $content .= "],";
        }
        continue;
    }
    if (!empty(next($entityFields))) {
        $content .= "'" . $column . "' => ['required'],\n            ";
    } else {
        $content .= "'" . $column . "' => ['required'],";
    }
}
return
"<?php

namespace App\Http\Requests;

use App\Models\\$entity;
use Illuminate\Validation\Rule;

class Save" . $entity . "Request extends CrudRequest
{
    /**
     * Type of class being validated.
     *
     * @var string
     */
    protected \$type = $entity::class;

    protected function prepareForValidation()
    {
        \$this->merge([
            " . $merge . "
        ]);
    }

    /**
     * Base rules for both creating and editing the resource.
     *
     * @return array
     */
    public function baseRules()
    {
        return [
            " . $content . "
        ];
    }
}
";
}

public function getControllerContent(string $entity): string
{
return
"<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\Save" . $entity . "Request;
use App\Models\\$entity;
use App\Repositories\\" . $entity . "Repository;

class " . $entity . "Controller extends CrudController
{
    /**
     * Type of the resource to manage.
     *
     * @var string
     */
    protected \$resourceType = $entity::class;

    /**
     * Type of the managing repository.
     *
     * @var string
     */
    protected \$repositoryType = " . $entity . "Repository::class;

    /**
     * Returns the request that should be used to validate.
     *
     * @return Request
     */
    protected function formRequest()
    {
        return app(Save" . $entity . "Request::class);
    }
}
";
}

public function getPolicyContent(string $entity): string
{
return
"<?php

namespace App\Policies;

use App\Policies\Concerns\CreateResource;
use App\Policies\Concerns\DeleteResource;
use App\Policies\Concerns\ListResource;
use App\Policies\Concerns\ManageResource;
use App\Policies\Concerns\RestoreResource;
use App\Policies\Concerns\UpdateResource;
use App\Policies\Concerns\ViewResource;
use Illuminate\Auth\Access\HandlesAuthorization;

class " . $entity . "Policy
{
    use HandlesAuthorization;
    use ManageResource;
    use ListResource;
    use ViewResource;
    use CreateResource;
    use UpdateResource;
    use DeleteResource;
    use RestoreResource;

    protected \$resource = '" . Str::lower(Str::plural($entity)) . "';
}
";
}

public function getTranslateContent(string $entity, string $entityTranslated, string $pluralEntityTranslated, array $entityFields): string
{
$translation = '';
foreach ($entityFields as $key => $name) {
    [$column, $columnTranslated] = explode('|', $key);
    if (!empty(next($entityFields))) {
        $translation .= "        '$column' => '" . $columnTranslated . "',\n";
    } else {
        $translation .= "        '$column' => '" . $columnTranslated . "',";
    }
}
return
"<?php

return [
    'attributes' => [
        'id' => 'ID',
" . $translation . "
    ],
    'actions' => [
        'label' => '" . $pluralEntityTranslated . "',
        'index' => 'Lista de " . $pluralEntityTranslated . "',
        'create' => 'Criar $entityTranslated',
        'edit' => 'Editar $entityTranslated',
        'delete' => 'Excluir $entityTranslated',
        'restore' => 'Restaurar $entityTranslated',
    ],
];
";
}

public function getViewComposerContent(string $entity, string $entityTranslated, string $pluralEntityTranslated, array $entityFields): string
{
$content = '';
$imports = '';
$importList = [];
foreach ($entityFields as $name => $props) {
    $isSelectable = explode('|', $props['type'])[1] == 'select';
    $nextContent = next($entityFields)['type'] ?? null;
    if ($isSelectable) {
        if ($nextContent) {
            $nextIsSelectable = explode('|', $nextContent)[1] == 'select';
            if ($nextIsSelectable) {
                if (Str::contains($name, '_id')) {
                    $foreignTable = Str::of($name)->beforeLast('_id');
                    if (!in_array("use App\Repositories\\" . Str::ucfirst($foreignTable) . "Repository;", $importList)) {
                        $imports .= "use App\Repositories\\" . Str::ucfirst($foreignTable) . "Repository;\n";
                        array_push($importList, "use App\Repositories\\" . Str::ucfirst($foreignTable) . "Repository;");
                    }
                    $content .= "\$view->with('" . $foreignTable . "Options', (new " . Str::ucfirst($foreignTable) . "Repository())->selectOptions());\n        ";
                } else {
                    $column = explode('|', $name)[0];
                    if (!in_array("use App\Models\\" . $entity . ";", $importList)) {
                        $imports .= "use App\Models\\" . $entity . ";\n";
                        array_push($importList, "use App\Models\\" . $entity . ";");
                    }
                    $content .= "\$view->with('" . Str::camel($column) . "Options', $entity::query()->distinct('$column')->pluck('$column', '$column'));\n        ";
                }
            } else {
                if (Str::contains($name, '_id')) {
                    $foreignTable = Str::of($name)->beforeLast('_id');
                    if (!in_array("use App\Repositories\\" . Str::ucfirst($foreignTable) . "Repository;", $importList)) {
                        $imports .= "use App\Repositories\\" . Str::ucfirst($foreignTable) . "Repository;";
                        array_push($importList, "use App\Repositories\\" . Str::ucfirst($foreignTable) . "Repository;");
                    }
                    $content .= "\$view->with('" . $foreignTable . "Options', (new " . Str::ucfirst($foreignTable) . "Repository())->selectOptions());";
                } else {
                    $column = explode('|', $name)[0];
                    if (!in_array("use App\Models\\" . $entity . ";", $importList)) {
                        $imports .= "use App\Models\\" . $entity . ";";
                        array_push($importList, "use App\Models\\" . $entity . ";");
                    }
                    $content .= "\$view->with('" . Str::camel($column) . "Options', $entity::query()->distinct('$column')->pluck('$column', '$column'));";
                }
            }
            continue;
        }
        if (Str::contains($name, '_id')) {
            $foreignTable = Str::of($name)->beforeLast('_id');
            if (in_array("use App\Repositories\\" . Str::ucfirst($foreignTable) . "Repository;", $importList)) {

            }
            array_push($importList, "use App\Repositories\\" . Str::ucfirst($foreignTable) . "Repository;");
            $imports .= "use App\Repositories\\" . Str::ucfirst($foreignTable) . "Repository;";
            $content .= "\$view->with('" . $foreignTable . "Options', (new " . Str::ucfirst($foreignTable) . "Repository())->selectOptions());";
        } else {
            $column = explode('|', $name)[0];
            if (in_array("use App\Models\\" . $entity . ";", $importList)) {

            }
            array_push($importList, "use App\Models\\" . $entity . ";");
            $imports .= "use App\Models\\" . $entity . ";";
            $content .= "\$view->with('" . Str::camel($column) . "Options', $entity::query()->distinct('$column')->pluck('$column', '$column'));";
        }
    }
}
return
"<?php

namespace App\Http\ViewComposers;

" . $imports . "
use Illuminate\View\View;

class " . $entity . "ViewComposer
{
    /**
     * Bind data to the view.
     *
     * @param  View  \$view
     * @return void
     */
    public function compose(View \$view)
    {
        " . $content . "
    }
}
";
}

public function getFrontendContent(string $entity, string $entityTranslated, string $pluralEntityTranslated, array $entityFields): array
{
$formInputs = '';
$filterInputs = '';
foreach ($entityFields as $key => $props) {
    [$column, $columnTranslated] = explode('|', $key);
    $filter = explode('|', $props['type'])[1];

    if (!empty(next($entityFields))) {
        $filterInputs .= $this->filterInputsBuilder($column, $columnTranslated)[$filter] . "\n";
        $formInputs .= $this->formInputsBuilder($column, $columnTranslated)[$filter] . "\n";
    } else {
        $filterInputs .= $this->filterInputsBuilder($column, $columnTranslated)[$filter];
        $formInputs .= $this->formInputsBuilder($column, $columnTranslated)[$filter];
    }
}

$content = [];

$content['create'] = "<x-app-layout>
    @include('" . Str::lower(Str::plural($entity)) . ".form')
    @include('layouts.partials.crud.create')
</x-app-layout>";

$content['edit'] = "<x-app-layout>
    @include('" . Str::lower(Str::plural($entity)) . ".form')
    @include('layouts.partials.crud.edit')
</x-app-layout>";

$content['filters'] = '@section(\'filters\')
    <div class="grid grid-cols-12 gap-4 lg:gap-8 p-4 lg:p-6 px-4">
' . $filterInputs . '
        <div class="col-span-12 md:col-span-6 lg:col-span-4">
            @include(\'components.filters.created-at-start\')
        </div>
        <div class="col-span-12 md:col-span-6 lg:col-span-4">
            @include(\'components.filters.created-at-end\')
        </div>

        <div class="col-span-12 flex items-center space-x-4">
            <x-submit-filters>
                {{ __(\'Filtrar\') }}
            </x-submit-filters>
            <a class="whitespace-nowrap dark:text-slate-300" href="{{ $instance->route(\'index\') }}">Limpar filtros</a>
        </div>
    </div>
@stop';

$content['form'] = '@section(\'form\')

@php(html()->model($instance))

    <x-card class="mb-8" title="Dados básicos">
        <div class="grid grid-cols-12 gap-x-4 md:gap-x-6 lg:gap-x-8">
' . $formInputs . '
        </div>
    </x-card>

    <x-primary-button class="rounded">
        {{ __(\'Salvar\') }}
    </x-primary-button>

@php(html()->endModel())

@endsection';

$content['index'] = "<x-app-layout>
    @include('" . Str::lower(Str::plural($entity)) . ".filters')
    @include('layouts.partials.crud.index')
</x-app-layout>";

return $content;
}

public function formInputsBuilder(string $column, string $translatedName): array
{
    return [
        'string'=>
        '            <div class="col-span-12 lg:col-span-6 mb-4">
                <x-input-text class="h-8 w-full" name="' . $column . '" :type="$type" />
            </div>',
        'select' =>
        '            <div class="col-span-12 lg:col-span-6 mb-4">
                <x-select :options="$' . Str::of($column)->beforeLast('_id')->camel() . 'Options" name="' . $column . '" :type="$type" :value="$instance->' . $column . ' ?? \'\'" />
            </div>',
        'date' =>
        '            <div class="col-span-12 lg:col-span-6 mb-4">
                <x-input-date name="' . $column . '" class="h-8 w-full" :type="$type" value="{{ $instance->' . $column . '?->format(\'d/m/Y\') }}" />
            </div>',
        'boolean' =>
        '            <div class="col-span-4 md:col-span-3 lg:col-span-2 mb-5">
                <x-input-switch name="' . $column . '" :type="$type" :checked="$instance->' . $column . ' ?? false" label="' . Str::ucfirst($translatedName) . '" />
            </div>',
        'text' =>
        '            <div class="col-span-12 mb-4">
                <x-text-area name="' . $column . '" :type="$type" value="{{ $instance->' . $column . ' }}"></x-text-area>
            </div>',
    ];
}

public function filterInputsBuilder(string $column, string $translatedName): array
{
    return [
        'string'=>
        '        <div class="col-span-12 md:col-span-6 lg:col-span-6">
            @include(\'components.filters.text-contains\', [
                \'name\' => \'' . $column . '\'
            ])
        </div>',
        'select' =>
        '        <div class="col-span-12 md:col-span-6 lg:col-span-4">
            @include(\'components.filters.select-equals\', [
                \'placeholder\' => \'' . Str::ucfirst($translatedName) . '\',
                \'name\' => \'' . $column . '\',
                \'options\' => $' . Str::of($column)->beforeLast('_id')->camel() . 'Options
            ])
        </div>',
        'select2' =>
        '        <div class="col-span-12 md:col-span-6 lg:col-span-4">
            @include(\'components.filters.select2-equals\', [
                \'placeholder\' => \'' . Str::ucfirst($translatedName) . '\',
                \'name\' => \'' . $column . '\',
                \'options\' => $' . Str::of($column)->beforeLast('_id')->camel() . 'Options
            ])
        </div>',
        'date' => '',
        'boolean' =>
        '        <div class="col-span-12 md:col-span-6 lg:col-span-4">
            @include(\'components.filters.select-equals\', [
                \'placeholder\' => \'' . Str::ucfirst($translatedName) . '\',
                \'name\' => \'' . $column . '\',
                \'options\' => collect(["Falso", "Verdadeiro"])
            ])
        </div>',
        'text' => '',
    ];
}
}
