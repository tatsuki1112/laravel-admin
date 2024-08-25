<?php

namespace Encore\Admin\Widgets\Grid;

use Carbon\Carbon;
use Closure;
use Encore\Admin\Admin;
use Encore\Admin\Widgets\Grid\Grid;
use Encore\Admin\Widgets\Grid\Displayers\AbstractDisplayer;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Class Column.
 *
 * @method Displayers\Editable      editable()
 * @method Displayers\SwitchDisplay switch ($states = [])
 * @method Displayers\SwitchGroup   switchGroup($columns = [], $states = [])
 * @method Displayers\Select        select($options = [])
 * @method Displayers\Image         image($server = '', $width = 200, $height = 200)
 * @method Displayers\Label         label($style = 'success')
 * @method Displayers\Button        button($style = null)
 * @method Displayers\Link          link($href = '', $target = '_blank')
 * @method Displayers\Badge         badge($style = 'red')
 * @method Displayers\ProgressBar   progressBar($style = 'primary', $size = 'sm', $max = 100)
 * @method Displayers\Radio         radio($options = [])
 * @method Displayers\Checkbox      checkbox($options = [])
 * @method Displayers\Orderable     orderable($column, $label = '')
 * @method Displayers\Table         table($titles = [])
 * @method Displayers\Expand        expand($callback = null)
 * @method Displayers\Modal         modal($callback = null)
 * @method Displayers\Carousel      carousel(int $width = 300, int $height = 200, $server = '')
 * @method Displayers\Download      download($server = '')
 */
class Column
{
    const SELECT_COLUMN_NAME = '__row_selector__';

    const ACTION_COLUMN_NAME = '__actions__';

    /**
     * @var Grid
     */
    protected $grid;

    /**
     * Name of column.
     *
     * @var string
     */
    protected $name;

    /**
     * Label of column.
     *
     * @var string
     */
    protected $label;

    /**
     * Original value of column.
     *
     * @var mixed
     */
    protected $original;

    /**
     * Is column sortable.
     *
     * @var bool
     */
    protected $sortable = false;

    /**
     * Sort arguments.
     *
     * @var array<mixed>
     */
    protected $sort;

    /**
     * Sort column name.
     *
     * @var string|null
     */
    protected $sortName;

    /**
     * Help message.
     *
     * @var string
     */
    protected $help = '';

    /**
     * Cast Name for sort.
     *
     * @var array<mixed>|null
     */
    protected $cast;

    /**
     * Sort as callback.
     *
     * @var \Closure|null
     */
    protected $sortCallback;

    /**
     * Attributes of column.
     *
     * @var array<mixed>
     */
    protected $attributes = [];

    /**
     * Relation name.
     *
     * @var bool
     */
    protected $relation = false;

    /**
     * Relation column.
     *
     * @var string
     */
    protected $relationColumn;

    /**
     * Original grid data.
     *
     * @var Collection<int|string, mixed>
     */
    protected static $originalGridModels;

    /**
     * @var Closure[]
     */
    protected $displayCallbacks = [];

    /**
     * @var bool
     */
    protected $escape = true;

    /**
     * Displayers for grid column.
     *
     * @var array<mixed>
     */
    public static $displayers = [];

    /**
     * Defined columns.
     *
     * @var array<mixed>
     */
    public static $defined = [];

    /**
     * @var array<mixed>
     */
    protected static $htmlAttributes = [];

    /**
     * @var array<mixed>
     */
    protected static $classes = [];

    /**
     * @param string $name
     * @param string $label
     *
     * @return void
     */
    public function __construct($name, $label)
    {
        $this->name = $name;

        $this->label = $this->formatLabel($label);
    }

    /**
     * Extend column displayer.
     *
     * @param mixed $name
     * @param mixed $displayer
     *
     * @return void
     */
    public static function extend($name, $displayer)
    {
        static::$displayers[$name] = $displayer;
    }

    /**
     * Define a column globally.
     *
     * @param string $name
     * @param mixed  $definition
     *
     * @return void
     */
    public static function define($name, $definition)
    {
        static::$defined[$name] = $definition;
    }

