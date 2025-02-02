<?php namespace Savannabits\JetstreamInertiaGenerator\Generators;

use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputOption;

class ViewForm extends ViewGenerator {

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'jig:generate:form';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate create and edit view templates';

    /**
     * Path for create view
     *
     * @var string
     */
    protected $create = 'create';

    /**
     * Path for edit view
     *
     * @var string
     */
    protected $show = 'show';

    /**
     * Path for form view
     *
     * @var string
     */
    protected $form = 'form';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $force = $this->option('force');

        //TODO check if exists
        //TODO make global for all generator
        //TODO also with prefix
        /*if(!empty($template = $this->option('template'))) {
            $this->create = 'templates.'.$template.'.create';
            $this->edit = 'templates.'.$template.'.edit';
            $this->form = 'templates.'.$template.'.form';
            $this->formRight = 'templates.'.$template.'form-right';
            $this->formJs = 'templates.'.$template.'.form-js';
        }*/

        if(!empty($belongsToMany = $this->option('belongs-to-many'))) {
            $this->setBelongToManyRelation($belongsToMany);
        }
        /*Make Create Form*/
        $viewPath = resource_path('js/Pages/'.$this->modelPlural.'/CreateForm.vue');
        if ($this->alreadyExists($viewPath) && !$force) {
            $this->error('File '.$viewPath.' already exists!');
        } else {
            if ($this->alreadyExists($viewPath) && $force) {
                $this->warn('File '.$viewPath.' already exists! File will be deleted.');
                $this->files->delete($viewPath);
            }
            $this->makeDirectory($viewPath);
            $this->files->put($viewPath, $this->buildForm("create-form"));
            $this->info('Generating '.$viewPath.' finished');
        }
        /*Make Create Page*/
        $viewPath = resource_path('js/Pages/'.$this->modelPlural.'/Create.vue');
        if ($this->alreadyExists($viewPath) && !$force) {
            $this->error('File '.$viewPath.' already exists!');
        } else {
            if ($this->alreadyExists($viewPath) && $force) {
                $this->warn('File '.$viewPath.' already exists! File will be deleted.');
                $this->files->delete($viewPath);
            }
            $this->makeDirectory($viewPath);
            $this->files->put($viewPath, $this->buildForm("create"));
            $this->info('Generating '.$viewPath.' finished');
        }
        //Make edit form
        $viewPath = resource_path('js/Pages/'.$this->modelPlural.'/EditForm.vue');
        if ($this->alreadyExists($viewPath) && !$force) {
            $this->error('File '.$viewPath.' already exists!');
        } else {
            if ($this->alreadyExists($viewPath) && $force) {
                $this->warn('File '.$viewPath.' already exists! File will be deleted.');
                $this->files->delete($viewPath);
            }
            $this->makeDirectory($viewPath);
            $this->files->put($viewPath, $this->buildForm("edit-form"));
            $this->info('Generating '.$viewPath.' finished');
        }

        //Make edit form
        $viewPath = resource_path('js/Pages/'.$this->modelPlural.'/Edit.vue');
        if ($this->alreadyExists($viewPath) && !$force) {
            $this->error('File '.$viewPath.' already exists!');
        } else {
            if ($this->alreadyExists($viewPath) && $force) {
                $this->warn('File '.$viewPath.' already exists! File will be deleted.');
                $this->files->delete($viewPath);
            }
            $this->makeDirectory($viewPath);
            $this->files->put($viewPath, $this->buildForm("edit"));
            $this->info('Generating '.$viewPath.' finished');
        }