    /**
     * Set grid instance for column.
     *
     * @param Grid $grid
     *
     * @return void
     */
    public function setGrid(Grid $grid)
    {
        $this->grid = $grid;
    }

    /**
     * Set original data for column.
     *
     * @param Collection<int|string, mixed> $collection
     *
     * @return void
     */
    public static function setOriginalGridModels(Collection $collection)
    {
        static::$originalGridModels = $collection;
    }

    /**
     * Set column attributes.
     *
     * @param array<mixed> $attributes
     *
     * @return $this
     */
    public function setAttributes($attributes = [])
    {
        static::$htmlAttributes[$this->name] = $attributes;

        return $this;
    }

    /**
     * Get column attributes.
     *
     * @param string $name
     *
     * @return mixed
     */
    public static function getAttributes($name)
    {
        return Arr::get(static::$htmlAttributes, $name, '');
    }

    /**
     * Set column classes.
     *
     * @param array<mixed> $classes
     *
     * @return $this
     */
    public function setClasses($classes = [])
    {
        static::$classes[$this->name] = $classes;

        return $this;
    }

    /**
     * Get column classes.
     *
     * @param string $name
     *
     * @return mixed
     */
    public static function getClasses($name)
    {
        return Arr::get(static::$classes, $name, []);
    }

    /**
     * Set style of this column.
     *
     * @param string $style
     *
     * @return $this
     */
    public function style($style)
    {
        return $this->setAttributes(compact('style'));
    }

    /**
     * Set the width of column.
     *
     * @param int $width
     *
     * @return $this
     */
    public function width($width)
    {
        return $this->style("width: {$width}px;");
    }

    /**
     * Set the min-width of column.
     *
     * @param int|null $width
     *
     * @return $this
     */
    public function minWidth($width)
    {
        if(!isset($width)){
            return $this;
        }

        return $this->style("min-width: {$width}px;");
    }

    /**
     * Set the max-width of column.
     *
     * @param int|null $width
     *
     * @return $this
     */
    public function maxWidth($width)
    {
        if(!isset($width)){
            return $this;
        }

        return $this->style("max-width: {$width}px;");
    }

    /**
     * Set the color of column.
     *
     * @param string $color
     *
     * @return $this
     */
    public function color($color)
    {
        return $this->style("color:$color;");
    }

    /**
     * Get original column value.
     *
     * @return mixed
     */
    public function getOriginal()
    {
        return $this->original;
    }

    /**
     * Get name of this column.
     *
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Format label.
     *
     * @param string $label
     *
     * @return mixed
     */
    protected function formatLabel($label)
    {
        if ($label) {
            return $label;
        }

        $label = ucfirst($this->name);

        return __(str_replace(['.', '_'], ' ', $label));
    }

    /**
     * Get label of the column.
     *
     * @return mixed
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * Set relation.
     *
     * @param bool $relation
     * @param string|null $relationColumn
     *
     * @return Column
     */
    public function setRelation($relation, $relationColumn = null)
    {
        $this->relation = $relation;
        $this->relationColumn = $relationColumn;

        return $this;
    }

    /**
     * If this column is relation column.
     *
     * @return bool
     */
    protected function isRelation()
    {
        return (bool) $this->relation;
    }

    /**
     * Set sort value.
     *
     * @param bool $sort
     *
     * @return $this
     */
    public function sort($sort)
    {
        $this->sortable = $sort;

        return $this;
    }

    /**
     * Set sort coolumn name.
     *
     * @param string $sortName
     *
     * @return $this
     */
    public function sortName($sortName)
    {
        $this->sortName = $sortName;

        return $this;
    }

    /**
     * Get sort column name.
     * @return string
     */
    public function getSortName()
    {
        return isset($this->sortName) ? $this->sortName : $this->name;
    }

    /**
     * Mark this column as sortable.
     *
     * @return $this
     */
    public function sortable()
    {
        return $this->sort(true);
    }

    /**
     * Set cast name for sortable.
     * @param array<mixed> $cast
     *
     * @return $this
     */
    public function cast($cast)
    {
        $this->cast = $cast;

        return $this;
    }

    /**
     * Set sort callback.
     * @param Closure $callback
     *
     * @return $this
     */
    public function sortCallback(\Closure $callback)
    {
        $this->sortCallback = $callback;

        return $this;
    }

    /**
     * Get sort callback.
     *
     * @return Closure|null
     */
    public function getSortCallback()
    {
        return $this->sortCallback;
    }

    /**
     * Add a display callback.
     *
     * @param Closure $callback
     *
     * @return $this
     */
    public function display(Closure $callback)
    {
        $this->displayCallbacks[] = $callback;

        return $this;
    }

    /**
     * Whether escape this
     *
     * @param bool $escape
     *
     * @return $this
     */
    public function escape(bool $escape)
    {
        $this->escape = $escape;

        return $this;
    }

    /**
     * Display using display abstract.
     *
     * @param string $abstract
     * @param array<mixed>  $arguments
     *
     * @return $this
     */
    public function displayUsing($abstract, $arguments = [])
    {
        $grid = $this->grid;

        $column = $this;

        return $this->display(function ($value) use ($grid, $column, $abstract, $arguments) {
            /** @var AbstractDisplayer $displayer */
            $displayer = new $abstract($value, $grid, $column, $this);

            return $displayer->display(...$arguments);
        });
    }

    /**
     * Display column using array value map.
     *
     * @param array<mixed> $values
     * @param null  $default
     *
     * @return $this
     */
    public function using(array $values, $default = null)
    {
        return $this->display(function ($value) use ($values, $default) {
            if (is_null($value)) {
                return $default;
            }

            return Arr::get($values, $value, $default);
        });
    }

    /**
     * Replace output value with giving map.
     *
     * @param array<mixed> $replacements
     *
     * @return $this
     */
    public function replace(array $replacements)
    {
        return $this->display(function ($value) use ($replacements) {
            if (isset($replacements[$value])) {
                return $replacements[$value];
            }

            return $value;
        });
    }

    /**
     * Render this column with the given view.
     *
     * @param string $view
     *
     * @return $this
     */
    public function view($view)
    {
        return $this->display(function ($value) use ($view) {
            $model = $this;

            return view($view, compact('model', 'value'))->render();
        });
    }

    /**
     * Hide this column by default.
     *
     * @return $this
     */
    public function hide()
    {
        $this->grid->hideColumns($this->getName());

        return $this;
    }

    /**
     * Add column to total-row.
     *
     * @param null $display
     *
     * @return $this
     */
    public function totalRow($display = null)
    {
        /** @phpstan-ignore-next-line use magic method */
        $this->grid->addTotalRow($this->name, $display);

        return $this;
    }

    /**
     * Convert file size to a human readable format like `100mb`.
     *
     * @return $this
     */
    public function filesize()
    {
        return $this->display(function ($value) {
            return file_size($value);
        });
    }

    /**
     * Display the fields in the email format as gavatar.
     *
     * @param int $size
     *
     * @return $this
     */
    public function gravatar($size = 30)
    {
        return $this->display(function ($value) use ($size) {
            $src = sprintf(
                'https://www.gravatar.com/avatar/%s?s=%d',
                md5(strtolower($value)),
                $size
            );

            return "<img src='$src' class='img img-circle'/>";
        });
    }

    /**
     * Display field as a loading icon.
     *
     * @param array<mixed> $values
     * @param array<mixed> $others
     *
     * @return $this
     */
    public function loading($values = [], $others = [])
    {
        return $this->display(function ($value) use ($values, $others) {
            $values = (array) $values;

            if (in_array($value, $values)) {
                return '<i class="fa fa-refresh fa-spin text-primary"></i>';
            }

            return Arr::get($others, $value, $value);
        });
    }

    /**
     * Display column as an font-awesome icon based on it's value.
     *
     * @param array<mixed>  $setting
     * @param string $default
     *
     * @return $this
     */
    public function icon(array $setting, $default = '')
    {
        return $this->display(function ($value) use ($setting, $default) {
            $fa = '';

            if (isset($setting[$value])) {
                $fa = $setting[$value];
            } elseif ($default) {
                $fa = $default;
            }

            return "<i class=\"fa fa-{$fa}\"></i>";
        });
    }