        // Make Show Form
        $viewPath = resource_path('js/Pages/'.$this->modelPlural.'/Show.vue');
        if ($this->alreadyExists($viewPath) && !$force) {
            $this->error('File '.$viewPath.' already exists!');
        } else {
            if ($this->alreadyExists($viewPath) && $force) {
                $this->warn('File '.$viewPath.' already exists! File will be deleted.');
                $this->files->delete($viewPath);
            }
            $this->makeDirectory($viewPath);
            $this->files->put($viewPath, $this->buildShow());
            $this->info('Generating '.$viewPath.' finished');
        }
    }

    protected function isUsedTwoColumnsLayout() : bool {
        return false;
    }

    protected function buildForm($type=null) {
        $belongsTos = $this->setBelongsToRelations();
        $relatables = $this->getVisibleColumns($this->tableName, $this->modelVariableName)->filter(function($column) use($belongsTos) {
            return in_array($column['name'],$belongsTos->pluck('foreign_key')->toArray());
        })->keyBy('name');
        $columns = $this->getVisibleColumns($this->tableName, $this->modelVariableName)->reject(function($column) use($belongsTos) {
            return in_array($column['name'],$belongsTos->pluck('foreign_key')->toArray()) || $column["name"] ==='slug';
        })->map(function($column) {
            $column["label"] = str_replace("_"," ",Str::title($column['name']));
            return $column;
        })->keyBy('name');
        return view('jig::'.$type, [
            'modelBaseName' => $this->modelBaseName,
            'modelRouteAndViewName' => $this->modelRouteAndViewName,
            'modelPlural' => $this->modelPlural,
            'modelTitle' => $this->titleSingular,
            'modelDotNotation' => $this->modelDotNotation,
            'modelLangFormat' => $this->modelLangFormat,
            'columns' => $columns,
            "relatable" => $relatables,
            'hasTranslatable' => $this->readColumnsFromTable($this->tableName)->filter(function($column) {
                return $column['type'] == "json";
            })->count() > 0,
            'translatableTextarea' => ['perex', 'text', 'body'],
            'relations' => $this->relations,
        ])->render();
    }

    protected function buildShow() {

        $belongsTos = $this->setBelongsToRelations();
        $relatables = $this->getVisibleColumns($this->tableName, $this->modelVariableName)->filter(function($column) use($belongsTos) {
            return in_array($column['name'],$belongsTos->pluck('foreign_key')->toArray());
        })->keyBy('name');
        $columns = $this->readColumnsFromTable($this->tableName)->reject(function($column) use($belongsTos) {
            return in_array($column['name'],$belongsTos->pluck('foreign_key')->toArray());
        })->map(function($column) {
            $column["label"] = str_replace("_"," ",Str::title($column['name']));
            return $column;
        })->keyBy('name');
        return view('jig::'.$this->show, [
            'modelBaseName' => $this->modelBaseName,
            'modelRouteAndViewName' => $this->modelRouteAndViewName,
            'modelVariableName' => $this->modelVariableName,
            'modelPlural' => $this->modelPlural,
            'modelTitleSingular' => $this->titleSingular,
            'modelViewsDirectory' => $this->modelViewsDirectory,
            'modelDotNotation' => $this->modelDotNotation,
            'modelJSName' => $this->modelJSName,
            'modelLangFormat' => $this->modelLangFormat,
            'resource' => $this->resource,
            'isUsedTwoColumnsLayout' => $this->isUsedTwoColumnsLayout(),

            'modelTitle' => $this->readColumnsFromTable($this->tableName)->filter(function($column){
            	return in_array($column['name'], ['title', 'name', 'first_name', 'email']);
            })->first(null, ['name'=>'id'])['name'],
            'columns' => $columns,
            "relatables" => $relatables,
            'relations' => $this->relations,
            'hasTranslatable' => $this->readColumnsFromTable($this->tableName)->filter(function($column) {
                return $column['type'] == "json";
            })->count() > 0,
        ])->render();
    }

    protected function getOptions() {
        return [
            ['model-name', 'm', InputOption::VALUE_OPTIONAL, 'Generates a code for the given model'],
            ['belongs-to-many', 'btm', InputOption::VALUE_OPTIONAL, 'Specify belongs to many relations'],
            ['template', 't', InputOption::VALUE_OPTIONAL, 'Specify custom template'],
            ['force', 'f', InputOption::VALUE_NONE, 'Force will delete files before regenerating form'],
        ];
    }

}