    /**
     * Return a human-readable format time.
     *
     * @param mixed|null $locale
     *
     * @return $this
     */
    public function diffForHumans($locale = null)
    {
        if ($locale) {
            Carbon::setLocale($locale);
        }

        return $this->display(function ($value) {
            return Carbon::parse($value)->diffForHumans();
        });
    }

    /**
     * If has display callbacks.
     *
     * @return bool
     */
    protected function hasDisplayCallbacks()
    {
        return !empty($this->displayCallbacks);
    }

    /**
     * Call all of the "display" callbacks column.
     *
     * @param mixed $value
     * @param int   $key
     *
     * @return mixed
     */
    protected function callDisplayCallbacks($value, $key)
    {
        foreach ($this->displayCallbacks as $callback) {
            $previous = $value;

            $callback = $this->bindOriginalRowModel($callback, $key);
            $value = call_user_func_array($callback, [$value, $this, $this->getRowModel($key)]);

            if (($value instanceof static) &&
                ($last = array_pop($this->displayCallbacks))
            ) {
                $last = $this->bindOriginalRowModel($last, $key);
                $value = call_user_func($last, $previous);
            }
        }

        if(!$this->escape){
            return $value;
        }
        return $this->htmlEntityEncode($value);
    }

    /**
     * Set original grid data to column.
     *
     * @param Closure $callback
     * @param int     $key
     *
     * @return Closure
     */
    protected function bindOriginalRowModel(Closure $callback, $key)
    {
        $rowModel = $this->getRowModel($key);

        return $callback->bindTo($rowModel);
    }

    /**
     * Get row model
     *
     * @param string|int $key
     * @return ?Model
     */
    protected function getRowModel($key){
        return static::$originalGridModels[$key];
    }

    /**
     * Fill all data to every column.
     *
     * @param array<mixed> $data
     *
     * @return mixed
     * @throws \Exception
     */
    public function fill(array $data)
    {
        foreach ($data as $key => &$row) {
            $this->original = $value = Arr::get($row, $this->name);

            $value = $this->htmlEntityEncode($value);

            Arr::set($row, $this->name, $value);

            if ($this->isDefinedColumn()) {
                $this->useDefinedColumn();
            }

            if ($this->hasDisplayCallbacks()) {
                $value = $this->callDisplayCallbacks($this->original, $key);
                Arr::set($row, $this->name, $value);
            }
        }

        return $data;
    }

    /**
     * If current column is a defined column.
     *
     * @return bool
     */
    protected function isDefinedColumn()
    {
        return array_key_exists($this->name, static::$defined);
    }

    /**
     * Use a defined column.
     *
     * @throws \Exception
     *
     * @return void
     */
    protected function useDefinedColumn()
    {
        // clear all display callbacks.
        $this->displayCallbacks = [];

        $class = static::$defined[$this->name];

        if ($class instanceof Closure) {
            $this->display($class);

            return;
        }

        if (!class_exists($class) || !is_subclass_of($class, AbstractDisplayer::class)) {
            throw new \Exception("Invalid column definition [$class]");
        }

        $grid = $this->grid;
        $column = $this;

        $this->display(function ($value) use ($grid, $column, $class) {
            /** @var AbstractDisplayer $definition */
            $definition = new $class($value, $grid, $column, $this);

            return $definition->display();
        });
    }

    /**
     * Convert characters to HTML entities recursively.
     *
     * @param array<mixed>|string $item
     *
     * @return mixed
     */
    protected function htmlEntityEncode($item)
    {
        if (is_array($item)) {
            array_walk_recursive($item, function (&$value) {
                $value = htmlentities($value);
            });
        } else {
            $item = htmlentities($item);
        }

        return $item;
    }

    /**
     * Create the column sorter.
     *
     * @return string
     */
    public function sorter()
    {
        if (!$this->sortable) {
            return '';
        }

        $icon = 'fa-sort';
        $type = 'desc';

        if ($this->isSorted()) {
            $type = $this->sort['type'] == 'desc' ? 'asc' : 'desc';
            $icon .= "-amount-{$this->sort['type']}";
        }

        // set sort value
        if(isset($this->sortName)){
            $sort = ['column' => $this->sortName, 'type' => $type, 'direct' => true];
        }
        else{
            $sort = ['column' => $this->name, 'type' => $type];
        }
        
        if (isset($this->sortCallback)) {
            $sort['callback'] = 1;
        }
        elseif (isset($this->cast)) {
            $sort['cast'] = $this->cast;
        }

        $query = app('request')->all();
        $query = array_merge($query, [$this->grid->model()->getSortName() => $sort]);

        $url = url()->current().'?'.http_build_query($query);

        return "<a class=\"fa fa-fw $icon\" href=\"$url\"></a>";
    }

    /**
     * Determine if this column is currently sorted.
     *
     * @return bool
     */
    protected function isSorted()
    {
        $this->sort = app('request')->get($this->grid->model()->getSortName());

        if (empty($this->sort)) {
            return false;
        }

        if(isset($this->sort['column']) && $this->sort['column'] == $this->name){
            return true;
        };

        if(isset($this->sort['column']) && $this->sort['column'] == $this->sortName){
            return true;
        };

        return false;
    }

    /**
     * Set help message for column.
     *
     * @param string $help
     *
     * @return $this|string
     */
    public function help($help = '')
    {
        if (!empty($help)) {
            $this->help = $help;

            return $this;
        }

        if (empty($this->help)) {
            return '';
        }

        Admin::script("$('.column-help').popover();");

        $data = [
            'container' => 'body',
            'toggle'    => 'popover',
            'trigger'   => 'hover',
            'placement' => 'bottom',
            'content'   => $this->help,
        ];

        $data = collect($data)->map(function ($val, $key) {
            return "data-{$key}=\"{$val}\"";
        })->implode(' ');

        return <<<HELP
<a href="javascript:void(0);" class="column-help" {$data}>
    <i class="fa fa-question-circle"></i>
</a>
HELP;
    }

    /**
     * Find a displayer to display column.
     *
     * @param string $abstract
     * @param array<mixed>  $arguments
     *
     * @return $this
     */
    protected function resolveDisplayer($abstract, $arguments)
    {
        if (array_key_exists($abstract, static::$displayers)) {
            return $this->callBuiltinDisplayer(static::$displayers[$abstract], $arguments);
        }

        return $this->callSupportDisplayer($abstract, $arguments);
    }

    /**
     * Call Illuminate/Support displayer.
     *
     * @param string $abstract
     * @param array<mixed>  $arguments
     *
     * @return $this
     */
    protected function callSupportDisplayer($abstract, $arguments)
    {
        return $this->display(function ($value) use ($abstract, $arguments) {
            if (is_array($value) || $value instanceof Arrayable) {
                return call_user_func_array([collect($value), $abstract], $arguments);
            }

            if (is_string($value)) {
                return call_user_func_array([Str::class, $abstract], array_merge([$value], $arguments));
            }

            return $value;
        });
    }

    /**
     * Call Builtin displayer.
     *
     * @param string|Closure $abstract
     * @param array<mixed>  $arguments
     *
     * @return $this
     */
    protected function callBuiltinDisplayer($abstract, $arguments)
    {
        if ($abstract instanceof Closure) {
            return $this->display(function ($value) use ($abstract, $arguments) {
                return $abstract->call($this, ...array_merge([$value], $arguments));
            });
        }

        if (class_exists($abstract) && is_subclass_of($abstract, AbstractDisplayer::class)) {
            $grid = $this->grid;
            $column = $this;

            return $this->display(function ($value) use ($abstract, $grid, $column, $arguments) {
                /** @var AbstractDisplayer $displayer */
                $displayer = new $abstract($value, $grid, $column, $this);

                return $displayer->display(...$arguments);
            })->escape(false);
        }

        return $this;
    }

    /**
     * Passes through all unknown calls to builtin displayer or supported displayer.
     *
     * Allow fluent calls on the Column object.
     *
     * @param string $method
     * @param array<int, mixed>  $arguments
     *
     * @return $this
     */
    public function __call($method, $arguments)
    {
        if ($this->isRelation() && !$this->relationColumn) {
            $this->name = "{$this->relation}.$method";
            $this->label = $this->formatLabel($arguments[0] ?? null);

            $this->relationColumn = $method;

            return $this;
        }

        return $this->resolveDisplayer($method, $arguments);
    }
}
